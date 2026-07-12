<?php
require_once __DIR__ . '/includes/data.php';

// Handle "+ Register asset" inline form submit
$registerOpen = false;
$registerSuccess = '';
$registerError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_asset') {
    if (!af_csrf_check()) {
        $registerError = 'Session expired, please try again.';
        $registerOpen = true;
    } elseif (!af_is_admin()) {
        $registerError = "You don't have permission to do that. Registering assets is Admin-only.";
        $registerOpen = true;
    } else {
        $id       = trim($_POST['asset_id'] ?? '');
        $name     = trim($_POST['asset_name'] ?? '');
        $category = trim($_POST['asset_category'] ?? '');
        if ($id !== '' && $name !== '') {
            $result = af_register_asset($id, $name, $category ?: 'Uncategorized');
            if ($result === true) {
                $registerSuccess = "Asset $id registered and marked available.";
            } else {
                $registerError = $result;
                $registerOpen = true;
            }
        } else {
            $registerError = 'Asset ID and name are required.';
            $registerOpen = true;
        }
    }
}

// Handle "Reset demo data"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_data' && af_csrf_check() && af_is_admin()) {
    af_reset_data();
    header('Location: index.php?reset=1');
    exit;
}

$stats = af_stats();
$activity = af_activity();
$idleTop = af_idle_assets(30); // 30+ days idle, worst first

$pageTitle = 'Dashboard';
$pageSubtitle = "Today's overview";
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<h2 class="section-title">Today's Overview</h2>

<div class="stat-grid">
  <div class="stat-card teal">
    <div class="stat-label">Available</div>
    <div class="stat-value"><?php echo $stats['available']; ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Allocated</div>
    <div class="stat-value"><?php echo $stats['allocated']; ?></div>
  </div>
  <div class="stat-card red">
    <div class="stat-label">Overdue for return</div>
    <div class="stat-value"><?php echo $stats['overdue']; ?></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-label">Active bookings</div>
    <div class="stat-value"><?php echo $stats['active_bookings']; ?></div>
  </div>
  <div class="stat-card amber">
    <div class="stat-label">Pending transfers</div>
    <div class="stat-value"><?php echo $stats['pending_transfers']; ?></div>
  </div>
  <div class="stat-card teal">
    <div class="stat-label">Upcoming returns</div>
    <div class="stat-value"><?php echo $stats['upcoming_returns']; ?></div>
  </div>
</div>

<?php if ($stats['overdue'] > 0): ?>
<div class="alert-banner">
  <strong><?php echo $stats['overdue']; ?> asset<?php echo $stats['overdue'] > 1 ? 's' : ''; ?> overdue for return</strong>
  — flagged for follow-up in Audit.
</div>
<?php endif; ?>

<?php if (!empty($idleTop)): $worst = $idleTop[0]['asset']; $worstDays = $idleTop[0]['days']; ?>
<div class="alert-banner" style="border-color:#ECD09B; background:var(--amber-tint);">
  <strong><?php echo htmlspecialchars($worst['name'] . ' (' . $worst['id'] . ')'); ?> has been unbooked for <?php echo $worstDays; ?> days</strong>
  — <?php echo count($idleTop) - 1 > 0 ? (count($idleTop) - 1) . ' more asset' . ((count($idleTop) - 1) === 1 ? '' : 's') . ' also idle 30+ days. ' : ''; ?><a href="reports.php" style="text-decoration:underline; color:inherit;">See idle-time insights →</a>
</div>
<?php endif; ?>

<?php if ($registerSuccess): ?>
<div class="confirm-banner"><strong>Registered</strong><?php echo htmlspecialchars($registerSuccess); ?></div>
<?php endif; ?>

<?php if (isset($_GET['reset'])): ?>
<div class="confirm-banner"><strong>Demo data reset</strong>Assets, transfers, bookings, and activity are back to defaults.</div>
<?php endif; ?>

<div class="action-row">
  <?php if (af_is_admin()): ?>
    <button class="btn btn-primary" onclick="document.getElementById('registerDrawer').classList.toggle('open')">+ Register asset</button>
  <?php endif; ?>
  <a class="btn btn-outline" href="booking.php">Book resource</a>
  <a class="btn btn-outline" href="page.php?page=requests">Raise requests</a>
  <?php if (af_is_admin()): ?>
    <form method="post" action="index.php" style="margin-left:auto;" onsubmit="return confirm('Reset all demo data back to defaults?');">
      <input type="hidden" name="action" value="reset_data">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
      <button class="btn btn-danger-outline" type="submit">Reset demo data</button>
    </form>
  <?php endif; ?>
</div>

<?php if (af_is_admin()): ?>
<div id="registerDrawer" class="drawer <?php echo $registerOpen ? 'open' : ''; ?>">
  <div class="panel">
    <h2 class="section-title">Register a new asset</h2>
    <?php if ($registerError): ?>
      <div class="block-banner"><strong>Couldn't register asset</strong><?php echo htmlspecialchars($registerError); ?></div>
    <?php endif; ?>
    <form method="post" action="index.php">
      <input type="hidden" name="action" value="register_asset">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
      <div class="form-grid">
        <div class="field">
          <label for="asset_id">Asset ID</label>
          <input id="asset_id" name="asset_id" type="text" placeholder="AF-0245" required>
        </div>
        <div class="field">
          <label for="asset_name">Asset name</label>
          <input id="asset_name" name="asset_name" type="text" placeholder="Dell Latitude 5440" required>
        </div>
        <div class="field full">
          <label for="asset_category">Category</label>
          <input id="asset_category" name="asset_category" type="text" list="categoryList" placeholder="Laptop, Projector, Monitor…">
          <datalist id="categoryList">
            <?php foreach (af_active_categories() as $c): ?>
              <option value="<?php echo htmlspecialchars($c['name']); ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">Save asset</button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="panel">
  <h2 class="section-title">Recent Activity</h2>
  <div class="activity-list">
    <?php foreach ($activity as $item): ?>
      <div class="activity-item">
        <span><?php echo htmlspecialchars($item['text']); ?></span>
        <span class="tag"><?php echo htmlspecialchars($item['time']); ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
