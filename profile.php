<?php
require_once __DIR__ . '/includes/data.php';

$myName = af_current_user_label();
$myRole = af_current_role();
$myInitials = af_current_user_initials();

// Look up the matching employee record (Organization Setup) for department/status.
$myEmployee = null;
foreach (af_employees() as $e) {
    if ($e['name'] === $myName) { $myEmployee = $e; break; }
}

$myAssets = array_values(array_filter(af_assets(), fn($a) => $a['holder'] === $myName));
$myAssetCount = count($myAssets);
$myOverdueCount = count(array_filter($myAssets, fn($a) => $a['status'] === 'Overdue'));

$myTransfers = array_values(array_filter(af_transfers(), fn($t) => $t['to'] === $myName || $t['from'] === $myName));

// Loose match on the demo activity log — good enough since there's no real user_id.
$myActivity = array_values(array_filter(af_activity(), fn($item) => str_contains($item['text'], $myName)));

$pageTitle = 'Profile';
$pageSubtitle = 'Your account, role, and assigned assets';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>

<div class="panel profile-head">
  <div class="profile-avatar"><?php echo htmlspecialchars($myInitials); ?></div>
  <div class="profile-info">
    <h2 style="margin:0 0 4px; font-family:var(--font-display);"><?php echo htmlspecialchars($myName); ?></h2>
    <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
      <span class="badge <?php echo $myRole === 'Admin' ? 'badge-allocated' : 'badge-available'; ?>"><?php echo htmlspecialchars($myRole); ?></span>
      <span style="color:var(--text-dim); font-size:14px;"><?php echo htmlspecialchars($myEmployee['department'] ?? '—'); ?></span>
    </div>
    <div style="color:var(--text-faint); font-size:13px; margin-top:8px; font-family:var(--font-mono);">
      <?php echo htmlspecialchars(strtolower(str_replace(' ', '.', $myName)) . '@assetflow.demo'); ?>
    </div>
  </div>
  <div class="profile-switch-note">
    <span class="permission-note" style="margin:0;">This is a demo profile — switch "Viewing as" in the topbar to see the other role.</span>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(3, 1fr);">
  <div class="stat-card blue">
    <div class="stat-label">Assets assigned to me</div>
    <div class="stat-value"><?php echo $myAssetCount; ?></div>
  </div>
  <div class="stat-card red">
    <div class="stat-label">Overdue on my account</div>
    <div class="stat-value"><?php echo $myOverdueCount; ?></div>
  </div>
  <div class="stat-card amber">
    <div class="stat-label">Transfers involving me</div>
    <div class="stat-value"><?php echo count($myTransfers); ?></div>
  </div>
</div>

<div class="panel">
  <h2 class="section-title">My assets</h2>
  <table class="data-table">
    <thead>
      <tr><th>Asset ID</th><th>Name</th><th>Category</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($myAssets as $a): ?>
        <tr>
          <td><span class="asset-tag"><?php echo htmlspecialchars($a['id']); ?></span></td>
          <td><?php echo htmlspecialchars($a['name']); ?></td>
          <td><?php echo htmlspecialchars($a['category']); ?></td>
          <td><?php echo af_status_badge($a['status']); ?></td>
          <td><a class="btn btn-ghost" style="padding:6px 12px;" href="asset.php?id=<?php echo urlencode($a['id']); ?>">Timeline</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($myAssets)): ?>
        <tr><td colspan="5" style="color:var(--text-dim);">Nothing assigned to you right now.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h2 class="section-title">Transfers involving me</h2>
  <table class="data-table">
    <thead>
      <tr><th>Asset</th><th>From</th><th>To</th><th>Status</th></tr>
    </thead>
    <tbody>
      <?php foreach (array_reverse($myTransfers) as $t): ?>
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
        </tr>
      <?php endforeach; ?>
      <?php if (empty($myTransfers)): ?>
        <tr><td colspan="4" style="color:var(--text-dim);">No transfers involving you yet.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="panel">
  <h2 class="section-title">My recent activity</h2>
  <div class="activity-list">
    <?php foreach ($myActivity as $item): ?>
      <div class="activity-item">
        <span><?php echo htmlspecialchars($item['text']); ?></span>
        <span class="tag"><?php echo htmlspecialchars($item['time']); ?></span>
      </div>
    <?php endforeach; ?>
    <?php if (empty($myActivity)): ?>
      <div class="activity-item"><span style="color:var(--text-dim);">Nothing involving you in the recent activity log.</span></div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
