<?php
require_once __DIR__ . '/includes/data.php';

$allowedFilters = ['all', 'alerts', 'approvals', 'bookings'];
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

$items = af_notifications($filter);

$tabs = [
    'all'        => 'All',
    'alerts'     => 'Alerts',
    'approvals'  => 'Approvals',
    'bookings'   => 'Bookings',
];

$pageTitle = 'Notifications';
$pageSubtitle = 'Activity logs & notifications';
$activeNav = 'notifications';
require __DIR__ . '/includes/header.php';
?>

<div class="tab-row">
  <?php foreach ($tabs as $key => $label): ?>
    <a class="btn btn-tab <?php echo $filter === $key ? 'active' : ''; ?>" href="notifications.php?filter=<?php echo urlencode($key); ?>"><?php echo htmlspecialchars($label); ?></a>
  <?php endforeach; ?>
</div>

<div class="panel">
  <div class="notif-list">
    <?php foreach ($items as $n): ?>
      <div class="notif-item">
        <span class="notif-dot <?php echo htmlspecialchars($n['type']); ?>"></span>
        <span class="notif-text"><?php echo htmlspecialchars($n['text']); ?></span>
        <span class="notif-time"><?php echo htmlspecialchars($n['time']); ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
      <div class="notif-item"><span class="notif-text" style="color:var(--text-dim);">Nothing here yet.</span></div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
