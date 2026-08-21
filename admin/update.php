<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../mail.php';

$item_id = (int)($_GET['id'] ?? $_POST['item_id'] ?? 0);

$stmt = $pdo->prepare("SELECT items.*, users.full_name AS reporter, users.email AS reporter_email
    FROM items JOIN users ON items.user_id = users.id WHERE items.id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: manage_items.php");
    exit();
}

$saved = false;
$block_error = null;

// Found-item reports (a student handing in something they found) never go
// through the pending/found/not_found/collected review workflow -- that
// workflow exists to track admin's search for a LOST item. A found-item
// report is just a plain log entry, so it skips all status handling below.
if ($item['report_type'] === 'found') {
    goto render;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_code'])) {
    // --- resend a fresh verification code without changing status ---
    $newCode = (string)random_int(100000, 999999);
    $stmt = $pdo->prepare("UPDATE items SET verification_code = ?, owner_verified_at = NULL WHERE id = ?");
    $stmt->execute([$newCode, $item_id]);

    $otp_email = email_otp_code($item['reporter'], $item['item_name'], $newCode);
    send_notification_email($item['reporter_email'], $otp_email['subject'], $otp_email['html'], $otp_email['text']);

    $stmt = $pdo->prepare("SELECT items.*, users.full_name AS reporter, users.email AS reporter_email
        FROM items JOIN users ON items.user_id = users.id WHERE items.id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch();
    $saved = true;
}
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $previous_status     = $item['status'];
    $status              = in_array($_POST['status'] ?? '', ['pending', 'found', 'not_found', 'collected']) ? $_POST['status'] : $item['status'];
    $send_email          = isset($_POST['sendEmail']);

    // Ownership must be verified by the student (via the emailed code)
    // before admin can finalize a Collected status -- this is the
    // checkpoint that stops the wrong person walking off with someone
    // else's item at the collection desk.
    if ($status === 'collected' && empty($item['owner_verified_at'])) {
        $block_error = "This item can't be marked Collected yet — the student hasn't verified ownership with their emailed code.";
        $status = $item['status'];
    }

    if (!$block_error) {
        if ($status === 'found') {
            // Collection details only apply when marking an item Found --
            // that's the only status where a student needs to know where/when to pick it up.
            $collection_location    = trim($_POST['collection_location'] ?? '');
            $collection_room        = trim($_POST['collection_room'] ?? '');
            $collection_timings     = trim($_POST['collection_timings'] ?? '');
            $collection_requirement = trim($_POST['collection_requirement'] ?? '');
            $admin_notes             = trim($_POST['admin_notes'] ?? '');

            $collection_lines = [];
            if ($collection_location) $collection_lines[] = "Location: $collection_location";
            if ($collection_room) $collection_lines[] = "Room/Desk: $collection_room";
            if ($collection_timings) $collection_lines[] = "Timings: $collection_timings";
            if ($collection_requirement) $collection_lines[] = "Bring: $collection_requirement";
            if ($admin_notes) $collection_lines[] = "Note: $admin_notes";
            $collection_details = implode("\n", $collection_lines);

            // Only generate a fresh verification code (and reset any previous
            // verification) when this is a NEW transition into Found. If the
            // item was already Found and the admin is just editing collection
            // details, keep the existing code/verification -- otherwise a
            // student who already verified would silently lose that
            // verification every time the admin re-saved the form.
            if ($previous_status !== 'found') {
                $verification_code = (string)random_int(100000, 999999);
                $reset_verification = true;
            } else {
                $verification_code = $item['verification_code'];
                $reset_verification = false;
            }
        } else {
            // Pending / Not Found / Collected -- no collection details form for these,
            // so keep whatever was saved before (e.g. don't wipe Found details when
            // later marking the same item Collected).
            $collection_details = $item['collection_details'];
            $verification_code = null;
        }

        if ($status === 'found') {
            if ($reset_verification) {
                $stmt = $pdo->prepare("UPDATE items SET status = ?, collection_details = ?, verification_code = ?, owner_verified_at = NULL WHERE id = ?");
                $stmt->execute([$status, $collection_details, $verification_code, $item_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE items SET status = ?, collection_details = ? WHERE id = ?");
                $stmt->execute([$status, $collection_details, $item_id]);
            }
        } else {
            $stmt = $pdo->prepare("UPDATE items SET status = ?, collection_details = ? WHERE id = ?");
            $stmt->execute([$status, $collection_details, $item_id]);
        }

        // If this update came after a "found it" report via QR scan, close those out --
        // admin has now reviewed and acted on it, so it shouldn't sit in the open list.
        try {
            $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_at = NOW() WHERE item_id = ? AND type = 'found_alert' AND status = 'open'");
            $stmt->execute([$item_id]);
        } catch (PDOException $e) { /* reports table missing/outdated -- non-critical, skip */ }

        // --- notify the student ---
        $statusMessages = [
            'pending'   => "Your report for \"{$item['item_name']}\" is under review.",
            'found'     => "Good news — your item \"{$item['item_name']}\" has been found! Check the collection details and verify ownership with the code we emailed you before you can collect it.",
            'not_found' => "Your item \"{$item['item_name']}\" could not be located after admin review.",
            'collected' => "Your item \"{$item['item_name']}\" has been marked as collected. Case closed.",
        ];
        $notifTypeMap = ['pending' => 'review', 'found' => 'found', 'not_found' => 'closed', 'collected' => 'system'];

        $status_email = email_status_update($item['reporter'], $item['item_name'], $status, $status === 'found' ? $collection_details : null);

        notify_user(
            $pdo,
            $item['user_id'],
            $notifTypeMap[$status],
            $statusMessages[$status],
            $item_id,
            $send_email ? $item['reporter_email'] : null,
            $status_email['subject'],
            $status_email['html'],
            $status_email['text']
        );

        // Separate, focused verification email with just the code -- easier
        // for the student to find/act on than burying it in the status update email.
        if ($status === 'found' && $send_email) {
            $otp_email = email_otp_code($item['reporter'], $item['item_name'], $verification_code);
            send_notification_email($item['reporter_email'], $otp_email['subject'], $otp_email['html'], $otp_email['text']);
        }

        // refresh local copy
        $stmt = $pdo->prepare("SELECT items.*, users.full_name AS reporter, users.email AS reporter_email
            FROM items JOIN users ON items.user_id = users.id WHERE items.id = ?");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();
        $saved = true;
    }
}

render:
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Item Status | Admin | Smart Belonging System</title>
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
  .status-option.pending i { color: #E0A23B; }
  .status-option.found i { color: #2E8A5E; }
  .status-option.not_found i { color: #C23A3A; }
  .status-option.collected i { color: var(--deep); }
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
      <a class="nav-link active" href="manage_items.php"><i class="bi bi-box-seam"></i> Manage Items</a>
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
        <h5 class="mb-0" style="color:var(--deep)">Update Item Status</h5>
        <div class="text-secondary small"><a href="manage_items.php" class="text-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Manage Items</a></div>
      </div>
    </div>

    <div class="container-fluid p-4">
      <div class="row g-4 justify-content-center">

        <!-- Left: item summary -->
        <div class="col-lg-4">
          <div class="feature-card">
            <h6 class="mb-3" style="color:var(--deep)">Item Summary</h6>
            <div class="info-row"><span class="label">Report ID</span><span class="value">#SBS-<?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?></span></div>
            <div class="info-row"><span class="label">Item</span><span class="value"><?php echo htmlspecialchars($item['item_name']); ?></span></div>
            <div class="info-row"><span class="label">Reported By</span><span class="value"><?php echo htmlspecialchars($item['reporter']); ?></span></div>
            <div class="info-row"><span class="label">Location</span><span class="value"><?php echo htmlspecialchars($item['location']); ?></span></div>
            <div class="info-row"><span class="label">Date Reported</span><span class="value"><?php echo date('d M Y', strtotime($item['created_at'])); ?></span></div>
            <?php if ($item['report_type'] === 'found'): ?>
            <div class="info-row"><span class="label">Report Type</span><span class="value"><span class="complaint-tag" style="background:rgba(76,175,131,0.15);color:#2E8A5E;"><i class="bi bi-hand-index-thumb me-1"></i>Found by student</span></span></div>
            <?php else: ?>
            <div class="info-row"><span class="label">Current Status</span><span class="value"><span class="badge-status badge-<?php echo htmlspecialchars($item['status']); ?>"><?php echo str_replace('_', ' ', ucfirst($item['status'])); ?></span></span></div>
            <?php endif; ?>
            <?php if ($item['report_type'] !== 'found' && $item['status'] === 'found'): ?>
            <div class="info-row">
              <span class="label">Ownership Verification</span>
              <span class="value">
                <?php if ($item['owner_verified_at']): ?>
                  <span class="text-success"><i class="bi bi-patch-check-fill me-1"></i>Verified <?php echo date('d M Y', strtotime($item['owner_verified_at'])); ?></span>
                <?php else: ?>
                  <span style="color:#B4791E"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                <?php endif; ?>
              </span>
            </div>
            <?php if (!$item['owner_verified_at']): ?>
            <form action="update.php?id=<?php echo $item['id']; ?>" method="POST" class="mt-2">
              <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
              <input type="hidden" name="resend_code" value="1">
              <button type="submit" class="btn btn-outline-brand btn-sm w-100"><i class="bi bi-envelope-arrow-up me-1"></i>Resend Verification Code</button>
            </form>
            <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right: update form (lost-item review workflow) or plain summary (found-item reports) -->
        <div class="col-lg-7">
          <div class="feature-card">
            <?php if ($item['report_type'] === 'found'): ?>

              <h6 class="mb-3" style="color:var(--deep)">Found Item Report</h6>
              <p class="text-secondary small">This is a plain log of an item a student found and handed in — it doesn't go through the lost-item review workflow. No status changes are needed here.</p>
              <?php if ($item['description']): ?>
                <div class="small mt-3"><span class="fw-semibold" style="color:var(--deep)">Description:</span> <?php echo nl2br(htmlspecialchars($item['description'])); ?></div>
              <?php endif; ?>
              <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="manage_items.php" class="btn btn-outline-brand">Back to Manage Items</a>
              </div>

            <?php else: ?>

            <h6 class="mb-3" style="color:var(--deep)">Change Status</h6>

            <?php if ($block_error): ?>
              <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($block_error); ?></div>
            <?php elseif ($saved): ?>
              <div class="alert alert-success py-2 small">Status updated and the student has been notified.</div>
            <?php endif; ?>

            <form action="update.php?id=<?php echo $item['id']; ?>" method="POST" novalidate>
              <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">

              <div class="row g-2 mb-4">
                <div class="col-md-3">
                  <label class="status-option pending">
                    <input type="radio" name="status" value="pending" <?php echo $item['status'] === 'pending' ? 'checked' : ''; ?>>
                    <i class="bi bi-hourglass-split"></i> Pending
                  </label>
                </div>
                <div class="col-md-3">
                  <label class="status-option found">
                    <input type="radio" name="status" value="found" <?php echo $item['status'] === 'found' ? 'checked' : ''; ?>>
                    <i class="bi bi-check-circle"></i> Found
                  </label>
                </div>
                <div class="col-md-3">
                  <label class="status-option not_found">
                    <input type="radio" name="status" value="not_found" <?php echo $item['status'] === 'not_found' ? 'checked' : ''; ?>>
                    <i class="bi bi-x-circle"></i> Not Found
                  </label>
                </div>
                <div class="col-md-3">
                  <label class="status-option collected" id="collectedOptionLabel">
                    <input type="radio" name="status" value="collected" id="collectedRadio" <?php echo $item['status'] === 'collected' ? 'checked' : ''; ?>>
                    <i class="bi bi-box-seam"></i> Collected
                  </label>
                </div>
              </div>

              <?php if ($item['status'] === 'found' && !$item['owner_verified_at']): ?>
                <div class="small mb-3 p-2" style="background:rgba(224,162,59,0.12);border-radius:8px;color:#B4791E">
                  <i class="bi bi-exclamation-triangle me-1"></i>The student hasn't verified ownership yet — Collected can't be selected until they enter the code emailed to them.
                </div>
              <?php endif; ?>

              <hr style="border-color:#EEF1EC">

              <div id="collectionDetailsBlock" style="display:none;">
                <h6 class="mb-3 mt-3" style="color:var(--deep)">Collection Details</h6>
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Collection Location</label>
                    <input type="text" name="collection_location" class="form-control" placeholder="e.g. Admin Office, Block A">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Room / Desk</label>
                    <input type="text" name="collection_room" class="form-control" placeholder="e.g. Ground Floor, Room 3">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Available Timings</label>
                    <input type="text" name="collection_timings" class="form-control" placeholder="e.g. Mon-Fri, 10am-4pm">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Bring Along</label>
                    <input type="text" name="collection_requirement" class="form-control" placeholder="e.g. College ID Card">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Notes to Student <span class="text-secondary fw-normal">(optional)</span></label>
                    <textarea name="admin_notes" class="form-control" rows="3" placeholder="Any additional message for the student..."></textarea>
                  </div>
                </div>

                <?php if ($item['collection_details']): ?>
                  <div class="small text-secondary mt-2"><i class="bi bi-info-circle me-1"></i>Current saved details: <?php echo nl2br(htmlspecialchars($item['collection_details'])); ?></div>
                <?php endif; ?>
              </div>

              <div id="collectedNote" class="small text-secondary mb-2" style="display:none;">
                <i class="bi bi-info-circle me-1"></i>This closes the case — the student will be notified that their item has been collected. No further details needed.
              </div>
              <div id="notFoundNote" class="small text-secondary mb-2" style="display:none;">
                <i class="bi bi-info-circle me-1"></i>The student will be notified that the item couldn't be located. They may re-report it with more details.
              </div>
              <div id="pendingNote" class="small text-secondary mb-2" style="display:none;">
                <i class="bi bi-info-circle me-1"></i>The student will see their report is still under review.
              </div>

              <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="sendEmail" name="sendEmail" checked>
                <label class="form-check-label small text-secondary" for="sendEmail">
                  Notify student by email about this update
                </label>
              </div>

              <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="manage_items.php" class="btn btn-outline-brand">Cancel</a>
                <button type="submit" class="btn btn-brand px-4">Save Update</button>
              </div>
            </form>

            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const statusRadios = document.querySelectorAll('input[name="status"]');
  const blocks = {
    found: document.getElementById('collectionDetailsBlock'),
    collected: document.getElementById('collectedNote'),
    not_found: document.getElementById('notFoundNote'),
    pending: document.getElementById('pendingNote'),
  };

  function updateVisibility() {
    const selected = document.querySelector('input[name="status"]:checked')?.value;
    Object.entries(blocks).forEach(([key, el]) => {
      if (el) el.style.display = key === selected ? '' : 'none';
    });
  }

  statusRadios.forEach(r => r.addEventListener('change', updateVisibility));
  updateVisibility();

  <?php if ($item['report_type'] !== 'found' && $item['status'] === 'found' && !$item['owner_verified_at']): ?>
  document.getElementById('collectedRadio').disabled = true;
  document.getElementById('collectedOptionLabel').style.opacity = '0.5';
  document.getElementById('collectedOptionLabel').style.cursor = 'not-allowed';
  <?php endif; ?>
</script>
</body>
</html>
