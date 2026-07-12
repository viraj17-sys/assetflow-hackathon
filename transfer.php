<?php
require_once __DIR__ . '/includes/data.php';

$assets = af_assets();
$selectedId = $_GET['asset'] ?? ($assets[0]['id'] ?? '');
$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !af_csrf_check()) {
    $errorMsg = 'Session expired, please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_transfer') {
    $selectedId = $_POST['asset_id'] ?? $selectedId;
    $to   = trim($_POST['to_holder'] ?? '');
    $note = trim($_POST['note'] ?? '');
    if ($to !== '') {
        af_submit_transfer($selectedId, $to, $note);
        $successMsg = "Transfer request submitted for $selectedId. Awaiting approval.";
        $conflictAsset = af_holder_has_overdue($to);
        if ($conflictAsset) {
            $successMsg .= " Heads up: $to already holds {$conflictAsset['id']}, which is overdue — worth checking before this transfer is approved.";
        }
    } else {
        $errorMsg = 'Enter who the asset is going to.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'allocate_asset') {
    if (!af_is_admin()) {
        $errorMsg = "You don't have permission to do that. Direct allocation is Admin-only.";
    } else {
        $selectedId = $_POST['asset_id'] ?? $selectedId;
        $holder = trim($_POST['holder'] ?? '');
        $department = trim($_POST['department'] ?? '');
        if ($holder !== '') {
            $result = af_allocate_asset($selectedId, $holder, $department);
            if ($result === true) {
                $successMsg = "$selectedId allocated to $holder.";
                $conflictAsset = af_holder_has_overdue($holder);
                if ($conflictAsset) {
                    $successMsg .= " Heads up: $holder already holds {$conflictAsset['id']}, which is overdue.";
                }
            } else {
                $errorMsg = $result;
            }
        } else {
            $errorMsg = 'Enter who to allocate the asset to.';
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'decide_transfer') {
    if (!af_is_admin()) {
        $errorMsg = "You don't have permission to do that. Approving or rejecting transfers is Admin-only.";
    } else {
        $idx = (int)($_POST['transfer_index'] ?? -1);
        $approve = ($_POST['decision'] ?? '') === 'approve';
        if (af_decide_transfer($idx, $approve)) {
            $successMsg = $approve ? 'Transfer approved.' : 'Transfer rejected.';
        } else {
            $errorMsg = 'That transfer could not be updated.';
        }
    }
}

$selectedAsset = af_find_asset($selectedId);
$transfers = af_transfers();

$pageTitle = 'Allocation & Transfer';
$pageSubtitle = 'Direct re-allocation of assigned assets is blocked by design';
$activeNav = 'transfer';
require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <h2 class="section-title">Asset</h2>

  <form method="get" action="transfer.php" class="field full" style="margin-bottom:20px;">
    <label for="asset">Select an asset</label>
    <select id="asset" name="asset" onchange="this.form.submit()">
      <?php foreach ($assets as $a): ?>
        <option value="<?php echo htmlspecialchars($a['id']); ?>" <?php echo $a['id'] === $selectedId ? 'selected' : ''; ?>>
          <?php echo htmlspecialchars($a['id'] . ' — ' . $a['name']); ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($errorMsg): ?>
    <div class="block-banner"><strong>Couldn't complete that</strong><?php echo htmlspecialchars($errorMsg); ?></div>
  <?php endif; ?>

  <?php if ($selectedAsset): ?>

    <?php if ($selectedAsset['status'] === 'Allocated' || $selectedAsset['status'] === 'Overdue'): ?>
      <div class="block-banner">
        <strong>Already allocated to <?php echo htmlspecialchars($selectedAsset['holder']); ?> (<?php echo htmlspecialchars($selectedAsset['department']); ?>)</strong>
        Direct re-allocation is blocked — submit a transfer request below.
      </div>

      <?php if ($selectedAsset['status'] === 'Overdue'): ?>
        <div class="block-banner" style="border-color:var(--red-border);">
          <strong>⚠ Conflict warning: this asset is overdue</strong>
          <?php echo htmlspecialchars($selectedAsset['id']); ?> was due back
          <?php echo af_overdue_days($selectedAsset); ?> day<?php echo af_overdue_days($selectedAsset) === 1 ? '' : 's'; ?> ago.
          Transferring it now moves it straight to the new holder without a return check — confirm that's intended before approving.
        </div>
      <?php endif; ?>

      <?php if ($successMsg): ?>
        <div class="confirm-banner"><strong>Done</strong><?php echo htmlspecialchars($successMsg); ?></div>
      <?php endif; ?>

      <h2 class="section-title">Transfer Request</h2>
      <form method="post" action="transfer.php">
        <input type="hidden" name="action" value="submit_transfer">
        <input type="hidden" name="asset_id" value="<?php echo htmlspecialchars($selectedAsset['id']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">

        <div class="form-grid">
          <div class="field">
            <label>From</label>
            <div class="holder-chip"><span class="dot"></span><?php echo htmlspecialchars($selectedAsset['holder']); ?></div>
          </div>
          <div class="field">
            <label for="to_holder">To</label>
            <input id="to_holder" name="to_holder" type="text" placeholder="Search employee…" required>
          </div>
          <div class="field full">
            <label for="note">Reason for transfer (optional)</label>
            <textarea id="note" name="note" rows="3" placeholder="e.g. Employee moving to a new team"></textarea>
          </div>
        </div>

        <button class="btn btn-primary" type="submit">Submit transfer request</button>
      </form>

    <?php else: ?>
      <div class="confirm-banner">
        <strong><?php echo htmlspecialchars($selectedAsset['id']); ?> is available</strong>
        No current holder — allocate it directly below.
      </div>

      <?php if ($successMsg): ?>
        <div class="confirm-banner"><strong>Done</strong><?php echo htmlspecialchars($successMsg); ?></div>
      <?php endif; ?>

      <h2 class="section-title">Allocate directly</h2>
      <?php if (af_is_admin()): ?>
      <form method="post" action="transfer.php">
        <input type="hidden" name="action" value="allocate_asset">
        <input type="hidden" name="asset_id" value="<?php echo htmlspecialchars($selectedAsset['id']); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
        <div class="form-grid">
          <div class="field">
            <label for="holder">Assign to</label>
            <input id="holder" name="holder" type="text" placeholder="Employee name" required>
          </div>
          <div class="field">
            <label for="department">Department</label>
            <select id="department" name="department">
              <?php foreach (af_active_departments() as $d): ?>
                <option value="<?php echo htmlspecialchars($d['name']); ?>"><?php echo htmlspecialchars($d['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Allocate asset</button>
      </form>
      <?php else: ?>
        <div class="permission-note">Direct allocation is Admin-only. Ask an Admin to allocate this asset.</div>
      <?php endif; ?>
    <?php endif; ?>

  <?php endif; ?>
</div>

<div class="panel">
  <h2 class="section-title">Pending &amp; Recent Transfers</h2>
  <table class="data-table">
    <thead>
      <tr><th>Asset</th><th>From</th><th>To</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach (array_reverse($transfers, true) as $idx => $t): ?>
        <tr>
          <td><span class="asset-tag"><?php echo htmlspecialchars($t['asset_id']); ?></span></td>
          <td><?php echo htmlspecialchars($t['from']); ?></td>
          <td><?php echo htmlspecialchars($t['to']); ?></td>
          <td>
            <?php
              $badgeClass = $t['status'] === 'Approved' ? 'badge-confirmed'
                          : ($t['status'] === 'Rejected' ? 'badge-overdue' : 'badge-pending');
            ?>
            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($t['status']); ?></span>
          </td>
          <td>
            <?php if ($t['status'] === 'Pending' && af_is_admin()): ?>
              <form method="post" action="transfer.php" style="display:inline;">
                <input type="hidden" name="action" value="decide_transfer">
                <input type="hidden" name="transfer_index" value="<?php echo (int)$idx; ?>">
                <input type="hidden" name="decision" value="approve">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
                <button class="btn btn-ghost" style="padding:6px 10px;" type="submit">Approve</button>
              </form>
              <form method="post" action="transfer.php" style="display:inline;">
                <input type="hidden" name="action" value="decide_transfer">
                <input type="hidden" name="transfer_index" value="<?php echo (int)$idx; ?>">
                <input type="hidden" name="decision" value="reject">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
                <button class="btn btn-danger-outline" style="padding:6px 10px;" type="submit">Reject</button>
              </form>
            <?php elseif ($t['status'] === 'Pending'): ?>
              <span style="color:var(--text-faint); font-size:12.5px;">Awaiting Admin</span>
            <?php else: ?>
              <span style="color:var(--text-faint); font-size:12.5px;">—</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($transfers)): ?>
        <tr><td colspan="5" style="color:var(--text-dim);">No transfers yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
