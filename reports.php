<?php
require_once __DIR__ . '/includes/data.php';

$data = af_reports_data();
$util = $data['utilization'];
$trend = $data['maintenance_trend'];
$idleAssets = af_idle_assets(1);

// Bar chart heights (percentage of max)
$maxUtil = ($util ? max(array_column($util, 'value')) : 0) ?: 1;

// Line chart points (SVG viewBox 0 0 300 130, small margin)
$maxTrend = ($trend ? max(array_column($trend, 'value')) : 0) ?: 1;
$minTrend = $trend ? min(array_column($trend, 'value')) : 0;
$count = count($trend);
$points = [];
foreach ($trend as $i => $t) {
    $x = $count > 1 ? ($i / ($count - 1)) * 300 : 150;
    $range = ($maxTrend - $minTrend) ?: 1;
    $y = 120 - (($t['value'] - $minTrend) / $range) * 110;
    $points[] = "$x,$y";
}
$polyline = implode(' ', $points);

$pageTitle = 'Reports & Analytics';
$pageSubtitle = 'Utilization, maintenance frequency, most-used/idle, booking heatmap';
$activeNav = 'reports';
require __DIR__ . '/includes/header.php';
?>

<div class="chart-grid">
  <div class="chart-panel">
    <div class="chart-title">Utilization by department</div>
    <div class="bar-chart">
      <?php foreach ($util as $u): ?>
        <div class="bar" style="height: <?php echo max(6, round(($u['value'] / $maxUtil) * 100)); ?>%" title="<?php echo htmlspecialchars($u['label'] . ': ' . $u['value'] . '%'); ?>"></div>
      <?php endforeach; ?>
    </div>
    <div style="display:flex; gap:12px; margin-top:8px;">
      <?php foreach ($util as $u): ?>
        <div style="flex:1; text-align:center; font-size:11px; color:rgba(255,255,255,0.65);"><?php echo htmlspecialchars($u['label']); ?></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="chart-panel">
    <div class="chart-title">Maintenance Frequency</div>
    <svg class="line-chart" viewBox="0 0 300 130" preserveAspectRatio="none">
      <line x1="0" y1="120" x2="300" y2="120"></line>
      <polyline points="<?php echo htmlspecialchars($polyline); ?>"></polyline>
      <?php foreach ($points as $p): [$px, $py] = explode(',', $p); ?>
        <circle cx="<?php echo $px; ?>" cy="<?php echo $py; ?>" r="3"></circle>
      <?php endforeach; ?>
    </svg>
    <div style="display:flex; gap:8px; margin-top:8px;">
      <?php foreach ($trend as $t): ?>
        <div style="flex:1; text-align:center; font-size:11px; color:rgba(255,255,255,0.65);"><?php echo htmlspecialchars($t['label']); ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="panel">
  <div class="report-columns">
    <div class="report-list">
      <h3>Most used assets</h3>
      <ul>
        <?php foreach ($data['most_used'] as $item): ?>
          <li><strong><?php echo htmlspecialchars($item['label']); ?>:</strong> <?php echo htmlspecialchars($item['detail']); ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="report-list">
      <h3>Idle assets <span style="font-weight:400; color:var(--text-faint); font-size:12px;">— unused time, worst first</span></h3>
      <ul>
        <?php if (empty($idleAssets)): ?>
          <li style="color:var(--text-dim);">Nothing sitting idle right now — nice.</li>
        <?php endif; ?>
        <?php foreach (array_slice($idleAssets, 0, 6) as $row): $a = $row['asset']; ?>
          <li>
            <a href="asset.php?id=<?php echo urlencode($a['id']); ?>" style="color:inherit;">
              <strong><?php echo htmlspecialchars($a['name'] . ' (' . $a['id'] . ')'); ?>:</strong>
            </a>
            unused <?php echo $row['days']; ?> day<?php echo $row['days'] === 1 ? '' : 's'; ?><?php echo $row['days'] >= 30 ? ' ⚠' : ''; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <hr class="report-divider">

  <div class="report-list">
    <h3>Assets due for maintenance / nearing retirement</h3>
    <ul>
      <?php foreach ($data['due'] as $item): ?>
        <li><strong><?php echo htmlspecialchars($item['label']); ?>:</strong> <?php echo htmlspecialchars($item['detail']); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <div class="action-row" style="margin-top:20px; margin-bottom:0;">
    <a class="btn btn-danger-outline" href="export_report.php">Export report</a>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
