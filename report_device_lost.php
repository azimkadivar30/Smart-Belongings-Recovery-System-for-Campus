<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];
$device_id = (int) ($_GET['device_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ? AND user_id = ?");
$stmt->execute([$device_id, $user_id]);
$device = $stmt->fetch();

if (!$device) {
  header("Location: register_device.php");
  exit();
}

if ($device['device_status'] === 'lost') {
  // Already reported -- nothing new to do here.
  header("Location: register_device.php");
  exit();
}

$error = $_SESSION['lost_device_error'] ?? '';
unset($_SESSION['lost_device_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Device as Lost | Smart Belonging System</title>
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
        <a class="nav-link" href="dashboard.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a class="nav-link" href="report.php"><i class="bi bi-file-earmark-plus"></i> Report Lost Item</a>
        <a class="nav-link" href="my_items.php"><i class="bi bi-list-check"></i> My Items</a> <a class="nav-link active"
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
          <h5 class="mb-0" style="color:var(--deep)">Report Device as Lost</h5>
          <div class="text-secondary small"><a href="register_device.php" class="text-secondary"><i
                class="bi bi-arrow-left me-1"></i>Back to My Devices</a></div>
        </div>
      </div>

      <div class="container-fluid p-4">
        <div class="row justify-content-center">
          <div class="col-lg-7">
            <div class="feature-card">
              <div class="d-flex align-items-start gap-2 p-3 mb-4"
                style="background:rgba(214,84,84,0.08);border-radius:var(--radius-sm)">
                <i class="bi bi-exclamation-triangle mt-1" style="color:#C23A3A"></i>
                <div class="small text-secondary">
                  This turns <strong
                    style="color:var(--ink)"><?php echo htmlspecialchars($device['device_name']); ?></strong> into a
                  lost item report the admin team can act on, and marks the device itself as Lost. You can still track
                  it from My Items afterward.
                </div>
              </div>

              <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
              <?php endif; ?>

              <form action="process/report_device_lost.php" method="POST" novalidate>
                <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Last Seen Location</label>
                    <input type="text" name="location" class="form-control"
                      placeholder="e.g. Central Library, 2nd Floor" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Date</label>
                    <input type="date" name="item_date" class="form-control" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Additional Details <span
                        class="text-secondary fw-normal">(optional)</span></label>
                    <textarea name="extra_notes" class="form-control" rows="3"
                      placeholder="Anything else that could help identify it, e.g. last app used, last known location details..."></textarea>
                  </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                  <a href="register_device.php" class="btn btn-outline-brand">Cancel</a>
                  <button type="submit" class="btn btn-brand px-4">Submit Lost Report</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>