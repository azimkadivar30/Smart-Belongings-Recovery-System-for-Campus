<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];
$item_id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
$stmt->execute([$item_id, $user_id]);
$item = $stmt->fetch();

if (!$item) {
  header("Location: my_items.php");
  exit();
}

$complaint_sent = isset($_GET['complaint']) && $_GET['complaint'] === 'sent';
$contact_sent = isset($_GET['contact']) && $_GET['contact'] === 'sent';
$verify_error = $_SESSION['verify_error'] ?? '';
unset($_SESSION['verify_error']);

$icon = 'bi-box-seam';
$c = strtolower($item['category']);
if (strpos($c, 'electronic') !== false)
  $icon = 'bi-laptop';
elseif (strpos($c, 'id card') !== false || strpos($c, 'document') !== false)
  $icon = 'bi-person-vcard';
elseif (strpos($c, 'bag') !== false || strpos($c, 'wallet') !== false)
  $icon = 'bi-wallet2';
elseif (strpos($c, 'bottle') !== false)
  $icon = 'bi-cup-straw';
elseif (strpos($c, 'stationery') !== false || strpos($c, 'book') !== false)
  $icon = 'bi-journal-bookmark';

$status = $item['status'];
$badgeLabel = str_replace('_', ' ', ucfirst($status));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Item Details | Smart Belonging System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    .detail-hero {
      background: linear-gradient(145deg, var(--light), var(--mid));
      border-radius: var(--radius-md);
      height: 220px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--deep);
      font-size: 3.5rem;
      overflow: hidden;
    }

    .detail-hero img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 0.75rem 0;
      border-bottom: 1px solid #EEF1EC;
      font-size: 0.92rem;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-row .label {
      color: var(--ink-soft);
      font-weight: 500;
    }

    .info-row .value {
      color: var(--ink);
      font-weight: 600;
      text-align: right;
    }

    .tracker-v {
      position: relative;
      padding-left: 2.2rem;
    }

    .tracker-v .tstep {
      position: relative;
      padding-bottom: 1.8rem;
    }

    .tracker-v .tstep:last-child {
      padding-bottom: 0;
    }

    .tracker-v .tstep::before {
      content: "";
      position: absolute;
      left: -2.2rem;
      top: 2px;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: var(--deep);
      border: 3px solid var(--white);
      box-shadow: 0 0 0 2px var(--deep);
    }

    .tracker-v .tstep.pending::before {
      background: var(--white);
      box-shadow: 0 0 0 2px #D7E1E6;
    }

    .tracker-v .tstep::after {
      content: "";
      position: absolute;
      left: -1.35rem;
      top: 24px;
      width: 2px;
      height: calc(100% - 10px);
      background: var(--deep);
    }

    .tracker-v .tstep.pending::after {
      background: #D7E1E6;
    }

    .tracker-v .tstep:last-child::after {
      display: none;
    }

    .tracker-v .tstep .tlabel {
      font-weight: 700;
      color: var(--deep);
      font-size: 0.9rem;
    }

    .tracker-v .tstep.pending .tlabel {
      color: var(--ink-soft);
    }

    .tracker-v .tstep .tdate {
      font-size: 0.78rem;
      color: var(--ink-soft);
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
        <a class="nav-link active" href="my_items.php"><i class="bi bi-list-check"></i> My Items</a> <a class="nav-link"
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
          <h5 class="mb-0" style="color:var(--deep)">Item Details</h5>
          <div class="text-secondary small"><a href="my_items.php" class="text-secondary"><i
                class="bi bi-arrow-left me-1"></i>Back to My Items</a></div>
        </div>
        <span
          class="badge-status badge-<?php echo htmlspecialchars($status); ?> fs-6"><?php echo htmlspecialchars($badgeLabel); ?></span>
      </div>

      <div class="container-fluid p-4">
        <div class="row g-4">

          <!-- Left: item info -->
          <div class="col-lg-8">
            <div class="feature-card mb-4">
              <div class="detail-hero mb-3">
                <?php if ($item['image_path']): ?>
                  <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="">
                <?php else: ?>
                  <i class="bi <?php echo $icon; ?>"></i>
                <?php endif; ?>
              </div>
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <div>
                  <h4 class="mb-1" style="color:var(--deep)"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                  <div class="text-secondary small">Report ID:
                    #SBS-<?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?> &nbsp;·&nbsp; Category:
                    <?php echo htmlspecialchars($item['category']); ?></div>
                </div>
              </div>

              <p class="text-secondary mb-4"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>

              <div class="info-row"><span class="label">Reported By</span><span class="value">You
                  (<?php echo htmlspecialchars($_SESSION['full_name']); ?>)</span></div>
              <div class="info-row"><span class="label">Report Type</span><span
                  class="value"><?php echo ucfirst($item['report_type']); ?> Item</span></div>
              <div class="info-row"><span class="label">Last Seen Location</span><span
                  class="value"><?php echo htmlspecialchars($item['location']); ?></span></div>
              <div class="info-row"><span class="label">Date</span><span
                  class="value"><?php echo date('d M Y', strtotime($item['item_date'])); ?></span></div>
              <div class="info-row"><span class="label">Date Reported</span><span
                  class="value"><?php echo date('d M Y, g:i A', strtotime($item['created_at'])); ?></span></div>
            </div>

            <div class="feature-card">
              <h6 class="mb-3" style="color:var(--deep)">Status Timeline</h6>
              <div class="tracker-v">
                <div class="tstep">
                  <div class="tlabel">Report Submitted</div>
                  <div class="tdate"><?php echo date('d M Y, g:i A', strtotime($item['created_at'])); ?></div>
                </div>
                <div class="tstep <?php echo $status === 'pending' ? 'pending' : ''; ?>">
                  <div class="tlabel">Under Admin Review</div>
                  <div class="tdate">
                    <?php echo $status === 'pending' ? 'In progress' : date('d M Y, g:i A', strtotime($item['updated_at'])); ?>
                  </div>
                </div>
                <?php if ($status === 'not_found'): ?>
                  <div class="tstep">
                    <div class="tlabel">Marked Not Found</div>
                    <div class="tdate"><?php echo date('d M Y, g:i A', strtotime($item['updated_at'])); ?></div>
                  </div>
                <?php else: ?>
                  <div class="tstep <?php echo in_array($status, ['found', 'collected']) ? '' : 'pending'; ?>">
                    <div class="tlabel">Item Found &amp; Verified</div>
                    <div class="tdate">
                      <?php echo in_array($status, ['found', 'collected']) ? date('d M Y, g:i A', strtotime($item['updated_at'])) : 'Awaiting review'; ?>
                    </div>
                  </div>
                  <div class="tstep <?php echo $status === 'collected' ? '' : 'pending'; ?>">
                    <div class="tlabel">Collected by Owner</div>
                    <div class="tdate">
                      <?php echo $status === 'collected' ? date('d M Y, g:i A', strtotime($item['updated_at'])) : 'Awaiting pickup'; ?>
                    </div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Right: status + collection -->
          <div class="col-lg-4">
            <?php if ($status === 'found'): ?>
              <div class="feature-card mb-4" style="border-left:4px solid #6FCF97">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-check-circle-fill" style="color:#2E8A5E;font-size:1.3rem"></i>
                  <h6 class="mb-0" style="color:var(--deep)">Great news — it's found!</h6>
                </div>
                <p class="text-secondary small mb-0">The admin team has located this item and it's ready for collection
                  once you verify ownership below.</p>
              </div>
              <div class="feature-card mb-4">
                <h6 class="mb-3" style="color:var(--deep)"><i class="bi bi-geo-alt me-1"></i>Collection Details</h6>
                <p class="text-secondary small mb-0">
                  <?php echo $item['collection_details'] ? nl2br(htmlspecialchars($item['collection_details'])) : 'The admin will add collection details shortly.'; ?>
                </p>
              </div>

              <div class="feature-card mb-4">
                <h6 class="mb-3" style="color:var(--deep)"><i class="bi bi-patch-check me-1"></i>Ownership Verification
                </h6>
                <?php if ($item['owner_verified_at']): ?>
                  <div class="d-flex align-items-center gap-2 text-success">
                    <i class="bi bi-patch-check-fill" style="font-size:1.2rem"></i>
                    <span class="small fw-semibold">Verified on
                      <?php echo date('d M Y, g:i A', strtotime($item['owner_verified_at'])); ?></span>
                  </div>
                  <p class="text-secondary small mb-0 mt-2">You're all set — show your Student ID at the desk to collect it.
                  </p>
                <?php else: ?>
                  <p class="text-secondary small mb-2">We emailed a 6-digit code to
                    <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong>. Enter it below to confirm this
                    item is yours before you can collect it.</p>
                  <?php if ($verify_error): ?>
                    <div class="alert alert-danger py-2 small mb-2"><?php echo htmlspecialchars($verify_error); ?></div>
                  <?php endif; ?>
                  <form action="process/verify_ownership.php" method="POST" class="d-flex gap-2">
                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                    <input type="text" name="code" class="form-control form-control-sm" placeholder="6-digit code"
                      maxlength="6" inputmode="numeric" required>
                    <button type="submit" class="btn btn-brand btn-sm text-nowrap">Verify</button>
                  </form>
                  <div class="text-secondary small mt-2">Didn't get the email? Check spam, or ask the admin to resend it.
                  </div>
                <?php endif; ?>
              </div>
            <?php elseif ($status === 'not_found'): ?>
              <div class="feature-card mb-4" style="border-left:4px solid #D65454">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-x-circle-fill" style="color:#C23A3A;font-size:1.3rem"></i>
                  <h6 class="mb-0" style="color:var(--deep)">Not located yet</h6>
                </div>
                <p class="text-secondary small mb-0">The admin team couldn't locate this item. You can re-report it with
                  more details if needed.</p>
              </div>
            <?php elseif ($status === 'collected'): ?>
              <div class="feature-card mb-4" style="border-left:4px solid var(--deep)">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-check2-circle" style="color:var(--deep);font-size:1.3rem"></i>
                  <h6 class="mb-0" style="color:var(--deep)">Collected</h6>
                </div>
                <p class="text-secondary small mb-0">This item has been collected. Case closed.</p>
              </div>
            <?php else: ?>
              <div class="feature-card mb-4" style="border-left:4px solid #E0A23B">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <i class="bi bi-hourglass-split" style="color:#B4791E;font-size:1.3rem"></i>
                  <h6 class="mb-0" style="color:var(--deep)">Under review</h6>
                </div>
                <p class="text-secondary small mb-0">The admin team is reviewing your report. You'll be notified of any
                  updates.</p>
              </div>
            <?php endif; ?>

            <div class="feature-card mb-4">
              <h6 class="mb-3" style="color:var(--deep)">Need help?</h6>
              <p class="text-secondary small mb-3">Contact the admin desk if you have questions about this item.</p>
              <?php if ($contact_sent): ?>
                <div class="alert alert-success py-2 small mb-2">Sent — the admin team will reply to your registered
                  email.</div>
              <?php else: ?>
                <form action="process/contact_admin.php" method="POST" class="mb-2">
                  <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                  <button type="submit" class="btn btn-outline-brand w-100"><i class="bi bi-envelope me-1"></i> Email
                    Admin</button>
                </form>
              <?php endif; ?>
              <a href="my_items.php" class="btn btn-brand w-100">Back to My Items</a>
            </div>

            <div class="feature-card">
              <h6 class="mb-3" style="color:var(--deep)"><i class="bi bi-flag me-1"></i>Report an Issue</h6>
              <?php if ($complaint_sent): ?>
                <div class="alert alert-success py-2 small mb-0">Thanks — your report has been sent to the admin team.
                </div>
              <?php else: ?>
                <p class="text-secondary small mb-2">Something wrong with this item's handling? Let the admin know.</p>
                <form action="process/complaint_process.php" method="POST">
                  <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                  <textarea name="message" class="form-control form-control-sm mb-2" rows="2"
                    placeholder="Briefly describe the issue..." required></textarea>
                  <button type="submit" class="btn btn-outline-brand btn-sm w-100">Submit</button>
                </form>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>