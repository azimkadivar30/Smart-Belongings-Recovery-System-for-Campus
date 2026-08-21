<?php
/**
 * Public QR Scan Landing Page -- Devices
 * Smart Belonging System for Campus -- Feature 4
 *
 * The devices-table counterpart of scan.php. No login required --
 * this is what opens when someone scans a registered gadget's QR
 * tag. It reflects the device's *live* status from the database and
 * lets the finder notify the owner without ever seeing their raw
 * contact details, same privacy model as the items scan flow.
 */
require_once __DIR__ . '/includes/db.php';

$token = trim($_GET['token'] ?? '');
$device = null;

// Deliberately NOT joining/selecting anything from `users` here -- the
// public scan page must never expose the owner's name, email, or any
// other personal detail (spec: Feature 6, QR Scan Workflow). Only the
// device's own fields are needed to render the page.
if ($token !== '') {
  $stmt = $pdo->prepare("SELECT * FROM devices WHERE qr_token = ?");
  $stmt->execute([$token]);
  $device = $stmt->fetch();
}

$reported = isset($_GET['reported']) && $_GET['reported'] === '1';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scan Result | Smart Belonging System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(160deg, var(--light), var(--paper));
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
    }

    .scan-card {
      background: var(--white);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-card);
      padding: 2.2rem 1.8rem;
      max-width: 460px;
      width: 100%;
      text-align: center;
    }

    .scan-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.7rem;
      margin: 0 auto 1rem;
    }

    .scan-icon.ok {
      background: rgba(122, 170, 206, 0.18);
      color: var(--deep);
    }

    .scan-icon.done {
      background: rgba(76, 175, 131, 0.15);
      color: #2E8A5E;
    }

    .scan-icon.missing {
      background: rgba(214, 84, 84, 0.14);
      color: #C23A3A;
    }

    .owner-pill {
      display: inline-block;
      background: var(--paper);
      border-radius: 20px;
      padding: 0.35rem 0.9rem;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--deep);
      margin-top: 0.5rem;
    }
  </style>
</head>

<body>

  <div class="scan-card">
    <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
      <span class="brand-tag"><i class="bi bi-qr-code"></i></span>
      <span class="fw-semibold" style="color:var(--deep)">Smart Belonging System</span>
    </div>

    <?php if (!$device): ?>
      <!-- Tag not found in the system -->
      <div class="scan-icon missing"><i class="bi bi-question-circle"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Tag not recognized</h5>
      <p class="text-secondary small mb-0">This QR tag isn't registered in Smart Belonging System, or the link is
        incorrect.</p>

    <?php elseif ($device['device_status'] === 'recovered'): ?>
      <!-- Already recovered -->
      <div class="scan-icon done"><i class="bi bi-check-circle"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Already recovered</h5>
      <p class="text-secondary small mb-0">Good news — this gadget has already been reunited with its owner. Thanks for
        checking!</p>

    <?php elseif ($reported): ?>
      <!-- Finder just submitted the form -->
      <div class="scan-icon done"><i class="bi bi-check-circle"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Thank you!</h5>
      <p class="text-secondary small mb-0">Your report has been sent to the campus admin team for verification. They'll
        arrange collection once confirmed.</p>

    <?php else: ?>
      <!-- Live device, not yet recovered -- show device details only, no owner info, + found form -->
      <div class="scan-icon ok"><i class="bi bi-qr-code-scan"></i></div>
      <h5 class="mb-1" style="color:var(--deep)">You scanned a Smart Belonging tag</h5>
      <p class="text-secondary small mb-2">This device is registered with Smart Belonging System.</p>

      <div class="mb-3 text-start">
        <div class="fw-semibold" style="color:var(--ink);font-size:1.05rem">
          <?php echo htmlspecialchars($device['device_name']); ?></div>
        <div class="text-secondary small">
          Brand: <?php echo htmlspecialchars($device['brand'] ?: '—'); ?><br>
          Model: <?php echo htmlspecialchars($device['model'] ?: '—'); ?>
        </div>
        <span class="owner-pill"><i class="bi bi-patch-check me-1"></i>Registration confirmed</span>
      </div>

      <hr style="border-color:#EEF1EC">

      <h6 class="mb-2" style="color:var(--deep)">Report Found</h6>
      <p class="text-secondary small mb-3">No owner details are shared with you. Fill this in and the admin team will take
        it from here.</p>

      <form action="process/device_scan_report.php" method="POST" class="text-start">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="mb-2">
          <label class="form-label small">Your Name</label>
          <input type="text" name="finder_name" class="form-control form-control-sm" placeholder="Full name" required>
        </div>
        <div class="mb-2">
          <label class="form-label small">Contact Number</label>
          <input type="tel" name="finder_contact" class="form-control form-control-sm" placeholder="Phone number"
            required>
        </div>
        <div class="mb-2">
          <label class="form-label small">Email</label>
          <input type="email" name="finder_email" class="form-control form-control-sm" placeholder="you@example.com"
            required>
        </div>
        <div class="mb-2">
          <label class="form-label small">Found Location</label>
          <input type="text" name="found_location" class="form-control form-control-sm"
            placeholder="e.g. Library, 2nd floor" required>
        </div>
        <div class="mb-3">
          <label class="form-label small">Message (optional)</label>
          <textarea name="message" class="form-control form-control-sm" rows="2"
            placeholder="Any other details..."></textarea>
        </div>
        <button type="submit" class="btn btn-brand w-100"><i class="bi bi-send me-1"></i> Submit Report to Admin</button>
      </form>
    <?php endif; ?>

  </div>

</body>

</html>