<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM devices WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$devices = $stmt->fetchAll();

$device_error = $_SESSION['device_error'] ?? '';
$device_success = $_SESSION['device_success'] ?? '';
unset($_SESSION['device_error'], $_SESSION['device_success']);

$highlight_id = (int) ($_GET['device_id'] ?? 0);

function device_icon($brand, $model)
{
  $t = strtolower($brand . ' ' . $model);
  if (strpos($t, 'laptop') !== false || strpos($t, 'macbook') !== false || strpos($t, 'dell') !== false || strpos($t, 'hp') !== false || strpos($t, 'lenovo') !== false)
    return 'bi-laptop';
  if (strpos($t, 'phone') !== false || strpos($t, 'iphone') !== false || strpos($t, 'samsung') !== false || strpos($t, 'redmi') !== false || strpos($t, 'oneplus') !== false)
    return 'bi-phone';
  if (strpos($t, 'watch') !== false)
    return 'bi-smartwatch';
  if (strpos($t, 'buds') !== false || strpos($t, 'headphone') !== false || strpos($t, 'earphone') !== false)
    return 'bi-earbuds';
  if (strpos($t, 'tablet') !== false || strpos($t, 'ipad') !== false)
    return 'bi-tablet';
  return 'bi-cpu';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Devices | Smart Belonging System</title>
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

    .device-thumb {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      object-fit: cover;
      flex-shrink: 0;
    }

    .device-thumb-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      background: linear-gradient(145deg, var(--light), var(--mid));
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--deep);
      font-size: 1.3rem;
      flex-shrink: 0;
    }

    .device-card {
      margin-bottom: 1rem;
    }

    .device-card.highlight {
      outline: 2px solid var(--mid);
    }

    .badge-active {
      background: rgba(122, 170, 206, 0.18);
      color: var(--deep);
    }

    .badge-lost {
      background: rgba(214, 84, 84, 0.14);
      color: #C23A3A;
    }

    .badge-recovered {
      background: rgba(76, 175, 131, 0.15);
      color: #2E8A5E;
    }

    .qr-mini-box {
      background: var(--white);
      border: 1.5px dashed #D7E1E6;
      border-radius: var(--radius-sm);
      padding: 0.8rem;
      text-align: center;
    }

    .qr-mini-box img {
      max-width: 96px;
      border-radius: 8px;
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
          <h5 class="mb-0" style="color:var(--deep)">My Devices</h5>
          <div class="text-secondary small">Register your gadgets and give each one a smart QR ID tag</div>
        </div>
      </div>

      <div class="container-fluid p-4">
        <?php if ($device_error): ?>
          <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($device_error); ?></div>
        <?php endif; ?>
        <?php if ($device_success): ?>
          <div class="alert alert-success py-2 small"><i
              class="bi bi-check-circle me-1"></i><?php echo htmlspecialchars($device_success); ?></div>
        <?php endif; ?>

        <div class="row g-4">

          <!-- Left: registration form -->
          <div class="col-lg-5">
            <div class="feature-card">
              <h6 class="mb-3" style="color:var(--deep)"><i class="bi bi-cpu me-1"></i> Register a Gadget</h6>

              <form action="process/register_device_process.php" method="POST" enctype="multipart/form-data" novalidate>
                <div class="mb-3">
                  <label class="form-label">Device Name</label>
                  <input type="text" name="device_name" class="form-control" placeholder="e.g. My College Laptop"
                    required>
                </div>

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Brand</label>
                    <input type="text" name="brand" class="form-control" placeholder="e.g. Dell, Apple" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Model <span class="text-secondary fw-normal">(optional)</span></label>
                    <input type="text" name="model" class="form-control" placeholder="e.g. Inspiron 15">
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Serial Number <span
                        class="text-secondary fw-normal">(optional)</span></label>
                    <input type="text" name="serial_number" class="form-control" placeholder="e.g. SN123456789">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Colour</label>
                    <input type="text" name="colour" class="form-control" placeholder="e.g. Space Grey" required>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Description <span
                        class="text-secondary fw-normal">(optional)</span></label>
                    <textarea name="description" class="form-control" rows="3"
                      placeholder="Any identifying marks, stickers, accessories..."></textarea>
                  </div>

                  <div class="col-12">
                    <label class="form-label">Device Photo <span
                        class="text-secondary fw-normal">(optional)</span></label>
                    <label class="upload-drop d-block" id="uploadDropLabel">
                      <input type="file" name="device_image" id="deviceImageInput"
                        accept="image/png,image/jpeg,image/jpg" hidden>
                      <img id="imagePreview" src="" alt=""
                        style="display:none;max-height:110px;border-radius:10px;margin-bottom:0.5rem;">
                      <div id="uploadPromptText">
                        <i class="bi bi-cloud-arrow-up d-block mb-2"></i>
                        <span class="fw-semibold" style="color:var(--deep)">Click to upload</span>
                        <div class="text-secondary small">PNG or JPG, up to 5MB</div>
                      </div>
                      <div id="uploadFileName" class="small fw-semibold mt-1" style="color:var(--deep);display:none;">
                      </div>
                    </label>
                    <div id="uploadError" class="text-danger small mt-1" style="display:none;"></div>
                  </div>
                </div>

                <button type="submit" class="btn btn-brand w-100 py-2 mt-4"><i class="bi bi-qr-code-scan me-1"></i>
                  Register &amp; Generate QR Tag</button>
              </form>
            </div>
          </div>

          <!-- Right: registered devices -->
          <div class="col-lg-7">
            <?php if (empty($devices)): ?>
              <div class="feature-card text-center py-5">
                <i class="bi bi-cpu" style="font-size:2.2rem;color:var(--mid)"></i>
                <p class="text-secondary mt-2 mb-0">You haven't registered any devices yet. Fill in the form to add your
                  first one.</p>
              </div>
            <?php else: ?>
              <?php foreach ($devices as $d):
                $isHighlighted = $highlight_id && (int) $d['id'] === $highlight_id;
                ?>
                <div class="feature-card device-card <?php echo $isHighlighted ? 'highlight' : ''; ?>">
                  <div class="d-flex gap-3">
                    <?php if (!empty($d['image'])): ?>
                      <img src="<?php echo htmlspecialchars($d['image']); ?>" class="device-thumb" alt="">
                    <?php else: ?>
                      <div class="device-thumb-icon"><i class="bi <?php echo device_icon($d['brand'], $d['model']); ?>"></i>
                      </div>
                    <?php endif; ?>

                    <div class="flex-grow-1">
                      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                          <div class="fw-semibold" style="color:var(--ink)">
                            <?php echo htmlspecialchars($d['device_name']); ?></div>
                          <div class="text-secondary small">
                            <?php echo htmlspecialchars($d['brand']); ?>    <?php echo $d['model'] ? ' · ' . htmlspecialchars($d['model']) : ''; ?>
                            &nbsp;·&nbsp; <?php echo htmlspecialchars($d['colour']); ?>
                          </div>
                        </div>
                        <span
                          class="badge-status badge-<?php echo htmlspecialchars($d['device_status']); ?>"><?php echo ucfirst($d['device_status']); ?></span>
                      </div>

                      <?php if (!empty($d['serial_number'])): ?>
                        <div class="text-secondary small mt-1"><i class="bi bi-upc-scan"></i> S/N:
                          <?php echo htmlspecialchars($d['serial_number']); ?></div>
                      <?php endif; ?>
                      <?php if (!empty($d['description'])): ?>
                        <div class="text-secondary small mt-1"><?php echo nl2br(htmlspecialchars($d['description'])); ?></div>
                      <?php endif; ?>

                      <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                        <span class="text-secondary small"><i class="bi bi-tag"></i> Tag ID:
                          <?php echo htmlspecialchars($d['qr_token'] ?? '—'); ?></span>
                      </div>

                      <div class="d-flex align-items-center gap-2 mt-3 flex-wrap">
                        <a href="edit_device.php?id=<?php echo $d['id']; ?>" class="btn btn-outline-brand btn-sm"><i
                            class="bi bi-pencil"></i> Edit</a>
                        <?php if ($d['device_status'] !== 'lost'): ?>
                          <a href="report_device_lost.php?device_id=<?php echo $d['id']; ?>"
                            class="btn btn-outline-brand btn-sm" style="color:#C23A3A;border-color:#e9c3c3"><i
                              class="bi bi-exclamation-triangle"></i> Report as Lost</a>
                        <?php endif; ?>
                        <a href="process/delete_device_process.php?id=<?php echo $d['id']; ?>"
                          class="btn btn-outline-brand btn-sm" style="color:#C23A3A;border-color:#e9c3c3"
                          onclick="return confirm('Delete this device? This cannot be undone.');"><i
                            class="bi bi-trash"></i> Delete</a>
                      </div>
                    </div>

                    <div class="qr-mini-box" style="flex-shrink:0;">
                      <?php if (!empty($d['qr_image'])): ?>
                        <img src="<?php echo htmlspecialchars($d['qr_image']); ?>" alt="QR tag">
                        <a href="<?php echo htmlspecialchars($d['qr_image']); ?>"
                          download="qr-<?php echo htmlspecialchars($d['qr_token']); ?>.png" class="d-block small mt-1"
                          style="color:var(--deep)"><i class="bi bi-download"></i> Save</a>
                      <?php elseif (!empty($d['qr_token'])): ?>
                        <div class="qr-fallback" data-token="<?php echo htmlspecialchars($d['qr_token']); ?>"
                          data-name="<?php echo htmlspecialchars($d['device_name']); ?>"></div>
                        <a href="#" class="qr-fallback-download d-block small mt-1"
                          data-token="<?php echo htmlspecialchars($d['qr_token']); ?>" style="color:var(--deep)"><i
                            class="bi bi-download"></i> Save</a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
  <script>
    // Image preview for the upload form
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

    // Fallback client-side QR rendering for any device whose server-side
    // QR image couldn't be generated (e.g. no outbound internet access) --
    // reuses the same qrcodejs library qr_generate.php already relies on.
    document.querySelectorAll('.qr-fallback').forEach((el) => {
      const token = el.dataset.token;
      const name = el.dataset.name;
      const scanUrl = window.location.origin + window.location.pathname.replace(/register_device\.php$/, '') + 'device_scan.php?token=' + encodeURIComponent(token);
      new QRCode(el, {
        text: scanUrl,
        width: 96,
        height: 96,
        colorDark: '#355872',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
      });
    });

    // Client-rendered (fallback) QR codes have no stored image file, so a
    // plain <a href download> can't work -- pull the pixels straight out of
    // the qrcodejs canvas/img instead when the user clicks "Save".
    document.querySelectorAll('.qr-fallback-download').forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const box = link.closest('.qr-mini-box');
        const el = box?.querySelector('.qr-fallback img, .qr-fallback canvas');
        if (!el) return;
        const dataUrl = el.tagName === 'CANVAS' ? el.toDataURL('image/png') : el.src;
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = 'qr-' + link.dataset.token + '.png';
        a.click();
      });
    });
  </script>
</body>

</html>