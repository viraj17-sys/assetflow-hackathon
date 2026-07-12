<?php
/**
 * AssetFlow - data layer
 * Demo/prototype persistence using PHP sessions so the app runs on plain
 * PHP with no database setup. Swap out the get/save functions below for
 * real queries (e.g. PDO + MySQL) when you're ready to go to production.
 */
session_start();

function af_seed_data() {
    if (!isset($_SESSION['af_assets'])) {
        $_SESSION['af_assets'] = [];
    }
    if (!isset($_SESSION['af_asset_events'])) {
        $_SESSION['af_asset_events'] = [];
    }
    if (!isset($_SESSION['af_transfers'])) {
        $_SESSION['af_transfers'] = [];
    }
    if (!isset($_SESSION['af_bookings'])) {
        $_SESSION['af_bookings'] = [];
    }
    if (!isset($_SESSION['af_activity'])) {
        $_SESSION['af_activity'] = [];
    }
    if (!isset($_SESSION['af_audit_cycle'])) {
        $_SESSION['af_audit_cycle'] = [
            'department' => '',
            'range'      => '',
            'auditors'   => '',
            'status'     => 'Open', // Open | Closed
            'closed_note'=> '',
        ];
    }
    if (!isset($_SESSION['af_audit_items'])) {
        $_SESSION['af_audit_items'] = [];
    }
    if (!isset($_SESSION['af_departments'])) {
        $_SESSION['af_departments'] = [];
    }
    if (!isset($_SESSION['af_categories'])) {
        $_SESSION['af_categories'] = [];
    }
    if (!isset($_SESSION['af_employees'])) {
        $_SESSION['af_employees'] = [];
    }
    if (!isset($_SESSION['af_notifications'])) {
        $_SESSION['af_notifications'] = [];
    }
    if (!isset($_SESSION['af_reports'])) {
        $_SESSION['af_reports'] = [
            'utilization' => [],
            'maintenance_trend' => [],
            'most_used' => [],
            'idle' => [],
            'due' => [],
        ];
    }
}

function af_assets() {
    af_seed_data();
    return $_SESSION['af_assets'];
}

function af_find_asset($id) {
    foreach (af_assets() as $asset) {
        if ($asset['id'] === $id) return $asset;
    }
    return null;
}

function af_transfers() {
    af_seed_data();
    return $_SESSION['af_transfers'];
}

function af_bookings() {
    af_seed_data();
    return $_SESSION['af_bookings'];
}

function af_activity() {
    af_seed_data();
    return $_SESSION['af_activity'];
}

function af_log_activity($text) {
    af_seed_data();
    array_unshift($_SESSION['af_activity'], ['text' => $text, 'time' => 'Just now']);
    $_SESSION['af_activity'] = array_slice($_SESSION['af_activity'], 0, 8);
}

function af_stats() {
    $assets = af_assets();
    $available = count(array_filter($assets, fn($a) => $a['status'] === 'Available'));
    $allocated = count(array_filter($assets, fn($a) => $a['status'] === 'Allocated'));
    $overdue   = count(array_filter($assets, fn($a) => $a['status'] === 'Overdue'));
    $pendingTransfers = count(array_filter(af_transfers(), fn($t) => $t['status'] === 'Pending'));
    return [
        'available'        => $available,
        'allocated'        => $allocated,
        'overdue'          => $overdue,
        'active_bookings'  => count(af_bookings()),
        'pending_transfers'=> $pendingTransfers,
        'upcoming_returns' => 0,
    ];
}

/** Register a brand-new asset into inventory.
 *  Returns true on success, or a string error message on failure. */
function af_register_asset($id, $name, $category) {
    af_seed_data();
    if (af_find_asset($id)) {
        return "Asset ID \"$id\" already exists. Choose a different ID.";
    }
    $_SESSION['af_assets'][] = [
        'id' => $id, 'name' => $name, 'category' => $category,
        'status' => 'Available', 'holder' => null, 'department' => null,
        'last_activity' => date('Y-m-d'), 'purchased' => date('Y-m-d'),
    ];
    af_log_activity("New asset $id ($name) registered - Available");
    af_log_asset_event($id, 'Registered', 'Added to inventory, marked Available');
    return true;
}

/** Directly allocate an *available* asset to someone (no transfer needed
 *  since nobody currently holds it). Fails if the asset is not Available. */
function af_allocate_asset($assetId, $holder, $department) {
    af_seed_data();
    foreach ($_SESSION['af_assets'] as &$asset) {
        if ($asset['id'] === $assetId) {
            if ($asset['status'] !== 'Available') {
                return "Asset $assetId is not available for direct allocation.";
            }
            $asset['status'] = 'Allocated';
            $asset['holder'] = $holder;
            $asset['department'] = $department ?: '—';
            $asset['last_activity'] = date('Y-m-d');
            af_log_activity("$assetId allocated to $holder" . ($department ? " - $department" : ''));
            af_log_asset_event($assetId, 'Allocated', "Assigned to $holder" . ($department ? " — $department" : ''));
            return true;
        }
    }
    unset($asset);
    return "Asset $assetId not found.";
}

/** Submit a transfer request. Direct re-allocation of an already-allocated
 *  asset is never allowed here - it always routes through this request. */
function af_submit_transfer($assetId, $to, $note) {
    af_seed_data();
    $asset = af_find_asset($assetId);
    if (!$asset) return false;
    $_SESSION['af_transfers'][] = [
        'asset_id' => $assetId,
        'from' => $asset['holder'] ?? '—',
        'to' => $to,
        'status' => 'Pending',
        'note' => $note,
    ];
    af_log_activity("Transfer requested: $assetId to $to (pending approval)");
    return true;
}

/** Approve or reject a pending transfer by its index in af_transfers.
 *  Approving moves the asset to the new holder; rejecting just closes it out. */
function af_decide_transfer($index, $approve) {
    af_seed_data();
    if (!isset($_SESSION['af_transfers'][$index])) return false;
    $transfer = &$_SESSION['af_transfers'][$index];
    if ($transfer['status'] !== 'Pending') return false;

    if ($approve) {
        foreach ($_SESSION['af_assets'] as &$asset) {
            if ($asset['id'] === $transfer['asset_id']) {
                $asset['holder'] = $transfer['to'];
                $asset['status'] = 'Allocated';
                $asset['last_activity'] = date('Y-m-d');
                unset($asset['due_date']);
                break;
            }
        }
        unset($asset);
        $transfer['status'] = 'Approved';
        af_log_activity("Transfer approved: {$transfer['asset_id']} now with {$transfer['to']}");
        af_log_asset_event($transfer['asset_id'], 'Transferred', "From {$transfer['from']} to {$transfer['to']}");
    } else {
        $transfer['status'] = 'Rejected';
        af_log_activity("Transfer rejected: {$transfer['asset_id']} to {$transfer['to']}");
    }
    unset($transfer);
    return true;
}

/** True overlap-aware booking conflict check: same room + same date, and the
 *  requested [start,end) window overlaps an existing confirmed booking. */
function af_booking_conflict($room, $date, $start, $end) {
    af_seed_data();
    $newStart = strtotime($start);
    $newEnd   = strtotime($end);
    foreach ($_SESSION['af_bookings'] as $b) {
        if ($b['room'] !== $room || $b['date'] !== $date) continue;
        $exStart = strtotime($b['start']);
        $exEnd   = strtotime($b['end']);
        if ($newStart < $exEnd && $exStart < $newEnd) {
            return $b;
        }
    }
    return false;
}

function af_create_booking($room, $date, $start, $end, $bookedBy) {
    af_seed_data();
    $_SESSION['af_bookings'][] = [
        'room' => $room, 'date' => $date, 'start' => $start, 'end' => $end,
        'booked_by' => $bookedBy, 'status' => 'Confirmed',
    ];
    af_log_activity("$room booked by $bookedBy - " . af_format_time($start) . " to " . af_format_time($end));
}

/** Format a 24hr "HH:MM" time string as "2:00 PM" for display. */
function af_format_time($t) {
    $ts = strtotime($t);
    return $ts ? date('g:i A', $ts) : $t;
}

/* --- Asset lifecycle timeline --- */

/** Append an event to an asset's lifecycle history (chronological, oldest first). */
function af_log_asset_event($assetId, $event, $detail) {
    af_seed_data();
    if (!isset($_SESSION['af_asset_events'][$assetId])) {
        $_SESSION['af_asset_events'][$assetId] = [];
    }
    $_SESSION['af_asset_events'][$assetId][] = [
        'event' => $event, 'detail' => $detail, 'date' => date('Y-m-d'),
    ];
}

/** Full chronological history for one asset (oldest first). */
function af_asset_events($assetId) {
    af_seed_data();
    return $_SESSION['af_asset_events'][$assetId] ?? [];
}

/** Days since an asset last had any activity (allocation, transfer, return, etc).
 *  Meaningful mainly for Available assets — this is "days sitting idle". */
function af_idle_days($asset) {
    if (empty($asset['last_activity'])) return 0;
    $days = (int) floor((time() - strtotime($asset['last_activity'])) / 86400);
    return max(0, $days);
}

/** Days an Overdue asset has been overdue, based on its due_date. */
function af_overdue_days($asset) {
    if (empty($asset['due_date'])) return 0;
    $days = (int) floor((time() - strtotime($asset['due_date'])) / 86400);
    return max(0, $days);
}

/** Send an asset to Maintenance. Admin-only, enforced by caller. */
function af_send_to_maintenance($assetId, $note = '') {
    af_seed_data();
    foreach ($_SESSION['af_assets'] as &$asset) {
        if ($asset['id'] === $assetId) {
            if (in_array($asset['status'], ['Maintenance', 'Retired'], true)) {
                return "Asset $assetId is already {$asset['status']}.";
            }
            $asset['status'] = 'Maintenance';
            $asset['last_activity'] = date('Y-m-d');
            af_log_activity("$assetId sent to Maintenance" . ($note ? " - $note" : ''));
            af_log_asset_event($assetId, 'Maintenance', $note ?: 'Sent for maintenance');
            return true;
        }
    }
    unset($asset);
    return "Asset $assetId not found.";
}

/** Resolve maintenance, returning the asset to Available. Admin-only. */
function af_resolve_maintenance($assetId) {
    af_seed_data();
    foreach ($_SESSION['af_assets'] as &$asset) {
        if ($asset['id'] === $assetId) {
            if ($asset['status'] !== 'Maintenance') {
                return "Asset $assetId is not currently in Maintenance.";
            }
            $asset['status'] = 'Available';
            $asset['holder'] = null;
            $asset['department'] = null;
            $asset['last_activity'] = date('Y-m-d');
            af_log_activity("$assetId maintenance resolved - back to Available");
            af_log_asset_event($assetId, 'Available', 'Maintenance resolved, returned to pool');
            return true;
        }
    }
    unset($asset);
    return "Asset $assetId not found.";
}

/** Retire an asset permanently. Admin-only, enforced by caller. */
function af_retire_asset($assetId) {
    af_seed_data();
    foreach ($_SESSION['af_assets'] as &$asset) {
        if ($asset['id'] === $assetId) {
            if ($asset['status'] === 'Retired') {
                return "Asset $assetId is already retired.";
            }
            $asset['status'] = 'Retired';
            $asset['holder'] = null;
            $asset['department'] = null;
            $asset['last_activity'] = date('Y-m-d');
            af_log_activity("$assetId retired");
            af_log_asset_event($assetId, 'Retired', 'Removed from active circulation');
            return true;
        }
    }
    unset($asset);
    return "Asset $assetId not found.";
}

/** Idle assets (Available, sorted by longest idle first). Used by Reports & Dashboard. */
function af_idle_assets($minDays = 1) {
    $out = [];
    foreach (af_assets() as $a) {
        if ($a['status'] !== 'Available') continue;
        $days = af_idle_days($a);
        if ($days >= $minDays) $out[] = ['asset' => $a, 'days' => $days];
    }
    usort($out, fn($x, $y) => $y['days'] <=> $x['days']);
    return $out;
}

function af_audit_cycle() {
    af_seed_data();
    return $_SESSION['af_audit_cycle'];
}

function af_audit_items() {
    af_seed_data();
    return $_SESSION['af_audit_items'];
}

/** Discrepancies = anything not Verified (Missing or Damaged). */
function af_audit_discrepancy_count() {
    return count(array_filter(af_audit_items(), fn($i) => $i['verification'] !== 'Verified'));
}

/** Update the verification result for a single checklist row. */
function af_set_audit_verification($assetId, $verification) {
    af_seed_data();
    $allowed = ['Verified', 'Missing', 'Damaged'];
    if (!in_array($verification, $allowed, true)) return false;
    foreach ($_SESSION['af_audit_items'] as &$item) {
        if ($item['asset_id'] === $assetId) {
            $item['verification'] = $verification;
            unset($item);
            return true;
        }
    }
    unset($item);
    return false;
}

/** Close the current audit cycle, logging a discrepancy summary to activity.
 *  Cannot be closed twice. */
function af_close_audit_cycle() {
    af_seed_data();
    if ($_SESSION['af_audit_cycle']['status'] === 'Closed') return false;
    $flagged = af_audit_discrepancy_count();
    $_SESSION['af_audit_cycle']['status'] = 'Closed';
    $dept = $_SESSION['af_audit_cycle']['department'];
    af_log_activity("Audit cycle closed - $dept - $flagged discrepanc" . ($flagged === 1 ? 'y' : 'ies') . " logged");
    return true;
}

/** Start a brand-new open audit cycle (used after closing one, or on reset). */
function af_start_new_audit_cycle($department, $range, $auditors) {
    af_seed_data();
    $_SESSION['af_audit_cycle'] = [
        'department' => $department ?: 'Engineering',
        'range'      => $range ?: date('j M'),
        'auditors'   => $auditors ?: '—',
        'status'     => 'Open',
        'closed_note'=> '',
    ];
    $_SESSION['af_audit_items'] = [];
    af_log_activity("New audit cycle started - {$_SESSION['af_audit_cycle']['department']}");
}

/* --- Organization Setup: departments, categories, employees --- */

function af_departments() {
    af_seed_data();
    return $_SESSION['af_departments'];
}

/** Departments people can actually be allocated/transferred into (feeds
 *  the picklists on the Allocation & Transfer and Resource Booking screens). */
function af_active_departments() {
    return array_values(array_filter(af_departments(), fn($d) => $d['status'] === 'Active'));
}

function af_add_department($name, $head, $parent, $status) {
    af_seed_data();
    if (trim($name) === '') return 'Department name is required.';
    foreach ($_SESSION['af_departments'] as $d) {
        if (strcasecmp($d['name'], $name) === 0) return "Department \"$name\" already exists.";
    }
    $_SESSION['af_departments'][] = [
        'name' => $name, 'head' => $head ?: '—', 'parent' => $parent, 'status' => $status ?: 'Active',
    ];
    af_log_activity("Department \"$name\" added to Organization Setup");
    return true;
}

function af_categories() {
    af_seed_data();
    return $_SESSION['af_categories'];
}

function af_active_categories() {
    return array_values(array_filter(af_categories(), fn($c) => $c['status'] === 'Active'));
}

function af_add_category($name) {
    af_seed_data();
    if (trim($name) === '') return 'Category name is required.';
    foreach ($_SESSION['af_categories'] as $c) {
        if (strcasecmp($c['name'], $name) === 0) return "Category \"$name\" already exists.";
    }
    $_SESSION['af_categories'][] = ['name' => $name, 'status' => 'Active'];
    af_log_activity("Category \"$name\" added to Organization Setup");
    return true;
}

/** How many assets currently use a category (for the Categories tab). */
function af_category_asset_count($categoryName) {
    return count(array_filter(af_assets(), fn($a) => $a['category'] === $categoryName));
}

function af_employees() {
    af_seed_data();
    return $_SESSION['af_employees'];
}

function af_add_employee($name, $department, $status) {
    af_seed_data();
    if (trim($name) === '') return 'Employee name is required.';
    foreach ($_SESSION['af_employees'] as $e) {
        if (strcasecmp($e['name'], $name) === 0) return "Employee \"$name\" already exists.";
    }
    $_SESSION['af_employees'][] = ['name' => $name, 'department' => $department ?: '—', 'status' => $status ?: 'Active'];
    af_log_activity("Employee \"$name\" added to Organization Setup");
    return true;
}

/** How many assets are currently held by an employee (for the Employee tab). */
function af_employee_asset_count($employeeName) {
    return count(array_filter(af_assets(), fn($a) => $a['holder'] === $employeeName));
}

function af_notifications($filter = 'all') {
    af_seed_data();
    $all = $_SESSION['af_notifications'];
    if ($filter === 'all') return $all;
    $map = ['alerts' => 'alert', 'approvals' => 'approval', 'bookings' => 'booking'];
    $type = $map[$filter] ?? null;
    if (!$type) return $all;
    return array_values(array_filter($all, fn($n) => $n['type'] === $type));
}

function af_reports_data() {
    af_seed_data();
    return $_SESSION['af_reports'];
}

/** Wipe the session and reseed fresh demo data. */
function af_reset_data() {
    unset(
        $_SESSION['af_assets'], $_SESSION['af_transfers'], $_SESSION['af_bookings'],
        $_SESSION['af_activity'], $_SESSION['af_audit_cycle'], $_SESSION['af_audit_items'],
        $_SESSION['af_reports'], $_SESSION['af_notifications'],
        $_SESSION['af_departments'], $_SESSION['af_categories'], $_SESSION['af_employees'],
        $_SESSION['af_asset_events']
    );
    af_seed_data();
}

/* --- User roles & permissions (demo simulation, no real login) ---
 * There's no auth system here, so the "current user" is simulated via a
 * role stored in the session, switchable from the topbar. In a real app,
 * af_current_role() would instead read the logged-in user's role from
 * the database/auth layer. */
function af_current_role() {
    af_seed_data();
    if (!isset($_SESSION['af_role'])) {
        $_SESSION['af_role'] = 'Admin';
    }
    return $_SESSION['af_role'];
}

function af_set_role($role) {
    $allowed = ['Admin', 'Staff'];
    if (!in_array($role, $allowed, true)) return false;
    $_SESSION['af_role'] = $role;
    return true;
}

function af_is_admin() {
    return af_current_role() === 'Admin';
}

/** Demo display name/initials that go with the current role, for the topbar avatar. */
function af_current_user_label() {
    return af_is_admin() ? 'Admin User' : 'Staff User';
}

function af_current_user_initials() {
    return af_is_admin() ? 'AU' : 'SU';
}

/** True if this person already holds an Overdue asset — used to warn admins
 *  before approving a transfer/allocation into an already-overdue holder. */
function af_holder_has_overdue($name) {
    foreach (af_assets() as $a) {
        if ($a['holder'] === $name && $a['status'] === 'Overdue') return $a;
    }
    return false;
}

/** Shared status → badge HTML helper (used by Assets, Asset detail, Transfer). */
function af_status_badge($status) {
    $map = [
        'Available'   => 'badge-available',
        'Allocated'   => 'badge-allocated',
        'Overdue'     => 'badge-overdue',
        'Maintenance' => 'badge-pending',
        'Retired'     => 'badge-retired',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return "<span class=\"badge $cls\">" . htmlspecialchars($status) . "</span>";
}

/* --- Minimal CSRF protection for demo forms --- */
function af_csrf_token() {
    if (empty($_SESSION['af_csrf'])) {
        $_SESSION['af_csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['af_csrf'];
}

function af_csrf_check() {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && !empty($_SESSION['af_csrf']) && hash_equals($_SESSION['af_csrf'], $token);
}
