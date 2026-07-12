<?php
/**
 * AssetFlow - Database Data Layer
 * All functions use MySQL database
 */
require_once __DIR__ . '/../config/database.php';

// Initialize session for CSRF and user role
session_start();

/* ============================================================
   CSRF Protection
   ============================================================ */
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

/* ============================================================
   User Roles
   ============================================================ */
function af_current_role() {
    if (!isset($_SESSION['af_role'])) {
        // Check if user is logged in via users table
        if (isset($_SESSION['user_id'])) {
            $sql = "SELECT role FROM users WHERE id = " . (int)$_SESSION['user_id'];
            $result = fetchOne($sql);
            if ($result) {
                $_SESSION['af_role'] = ucfirst($result['role']);
            } else {
                $_SESSION['af_role'] = 'Employee';
            }
        } else {
            $_SESSION['af_role'] = 'Employee';
        }
    }
    return $_SESSION['af_role'];
}

function af_set_role($role) {
    $allowed = ['Admin', 'Manager', 'Employee', 'Auditor', 'Maintenance'];
    if (!in_array($role, $allowed, true)) return false;
    $_SESSION['af_role'] = $role;
    return true;
}

function af_is_admin() {
    return af_current_role() === 'Admin';
}

function af_current_user_label() {
    if (isset($_SESSION['user_name'])) {
        return $_SESSION['user_name'];
    }
    return af_current_role() . ' User';
}

function af_current_user_initials() {
    $name = af_current_user_label();
    $parts = explode(' ', $name);
    if (count($parts) >= 2) {
        return strtoupper($parts[0][0] . $parts[1][0]);
    }
    return strtoupper(substr($name, 0, 2));
}

function af_get_user_id() {
    // First try to get from employees table based on email from users table
    if (isset($_SESSION['user_email'])) {
        $sql = "SELECT id FROM employees WHERE email = '" . escape($_SESSION['user_email']) . "'";
        $result = fetchOne($sql);
        if ($result) {
            return $result['id'];
        }
    }
    
    // If session has user_id, check if it exists in employees
    if (isset($_SESSION['user_id'])) {
        $sql = "SELECT id FROM employees WHERE id = " . (int)$_SESSION['user_id'];
        $result = fetchOne($sql);
        if ($result) {
            return $result['id'];
        }
    }
    
    // Get first admin user from employees
    $sql = "SELECT id FROM employees WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Admin') LIMIT 1";
    $result = fetchOne($sql);
    if ($result) {
        return $result['id'];
    }
    
    // Get any active employee
    $sql = "SELECT id FROM employees WHERE status = 'active' LIMIT 1";
    $result = fetchOne($sql);
    if ($result) {
        return $result['id'];
    }
    
    return null;
}

/* ============================================================
   Activity Log - FIXED
   ============================================================ */
function af_log_activity($text) {
    $userId = af_get_user_id();
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if ($userId) {
        $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
                VALUES ($userId, 'action', '" . escape($text) . "', '" . escape($ip) . "', '" . escape($userAgent) . "', NOW())";
    } else {
        $sql = "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent, created_at) 
                VALUES (NULL, 'action', '" . escape($text) . "', '" . escape($ip) . "', '" . escape($userAgent) . "', NOW())";
    }
    
    @query($sql);
}

function af_activity($limit = 10) {
    $sql = "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT $limit";
    $result = query($sql);
    $activity = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $activity[] = [
                'text' => $row['description'],
                'time' => date('M d, H:i', strtotime($row['created_at']))
            ];
        }
    }
    return $activity;
}

/* ============================================================
   Assets
   ============================================================ */
function af_assets() {
    $sql = "
        SELECT a.*, c.category_name, 
               CONCAT(e.first_name, ' ', e.last_name) as holder_name,
               d.department_name
        FROM assets a
        LEFT JOIN asset_categories c ON a.category_id = c.id
        LEFT JOIN employees e ON a.assigned_to = e.id
        LEFT JOIN departments d ON a.department_id = d.id
        ORDER BY a.created_at DESC
    ";
    $result = query($sql);
    $assets = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $assets[] = [
                'id' => $row['asset_tag'],
                'name' => $row['name'],
                'category' => $row['category_name'] ?? 'Uncategorized',
                'status' => ucfirst($row['status']),
                'holder' => $row['holder_name'] ?? null,
                'department' => $row['department_name'] ?? null,
                'last_activity' => $row['updated_at'] ?? $row['created_at'],
                'purchased' => $row['purchase_date'],
                'due_date' => null,
            ];
        }
    }
    return $assets;
}

function af_find_asset($id) {
    $sql = "
        SELECT a.*, c.category_name, 
               CONCAT(e.first_name, ' ', e.last_name) as holder_name,
               d.department_name,
               aa.expected_return_date as due_date
        FROM assets a
        LEFT JOIN asset_categories c ON a.category_id = c.id
        LEFT JOIN employees e ON a.assigned_to = e.id
        LEFT JOIN departments d ON a.department_id = d.id
        LEFT JOIN asset_allocations aa ON a.id = aa.asset_id AND aa.status = 'active'
        WHERE a.asset_tag = '" . escape($id) . "'
        ORDER BY aa.created_at DESC LIMIT 1
    ";
    $row = fetchOne($sql);
    if (!$row) return null;
    
    return [
        'id' => $row['asset_tag'],
        'name' => $row['name'],
        'category' => $row['category_name'] ?? 'Uncategorized',
        'status' => ucfirst($row['status']),
        'holder' => $row['holder_name'] ?? null,
        'department' => $row['department_name'] ?? null,
        'last_activity' => $row['updated_at'] ?? $row['created_at'],
        'purchased' => $row['purchase_date'],
        'due_date' => $row['due_date'] ?? null,
        'id_db' => $row['id'],
    ];
}

function af_register_asset($id, $name, $category) {
    if (af_find_asset($id)) {
        return "Asset ID \"$id\" already exists.";
    }
    
    // Get or create category
    $catSql = "SELECT id FROM asset_categories WHERE category_name = '" . escape($category) . "'";
    $cat = fetchOne($catSql);
    if ($cat) {
        $categoryId = $cat['id'];
    } else {
        return "Category '$category' does not exist. Please add it first.";
    }
    
    $sql = "INSERT INTO assets (asset_tag, name, category_id, status, created_at) 
            VALUES ('" . escape($id) . "', '" . escape($name) . "', $categoryId, 'available', NOW())";
    
    if (query($sql)) {
        $assetId = mysqli_insert_id(db());
        query("INSERT INTO asset_history (asset_id, event_type, description, created_at) 
               VALUES ($assetId, 'created', 'Asset registered', NOW())");
        
        af_log_activity("New asset $id ($name) registered - Available");
        return true;
    }
    return "Failed to register asset.";
}

function af_allocate_asset($assetId, $holder, $department) {
    // Find employee by name
    $empSql = "SELECT id FROM employees WHERE CONCAT(first_name, ' ', last_name) = '" . escape($holder) . "'";
    $emp = fetchOne($empSql);
    if (!$emp) return "Employee '$holder' not found.";
    
    // Find department
    $deptSql = "SELECT id FROM departments WHERE department_name = '" . escape($department) . "'";
    $dept = fetchOne($deptSql);
    if (!$dept) return "Department '$department' not found.";
    
    // Find asset
    $asset = af_find_asset($assetId);
    if (!$asset) return "Asset $assetId not found.";
    if ($asset['status'] !== 'Available') {
        return "Asset $assetId is not available for direct allocation.";
    }
    
    mysqli_begin_transaction(db());
    try {
        query("UPDATE assets SET assigned_to = {$emp['id']}, department_id = {$dept['id']}, status = 'allocated', updated_at = NOW() 
               WHERE asset_tag = '" . escape($assetId) . "'");
        
        query("INSERT INTO asset_allocations (asset_id, employee_id, allocation_date, status, created_at) 
               VALUES ((SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "'), 
                       {$emp['id']}, CURDATE(), 'active', NOW())");
        
        $assetDbId = fetchOne("SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "'")['id'];
        query("INSERT INTO asset_history (asset_id, event_type, description, created_at) 
               VALUES ($assetDbId, 'allocated', 'Allocated to $holder - $department', NOW())");
        
        mysqli_commit(db());
        af_log_activity("$assetId allocated to $holder - $department");
        return true;
    } catch (Exception $e) {
        mysqli_rollback(db());
        return "Allocation failed: " . $e->getMessage();
    }
}

/* ============================================================
   Transfers - FIXED
   ============================================================ */
function af_submit_transfer($assetId, $to, $note) {
    $asset = af_find_asset($assetId);
    if (!$asset) return false;
    
    $from = $asset['holder'] ?? '';
    
    // Find the "to" employee
    $empSql = "SELECT id FROM employees WHERE CONCAT(first_name, ' ', last_name) = '" . escape($to) . "'";
    $emp = fetchOne($empSql);
    if (!$emp) return false;
    
    // Find the "from" employee
    $fromEmpSql = "SELECT id FROM employees WHERE CONCAT(first_name, ' ', last_name) = '" . escape($from) . "'";
    $fromEmp = fetchOne($fromEmpSql);
    $fromEmpId = $fromEmp ? $fromEmp['id'] : 'NULL';
    
    $sql = "INSERT INTO asset_transfers (asset_id, from_employee_id, to_employee_id, transfer_date, status, reason, created_at) 
            VALUES ((SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "'), 
                    $fromEmpId,
                    {$emp['id']}, CURDATE(), 'pending', '" . escape($note) . "', NOW())";
    
    if (query($sql)) {
        af_log_activity("Transfer requested: $assetId to $to (pending approval)");
        return true;
    }
    return false;
}

function af_transfers() {
    $sql = "
        SELECT t.*, 
               a.asset_tag as asset_id,
               CONCAT(e1.first_name, ' ', e1.last_name) as `from`,
               CONCAT(e2.first_name, ' ', e2.last_name) as `to`
        FROM asset_transfers t
        JOIN assets a ON t.asset_id = a.id
        LEFT JOIN employees e1 ON t.from_employee_id = e1.id
        LEFT JOIN employees e2 ON t.to_employee_id = e2.id
        ORDER BY t.created_at DESC
    ";
    $result = query($sql);
    $transfers = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $transfers[] = [
                'asset_id' => $row['asset_id'],
                'from' => $row['from'] ?? '—',
                'to' => $row['to'] ?? '—',
                'status' => ucfirst($row['status']),
                'note' => $row['reason'] ?? '',
            ];
        }
    }
    return $transfers;
}

function af_decide_transfer($index, $approve) {
    $transfers = af_transfers();
    if (!isset($transfers[$index])) return false;
    
    $transfer = $transfers[$index];
    if ($transfer['status'] !== 'Pending') return false;
    
    // Find the actual transfer record in database
    $sql = "SELECT id, asset_id, from_employee_id, to_employee_id FROM asset_transfers 
            WHERE asset_id = (SELECT id FROM assets WHERE asset_tag = '" . escape($transfer['asset_id']) . "') 
              AND status = 'pending'
            ORDER BY created_at DESC LIMIT 1";
    $record = fetchOne($sql);
    if (!$record) return false;
    
    if ($approve) {
        mysqli_begin_transaction(db());
        try {
            // Update asset - assign to new employee
            query("UPDATE assets SET assigned_to = {$record['to_employee_id']}, status = 'allocated', updated_at = NOW() 
                   WHERE id = {$record['asset_id']}");
            
            // Update transfer status
            query("UPDATE asset_transfers SET status = 'approved', updated_at = NOW() WHERE id = {$record['id']}");
            
            // Log to asset history
            query("INSERT INTO asset_history (asset_id, event_type, description, created_at) 
                   VALUES ({$record['asset_id']}, 'transferred', 'Transferred from {$transfer['from']} to {$transfer['to']}', NOW())");
            
            mysqli_commit(db());
            af_log_activity("Transfer approved: {$transfer['asset_id']} now with {$transfer['to']}");
            return true;
        } catch (Exception $e) {
            mysqli_rollback(db());
            return false;
        }
    } else {
        // Reject transfer
        query("UPDATE asset_transfers SET status = 'rejected', updated_at = NOW() WHERE id = {$record['id']}");
        af_log_activity("Transfer rejected: {$transfer['asset_id']} to {$transfer['to']}");
        return true;
    }
}

/* ============================================================
   Statistics
   ============================================================ */
function af_stats() {
    $available = fetchOne("SELECT COUNT(*) as count FROM assets WHERE status = 'available'")['count'] ?? 0;
    $allocated = fetchOne("SELECT COUNT(*) as count FROM assets WHERE status = 'allocated'")['count'] ?? 0;
    $overdue = fetchOne("SELECT COUNT(*) as count FROM asset_allocations WHERE status = 'overdue'")['count'] ?? 0;
    $activeBookings = fetchOne("SELECT COUNT(*) as count FROM bookings WHERE status = 'approved' AND end_date >= CURDATE()")['count'] ?? 0;
    $pendingTransfers = fetchOne("SELECT COUNT(*) as count FROM asset_transfers WHERE status = 'pending'")['count'] ?? 0;
    
    $upcomingReturns = fetchOne("
        SELECT COUNT(*) as count FROM asset_allocations 
        WHERE status = 'active' AND expected_return_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ")['count'] ?? 0;
    
    return [
        'available' => $available,
        'allocated' => $allocated,
        'overdue' => $overdue,
        'active_bookings' => $activeBookings,
        'pending_transfers' => $pendingTransfers,
        'upcoming_returns' => $upcomingReturns
    ];
}

/* ============================================================
   Bookings
   ============================================================ */
function af_bookings() {
    $sql = "
        SELECT b.*, 
               CONCAT(e.first_name, ' ', e.last_name) as booked_by,
               br.room_name as room,
               b.start_date as date,
               b.start_date as start,
               b.end_date as end
        FROM bookings b
        JOIN employees e ON b.employee_id = e.id
        LEFT JOIN booking_rooms br ON b.id = br.booking_id
        ORDER BY b.created_at DESC
    ";
    $result = query($sql);
    $bookings = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $bookings[] = [
                'room' => $row['room'] ?? 'Unknown Room',
                'date' => $row['date'],
                'start' => $row['start'] . ' 09:00:00',
                'end' => $row['end'] . ' 17:00:00',
                'booked_by' => $row['booked_by'],
                'status' => ucfirst($row['status']),
            ];
        }
    }
    return $bookings;
}

function af_booking_conflict($room, $date, $start, $end) {
    $sql = "
        SELECT b.*, CONCAT(e.first_name, ' ', e.last_name) as booked_by
        FROM bookings b
        JOIN employees e ON b.employee_id = e.id
        JOIN booking_rooms br ON b.id = br.booking_id
        WHERE br.room_name = '" . escape($room) . "'
          AND b.start_date <= '" . escape($date) . "'
          AND b.end_date >= '" . escape($date) . "'
          AND b.status IN ('approved', 'pending')
    ";
    $result = query($sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return ['start' => $row['start_date'], 'end' => $row['end_date'], 'booked_by' => $row['booked_by']];
    }
    return false;
}

function af_create_booking($room, $date, $start, $end, $bookedBy) {
    $empSql = "SELECT id FROM employees WHERE CONCAT(first_name, ' ', last_name) = '" . escape($bookedBy) . "'";
    $emp = fetchOne($empSql);
    if (!$emp) return false;
    
    $bookingCode = 'BK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    
    mysqli_begin_transaction(db());
    try {
        $sql = "INSERT INTO bookings (booking_code, employee_id, booking_date, start_date, end_date, purpose, status, created_at) 
                VALUES ('$bookingCode', {$emp['id']}, CURDATE(), '" . escape($date) . "', '" . escape($date) . "', 'Room booking', 'approved', NOW())";
        query($sql);
        $bookingId = mysqli_insert_id(db());
        
        $sql = "INSERT INTO booking_rooms (booking_id, room_name, created_at) 
                VALUES ($bookingId, '" . escape($room) . "', NOW())";
        query($sql);
        
        mysqli_commit(db());
        af_log_activity("$room booked by $bookedBy - " . date('M d, Y', strtotime($date)));
        return true;
    } catch (Exception $e) {
        mysqli_rollback(db());
        return false;
    }
}

/* ============================================================
   Idle Assets
   ============================================================ */
function af_idle_assets($minDays = 1) {
    $sql = "
        SELECT a.asset_tag as id, a.name, 
               DATEDIFF(CURDATE(), a.created_at) as days
        FROM assets a
        WHERE a.status = 'available'
          AND NOT EXISTS (
              SELECT 1 FROM asset_allocations aa 
              WHERE aa.asset_id = a.id AND aa.status = 'active'
          )
          AND DATEDIFF(CURDATE(), a.created_at) >= $minDays
        ORDER BY days DESC
        LIMIT 10
    ";
    $result = query($sql);
    $idle = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $idle[] = [
                'asset' => ['id' => $row['id'], 'name' => $row['name']],
                'days' => (int)$row['days']
            ];
        }
    }
    return $idle;
}

function af_idle_days($asset) {
    if (empty($asset['last_activity'])) return 0;
    $days = (int) floor((time() - strtotime($asset['last_activity'])) / 86400);
    return max(0, $days);
}

function af_overdue_days($asset) {
    if (empty($asset['due_date'])) return 0;
    $days = (int) floor((time() - strtotime($asset['due_date'])) / 86400);
    return max(0, $days);
}

/* ============================================================
   Asset Events / Timeline
   ============================================================ */
function af_log_asset_event($assetId, $event, $detail) {
    $sql = "INSERT INTO asset_history (asset_id, event_type, description, created_at) 
            VALUES ((SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "'), 
                    '" . escape(strtolower($event)) . "', 
                    '" . escape($detail) . "', NOW())";
    query($sql);
}

function af_asset_events($assetId) {
    $sql = "
        SELECT event_type as event, description as detail, created_at as date
        FROM asset_history
        WHERE asset_id = (SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "')
        ORDER BY created_at ASC
    ";
    $result = query($sql);
    $events = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = [
                'event' => ucfirst($row['event']),
                'detail' => $row['detail'],
                'date' => $row['date']
            ];
        }
    }
    return $events;
}

/* ============================================================
   Maintenance
   ============================================================ */
function af_send_to_maintenance($assetId, $note = '') {
    $asset = af_find_asset($assetId);
    if (!$asset) return "Asset $assetId not found.";
    if (in_array($asset['status'], ['Maintenance', 'Retired'])) {
        return "Asset $assetId is already {$asset['status']}.";
    }
    
    $assetDbId = fetchOne("SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "'")['id'];
    
    $sql = "UPDATE assets SET status = 'maintenance', updated_at = NOW() 
            WHERE asset_tag = '" . escape($assetId) . "'";
    query($sql);
    
    $sql = "INSERT INTO maintenance_requests (asset_id, request_date, issue_description, status, created_at) 
            VALUES ($assetDbId, CURDATE(), '" . escape($note) . "', 'pending', NOW())";
    query($sql);
    
    query("INSERT INTO asset_history (asset_id, event_type, description, created_at) 
           VALUES ($assetDbId, 'maintained', 'Sent to maintenance" . ($note ? ": $note" : "") . "', NOW())");
    
    af_log_activity("$assetId sent to Maintenance" . ($note ? " - $note" : ''));
    return true;
}

function af_resolve_maintenance($assetId) {
    $asset = af_find_asset($assetId);
    if (!$asset) return "Asset $assetId not found.";
    if ($asset['status'] !== 'Maintenance') {
        return "Asset $assetId is not currently in Maintenance.";
    }
    
    $assetDbId = fetchOne("SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "'")['id'];
    
    $sql = "UPDATE assets SET status = 'available', assigned_to = NULL, department_id = NULL, updated_at = NOW() 
            WHERE asset_tag = '" . escape($assetId) . "'";
    query($sql);
    
    $sql = "UPDATE maintenance_requests SET status = 'completed', completed_date = CURDATE(), updated_at = NOW() 
            WHERE asset_id = $assetDbId AND status IN ('pending', 'assigned', 'in_progress')
            ORDER BY created_at DESC LIMIT 1";
    query($sql);
    
    query("INSERT INTO asset_history (asset_id, event_type, description, created_at) 
           VALUES ($assetDbId, 'available', 'Maintenance resolved, returned to pool', NOW())");
    
    af_log_activity("$assetId maintenance resolved - back to Available");
    return true;
}

function af_retire_asset($assetId) {
    $asset = af_find_asset($assetId);
    if (!$asset) return "Asset $assetId not found.";
    if ($asset['status'] === 'Retired') {
        return "Asset $assetId is already retired.";
    }
    
    $assetDbId = fetchOne("SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "'")['id'];
    
    $sql = "UPDATE assets SET status = 'retired', assigned_to = NULL, department_id = NULL, updated_at = NOW() 
            WHERE asset_tag = '" . escape($assetId) . "'";
    query($sql);
    
    query("INSERT INTO asset_history (asset_id, event_type, description, created_at) 
           VALUES ($assetDbId, 'retired', 'Asset retired from active circulation', NOW())");
    
    af_log_activity("$assetId retired");
    return true;
}

/* ============================================================
   Organization Setup
   ============================================================ */
function af_departments() {
    $sql = "SELECT * FROM departments ORDER BY department_name";
    $result = query($sql);
    $depts = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $depts[] = [
                'name' => $row['department_name'],
                'head' => $row['manager_id'] ? 'Has Manager' : '—',
                'parent' => null,
                'status' => 'Active'
            ];
        }
    }
    return $depts;
}

function af_active_departments() {
    return af_departments();
}

function af_add_department($name, $head, $parent, $status) {
    if (trim($name) === '') return 'Department name is required.';
    $sql = "INSERT INTO departments (department_name, location, created_at) 
            VALUES ('" . escape($name) . "', '" . escape($head) . "', NOW())";
    if (query($sql)) {
        af_log_activity("Department \"$name\" added");
        return true;
    }
    return "Failed to add department.";
}

function af_categories() {
    $sql = "SELECT * FROM asset_categories ORDER BY category_name";
    $result = query($sql);
    $cats = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cats[] = [
                'name' => $row['category_name'],
                'status' => 'Active'
            ];
        }
    }
    return $cats;
}

function af_active_categories() {
    return af_categories();
}

function af_add_category($name) {
    if (trim($name) === '') return 'Category name is required.';
    $sql = "INSERT INTO asset_categories (category_name, created_at) 
            VALUES ('" . escape($name) . "', NOW())";
    if (query($sql)) {
        af_log_activity("Category \"$name\" added");
        return true;
    }
    return "Failed to add category.";
}

function af_category_asset_count($categoryName) {
    $sql = "SELECT COUNT(*) as count FROM assets a 
            JOIN asset_categories c ON a.category_id = c.id 
            WHERE c.category_name = '" . escape($categoryName) . "'";
    return fetchOne($sql)['count'] ?? 0;
}

function af_employees() {
    $sql = "
        SELECT e.*, d.department_name 
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        ORDER BY e.first_name
    ";
    $result = query($sql);
    $emps = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $emps[] = [
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'department' => $row['department_name'] ?? '—',
                'status' => ucfirst($row['status'])
            ];
        }
    }
    return $emps;
}

function af_add_employee($name, $department, $status) {
    if (trim($name) === '') return 'Employee name is required.';
    
    $parts = explode(' ', $name, 2);
    $firstName = $parts[0];
    $lastName = $parts[1] ?? '';
    
    $deptSql = "SELECT id FROM departments WHERE department_name = '" . escape($department) . "'";
    $dept = fetchOne($deptSql);
    $deptId = $dept ? $dept['id'] : 'NULL';
    
    $email = strtolower($firstName . '.' . $lastName . '@assetflow.com');
    $code = 'EMP' . rand(100, 999);
    
    $sql = "INSERT INTO employees (employee_code, first_name, last_name, email, department_id, status, created_at) 
            VALUES ('$code', 
                    '" . escape($firstName) . "', 
                    '" . escape($lastName) . "', 
                    '" . escape($email) . "',
                    $deptId, 'active', NOW())";
    if (query($sql)) {
        af_log_activity("Employee \"$name\" added");
        return true;
    }
    return "Failed to add employee.";
}

function af_employee_asset_count($employeeName) {
    $sql = "SELECT COUNT(*) as count FROM assets a 
            JOIN employees e ON a.assigned_to = e.id 
            WHERE CONCAT(e.first_name, ' ', e.last_name) = '" . escape($employeeName) . "'";
    return fetchOne($sql)['count'] ?? 0;
}

/* ============================================================
   Audit
   ============================================================ */
function af_audit_cycle() {
    $sql = "SELECT * FROM audits ORDER BY created_at DESC LIMIT 1";
    $row = fetchOne($sql);
    if ($row) {
        return [
            'department' => $row['title'] ?? 'All Departments',
            'range' => date('d M Y', strtotime($row['audit_date'])) . ' - ' . date('d M Y', strtotime($row['audit_date'] . ' +7 days')),
            'auditors' => 'Audit Team',
            'status' => ucfirst($row['status'])
        ];
    }
    return ['department' => 'All Departments', 'range' => date('M Y'), 'auditors' => '—', 'status' => 'Scheduled'];
}

function af_audit_items() {
    $sql = "
        SELECT a.asset_tag as asset_id, a.name, 
               d.department_name as expected_location,
               'Pending' as verification
        FROM assets a
        LEFT JOIN departments d ON a.department_id = d.id
        WHERE a.status != 'retired'
        ORDER BY a.asset_tag
    ";
    $result = query($sql);
    $items = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = [
                'asset_id' => $row['asset_id'],
                'name' => $row['name'],
                'expected_location' => $row['expected_location'] ?? '—',
                'verification' => 'Pending'
            ];
        }
    }
    return $items;
}

function af_audit_discrepancy_count() {
    return 0;
}

function af_set_audit_verification($assetId, $verification) {
    $sql = "INSERT INTO audit_items (audit_id, asset_id, actual_condition, created_at) 
            VALUES ((SELECT id FROM audits ORDER BY created_at DESC LIMIT 1), 
                    (SELECT id FROM assets WHERE asset_tag = '" . escape($assetId) . "'), 
                    '" . escape($verification) . "', NOW())";
    query($sql);
    return true;
}

function af_close_audit_cycle() {
    $sql = "UPDATE audits SET status = 'completed', updated_at = NOW() WHERE status = 'scheduled' ORDER BY created_at DESC LIMIT 1";
    query($sql);
    af_log_activity("Audit cycle closed");
    return true;
}

function af_start_new_audit_cycle($department, $range, $auditors) {
    $sql = "INSERT INTO audits (audit_code, title, description, audit_date, status, created_at) 
            VALUES ('AUD-" . date('Ymd') . '-' . rand(100, 999) . "', 
                    '" . escape($department) . " Department Audit', 
                    'Scheduled audit for $range by $auditors', 
                    CURDATE(), 'scheduled', NOW())";
    query($sql);
    af_log_activity("New audit cycle started - $department");
    return true;
}

/* ============================================================
   Notifications
   ============================================================ */
function af_notifications($filter = 'all') {
    $sql = "SELECT * FROM notifications ORDER BY created_at DESC LIMIT 20";
    $result = query($sql);
    $notifs = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $notifs[] = [
                'text' => $row['title'] . ': ' . $row['message'],
                'time' => date('M d, H:i', strtotime($row['created_at'])),
                'type' => $row['notification_type'] ?? 'activity'
            ];
        }
    }
    return $notifs;
}

/* ============================================================
   Reports
   ============================================================ */
function af_reports_data() {
    $util = [];
    $utilSql = "
        SELECT d.department_name as label, 
               ROUND(COUNT(a.id) * 100.0 / (SELECT COUNT(*) FROM assets WHERE status != 'retired'), 1) as value
        FROM departments d
        LEFT JOIN assets a ON a.department_id = d.id AND a.status != 'retired'
        GROUP BY d.id
        HAVING value > 0
        ORDER BY value DESC
        LIMIT 6
    ";
    $result = query($utilSql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $util[] = ['label' => $row['label'], 'value' => (float)$row['value']];
        }
    }
    
    $trend = [];
    $trendSql = "
        SELECT DATE_FORMAT(created_at, '%b') as label, COUNT(*) as value
        FROM maintenance_requests
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY MONTH(created_at)
        ORDER BY created_at
    ";
    $result = query($trendSql);
    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $trend[] = ['label' => $row['label'], 'value' => (int)$row['value']];
        }
    } else {
        $trend = [['label' => 'No data', 'value' => 0]];
    }
    
    $mostUsed = [];
    $mostSql = "
        SELECT a.asset_tag as label, a.name as detail, COUNT(aa.id) as usage_count
        FROM assets a
        JOIN asset_allocations aa ON a.id = aa.asset_id
        GROUP BY a.id
        ORDER BY usage_count DESC
        LIMIT 5
    ";
    $result = query($mostSql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $mostUsed[] = ['label' => $row['label'], 'detail' => $row['detail'] . ' (' . $row['usage_count'] . ' uses)'];
        }
    }
    
    $idle = [];
    $idleResult = af_idle_assets(1);
    foreach ($idleResult as $item) {
        $idle[] = ['label' => $item['asset']['id'], 'detail' => $item['asset']['name'] . ' (' . $item['days'] . ' days idle)'];
    }
    
    $due = [];
    $dueSql = "
        SELECT a.asset_tag as label, a.name as detail
        FROM assets a
        WHERE a.status = 'maintenance'
        LIMIT 5
    ";
    $result = query($dueSql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $due[] = ['label' => $row['label'], 'detail' => $row['detail'] . ' - In maintenance'];
        }
    }
    
    return [
        'utilization' => $util,
        'maintenance_trend' => $trend,
        'most_used' => $mostUsed,
        'idle' => $idle,
        'due' => $due,
    ];
}

/* ============================================================
   Reset Data
   ============================================================ */
function af_reset_data() {
    af_log_activity("Reset data attempted - function disabled");
    return false;
}

/* ============================================================
   Utility Functions
   ============================================================ */
function af_status_badge($status) {
    $map = [
        'Available' => 'badge-available',
        'Allocated' => 'badge-allocated',
        'Overdue' => 'badge-overdue',
        'Maintenance' => 'badge-pending',
        'Retired' => 'badge-retired',
    ];
    $cls = $map[$status] ?? 'badge-pending';
    return "<span class=\"badge $cls\">" . htmlspecialchars($status) . "</span>";
}

function af_format_time($t) {
    $ts = strtotime($t);
    return $ts ? date('g:i A', $ts) : $t;
}

function af_holder_has_overdue($name) {
    $sql = "
        SELECT a.asset_tag as id, a.name 
        FROM assets a
        JOIN employees e ON a.assigned_to = e.id
        WHERE CONCAT(e.first_name, ' ', e.last_name) = '" . escape($name) . "'
          AND a.status = 'overdue'
        LIMIT 1
    ";
    return fetchOne($sql);
}
?>