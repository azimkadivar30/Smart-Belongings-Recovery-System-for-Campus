<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

$stmt = $pdo->query("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'found' THEN 1 ELSE 0 END) AS found,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'not_found' THEN 1 ELSE 0 END) AS not_found,
    SUM(CASE WHEN status = 'collected' THEN 1 ELSE 0 END) AS collected
    FROM items");
$stats = $stmt->fetch();
$total     = (int)($stats['total'] ?? 0);
$found     = (int)($stats['found'] ?? 0);
$pending   = (int)($stats['pending'] ?? 0);
$not_found = (int)($stats['not_found'] ?? 0);
$collected = (int)($stats['collected'] ?? 0);

$pctFound     = $total ? round(($found / $total) * 100) : 0;
$pctPending   = $total ? round(($pending / $total) * 100) : 0;
$pctNotFound  = $total ? round(($not_found / $total) * 100) : 0;

$stmt = $pdo->query("SELECT items.*, users.full_name AS reporter
    FROM items JOIN users ON items.user_id = users.id
    ORDER BY items.created_at DESC LIMIT 5");
$recent = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Smart Belonging System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
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
      <a class="nav-link active" href="dashboard.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
      <a class="nav-link" href="manage_items.php"><i class="bi bi-box-seam"></i> Manage Items</a>
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
        <h5 class="mb-0" style="color:var(--deep)">Admin Overview</h5>
        <div class="text-secondary small">Campus-wide lost &amp; found activity at a glance</div>
      </div>
      <span class="badge-status badge-collected"><i class="bi bi-shield-check me-1"></i>Administrator</span>
    </div>

    <div class="container-fluid p-4">

      <!-- Stat cards -->
      <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
          <div class="stat-card">
            <div class="stat-icon icon-total"><i class="bi bi-people"></i></div>
            <div>
              <div class="stat-value"><?php echo $totalUsers; ?></div>
              <div class="stat-label">Registered Users</div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card" style="border-left-color:var(--deep)">
            <div class="stat-icon icon-total"><i class="bi bi-archive"></i></div>
            <div>
              <div class="stat-value"><?php echo $total; ?></div>
              <div class="stat-label">Total Items Reported</div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card" style="border-left-color:#6FCF97">
            <div class="stat-icon icon-found"><i class="bi bi-check-circle"></i></div>
            <div>
              <div class="stat-value"><?php echo $found; ?></div>
              <div class="stat-label">Items Found</div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card" style="border-left-color:#E0A23B">
            <div class="stat-icon icon-pending"><i class="bi bi-hourglass-split"></i></div>
            <div>
              <div class="stat-value"><?php echo $pending; ?></div>
              <div class="stat-label">Pending Review</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Recent reports -->
        <div class="col-lg-8">
          <div class="feature-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="mb-0" style="color:var(--deep)">Recent Reports</h6>
              <a href="manage_items.php" class="small fw-semibold">View all</a>
            </div>

            <?php if (empty($recent)): ?>
              <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size:2.2rem;color:var(--mid)"></i>
                <p class="text-secondary mt-2 mb-0">No items have been reported yet.</p>
              </div>
            <?php else: ?>
              <?php foreach ($recent as $item): ?>
                <div class="recent-item-row">
                  <div class="item-thumb"><i class="bi bi-box-seam"></i></div>
                  <div class="flex-grow-1">
                    <div class="fw-semibold"><?php echo htmlspecialchars($item['item_name']); ?></div>
                    <div class="text-secondary small">Reported by <?php echo htmlspecialchars($item['reporter']); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($item['location']); ?></div>
                  </div>
                  <span class="badge-status badge-<?php echo htmlspecialchars($item['status']); ?>"><?php echo str_replace('_', ' ', ucfirst($item['status'])); ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Quick actions / breakdown -->
        <div class="col-lg-4">
          <div class="feature-card mb-4">
            <h6 class="mb-3" style="color:var(--deep)">Status Breakdown</h6>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small text-secondary">Found</span>
              <span class="small fw-semibold"><?php echo $found; ?></span>
            </div>
            <div class="progress mb-3" style="height:8px;border-radius:20px">
              <div class="progress-bar" style="width:<?php echo $pctFound; ?>%;background:#6FCF97"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small text-secondary">Pending</span>
              <span class="small fw-semibold"><?php echo $pending; ?></span>
            </div>
            <div class="progress mb-3" style="height:8px;border-radius:20px">
              <div class="progress-bar" style="width:<?php echo $pctPending; ?>%;background:#E0A23B"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="small text-secondary">Not Found</span>
              <span class="small fw-semibold"><?php echo $not_found; ?></span>
            </div>
            <div class="progress" style="height:8px;border-radius:20px">
              <div class="progress-bar" style="width:<?php echo $pctNotFound; ?>%;background:#D65454"></div>
            </div>
          </div>

          <div class="feature-card">
            <h6 class="mb-3" style="color:var(--deep)">Quick Actions</h6>
            <a href="manage_items.php" class="btn btn-outline-brand w-100 mb-2 text-start"><i class="bi bi-box-seam me-2"></i>Review Pending Items</a>
            <a href="users.php" class="btn btn-outline-brand w-100 mb-2 text-start"><i class="bi bi-people me-2"></i>Manage Users</a>
            <a href="reports.php" class="btn btn-outline-brand w-100 text-start"><i class="bi bi-flag me-2"></i>View Complaints</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
