<?php
require_once __DIR__ . '/includes/data.php';

$allowedTabs = ['departments', 'categories', 'employee'];
$tab = $_GET['tab'] ?? 'departments';
if (!in_array($tab, $allowedTabs, true)) {
    $tab = 'departments';
}

$drawerOpen = isset($_GET['add']);
$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !af_csrf_check()) {
    $errorMsg = 'Session expired, please try again.';
    $drawerOpen = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !af_is_admin()) {
    $errorMsg = "You don't have permission to do that. Organization Setup is Admin-only.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_department') {
    $result = af_add_department(
        trim($_POST['name'] ?? ''), trim($_POST['head'] ?? ''),
        trim($_POST['parent'] ?? ''), $_POST['status'] ?? 'Active'
    );
    if ($result === true) { $successMsg = 'Department added.'; } else { $errorMsg = $result; $drawerOpen = true; }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_category') {
    $result = af_add_category(trim($_POST['name'] ?? ''));
    if ($result === true) { $successMsg = 'Category added.'; } else { $errorMsg = $result; $drawerOpen = true; }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_employee') {
    $result = af_add_employee(trim($_POST['name'] ?? ''), trim($_POST['department'] ?? ''), $_POST['status'] ?? 'Active');
    if ($result === true) { $successMsg = 'Employee added.'; } else { $errorMsg = $result; $drawerOpen = true; }
}

$departments = af_departments();
$categories = af_categories();
$employees = af_employees();
$activeDepartments = af_active_departments();

function af_org_status_badge($status) {
    $cls = $status === 'Active' ? 'badge-verified' : 'badge-missing';
    return "<span class=\"badge $cls\">" . htmlspecialchars($status) . "</span>";
}

$pageTitle = 'Organization Setup';
$pageSubtitle = 'Admin only — feeds picklists used across Allocation, Transfer & Booking';
$activeNav = 'org';
require __DIR__ . '/includes/header.php';
?>

<?php if ($errorMsg): ?>
  <div class="block-banner"><strong>Couldn't save that</strong><?php echo htmlspecialchars($errorMsg); ?></div>
<?php endif; ?>
<?php if ($successMsg): ?>
  <div class="confirm-banner"><strong>Done</strong><?php echo htmlspecialchars($successMsg); ?></div>
<?php endif; ?>

<?php if (!af_is_admin()): ?>

<div class="panel">
  <h2 class="section-title">Access restricted</h2>
  <p style="color:var(--text-dim); font-size:13.5px;">Organization Setup — departments, categories, and employees — is Admin-only. Switch to Admin in the topbar if you need to make changes here.</p>
</div>

<?php else: ?>

<div class="tab-row">
  <a class="btn btn-tab <?php echo $tab === 'departments' ? 'active' : ''; ?>" href="org.php?tab=departments">Departments</a>
  <a class="btn btn-tab <?php echo $tab === 'categories' ? 'active' : ''; ?>" href="org.php?tab=categories">Categories</a>
  <a class="btn btn-tab <?php echo $tab === 'employee' ? 'active' : ''; ?>" href="org.php?tab=employee">Employee</a>
  <button class="btn btn-primary" style="margin-left:auto;" onclick="document.getElementById('addDrawer').classList.toggle('open')">+ Add</button>
</div>

<div id="addDrawer" class="drawer <?php echo $drawerOpen ? 'open' : ''; ?>">
  <div class="panel">
    <?php if ($tab === 'departments'): ?>
      <h2 class="section-title">Add a department</h2>
      <form method="post" action="org.php?tab=departments">
        <input type="hidden" name="action" value="add_department">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
        <div class="form-grid">
          <div class="field">
            <label for="name">Department name</label>
            <input id="name" name="name" type="text" placeholder="e.g. Field Ops (West)" required>
          </div>
          <div class="field">
            <label for="head">Department head</label>
            <input id="head" name="head" type="text" placeholder="e.g. Divya Nair">
          </div>
          <div class="field">
            <label for="parent">Parent department</label>
            <input id="parent" name="parent" type="text" placeholder="Optional">
          </div>
          <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Save department</button>
      </form>
    <?php elseif ($tab === 'categories'): ?>
      <h2 class="section-title">Add a category</h2>
      <form method="post" action="org.php?tab=categories">
        <input type="hidden" name="action" value="add_category">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
        <div class="field full">
          <label for="cat_name">Category name</label>
          <input id="cat_name" name="name" type="text" placeholder="e.g. Tablet" required>
        </div>
        <button class="btn btn-primary" type="submit">Save category</button>
      </form>
    <?php else: ?>
      <h2 class="section-title">Add an employee</h2>
      <form method="post" action="org.php?tab=employee">
        <input type="hidden" name="action" value="add_employee">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(af_csrf_token()); ?>">
        <div class="form-grid">
          <div class="field">
            <label for="emp_name">Employee name</label>
            <input id="emp_name" name="name" type="text" placeholder="e.g. Divya Nair" required>
          </div>
          <div class="field">
            <label for="emp_dept">Department</label>
            <select id="emp_dept" name="department">
              <?php foreach ($activeDepartments as $d): ?>
                <option value="<?php echo htmlspecialchars($d['name']); ?>"><?php echo htmlspecialchars($d['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Save employee</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <?php if ($tab === 'departments'): ?>
    <table class="data-table">
      <thead>
        <tr><th>Department</th><th>Head</th><th>Parent Dept</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($departments as $d): ?>
          <tr>
            <td><?php echo htmlspecialchars($d['name']); ?></td>
            <td><?php echo htmlspecialchars($d['head']); ?></td>
            <td><?php echo $d['parent'] ? htmlspecialchars($d['parent']) : '—'; ?></td>
            <td><?php echo af_org_status_badge($d['status']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <hr class="report-divider">
    <p style="color:var(--text-dim); font-size:13.5px;">Editing a department here also drives the picklist used on Allocation &amp; Transfer and Resource Booking.</p>

  <?php elseif ($tab === 'categories'): ?>
    <table class="data-table">
      <thead>
        <tr><th>Category</th><th>Assets in use</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($categories as $c): ?>
          <tr>
            <td><?php echo htmlspecialchars($c['name']); ?></td>
            <td><?php echo af_category_asset_count($c['name']); ?></td>
            <td><?php echo af_org_status_badge($c['status']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <hr class="report-divider">
    <p style="color:var(--text-dim); font-size:13.5px;">Editing categories here also drives the picklist used when registering a new asset on the Dashboard.</p>

  <?php else: ?>
    <table class="data-table">
      <thead>
        <tr><th>Employee</th><th>Department</th><th>Assets held</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $e): ?>
          <tr>
            <td><?php echo htmlspecialchars($e['name']); ?></td>
            <td><?php echo htmlspecialchars($e['department']); ?></td>
            <td><?php echo af_employee_asset_count($e['name']); ?></td>
            <td><?php echo af_org_status_badge($e['status']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
