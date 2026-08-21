<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

// --- handle delete (before any HTML output) ---
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = ?");
    $stmt->execute([(int)$_GET['delete']]);
    header("Location: manage_items.php");
    exit();
}

$filter = $_GET['status'] ?? 'all';
$typeFilter = $_GET['report_type'] ?? 'all';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT items.*, users.full_name AS reporter FROM items JOIN users ON items.user_id = users.id WHERE 1=1";
$params = [];

if (in_array($filter, ['pending', 'found', 'not_found', 'collected'])) {
    $sql .= " AND items.status = ?";
    $params[] = $filter;
}

if (in_array($typeFilter, ['lost', 'found'])) {
    $sql .= " AND items.report_type = ?";
    $params[] = $typeFilter;
}

if ($search !== '') {
    $sql .= " AND (items.item_name LIKE ? OR users.full_name LIKE ? OR items.location LIKE ?)";
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$sql .= " ORDER BY items.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

$counts = $pdo->query("SELECT
    COUNT(*) AS all_count,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'found' THEN 1 ELSE 0 END) AS found,
    SUM(CASE WHEN status = 'not_found' THEN 1 ELSE 0 END) AS not_found,
    SUM(CASE WHEN status = 'collected' THEN 1 ELSE 0 END) AS collected,
    SUM(CASE WHEN report_type = 'lost' THEN 1 ELSE 0 END) AS lost_type,
    SUM(CASE WHEN report_type = 'found' THEN 1 ELSE 0 END) AS found_type
    FROM items")->fetch();

function pill_url($status, $q, $reportType = 'all') {
    $params = [];
    if ($status !== 'all') $params['status'] = $status;
    if ($reportType !== 'all') $params['report_type'] = $reportType;
    if ($q !== '') $params['q'] = $q;
    return 'manage_items.php' . (count($params) ? '?' . http_build_query($params) : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Items | Admin | Smart Belonging System</title>
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
  .complaint-tag {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    display: inline-block;
    white-space: nowrap;
  }
  .tag-escalated { background: rgba(214,84,84,0.14); color: #C23A3A; }
  .tag-resolved { background: rgba(76,175,131,0.15); color: #2E8A5E; }
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
      <a class="nav-link active" href="manage_items.php"><i class="bi bi-box-seam"></i> Manage Items</a>
      <a class="nav-link" href="manage_devices.php"><i class="bi bi-cpu"></i> Manage Devices</a>
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
        <h5 class="mb-0" style="color:var(--deep)">Manage Items</h5>
        <div class="text-secondary small">Review and update all lost &amp; found reports</div>
      </div>
    </div>

    <div class="container-fluid p-4">

      <!-- Filters + search -->
      <div class="d-flex flex-wrap gap-2 mb-2">
        <a class="filter-pill <?php echo $typeFilter === 'all' ? 'active' : ''; ?>" href="<?php echo pill_url($filter, $search, 'all'); ?>">All Types (<?php echo (int)$counts['all_count']; ?>)</a>
        <a class="filter-pill <?php echo $typeFilter === 'lost' ? 'active' : ''; ?>" href="<?php echo pill_url($filter, $search, 'lost'); ?>"><i class="bi bi-question-circle me-1"></i>Lost Item Reports (<?php echo (int)$counts['lost_type']; ?>)</a>
        <a class="filter-pill <?php echo $typeFilter === 'found' ? 'active' : ''; ?>" href="<?php echo pill_url($filter, $search, 'found'); ?>"><i class="bi bi-hand-index-thumb me-1"></i>Found Item Reports (<?php echo (int)$counts['found_type']; ?>)</a>
      </div>

      <form method="GET" class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex flex-wrap gap-2">
          <a class="filter-pill <?php echo $filter === 'all' ? 'active' : ''; ?>" href="<?php echo pill_url('all', $search, $typeFilter); ?>">All Status (<?php echo (int)$counts['all_count']; ?>)</a>
          <a class="filter-pill <?php echo $filter === 'pending' ? 'active' : ''; ?>" href="<?php echo pill_url('pending', $search, $typeFilter); ?>">Pending (<?php echo (int)$counts['pending']; ?>)</a>
          <a class="filter-pill <?php echo $filter === 'found' ? 'active' : ''; ?>" href="<?php echo pill_url('found', $search, $typeFilter); ?>">Found (<?php echo (int)$counts['found']; ?>)</a>
          <a class="filter-pill <?php echo $filter === 'not_found' ? 'active' : ''; ?>" href="<?php echo pill_url('not_found', $search, $typeFilter); ?>">Not Found (<?php echo (int)$counts['not_found']; ?>)</a>
          <a class="filter-pill <?php echo $filter === 'collected' ? 'active' : ''; ?>" href="<?php echo pill_url('collected', $search, $typeFilter); ?>">Collected (<?php echo (int)$counts['collected']; ?>)</a>
        </div>
        <div class="search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="search-input" placeholder="Search by item, student, location...">
          <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
          <input type="hidden" name="report_type" value="<?php echo htmlspecialchars($typeFilter); ?>">
        </div>
      </form>

      <div class="table-card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Item</th>
                <th>Type</th>
                <th>Reported By</th>
                <th>Location</th>
                <th>Date</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($items)): ?>
                <tr><td colspan="7" class="text-center text-secondary py-4">No items match this view.</td></tr>
              <?php endif; ?>
              <?php foreach ($items as $item): ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="mini-thumb"><i class="bi bi-box-seam"></i></div>
                    <div>
                      <div class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                      <div class="text-secondary small">#SBS-<?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($item['report_type'] === 'lost'): ?>
                    <span class="complaint-tag tag-escalated"><i class="bi bi-question-circle me-1"></i>Lost</span>
                  <?php else: ?>
                    <span class="complaint-tag tag-resolved"><i class="bi bi-hand-index-thumb me-1"></i>Found</span>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($item['reporter']); ?></td>
                <td><?php echo htmlspecialchars($item['location']); ?></td>
                <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                <td>
                  <?php if ($item['report_type'] === 'found'): ?>
                    <span class="complaint-tag tag-resolved"><i class="bi bi-hand-index-thumb me-1"></i>Logged</span>
                  <?php else: ?>
                    <span class="badge-status badge-<?php echo htmlspecialchars($item['status']); ?>"><?php echo str_replace('_', ' ', ucfirst($item['status'])); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-end">
                  <?php if ($item['report_type'] !== 'found'): ?>
                    <a href="update.php?id=<?php echo $item['id']; ?>" class="icon-btn" title="Update Status"><i class="bi bi-pencil"></i></a>
                  <?php endif; ?>
                  <a href="update.php?id=<?php echo $item['id']; ?>" class="icon-btn" title="View"><i class="bi bi-eye"></i></a>
                  <a href="?delete=<?php echo $item['id']; ?>" class="icon-btn danger" title="Delete" onclick="return confirm('Delete this item report? This cannot be undone.');"><i class="bi bi-trash"></i></a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="text-secondary small mt-3">Showing <?php echo count($items); ?> of <?php echo (int)$counts['all_count']; ?> items</div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
