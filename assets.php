<?php
require_once __DIR__ . '/includes/data.php';

$assets = af_assets();
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $assets = array_filter($assets, function ($a) use ($q) {
        $needle = strtolower($q);
        return str_contains(strtolower($a['id']), $needle)
            || str_contains(strtolower($a['name']), $needle)
            || str_contains(strtolower($a['category']), $needle);
    });
}

$pageTitle = 'Assets';
$pageSubtitle = count(af_assets()) . ' assets in the registry';
$activeNav = 'assets';
require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <form method="get" action="assets.php" class="field full" style="margin-bottom:18px;">
    <label for="q">Search by ID, name, or category</label>
    <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($q); ?>" placeholder="AF-0114, laptop, printer…">
  </form>

  <table class="data-table">
    <thead>
      <tr>
        <th>Asset ID</th><th>Name</th><th>Category</th><th>Status</th><th>Assigned to</th><th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($assets as $a): ?>
        <tr>
          <td><span class="asset-tag"><?php echo htmlspecialchars($a['id']); ?></span></td>
          <td><?php echo htmlspecialchars($a['name']); ?></td>
          <td><?php echo htmlspecialchars($a['category']); ?></td>
          <td>
            <?php echo af_status_badge($a['status']); ?>
            <?php if ($a['status'] === 'Available'): $idle = af_idle_days($a); if ($idle >= 30): ?>
              <span class="idle-pill" title="Unbooked/unused for <?php echo $idle; ?> days">idle <?php echo $idle; ?>d</span>
            <?php endif; endif; ?>
          </td>
          <td><?php echo $a['holder'] ? htmlspecialchars($a['holder'] . ' — ' . $a['department']) : '—'; ?></td>
          <td>
            <a class="btn btn-ghost" style="padding:6px 12px;" href="asset.php?id=<?php echo urlencode($a['id']); ?>">Timeline</a>
            <?php if ($a['status'] === 'Retired'): ?>
              <span style="color:var(--text-faint); font-size:12.5px;">—</span>
            <?php elseif ($a['status'] !== 'Available'): ?>
              <a class="btn btn-ghost" style="padding:6px 12px;" href="transfer.php?asset=<?php echo urlencode($a['id']); ?>">Transfer</a>
            <?php else: ?>
              <a class="btn btn-ghost" style="padding:6px 12px;" href="transfer.php?asset=<?php echo urlencode($a['id']); ?>">Allocate</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($assets)): ?>
        <tr><td colspan="6" style="color:var(--text-dim);">No assets match "<?php echo htmlspecialchars($q); ?>".</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="action-row" style="margin-top:18px;">
  <a class="btn btn-primary" href="index.php">+ Register asset from Dashboard</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
