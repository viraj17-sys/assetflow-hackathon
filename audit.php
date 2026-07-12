<?php
require_once __DIR__ . '/includes/data.php';

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !af_csrf_check()) {
    $errorMsg = 'Session expired, please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_verification') {
    $assetId = $_POST['asset_id'] ?? '';
    $verification = $_POST['verification'] ?? '';
    if (af_set_audit_verification($assetId, $verification)) {
        $successMsg = "$assetId marked $verification.";
    } else {
        $errorMsg = 'Could not update that checklist row.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'close_cycle') {
    if (!af_is_admin()) {
        $errorMsg = "You don't have permission to do that. Closing an audit cycle is Admin-only.";
    } elseif (af_close_audit_cycle()) {
        $successMsg = 'Audit cycle closed and discrepancy report generated.';
    } else {
        $errorMsg = 'This audit cycle is already closed.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start_cycle') {
    if (!af_is_admin()) {
        $errorMsg = "You don't have permission to do that. Starting a new audit cycle is Admin-only.";
    } else {
        $dept = trim($_POST['department'] ?? '');
        $range = trim($_POST['range'] ?? '');
        $auditors = trim($_POST['auditors'] ?? '');
        af_start_new_audit_cycle($dept, $range, $auditors);
        $successMsg = 'New audit cycle started.';
    }
}

$cycle = af_audit_cycle();
$items = af_audit_items();
$flagged = af_audit_discrepancy_count();
$isClosed = $cycle['status'] === 'Closed';

function af_verification_badge($v) {
    $map = ['Verified' => 'badge-verified', 'Missing' => 'badge-missing', 'Damaged' => 'badge-damaged'];
    $cls = $map[$v] ?? 'badge-pending';
    return "<span class=\"badge $cls\">" . htmlspecialchars($v) . "</span>";
}

$pageTitle = 'Asset Audit';
$pageSubtitle = 'Audit cycle, checklist, and auto-generated discrepancy report';
$activeNav = 'audit';
require __DIR__ . '/includes/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="block-banner"><strong>Couldn't complete that</strong><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div class="confirm-banner"><strong>Done</strong><?php echo htmlspecialchars($successMsg); ?></div>
<?php endif; ?>

<div class="audit-info-box">
  <div class="line"><strong>Q<?php echo ceil((int)date('n') / 3); ?> audit: <?php echo htmlspecialchars($cycle['department']); ?> dept</strong> — <?php echo htmlspecialchars($cycle['range']); ?></div>
  <div class="line">Auditors: <?php echo htmlspecialchars($cycle['auditors']); ?></div>
  <div class="line">Status: <span class="badge <?php echo $isClosed ? 'badge-missing' : 'badge-verified'; ?>"><?php echo htmlspecialchars($cycle['status']); ?></span></div>
</div>

<div class="panel">
  <table class="data-table">
    <thead>
      <tr><th>Asset</th><th>Expected location</th><th>Verification</th><?php if (!$isClosed): ?><th></th><?php endif; ?></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><span class="asset-tag"><?php echo htmlspecialchars($item['asset_id']); ?></span> <?php echo htmlspecialchars($item['name']); ?></td>
          <td><?php echo htmlspecialchars($item['expected_location']); ?></td>
          <td><?php echo af_verification_badge($item['verification']); ?></td>
          <?php if (!$isClosed): ?>
          <td>
            <form method="post" action="audit.php" style="display:flex; gap:6px; align-items:center;">
              <input type="hidden" name="action" value="set_verification">
              <input type="hidden" name="asset_id" value="<?php echo htmlspecialchars($item['asset_id']); ?>">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
              <select name="verification" class="verify-select" onchange="this.form.submit()">
                <?php foreach (['Verified', 'Missing', 'Damaged'] as $opt): ?>
                  <option value="<?php echo $opt; ?>" <?php echo $item['verification'] === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                <?php endforeach; ?>
              </select>
            </form>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
        <tr><td colspan="4" style="color:var(--text-dim);">No checklist items in this cycle.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <?php if (!empty($items)): ?>
    <div class="discrepancy-banner">
      <?php echo $flagged; ?> asset<?php echo $flagged === 1 ? '' : 's'; ?> flagged — discrepancy report generated automatically
    </div>
  <?php endif; ?>

  <?php if (!$isClosed): ?>
    <?php if (af_is_admin()): ?>
    <form method="post" action="audit.php" onsubmit="return confirm('Close this audit cycle? You can start a new one afterwards.');">
      <input type="hidden" name="action" value="close_cycle">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
      <button class="btn btn-teal-outline" type="submit">Close audit cycle</button>
    </form>
    <?php else: ?>
      <div class="permission-note">Closing this audit cycle is Admin-only.</div>
    <?php endif; ?>
  <?php else: ?>
    <div class="confirm-banner" style="margin-top:20px;">
      <strong>Cycle closed</strong>This audit is done. Start a new cycle below to run another one.
    </div>
    <?php if (af_is_admin()): ?>
    <form method="post" action="audit.php">
      <input type="hidden" name="action" value="start_cycle">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
      <div class="form-grid">
        <div class="field">
          <label for="department">Department</label>
          <input id="department" name="department" type="text" placeholder="e.g. Finance" required>
        </div>
        <div class="field">
          <label for="range">Date range</label>
          <input id="range" name="range" type="text" placeholder="e.g. 16–31 Jul" required>
        </div>
        <div class="field full">
          <label for="auditors">Auditors</label>
          <input id="auditors" name="auditors" type="text" placeholder="e.g. A. Rao, S. Iqbal" required>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">Start new audit cycle</button>
    </form>
    <?php else: ?>
      <div class="permission-note">Starting a new audit cycle is Admin-only.</div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
