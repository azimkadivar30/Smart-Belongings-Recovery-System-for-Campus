<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
  header("Location: logout.php");
  exit();
}

$stmt = $pdo->prepare("SELECT
    COUNT(*) AS reported,
    SUM(CASE WHEN status IN ('found','collected') THEN 1 ELSE 0 END) AS found,
    SUM(CASE WHEN qr_code IS NOT NULL THEN 1 ELSE 0 END) AS qr_tags
    FROM items WHERE user_id = ?");
$stmt->execute([$user_id]);
$stats = $stmt->fetch();

$initials = '';
foreach (explode(' ', $user['full_name']) as $part) {
  $initials .= strtoupper(substr($part, 0, 1));
}
$initials = substr($initials, 0, 2);

$info_error = $_SESSION['profile_info_error'] ?? '';
$info_success = $_SESSION['profile_info_success'] ?? '';
$pwd_error = $_SESSION['profile_pwd_error'] ?? '';
$pwd_success = $_SESSION['profile_pwd_success'] ?? '';
$pic_error = $_SESSION['profile_pic_error'] ?? '';
unset($_SESSION['profile_info_error'], $_SESSION['profile_info_success'], $_SESSION['profile_pwd_error'], $_SESSION['profile_pwd_success'], $_SESSION['profile_pic_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile | Smart Belonging System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    .avatar-circle {
      width: 84px;
      height: 84px;
      border-radius: 50%;
      background: linear-gradient(145deg, var(--deep), var(--mid));
      color: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: var(--font-display);
      font-size: 2rem;
      font-weight: 600;
      flex-shrink: 0;
      overflow: hidden;
    }

    .avatar-circle img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .avatar-edit-btn {
      position: relative;
      display: inline-block;
    }

    .avatar-edit-label {
      position: absolute;
      bottom: -2px;
      right: -2px;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      background: var(--deep);
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border: 2px solid var(--white);
      font-size: 0.85rem;
    }

    .stat-mini {
      text-align: center;
      padding: 0.9rem;
      border-radius: var(--radius-sm);
      background: var(--paper);
    }

    .stat-mini .val {
      font-family: var(--font-display);
      font-size: 1.4rem;
      color: var(--deep);
      font-weight: 600;
    }

    .stat-mini .lab {
      font-size: 0.76rem;
      color: var(--ink-soft);
    }

    .nav-tabs-brand {
      border-bottom: 1.5px solid #E1E7EA;
    }

    .nav-tabs-brand .nav-link {
      border: none;
      color: var(--ink-soft);
      font-weight: 600;
      padding: 0.7rem 1.1rem;
    }

    .nav-tabs-brand .nav-link.active {
      color: var(--deep);
      border-bottom: 2.5px solid var(--deep);
      background: transparent;
    }
  </style>
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
        <a class="nav-link" href="my_items.php"><i class="bi bi-list-check"></i> My Items</a> <a class="nav-link"
          href="register_device.php"><i class="bi bi-cpu"></i> My Devices</a>
        <a class="nav-link" href="notifications.php"><i class="bi bi-bell"></i> Notifications</a>
        <a class="nav-link active" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
        <hr style="border-color:rgba(255,255,255,0.15)">
        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </nav>
    </div>

    <!-- ===== Main content ===== -->
    <div class="flex-grow-1">
      <div class="dash-topbar">
        <div>
          <h5 class="mb-0" style="color:var(--deep)">My Profile</h5>
          <div class="text-secondary small">View and update your account information</div>
        </div>
      </div>

      <div class="container-fluid p-4">
        <div class="row g-4 justify-content-center">

          <!-- Left: profile summary -->
          <div class="col-lg-4">
            <div class="feature-card text-center">
              <?php if ($pic_error): ?>
                <div class="alert alert-danger py-2 small text-start"><?php echo htmlspecialchars($pic_error); ?></div>
              <?php endif; ?>
              <form action="process/profile_picture_upload.php" method="POST" enctype="multipart/form-data"
                id="avatarForm">
                <div class="avatar-edit-btn mx-auto mb-3">
                  <div class="avatar-circle">
                    <?php if (!empty($user['profile_pic']) && file_exists(__DIR__ . '/' . $user['profile_pic'])): ?>
                      <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile picture">
                    <?php else: ?>
                      <?php echo htmlspecialchars($initials ?: 'U'); ?>
                    <?php endif; ?>
                  </div>
                  <label class="avatar-edit-label" for="profilePicInput" title="Change photo">
                    <i class="bi bi-camera-fill"></i>
                  </label>
                  <input type="file" name="profile_pic" id="profilePicInput" accept="image/png,image/jpeg,image/jpg"
                    hidden onchange="document.getElementById('avatarForm').submit()">
                </div>
              </form>
              <h5 class="mb-0" style="color:var(--deep)"><?php echo htmlspecialchars($user['full_name']); ?></h5>
              <div class="text-secondary small mb-3">
                <?php echo htmlspecialchars($user['department'] ?: '—'); ?><?php echo $user['enrollment_no'] ? ' · ' . htmlspecialchars($user['enrollment_no']) : ''; ?>
              </div>
              <span
                class="badge-status badge-collected"><?php echo $user['role'] === 'admin' ? 'Admin Account' : 'Student Account'; ?></span>

              <div class="row g-2 mt-4">
                <div class="col-4">
                  <div class="stat-mini">
                    <div class="val"><?php echo (int) ($stats['reported'] ?? 0); ?></div>
                    <div class="lab">Reported</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="stat-mini">
                    <div class="val"><?php echo (int) ($stats['found'] ?? 0); ?></div>
                    <div class="lab">Found</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="stat-mini">
                    <div class="val"><?php echo (int) ($stats['qr_tags'] ?? 0); ?></div>
                    <div class="lab">QR Tags</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: editable info -->
          <div class="col-lg-7">
            <div class="feature-card">

              <ul class="nav nav-tabs-brand mb-4" role="tablist">
                <li class="nav-item">
                  <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-info" type="button">Personal
                    Info</button>
                </li>
                <li class="nav-item">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-password" type="button">Change
                    Password</button>
                </li>
              </ul>

              <div class="tab-content">

                <!-- Personal info -->
                <div class="tab-pane fade show active" id="tab-info">
                  <?php if ($info_error): ?>
                    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($info_error); ?></div>
                  <?php endif; ?>
                  <?php if ($info_success): ?>
                    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($info_success); ?></div>
                  <?php endif; ?>
                  <form action="process/profile_update.php" method="POST" novalidate>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control"
                          value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Enrollment No.</label>
                        <input type="text" name="enrollment_no" class="form-control"
                          value="<?php echo htmlspecialchars($user['enrollment_no'] ?? ''); ?>">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>"
                          disabled>
                        <div class="form-text">Contact admin to change your email.</div>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control"
                          value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department" class="form-control">
                          <?php foreach (['Computer Engineering', 'Information Technology', 'Electronics', 'Mechanical', 'Civil', 'MBA', 'Administration'] as $d): ?>
                            <option <?php echo $user['department'] === $d ? 'selected' : ''; ?>><?php echo $d; ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
                      </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                      <button type="reset" class="btn btn-outline-brand">Reset</button>
                      <button type="submit" class="btn btn-brand px-4">Save Changes</button>
                    </div>
                  </form>
                </div>

                <!-- Change password -->
                <div class="tab-pane fade" id="tab-password">
                  <?php if ($pwd_error): ?>
                    <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($pwd_error); ?></div>
                  <?php endif; ?>
                  <?php if ($pwd_success): ?>
                    <div class="alert alert-success py-2 small"><?php echo htmlspecialchars($pwd_success); ?></div>
                  <?php endif; ?>
                  <form action="process/profile_password.php" method="POST" novalidate>
                    <div class="mb-3">
                      <label class="form-label">Current Password</label>
                      <input type="password" name="current_password" class="form-control" placeholder="••••••••"
                        required>
                    </div>
                    <div class="row g-3">
                      <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••••" required>
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" placeholder="••••••••"
                          required>
                      </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                      <button type="submit" class="btn btn-brand px-4">Update Password</button>
                    </div>
                  </form>
                </div>

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