<?php
/**
 * Public QR Scan Landing Page
 * Smart Belonging System for Campus
 *
 * No login required — this is what opens when someone scans a
 * student's smart-gadget QR tag. It reflects the item's *live*
 * status from the database (not a static sticker) and, if the item
 * hasn't been recovered yet, lets the finder notify the owner
 * without ever seeing their raw email or phone number.
 */
require_once __DIR__ . '/includes/db.php';

$tag = trim($_GET['tag'] ?? '');
$item = null;

if ($tag !== '') {
  $stmt = $pdo->prepare("SELECT items.*, users.full_name AS owner_name
        FROM items JOIN users ON items.user_id = users.id
        WHERE items.qr_code = ?");
  $stmt->execute([$tag]);
  $item = $stmt->fetch();
}

function mask_name($fullName)
{
  $parts = preg_split('/\s+/', trim($fullName));
  $masked = [];
  foreach ($parts as $i => $part) {
    $len = strlen($part);
    if ($i === 0) {
      $masked[] = $len > 2
        ? $part[0] . str_repeat('*', $len - 2) . $part[$len - 1]
        : $part[0] . '*';
    } else {
      $masked[] = $part[0] . '.';
    }
  }
  return implode(' ', $masked);
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

    <?php if (!$item): ?>
      <!-- Tag not found in the system -->
      <div class="scan-icon missing"><i class="bi bi-question-circle"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Tag not recognized</h5>
      <p class="text-secondary small mb-0">This QR tag isn't registered in Smart Belonging System, or the link is
        incorrect.</p>

    <?php elseif ($item['status'] === 'collected'): ?>
      <!-- Already recovered -->
      <div class="scan-icon done"><i class="bi bi-check-circle"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Already recovered</h5>
      <p class="text-secondary small mb-0">Good news — this item has already been reunited with its owner. Thanks for
        checking!</p>

    <?php elseif ($reported): ?>
      <!-- Finder just submitted the form -->
      <div class="scan-icon done"><i class="bi bi-check-circle"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Thank you!</h5>
      <p class="text-secondary small mb-0">The owner has been notified that their
        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong> may have been found. They'll reach out
        through the campus admin desk to arrange collection.</p>

    <?php else: ?>
      <!-- Live item, not yet recovered -- show details + found form -->
      <div class="scan-icon ok"><i class="bi bi-qr-code-scan"></i></div>
      <h5 class="mb-1" style="color:var(--deep)">You scanned a Smart Belonging tag</h5>
      <p class="text-secondary small mb-2">This gadget belongs to a student on campus.</p>

      <div class="mb-3">
        <div class="fw-semibold" style="color:var(--ink);font-size:1.05rem">
          <?php echo htmlspecialchars($item['item_name']); ?></div>
        <div class="text-secondary small"><?php echo htmlspecialchars($item['category']); ?></div>
        <span class="owner-pill"><i class="bi bi-person me-1"></i>Owner:
          <?php echo htmlspecialchars(mask_name($item['owner_name'])); ?></span>
      </div>

      <hr style="border-color:#EEF1EC">

      <h6 class="mb-2" style="color:var(--deep)">Found this item?</h6>
      <p class="text-secondary small mb-3">Let the owner know without sharing your contact details publicly — just tell us
        where you found it.</p>

      <form action="process/scan_report.php" method="POST" class="text-start">
        <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tag); ?>">
        <div class="mb-2">
          <label class="form-label small">Where did you find it?</label>
          <input type="text" name="found_location" class="form-control form-control-sm"
            placeholder="e.g. Library, 2nd floor" required>
        </div>
        <div class="mb-2">
          <label class="form-label small">Your contact (optional)</label>
          <input type="text" name="finder_contact" class="form-control form-control-sm"
            placeholder="Phone or email so the owner can reach you">
        </div>
        <div class="mb-3">
          <label class="form-label small">Message (optional)</label>
          <textarea name="message" class="form-control form-control-sm" rows="2"
            placeholder="Any other details..."></textarea>
        </div>
        <button type="submit" class="btn btn-brand w-100"><i class="bi bi-send me-1"></i> Notify the Owner</button>
      </form>
    <?php endif; ?>

  </div>

</body>

</html>