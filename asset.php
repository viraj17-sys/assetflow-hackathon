<?php
require_once __DIR__ . '/includes/data.php';

$id = trim($_GET['id'] ?? '');
$asset = $id ? af_find_asset($id) : null;

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !af_csrf_check()) {
    $errorMsg = 'Session expired, please try again.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $asset) {
    $action = $_POST['action'] ?? '';
    if (!af_is_admin()) {
        $errorMsg = "You don't have permission to do that.";
    } elseif ($action === 'send_maintenance') {
        $note = trim($_POST['note'] ?? '');
        $result = af_send_to_maintenance($asset['id'], $note);
        if ($result === true) { $successMsg = "$id sent to Maintenance."; } else { $errorMsg = $result; }
    } elseif ($action === 'resolve_maintenance') {
        $result = af_resolve_maintenance($asset['id']);
        if ($result === true) { $successMsg = "$id maintenance resolved — back to Available."; } else { $errorMsg = $result; }
    } elseif ($action === 'retire_asset') {
        $result = af_retire_asset($asset['id']);
        if ($result === true) { $successMsg = "$id retired."; } else { $errorMsg = $result; }
    }
    $asset = af_find_asset($id); // refresh after mutation
}

$events = $id ? af_asset_events($id) : [];

function af_event_icon($event) {
    $map = [
        'Registered'  => '＋',
        'Allocated'   => '→',
        'Transferred' => '⇄',
        'Maintenance' => '⚑',
        'Available'   => '✓',
        'Overdue'     => '!',
        'Retired'     => '■',
    ];
    return $map[$event] ?? '•';
}
function af_event_class($event) {
    $map = [
        'Registered'  => 'tl-registered',
        'Allocated'   => 'tl-allocated',
        'Transferred' => 'tl-transferred',
        'Maintenance' => 'tl-maintenance',
        'Available'   => 'tl-available',
        'Overdue'     => 'tl-overdue',
        'Retired'     => 'tl-retired',
    ];
    return $map[$event] ?? '';
}

$pageTitle = $asset ? $asset['id'] : 'Asset not found';
$pageSubtitle = $asset ? $asset['name'] . ' — lifecycle timeline' : '';
$activeNav = 'assets';
require __DIR__ . '/includes/header.php';
?>

<?php if (!$asset): ?>
  <div class="panel">
    <div class="block-banner"><strong>Asset not found</strong>No asset matches "<?php echo htmlspecialchars($id); ?>".</div>
    <div class="action-row" style="margin-top:14px;"><a class="btn btn-outline" href="assets.php">Back to Assets</a></div>
  </div>
<?php else: ?>

  <?php if ($successMsg): ?><div class="confirm-banner"><strong>Done</strong><?php echo htmlspecialchars($successMsg); ?></div><?php endif; ?>
  <?php if ($errorMsg): ?><div class="block-banner"><strong>Couldn't complete that</strong><?php echo htmlspecialchars($errorMsg); ?></div><?php endif; ?>

  <div class="panel asset-detail-head">
    <div class="asset-detail-main">
      <span class="asset-tag" style="font-size:14px;"><?php echo htmlspecialchars($asset['id']); ?></span>
      <h2 style="margin:8px 0 2px; font-family:var(--font-display);"><?php echo htmlspecialchars($asset['name']); ?></h2>
      <div style="color:var(--text-dim); font-size:14px;"><?php echo htmlspecialchars($asset['category']); ?></div>
    </div>
    <div class="asset-detail-meta">
      <div><span class="label">Status</span><?php echo af_status_badge($asset['status']); ?></div>
      <div><span class="label">Assigned to</span><span><?php echo $asset['holder'] ? htmlspecialchars($asset['holder'] . ' — ' . $asset['department']) : '—'; ?></span></div>
      <?php if ($asset['status'] === 'Available'):
        $idleDays = af_idle_days($asset);
      ?>
        <div><span class="label">Idle</span><span class="<?php echo $idleDays >= 30 ? 'idle-warn' : ''; ?>"><?php echo $idleDays; ?> day<?php echo $idleDays === 1 ? '' : 's'; ?></span></div>
      <?php endif; ?>
      <?php if ($asset['status'] === 'Overdue'):
        $overdueDays = af_overdue_days($asset);
      ?>
        <div><span class="label">Overdue by</span><span class="idle-warn"><?php echo $overdueDays; ?> day<?php echo $overdueDays === 1 ? '' : 's'; ?></span></div>
      <?php endif; ?>
      <?php if (!empty($asset['purchased'])): ?>
        <div><span class="label">Purchased</span><span><?php echo htmlspecialchars(date('j M Y', strtotime($asset['purchased']))); ?></span></div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (af_is_admin() && !in_array($asset['status'], ['Retired'], true)): ?>
  <div class="panel">
    <h2 class="section-title">Actions</h2>
    <div class="action-row">
      <?php if ($asset['status'] === 'Maintenance'): ?>
        <form method="post" action="asset.php?id=<?php echo urlencode($asset['id']); ?>">
          <input type="hidden" name="action" value="resolve_maintenance">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
          <button class="btn btn-success" type="submit">Resolve maintenance → Available</button>
        </form>
      <?php elseif (in_array($asset['status'], ['Available', 'Allocated'], true)): ?>
        <form method="post" action="asset.php?id=<?php echo urlencode($asset['id']); ?>" style="display:flex; gap:10px; align-items:flex-end;">
          <input type="hidden" name="action" value="send_maintenance">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
          <div class="field" style="margin-bottom:0;">
            <label for="note">Maintenance note (optional)</label>
            <input id="note" name="note" type="text" placeholder="e.g. Screen replacement">
          </div>
          <button class="btn btn-outline" type="submit">Send to Maintenance</button>
        </form>
      <?php endif; ?>
      <form method="post" action="asset.php?id=<?php echo urlencode($asset['id']); ?>" onsubmit="return confirm('Retire <?php echo htmlspecialchars($asset['id']); ?>? This removes it from active circulation.');">
        <input type="hidden" name="action" value="retire_asset">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
        <button class="btn btn-danger-outline" type="submit">Retire asset</button>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <div class="panel">
    <h2 class="section-title">Lifecycle timeline</h2>
    <div class="timeline">
      <?php foreach ($events as $ev): ?>
        <div class="timeline-item">
          <div class="timeline-dot <?php echo af_event_class($ev['event']); ?>"><?php echo af_event_icon($ev['event']); ?></div>
          <div class="timeline-body">
            <div class="timeline-head">
              <strong><?php echo htmlspecialchars($ev['event']); ?></strong>
              <span class="timeline-date"><?php echo htmlspecialchars(date('j M Y', strtotime($ev['date']))); ?></span>
            </div>
            <div class="timeline-detail"><?php echo htmlspecialchars($ev['detail']); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($events)): ?>
        <div style="color:var(--text-dim);">No history recorded yet for this asset.</div>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
