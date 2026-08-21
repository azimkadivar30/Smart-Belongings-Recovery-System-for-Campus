<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

// --- handle delete (before any HTML output) ---
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: manage_devices.php");
    exit();
}

$filter = $_GET['status'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT devices.*, users.full_name AS owner, users.email AS owner_email
        FROM devices JOIN users ON devices.user_id = users.id WHERE 1=1";
$params = [];

if (in_array($filter, ['active', 'lost', 'recovered'])) {
    $sql .= " AND devices.device_status = ?";
    $params[] = $filter;
}

if ($search !== '') {
    $sql .= " AND (devices.device_name LIKE ? OR devices.brand LIKE ? OR devices.serial_number LIKE ? OR users.full_name LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

$sql .= " ORDER BY devices.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$devices = $stmt->fetchAll();

$counts = $pdo->query("SELECT
    COUNT(*) AS all_count,
    SUM(CASE WHEN device_status = 'active' THEN 1 ELSE 0 END) AS active,
    SUM(CASE WHEN device_status = 'lost' THEN 1 ELSE 0 END) AS lost,
    SUM(CASE WHEN device_status = 'recovered' THEN 1 ELSE 0 END) AS recovered
    FROM devices")->fetch();

function device_pill_url($status, $q) {
    $params = [];
    if ($status !== 'all') $params['status'] = $status;
    if ($q !== '') $params['q'] = $q;
    return 'manage_devices.php' . (count($params) ? '?' . http_build_query($params) : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Devices | Admin | Smart Belonging System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
  .filter-pill {
    border: 1.5px solid #E1E7EA;
    background: var(--white);
    color: var(--ink-soft);
    font-weight: 600;
    font-size: 0.85rem;
    padding: 0.4rem 1rem;
    border-radius: 30px;
    text-decoration: none;
    display: inline-block;
  }
  .filter-pill.active, .filter-pill:hover { background: var(--deep); color: var(--white); border-color: var(--deep); }
  .search-wrap { position: relative; }
  .search-wrap i { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: var(--ink-soft); }
  .search-input { border-radius: var(--radius-sm); border: 1.5px solid #E1E7EA; padding: 0.6rem 1rem 0.6rem 2.4rem; background: var(--white); }
  .badge-active    { background: rgba(122,170,206,0.18); color: var(--deep); }
  .badge-lost      { background: rgba(214,84,84,0.14); color: #C23A3A; }
  .badge-recovered { background: rgba(76,175,131,0.15); color: #2E8A5E; }
</style>
</head>
<body style="background:var(--paper)">

<div class="d-flex">

  <!-- ===== Sidebar ===== -->
  <div class="dash-sidebar admin-sidebar" style="width:260px; flex-shrink:0;">
    <div class="d-flex align-items-center gap-2 mb-5">
      <span class="brand-tag"><i class="bi bi-shield-lock"></i></span>
      <span class="fw-semibold">Admin<span style="opacity:.7">Panel</span></span>
    </div>
    <nav class="nav flex-column">
      <a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
      <a class="nav-link" href="manage_items.php"><i class="bi bi-box-seam"></i> Manage Items</a>
      <a class="nav-link active" href="manage_devices.php"><i class="bi bi-cpu"></i> Manage Devices</a>
      <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Manage Users</a>
      <a class="nav-link" href="reports.php"><i class="bi bi-flag"></i> Reports / Complaints</a>
      <hr style="border-color:rgba(255,255,255,0.15)">
      <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
  </div>

  <!-- ===== Main content ===== -->
  <div class="flex-grow-1">
    <div class="dash-topbar">
      <div>
        <h5 class="mb-0" style="color:var(--deep)">Manage Devices</h5>
        <div class="text-secondary small">Every gadget students have registered with a smart QR tag</div>
      </div>
    </div>

    <div class="container-fluid p-4">

      <form method="GET" class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex flex-wrap gap-2">
          <a class="filter-pill <?php echo $filter === 'all' ? 'active' : ''; ?>" href="<?php echo device_pill_url('all', $search); ?>">All (<?php echo (int)$counts['all_count']; ?>)</a>
          <a class="filter-pill <?php echo $filter === 'active' ? 'active' : ''; ?>" href="<?php echo device_pill_url('active', $search); ?>">Active (<?php echo (int)$counts['active']; ?>)</a>
          <a class="filter-pill <?php echo $filter === 'lost' ? 'active' : ''; ?>" href="<?php echo device_pill_url('lost', $search); ?>">Lost (<?php echo (int)$counts['lost']; ?>)</a>
          <a class="filter-pill <?php echo $filter === 'recovered' ? 'active' : ''; ?>" href="<?php echo device_pill_url('recovered', $search); ?>">Recovered (<?php echo (int)$counts['recovered']; ?>)</a>
        </div>
        <div class="search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="search-input" placeholder="Search by device, brand, serial, owner...">
          <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
        </div>
      </form>

      <div class="table-card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Device</th>
                <th>Owner</th>
                <th>Serial No.</th>
                <th>Tag ID</th>
                <th>Registered</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($devices)): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">No devices match this view.</td></tr>
              <?php endif; ?>
              <?php foreach ($devices as $d): ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="mini-thumb"><i class="bi bi-cpu"></i></div>
                    <div>
                      <div class="fw-semibold"><?php echo htmlspecialchars($d['device_name']); ?></div>
                      <div class="text-secondary small"><?php echo htmlspecialchars($d['brand']); ?><?php echo $d['model'] ? ' · ' . htmlspecialchars($d['model']) : ''; ?></div>
                    </div>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($d['owner']); ?></td>
                <td><?php echo htmlspecialchars($d['serial_number'] ?: '—'); ?></td>
                <td><span class="text-secondary small"><?php echo htmlspecialchars($d['qr_token'] ?: '—'); ?></span></td>
                <td><?php echo date('d M Y', strtotime($d['created_at'])); ?></td>
                <td><span class="badge-status badge-<?php echo htmlspecialchars($d['device_status']); ?>"><?php echo ucfirst($d['device_status']); ?></span></td>
                <td class="text-end">
                  <a href="update_device.php?id=<?php echo $d['id']; ?>" class="icon-btn" title="Update Status"><i class="bi bi-pencil"></i></a>
                  <a href="?delete=<?php echo $d['id']; ?>" class="icon-btn danger" title="Delete" onclick="return confirm('Delete this device? This cannot be undone.');"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="text-secondary small mt-3">Showing <?php echo count($devices); ?> of <?php echo (int)$counts['all_count']; ?> devices</div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
