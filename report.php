<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$error = $_SESSION['report_error'] ?? '';
unset($_SESSION['report_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Report Lost Item | Smart Belonging System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
  .upload-drop {
    border: 2px dashed #D7E1E6;
    border-radius: var(--radius-md);
    padding: 2rem 1.2rem;
    text-align: center;
    background: var(--paper);
    cursor: pointer;
    transition: all 0.2s ease;
  }
  .upload-drop:hover { border-color: var(--mid); background: rgba(122,170,206,0.06); }
  .upload-drop i { font-size: 1.8rem; color: var(--mid); }
  .progress-track {
    display: flex;
    gap: 0.4rem;
    margin-bottom: 2rem;
  }
  .progress-track span {
    height: 5px;
    flex: 1;
    border-radius: 10px;
    background: #E5EAE0;
  }
  .progress-track span.done { background: var(--mid); }
  .type-toggle {
    display: flex;
    background: var(--paper);
    border-radius: var(--radius-sm);
    padding: 4px;
    max-width: 320px;
  }
  .type-toggle label {
    flex: 1;
    text-align: center;
    padding: 0.55rem 0;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.88rem;
    color: var(--ink-soft);
    cursor: pointer;
  }
  .type-toggle input { display: none; }
  .type-toggle input:checked + label {
    background: var(--deep);
    color: var(--white);
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
      <a class="nav-link active" href="report.php"><i class="bi bi-file-earmark-plus"></i> Report Lost Item</a>
      <a class="nav-link" href="my_items.php"><i class="bi bi-list-check"></i> My Items</a>      <a class="nav-link" href="register_device.php"><i class="bi bi-cpu"></i> My Devices</a>
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
        <h5 class="mb-0" style="color:var(--deep)">Report a Lost Item</h5>
        <div class="text-secondary small">Give as much detail as you can — it helps the admin match it faster</div>
      </div>
      <a href="my_items.php" class="btn btn-outline-brand"><i class="bi bi-list-check me-1"></i> My Items</a>
    </div>

    <div class="container-fluid p-4">
      <div class="row justify-content-center">
        <div class="col-lg-9">

          <div class="feature-card">

            <!-- static progress indicator, purely visual -->
            <div class="progress-track">
              <span class="done"></span><span class="done"></span><span></span>
            </div>

            <?php if ($error): ?>
              <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="process/report_process.php" method="POST" enctype="multipart/form-data" novalidate>

              <!-- Report type -->
              <div class="mb-4">
                <label class="form-label d-block mb-2">What are you reporting?</label>
                <div class="type-toggle">
                  <input type="radio" name="report_type" id="typeLost" value="lost" checked>
                  <label for="typeLost"><i class="bi bi-search me-1"></i>Lost Item</label>
                  <input type="radio" name="report_type" id="typeFound" value="found">
                  <label for="typeFound"><i class="bi bi-hand-thumbs-up me-1"></i>Found Item</label>
                </div>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Item Name</label>
                  <input type="text" name="item_name" class="form-control" placeholder="e.g. Blue Water Bottle" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Category</label>
                  <select name="category" class="form-control" required>
                    <option value="">Select category</option>
                    <option>Electronics (Phone / Laptop)</option>
                    <option>ID Card / Documents</option>
                    <option>Bag / Wallet</option>
                    <option>Bottle / Lunchbox</option>
                    <option>Stationery / Books</option>
                    <option>Clothing / Accessories</option>
                    <option>Other</option>
                  </select>
                </div>

                <div class="col-12">
                  <label class="form-label">Description</label>
                  <textarea name="description" class="form-control" rows="3" placeholder="Color, brand, any identifying marks or stickers..." required></textarea>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Last Seen Location</label>
                  <input type="text" name="location" class="form-control" placeholder="e.g. Central Library, 2nd Floor" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Date</label>
                  <input type="date" name="item_date" class="form-control" required>
                </div>

                <div class="col-12">
                  <label class="form-label">Upload a Photo <span class="text-secondary fw-normal">(optional)</span></label>
                  <label class="upload-drop d-block" id="uploadDropLabel">
                    <input type="file" name="item_image" id="itemImageInput" accept="image/png,image/jpeg,image/jpg" hidden>
                    <img id="imagePreview" src="" alt="" style="display:none;max-height:120px;border-radius:10px;margin-bottom:0.5rem;">
                    <div id="uploadPromptText">
                      <i class="bi bi-cloud-arrow-up d-block mb-2"></i>
                      <span class="fw-semibold" style="color:var(--deep)">Click to upload</span>
                      <div class="text-secondary small">PNG or JPG, up to 5MB</div>
                    </div>
                    <div id="uploadFileName" class="small fw-semibold mt-1" style="color:var(--deep);display:none;"></div>
                  </label>
                  <div id="uploadError" class="text-danger small mt-1" style="display:none;"></div>
                </div>

              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="dashboard.php" class="btn btn-outline-brand">Cancel</a>
                <button type="submit" class="btn btn-brand px-4">Submit Report</button>
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
  const itemImageInput = document.getElementById('itemImageInput');
  const imagePreview = document.getElementById('imagePreview');
  const uploadPromptText = document.getElementById('uploadPromptText');
  const uploadFileName = document.getElementById('uploadFileName');
  const uploadError = document.getElementById('uploadError');

  itemImageInput?.addEventListener('change', () => {
    const file = itemImageInput.files[0];
    uploadError.style.display = 'none';

    if (!file) return;

    const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg'];
    if (!allowedTypes.includes(file.type)) {
      uploadError.textContent = 'Please choose a PNG or JPG image.';
      uploadError.style.display = 'block';
      itemImageInput.value = '';
      return;
    }
    if (file.size > 5 * 1024 * 1024) {
      uploadError.textContent = 'Image must be 5MB or smaller.';
      uploadError.style.display = 'block';
      itemImageInput.value = '';
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
