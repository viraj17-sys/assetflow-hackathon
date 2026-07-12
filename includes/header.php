<?php
/**
 * Shared header: <head>, sidebar nav, topbar.
 * Expects $pageTitle, $pageSubtitle and $activeNav to be set by the caller.
 */
$activeNav = $activeNav ?? '';
$pageTitle = $pageTitle ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';

function af_nav_class($key, $activeNav) {
    return 'nav-link' . ($key === $activeNav ? ' active' : '');
}

// Role switcher (demo-only stand-in for a real login system). Handled here,
// before any HTML is echoed, so it can redirect back to whatever page the
// switcher was submitted from.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'switch_role' && af_csrf_check()) {
    af_set_role($_POST['role'] ?? 'Admin');
    $backTo = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header('Location: ' . $backTo);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AssetFlow — <?php echo htmlspecialchars($pageTitle); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app-shell">

  <aside class="sidebar">
    <div class="brand">
      <div class="brand-mark"></div>
      <div class="brand-name">AssetFlow</div>
    </div>

    <nav class="nav-group">
      <a class="<?php echo af_nav_class('dashboard', $activeNav); ?>" href="index.php"><span class="nav-dot"></span>Dashboard</a>
      <?php if (af_is_admin()): ?>
      <a class="<?php echo af_nav_class('org', $activeNav); ?>" href="org.php"><span class="nav-dot"></span>Organization setup</a>
      <?php endif; ?>
      <a class="<?php echo af_nav_class('assets', $activeNav); ?>" href="assets.php"><span class="nav-dot"></span>Assets</a>
      <a class="<?php echo af_nav_class('transfer', $activeNav); ?>" href="transfer.php"><span class="nav-dot"></span>Allocation &amp; Transfer</a>
      <a class="<?php echo af_nav_class('booking', $activeNav); ?>" href="booking.php"><span class="nav-dot"></span>Resource Booking</a>
      <a class="<?php echo af_nav_class('maintenance', $activeNav); ?>" href="page.php?page=maintenance"><span class="nav-dot"></span>Maintenance</a>
      <a class="<?php echo af_nav_class('audit', $activeNav); ?>" href="audit.php"><span class="nav-dot"></span>Audit</a>
      <a class="<?php echo af_nav_class('reports', $activeNav); ?>" href="reports.php"><span class="nav-dot"></span>Reports</a>
      <a class="<?php echo af_nav_class('notifications', $activeNav); ?>" href="notifications.php"><span class="nav-dot"></span>Notifications</a>
    </nav>
  </aside>

  <div class="main">
    <div class="topbar">
      <div>
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <?php if ($pageSubtitle): ?><div class="subtitle"><?php echo htmlspecialchars($pageSubtitle); ?></div><?php endif; ?>
      </div>
      <div class="topbar-right">
        <button type="button" class="cmdk-trigger" onclick="afOpenPalette()">
          <span>Search or jump to…</span>
          <span class="cmdk-kbd">⌘K</span>
        </button>
        <span><?php echo date('D, d M Y'); ?></span>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'index.php'); ?>" class="role-switch">
          <input type="hidden" name="action" value="switch_role">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
          <label for="roleSelect" class="role-switch-label">Viewing as</label>
          <select id="roleSelect" name="role" onchange="this.form.submit()">
            <option value="Admin" <?php echo af_is_admin() ? 'selected' : ''; ?>>Admin</option>
            <option value="Staff" <?php echo !af_is_admin() ? 'selected' : ''; ?>>Staff</option>
          </select>
        </form>
        <a class="avatar" href="profile.php" title="<?php echo htmlspecialchars(af_current_user_label() . ' — ' . af_current_role()); ?>"><?php echo htmlspecialchars(af_current_user_initials()); ?></a>
      </div>
    </div>

    <div class="content">

<div class="cmdk-overlay" id="cmdkOverlay" onclick="if(event.target===this) afClosePalette()">
  <div class="cmdk-box" role="dialog" aria-modal="true" aria-label="Command palette">
    <input type="text" id="cmdkInput" class="cmdk-input" placeholder="Jump to a page or an asset…" autocomplete="off">
    <div class="cmdk-results" id="cmdkResults"></div>
    <div class="cmdk-footer"><span>↑↓ to navigate</span><span>↵ to select</span><span>esc to close</span></div>
  </div>
</div>

<script>
(function() {
  var PAGES = [
    { label: 'Dashboard', sub: 'Overview & activity', href: 'index.php' },
    <?php if (af_is_admin()): ?>
    { label: 'Organization setup', sub: 'Departments, categories, employees', href: 'org.php' },
    <?php endif; ?>
    { label: 'Assets', sub: 'Asset registry', href: 'assets.php' },
    { label: 'Allocation & Transfer', sub: 'Allocate or transfer assets', href: 'transfer.php' },
    { label: 'Resource Booking', sub: 'Book rooms & equipment', href: 'booking.php' },
    { label: 'Maintenance', sub: '', href: 'page.php?page=maintenance' },
    { label: 'Audit', sub: 'Asset verification cycle', href: 'audit.php' },
    { label: 'Reports & Analytics', sub: 'Utilization, idle time, trends', href: 'reports.php' },
    { label: 'Notifications', sub: 'Activity log', href: 'notifications.php' },
    { label: 'Profile', sub: 'Your account & assigned assets', href: 'profile.php' }
  ];
  var ASSETS = <?php echo json_encode(array_map(fn($a) => [
      'label' => $a['id'] . ' — ' . $a['name'],
      'sub' => $a['status'] . ($a['holder'] ? ' · ' . $a['holder'] : ''),
      'href' => 'asset.php?id=' . urlencode($a['id']),
  ], af_assets())); ?>;
  var ALL = PAGES.map(function(p){ p.type='page'; return p; }).concat(ASSETS.map(function(a){ a.type='asset'; return a; }));

  var overlay = document.getElementById('cmdkOverlay');
  var input = document.getElementById('cmdkInput');
  var results = document.getElementById('cmdkResults');
  var activeIndex = 0;
  var currentList = [];

  function render(list) {
    currentList = list;
    activeIndex = 0;
    results.innerHTML = '';
    if (list.length === 0) {
      results.innerHTML = '<div class="cmdk-empty">No matches.</div>';
      return;
    }
    list.forEach(function(item, i) {
      var row = document.createElement('a');
      row.href = item.href;
      row.className = 'cmdk-row' + (i === 0 ? ' active' : '');
      row.innerHTML = '<span class="cmdk-row-type">' + (item.type === 'asset' ? '◆' : '›') + '</span>' +
        '<span class="cmdk-row-text"><span class="cmdk-row-label">' + item.label + '</span>' +
        (item.sub ? '<span class="cmdk-row-sub">' + item.sub + '</span>' : '') + '</span>';
      results.appendChild(row);
    });
  }

  function filter(q) {
    q = q.trim().toLowerCase();
    if (!q) return ALL.slice(0, 9);
    return ALL.filter(function(item) {
      return item.label.toLowerCase().includes(q) || (item.sub && item.sub.toLowerCase().includes(q));
    }).slice(0, 12);
  }

  function setActive(i) {
    var rows = results.querySelectorAll('.cmdk-row');
    if (!rows.length) return;
    activeIndex = (i + rows.length) % rows.length;
    rows.forEach(function(r, idx) { r.classList.toggle('active', idx === activeIndex); });
    rows[activeIndex].scrollIntoView({ block: 'nearest' });
  }

  window.afOpenPalette = function() {
    overlay.classList.add('open');
    input.value = '';
    render(filter(''));
    setTimeout(function() { input.focus(); }, 10);
  };
  window.afClosePalette = function() {
    overlay.classList.remove('open');
  };

  input.addEventListener('input', function() { render(filter(input.value)); });
  input.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); setActive(activeIndex + 1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(activeIndex - 1); }
    else if (e.key === 'Enter') {
      e.preventDefault();
      var rows = results.querySelectorAll('.cmdk-row');
      if (rows[activeIndex]) window.location.href = rows[activeIndex].getAttribute('href');
    } else if (e.key === 'Escape') { afClosePalette(); }
  });

  document.addEventListener('keydown', function(e) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      overlay.classList.contains('open') ? afClosePalette() : afOpenPalette();
    } else if (e.key === 'Escape' && overlay.classList.contains('open')) {
      afClosePalette();
    }
  });
})();
</script>
