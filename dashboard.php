<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];

// --- stat counts ---
$stmt = $pdo->prepare("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'found' THEN 1 ELSE 0 END) AS found,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
    FROM items WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();
$total = $stats['total'] ?? 0;
$found = $stats['found'] ?? 0;
$pending = $stats['pending'] ?? 0;

// --- recent items ---
$stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC LIMIT 6");
$stmt->execute([$user_id]);
$recentItems = $stmt->fetchAll();

// --- device count ---
$stmt = $pdo->prepare("SELECT COUNT(*) FROM devices WHERE user_id = ?");
$stmt->execute([$user_id]);
$deviceCount = (int) $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Smart Belonging System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body style="background:var(--paper)">

  <div class="d-flex">

    <!-- ===== Sidebar ===== -->
    <div class="dash-sidebar" style="width:260px; flex-shrink:0;">
      <div class="d-flex align-items-center gap-2 mb-5">
        <span class="brand-tag"><i class="bi bi-qr-code"></i></span>
        <span class="fw-semibold">Belonging<span style="opacity:.7">System</span></span>
      </div>
      <nav class="nav flex-column">
        <a class="nav-link active" href="dashboard.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="report.php"><i class="bi bi-file-earmark-plus"></i> Report Lost Item</a>
        <a class="nav-link" href="my_items.php"><i class="bi bi-list-check"></i> My Items</a> <a class="nav-link"
          href="register_device.php"><i class="bi bi-cpu"></i> My Devices</a>
        <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i> Notifications</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
        <hr style="border-color:rgba(255,255,255,0.15)">
        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </nav>
    </div>

    <!-- ===== Main content ===== -->
    <div class="flex-grow-1">
      <div class="dash-topbar">
        <div>
          <h5 class="mb-0" style="color:var(--deep)">Welcome back,
            <?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?> 👋</h5>
          <div class="text-secondary small">Here's what's happening with your belongings</div>
        </div>
        <div class="d-flex gap-2">
          <a href="register_device.php" class="btn btn-outline-brand"><i class="bi bi-cpu me-1"></i> Register Device</a>
          <a href="report.php" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Report Item</a>
        </div>
      </div>

      <div class="container-fluid p-4">

        <!-- Stat cards -->
        <div class="row g-4 mb-4">
          <div class="col-md-3 col-sm-6">
            <div class="stat-card">
              <div class="stat-icon icon-total"><i class="bi bi-archive"></i></div>
              <div>
                <div class="stat-value"><?php echo (int) $total; ?></div>
                <div class="stat-label">Total Items Reported</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="border-left-color:#6FCF97">
              <div class="stat-icon icon-found"><i class="bi bi-check-circle"></i></div>
              <div>
                <div class="stat-value"><?php echo (int) $found; ?></div>
                <div class="stat-label">Items Found</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="border-left-color:#F2C572">
              <div class="stat-icon icon-pending"><i class="bi bi-hourglass-split"></i></div>
              <div>
                <div class="stat-value"><?php echo (int) $pending; ?></div>
                <div class="stat-label">Pending Review</div>
              </div>
            </div>
          </div>
          <div class="col-md-3 col-sm-6">
            <div class="stat-card" style="border-left-color:var(--mid)">
              <div class="stat-icon icon-total"><i class="bi bi-cpu"></i></div>
              <div>
                <div class="stat-value"><?php echo $deviceCount; ?></div>
                <div class="stat-label">Registered Devices</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent items -->
        <div class="row g-4">
          <div class="col-lg-8">
            <div class="feature-card">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0" style="color:var(--deep)">Recent Items</h6>
                <a href="my_items.php" class="small fw-semibold">View all</a>
              </div>

              <?php if (empty($recentItems)): ?>
                <div class="text-center py-5">
                  <i class="bi bi-inbox" style="font-size:2.2rem;color:var(--mid)"></i>
                  <p class="text-secondary mt-2 mb-3">You haven't reported any items yet.</p>
                  <a href="report.php" class="btn btn-brand btn-sm">Report your first item</a>
                </div>
              <?php else: ?>
                <?php foreach ($recentItems as $item): ?>
                  <div class="recent-item-row">
                    <div class="item-thumb"><i class="bi bi-box-seam"></i></div>
                    <div class="flex-grow-1">
                      <div class="fw-semibold" style="color:var(--ink)"><?php echo htmlspecialchars($item['item_name']); ?>
                      </div>
                      <div class="text-secondary small"><i class="bi bi-geo-alt"></i>
                        <?php echo htmlspecialchars($item['location']); ?> &nbsp;·&nbsp;
                        <?php echo date('d M Y', strtotime($item['item_date'])); ?></div>
                    </div>
                    <span
                      class="badge-status badge-<?php echo htmlspecialchars($item['status']); ?>"><?php echo str_replace('_', ' ', $item['status']); ?></span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="col-lg-4">
            <div class="feature-card h-100">
              <h6 class="mb-3" style="color:var(--deep)">Quick Tips</h6>
              <div class="d-flex gap-2 mb-3">
                <i class="bi bi-qr-code-scan" style="color:var(--mid)"></i>
                <p class="small text-secondary mb-0">Attach a QR tag to your laptop or phone so it can be identified
                  instantly if found.</p>
              </div>
              <div class="d-flex gap-2 mb-3">
                <i class="bi bi-envelope" style="color:var(--mid)"></i>
                <p class="small text-secondary mb-0">You'll receive an email the moment the admin updates your item's
                  status.</p>
              </div>
              <div class="d-flex gap-2">
                <i class="bi bi-shield-check" style="color:var(--mid)"></i>
                <p class="small text-secondary mb-0">Only verified admin staff can mark an item as found or collected.
                </p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>