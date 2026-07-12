<?php
require_once __DIR__ . '/includes/data.php';

$pages = [
    'org-setup'     => ['title' => 'Organization Setup', 'nav' => 'org', 'copy' => 'Departments, locations, and approval chains will be configured here.'],
    'maintenance'   => ['title' => 'Maintenance', 'nav' => 'maintenance', 'copy' => 'Track service tickets and scheduled upkeep for allocated assets.'],
    'audit'         => ['title' => 'Audit', 'nav' => 'audit', 'copy' => 'Full history of allocations, transfers, and overdue follow-ups.'],
    'reports'       => ['title' => 'Reports', 'nav' => 'reports', 'copy' => 'Export utilization and allocation reports by department or date range.'],
    'notifications' => ['title' => 'Notifications', 'nav' => 'notifications', 'copy' => 'Alerts for overdue returns, pending approvals, and booking conflicts.'],
    'requests'      => ['title' => 'Raise a Request', 'nav' => 'dashboard', 'copy' => 'Submit a general request to the asset operations team.'],
];

$key = $_GET['page'] ?? 'org-setup';
$page = $pages[$key] ?? $pages['org-setup'];

$pageTitle = $page['title'];
$pageSubtitle = 'Coming soon';
$activeNav = $page['nav'];
require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="placeholder">
    <div class="icon"></div>
    <h2 class="section-title" style="text-align:center;"><?php echo htmlspecialchars($page['title']); ?></h2>
    <p><?php echo htmlspecialchars($page['copy']); ?></p>
    <a class="btn btn-outline" href="index.php" style="margin-top:10px; display:inline-block;">Back to Dashboard</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
