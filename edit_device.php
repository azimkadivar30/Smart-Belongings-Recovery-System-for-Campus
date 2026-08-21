<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];
$device_id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ? AND user_id = ?");
$stmt->execute([$device_id, $user_id]);
$device = $stmt->fetch();

if (!$device) {
  header("Location: register_device.php");
  exit();
}

$error = $_SESSION['edit_device_error'] ?? '';
unset($_SESSION['edit_device_error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Device | Smart Belonging System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    .upload-drop {
      border: 2px dashed #D7E1E6;
      border-radius: var(--radius-md);
      padding: 1.6rem 1.2rem;
      text-align: center;
      background: var(--paper);
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .upload-drop:hover {
      border-color: var(--mid);
      background: rgba(122, 170, 206, 0.06);
    }

    .upload-drop i {
      font-size: 1.6rem;
      color: var(--mid);
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
          <h5 class="mb-0" style="color:var(--deep)">Edit Device</h5>
          <div class="text-secondary small"><a href="register_device.php" class="text-secondary"><i
                class="bi bi-arrow-left me-1"></i>Back to My Devices</a></div>
        </div>
      </div>

      <div class="container-fluid p-4">
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="feature-card">

              <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
              <?php endif; ?>

              <form action="process/edit_device_process.php" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">

                <div class="mb-3">
                  <label class="form-label">Device Name</label>
                  <input type="text" name="device_name" class="form-control"
                    value="<?php echo htmlspecialchars($device['device_name']); ?>" required>
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control"
                      value="<?php echo htmlspecialchars($device['brand']); ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Model <span class="text-secondary fw-normal">(optional)</span></label>
                    <input type="text" name="model" class="form-control"
                      value="<?php echo htmlspecialchars($device['model'] ?? ''); ?>">
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Serial Number <span
                        class="text-secondary fw-normal">(optional)</span></label>
                    <input type="text" name="serial_number" class="form-control"
                      value="<?php echo htmlspecialchars($device['serial_number'] ?? ''); ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Colour</label>
                    <input type="text" name="colour" class="form-control"
                      value="<?php echo htmlspecialchars($device['colour']); ?>" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Description <span
                        class="text-secondary fw-normal">(optional)</span></label>
                    <textarea name="description" class="form-control"
                      rows="3"><?php echo htmlspecialchars($device['description'] ?? ''); ?></textarea>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Device Photo <span class="text-secondary fw-normal">(optional -- leave
                        blank to keep the current one)</span></label>
                    <?php if (!empty($device['image'])): ?>
                      <div class="mb-2"><img src="<?php echo htmlspecialchars($device['image']); ?>"
                          style="max-height:110px;border-radius:10px;" alt=""></div>
                    <?php endif; ?>
                    <label class="upload-drop d-block" id="uploadDropLabel">
                      <input type="file" name="device_image" id="deviceImageInput"
                        accept="image/png,image/jpeg,image/jpg" hidden>
                      <img id="imagePreview" src="" alt=""
                        style="display:none;max-height:110px;border-radius:10px;margin-bottom:0.5rem;">
                      <div id="uploadPromptText">
                        <i class="bi bi-cloud-arrow-up d-block mb-2"></i>
                        <span class="fw-semibold" style="color:var(--deep)">Click to replace photo</span>
                        <div class="text-secondary small">PNG or JPG, up to 5MB</div>
                      </div>
                      <div id="uploadFileName" class="small fw-semibold mt-1" style="color:var(--deep);display:none;">
                      </div>
                    </label>
                    <div id="uploadError" class="text-danger small mt-1" style="display:none;"></div>
                  </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                  <a href="register_device.php" class="btn btn-outline-brand">Cancel</a>
                  <button type="submit" class="btn btn-brand px-4">Save Changes</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const deviceImageInput = document.getElementById('deviceImageInput');
    const imagePreview = document.getElementById('imagePreview');
    const uploadPromptText = document.getElementById('uploadPromptText');
    const uploadFileName = document.getElementById('uploadFileName');
    const uploadError = document.getElementById('uploadError');

    deviceImageInput?.addEventListener('change', () => {
      const file = deviceImageInput.files[0];
      uploadError.style.display = 'none';
      if (!file) return;

      const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
      if (!allowedTypes.includes(file.type)) {
        uploadError.textContent = 'Please choose a PNG or JPG image.';
        uploadError.style.display = 'block';
        deviceImageInput.value = '';
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        uploadError.textContent = 'Image must be 5MB or smaller.';
        uploadError.style.display = 'block';
        deviceImageInput.value = '';
        return;
      }

      const reader = new FileReader();
      reader.onload = (e) => {
        imagePreview.src = e.target.result;
        imagePreview.style.display = 'inline-block';
        uploadPromptText.style.display = 'none';
        uploadFileName.textContent = file.name;
        uploadFileName.style.display = 'block';
      };
      reader.readAsDataURL(file);
    });
  </script>
</body>

</html>