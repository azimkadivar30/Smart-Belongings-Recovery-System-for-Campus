<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../mail.php';

$device_id = (int)($_GET['id'] ?? $_POST['device_id'] ?? 0);

$stmt = $pdo->prepare("SELECT devices.*, users.full_name AS owner, users.email AS owner_email
    FROM devices JOIN users ON devices.user_id = users.id WHERE devices.id = ?");
$stmt->execute([$device_id]);
$device = $stmt->fetch();

if (!$device) {
    header("Location: manage_devices.php");
    exit();
}

$saved = false;
$otp_error = null;
$otp_sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_otp'])) {
    // --- Feature 8: admin-triggered OTP, 10-minute expiry, sent to the
    //     owner's own verified account email (never entered by the finder) ---
    $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
    $stmt->execute([$device['user_id']]);
    $email_verified = (bool)$stmt->fetchColumn();

    if (!$email_verified) {
        $otp_error = "This student's account email isn't verified yet, so a recovery code can't be sent.";
    } else {
        $otp_code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("UPDATE devices SET otp = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 10 MINUTE), otp_verified_at = NULL WHERE id = ?");
        $stmt->execute([$otp_code, $device_id]);

        $otp_email = email_device_otp($device['owner'], $device['device_name'], $otp_code);
        send_notification_email($device['owner_email'], $otp_email['subject'], $otp_email['html'], $otp_email['text']);

        $otp_sent = true;
        $stmt = $pdo->prepare("SELECT devices.*, users.full_name AS owner, users.email AS owner_email
            FROM devices JOIN users ON devices.user_id = users.id WHERE devices.id = ?");
        $stmt->execute([$device_id]);
        $device = $stmt->fetch();
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    // --- admin enters the code the student read out to them ---
    $entered = trim($_POST['otp_input'] ?? '');

    if (empty($device['otp'])) {
        $otp_error = "No recovery code has been generated for this device yet.";
    } elseif (strtotime($device['otp_expiry']) < time()) {
        $otp_error = "That code has expired. Generate a new one and try again.";
    } elseif (!hash_equals((string)$device['otp'], $entered)) {
        $otp_error = "That code doesn't match. Double-check with the student and try again.";
    } else {
        // Correct + not expired: mark Recovered, stamp verified, and wipe
        // the code so it can never be reused for a second confirmation.
        $stmt = $pdo->prepare("UPDATE devices SET device_status = 'recovered', otp_verified_at = NOW(), otp = NULL, otp_expiry = NULL WHERE id = ?");
        $stmt->execute([$device_id]);

        try {
            $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_at = NOW() WHERE device_id = ? AND type = 'found_alert' AND status = 'open'");
            $stmt->execute([$device_id]);
        } catch (PDOException $e) { /* reports table missing device_id -- non-critical, skip */ }

        $status_email = email_device_status_update($device['owner'], $device['device_name'], 'recovered');
        notify_user(
            $pdo, $device['user_id'], 'system',
            "Good news — your device \"{$device['device_name']}\" has been verified and marked as recovered.",
            null, $device['owner_email'],
            $status_email['subject'], $status_email['html'], $status_email['text']
        );

        $stmt = $pdo->prepare("SELECT devices.*, users.full_name AS owner, users.email AS owner_email
            FROM devices JOIN users ON devices.user_id = users.id WHERE devices.id = ?");
        $stmt->execute([$device_id]);
        $device = $stmt->fetch();
        $saved = true;
    }
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Manually settable statuses only -- Recovered is never set directly
    // here, it's only ever a side effect of a correct OTP (see above).
    $status     = in_array($_POST['status'] ?? '', ['active', 'lost']) ? $_POST['status'] : $device['device_status'];
    $send_email = isset($_POST['sendEmail']);

    $stmt = $pdo->prepare("UPDATE devices SET device_status = ? WHERE id = ?");
    $stmt->execute([$status, $device_id]);

    // If this update follows a "found it" QR scan report, close those out --
    // admin has now reviewed and acted on it.
    try {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_at = NOW() WHERE device_id = ? AND type = 'found_alert' AND status = 'open'");
        $stmt->execute([$device_id]);
    } catch (PDOException $e) { /* reports table missing device_id -- non-critical, skip */ }

    $statusMessages = [
        'active'    => "Your device \"{$device['device_name']}\" is now marked active.",
        'lost'      => "Your device \"{$device['device_name']}\" has been marked as lost.",
        'recovered' => "Good news — your device \"{$device['device_name']}\" has been marked as recovered.",
    ];

    $status_email = email_device_status_update($device['owner'], $device['device_name'], $status);
    notify_user(
        $pdo,
        $device['user_id'],
        'system',
        $statusMessages[$status],
        null,
        $send_email ? $device['owner_email'] : null,
        $status_email['subject'],
        $status_email['html'],
        $status_email['text']
    );

    $stmt = $pdo->prepare("SELECT devices.*, users.full_name AS owner, users.email AS owner_email
        FROM devices JOIN users ON devices.user_id = users.id WHERE devices.id = ?");
    $stmt->execute([$device_id]);
    $device = $stmt->fetch();
    $saved = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Device Status | Admin | Smart Belonging System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
  .status-option {
    border: 1.5px solid #E1E7EA;
    border-radius: var(--radius-sm);
    padding: 0.9rem 1rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.7rem;
    font-weight: 600;
    font-size: 0.9rem;
    color: var(--ink-soft);
  }
  .status-option input { display: none; }
  .status-option i { font-size: 1.1rem; }
  .status-option.active i { color: var(--deep); }
  .status-option.lost i { color: #C23A3A; }
  .status-option.recovered i { color: #2E8A5E; }
  .status-option:has(input:checked) {
    border-color: var(--deep);
    background: rgba(53,88,114,0.05);
    color: var(--deep);
  }
  .info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.7rem 0;
    border-bottom: 1px solid #EEF1EC;
    font-size: 0.9rem;
  }
  .info-row:last-child { border-bottom: none; }
  .info-row .label { color: var(--ink-soft); font-weight: 500; }
  .info-row .value { color: var(--ink); font-weight: 600; text-align: right; }
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
        <h5 class="mb-0" style="color:var(--deep)">Update Device Status</h5>
        <div class="text-secondary small"><a href="manage_devices.php" class="text-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Manage Devices</a></div>
      </div>
    </div>

    <div class="container-fluid p-4">
      <div class="row g-4 justify-content-center">

        <!-- Left: device summary -->
        <div class="col-lg-4">
          <div class="feature-card">
            <h6 class="mb-3" style="color:var(--deep)">Device Summary</h6>
            <div class="info-row"><span class="label">Device</span><span class="value"><?php echo htmlspecialchars($device['device_name']); ?></span></div>
            <div class="info-row"><span class="label">Owner</span><span class="value"><?php echo htmlspecialchars($device['owner']); ?></span></div>
            <div class="info-row"><span class="label">Brand / Model</span><span class="value"><?php echo htmlspecialchars($device['brand']); ?><?php echo $device['model'] ? ' ' . htmlspecialchars($device['model']) : ''; ?></span></div>
            <div class="info-row"><span class="label">Serial No.</span><span class="value"><?php echo htmlspecialchars($device['serial_number'] ?: '—'); ?></span></div>
            <div class="info-row"><span class="label">Tag ID</span><span class="value"><?php echo htmlspecialchars($device['qr_token'] ?: '—'); ?></span></div>
            <div class="info-row"><span class="label">Registered</span><span class="value"><?php echo date('d M Y', strtotime($device['created_at'])); ?></span></div>
            <div class="info-row"><span class="label">Current Status</span><span class="value"><span class="badge-status badge-<?php echo htmlspecialchars($device['device_status']); ?>"><?php echo ucfirst($device['device_status']); ?></span></span></div>
          </div>

          <?php if (!empty($device['image'])): ?>
          <div class="feature-card mt-4">
            <h6 class="mb-3" style="color:var(--deep)"><i class="bi bi-image me-1"></i>Photo</h6>
            <img src="../<?php echo htmlspecialchars($device['image']); ?>" class="w-100" style="border-radius:10px;max-height:180px;object-fit:cover;" alt="Device photo">
          </div>
          <?php endif; ?>
        </div>

        <!-- Right: update form -->
        <div class="col-lg-7">
          <div class="feature-card">
            <h6 class="mb-3" style="color:var(--deep)">Change Status</h6>

            <?php if ($saved): ?>
              <div class="alert alert-success py-2 small">Status updated and the student has been notified.</div>
            <?php endif; ?>

            <form action="update_device.php?id=<?php echo $device['id']; ?>" method="POST" novalidate>
              <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">

              <div class="row g-2 mb-4">
                <div class="col-md-6">
                  <label class="status-option active">
                    <input type="radio" name="status" value="active" <?php echo $device['device_status'] === 'active' ? 'checked' : ''; ?>>
                    <i class="bi bi-check-circle"></i> Active
                  </label>
                </div>
                <div class="col-md-6">
                  <label class="status-option lost">
                    <input type="radio" name="status" value="lost" <?php echo $device['device_status'] === 'lost' ? 'checked' : ''; ?>>
                    <i class="bi bi-exclamation-circle"></i> Lost
                  </label>
                </div>
              </div>
              <p class="text-secondary small mb-3"><i class="bi bi-info-circle me-1"></i>Recovered isn't set here — verify the owner with an OTP first (below).</p>

              <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="sendEmail" name="sendEmail" checked>
                <label class="form-check-label small text-secondary" for="sendEmail">
                  Notify student by email about this update
                </label>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="manage_devices.php" class="btn btn-outline-brand">Cancel</a>
                <button type="submit" class="btn btn-brand px-4">Save Update</button>
              </div>
            </form>
          </div>

          <?php if ($device['device_status'] !== 'recovered'): ?>
          <div class="feature-card mt-4">
            <h6 class="mb-1" style="color:var(--deep)"><i class="bi bi-shield-lock me-1"></i>Verify &amp; Mark Recovered</h6>
            <p class="text-secondary small mb-3">Send a 6-digit code to the owner's verified account email, then enter the code the owner reads out to you at the collection desk. Correct + not expired marks the device Recovered automatically.</p>

            <?php if ($otp_error): ?>
              <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($otp_error); ?></div>
            <?php elseif ($otp_sent): ?>
              <div class="alert alert-success py-2 small">Code sent to <?php echo htmlspecialchars($device['owner_email']); ?>. It expires in 10 minutes.</div>
            <?php endif; ?>

            <?php if (!empty($device['otp'])): ?>
              <div class="small text-secondary mb-3">
                <i class="bi bi-hourglass-split me-1"></i>
                <?php if (strtotime($device['otp_expiry']) < time()): ?>
                  <span style="color:#C23A3A">Code expired — generate a new one.</span>
                <?php else: ?>
                  Code active until <?php echo date('h:i A', strtotime($device['otp_expiry'])); ?>.
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <div class="d-flex gap-2 flex-wrap">
              <form action="update_device.php?id=<?php echo $device['id']; ?>" method="POST" class="d-flex gap-2">
                <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
                <input type="hidden" name="generate_otp" value="1">
                <button type="submit" class="btn btn-outline-brand btn-sm"><i class="bi bi-envelope-arrow-up me-1"></i><?php echo !empty($device['otp']) ? 'Resend Code' : 'Generate Code'; ?></button>
              </form>
            </div>

            <form action="update_device.php?id=<?php echo $device['id']; ?>" method="POST" class="d-flex gap-2 mt-3">
              <input type="hidden" name="device_id" value="<?php echo $device['id']; ?>">
              <input type="hidden" name="verify_otp" value="1">
              <input type="text" name="otp_input" class="form-control form-control-sm" maxlength="6" placeholder="Enter 6-digit code" style="max-width:180px" required>
              <button type="submit" class="btn btn-brand btn-sm"><i class="bi bi-check2-circle me-1"></i> Verify &amp; Mark Recovered</button>
            </form>
          </div>
          <?php else: ?>
          <div class="feature-card mt-4">
            <div class="text-success small"><i class="bi bi-patch-check-fill me-1"></i>Ownership verified with OTP on <?php echo date('d M Y, h:i A', strtotime($device['otp_verified_at'])); ?>. This device is marked Recovered.</div>
          </div>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
