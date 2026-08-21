<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];

// --- handle mark-as-read actions (must run before any HTML output) ---
if (isset($_GET['read'])) {
  if ($_GET['read'] === 'all') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
  } else {
    $id = (int) $_GET['read'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
  }
  header("Location: notifications.php");
  exit();
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

$counts = ['all' => count($notifications), 'unread' => 0, 'found' => 0, 'review' => 0, 'pickup' => 0];
foreach ($notifications as $n) {
  if (!$n['is_read'])
    $counts['unread']++;
  if (isset($counts[$n['type']]))
    $counts[$n['type']]++;
  if ($n['type'] === 'found_alert')
    $counts['found']++;
}

function time_ago($datetime)
{
  $diff = time() - strtotime($datetime);
  if ($diff < 60)
    return 'Just now';
  if ($diff < 3600)
    return floor($diff / 60) . ' min ago';
  if ($diff < 86400)
    return floor($diff / 3600) . ' hour' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
  if ($diff < 604800)
    return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
  return date('d M Y', strtotime($datetime));
}

$icons = [
  'found' => ['icon' => 'bi-check-circle', 'class' => 'type-found'],
  'review' => ['icon' => 'bi-hourglass-split', 'class' => 'type-review'],
  'pickup' => ['icon' => 'bi-box-seam', 'class' => 'type-pickup'],
  'system' => ['icon' => 'bi-info-circle', 'class' => 'type-system'],
  'closed' => ['icon' => 'bi-x-circle', 'class' => 'type-closed'],
  'found_alert' => ['icon' => 'bi-qr-code-scan', 'class' => 'type-found'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications | Smart Belonging System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
  <style>
    .filter-pill {
      border: 1.5px solid #E1E7EA;
      background: var(--white);
      color: var(--ink-soft);
      font-weight: 600;
      font-size: 0.85rem;
      padding: 0.4rem 1rem;
      border-radius: 30px;
    }

    .filter-pill.active,
    .filter-pill:hover {
      background: var(--deep);
      color: var(--white);
      border-color: var(--deep);
    }

    .notif-item {
      display: flex;
      gap: 1rem;
      padding: 1rem 1.1rem;
      border-radius: var(--radius-md);
      margin-bottom: 0.7rem;
      background: var(--white);
      border: 1.5px solid #EDEFE6;
    }

    .notif-item.unread {
      background: rgba(156, 213, 255, 0.08);
      border-color: rgba(122, 170, 206, 0.35);
    }

    .notif-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
    }

    .notif-icon.type-found {
      background: rgba(76, 175, 131, 0.15);
      color: #2E8A5E;
    }

    .notif-icon.type-review {
      background: rgba(224, 162, 59, 0.15);
      color: #B4791E;
    }

    .notif-icon.type-pickup {
      background: rgba(122, 170, 206, 0.18);
      color: var(--deep);
    }

    .notif-icon.type-system {
      background: rgba(53, 88, 114, 0.1);
      color: var(--deep);
    }

    .notif-icon.type-closed {
      background: rgba(214, 84, 84, 0.14);
      color: #C23A3A;
    }

    .notif-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--mid);
      flex-shrink: 0;
      margin-top: 0.4rem;
    }

    .notif-time {
      font-size: 0.78rem;
      color: var(--ink-soft);
      white-space: nowrap;
    }

    .mark-read-btn {
      font-size: 0.78rem;
      color: var(--deep);
      text-decoration: none;
      font-weight: 600;
      white-space: nowrap;
    }

    .mark-read-btn:hover {
      text-decoration: underline;
    }

    .empty-state {
      text-align: center;
      padding: 3rem 1rem;
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
        <a class="nav-link" href="my_items.php"><i class="bi bi-list-check"></i> My Items</a> <a class="nav-link"
          href="register_device.php"><i class="bi bi-cpu"></i> My Devices</a>
        <a class="nav-link active" href="notifications.php"><i class="bi bi-bell"></i> Notifications</a>
        <a class="nav-link" href="profile.php"><i class="bi bi-person-circle"></i> My Profile</a>
        <hr style="border-color:rgba(255,255,255,0.15)">
        <a class="nav-link" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
      </nav>
    </div>

    <!-- ===== Main content ===== -->
    <div class="flex-grow-1">
      <div class="dash-topbar">
        <div>
          <h5 class="mb-0" style="color:var(--deep)">Notifications</h5>
          <div class="text-secondary small">Updates on your reported items</div>
        </div>
        <a href="?read=all" class="btn btn-outline-brand"><i class="bi bi-check2-all me-1"></i> Mark all as read</a>
      </div>

      <div class="container-fluid p-4">

        <!-- Filters -->
        <div class="d-flex flex-wrap gap-2 mb-4">
          <button class="filter-pill active" data-filter="all">All (<?php echo $counts['all']; ?>)</button>
          <button class="filter-pill" data-filter="unread">Unread (<?php echo $counts['unread']; ?>)</button>
          <button class="filter-pill" data-filter="found">Item Found (<?php echo $counts['found']; ?>)</button>
          <button class="filter-pill" data-filter="review">Under Review (<?php echo $counts['review']; ?>)</button>
          <button class="filter-pill" data-filter="pickup">Pickup (<?php echo $counts['pickup']; ?>)</button>
        </div>

        <div id="notifList">
          <?php if (empty($notifications)): ?>
            <div class="empty-state">
              <i class="bi bi-bell-slash" style="font-size:2rem;"></i>
              <p class="mb-0 mt-2">No notifications yet.</p>
            </div>
          <?php else: ?>
            <?php foreach ($notifications as $n):
              $meta = $icons[$n['type']] ?? $icons['system'];
              ?>
              <div class="notif-item <?php echo !$n['is_read'] ? 'unread' : ''; ?>"
                data-type="<?php echo htmlspecialchars($n['type'] === 'found_alert' ? 'found' : $n['type']); ?>"
                data-read="<?php echo $n['is_read'] ? '1' : '0'; ?>">
                <?php if (!$n['is_read']): ?>
                  <div class="notif-dot"></div><?php else: ?>
                  <div style="width:8px;"></div><?php endif; ?>
                <div class="notif-icon <?php echo $meta['class']; ?>"><i class="bi <?php echo $meta['icon']; ?>"></i></div>
                <div class="flex-grow-1">
                  <div class="text-secondary small"><?php echo htmlspecialchars($n['message']); ?></div>
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                  <span class="notif-time"><?php echo time_ago($n['created_at']); ?></span>
                  <?php if (!$n['is_read']): ?>
                    <a href="?read=<?php echo $n['id']; ?>" class="mark-read-btn">Mark as read</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="empty-state d-none" id="emptyState">
          <i class="bi bi-bell-slash" style="font-size:2rem;"></i>
          <p class="mb-0 mt-2">No notifications in this category yet.</p>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const filterBtns = document.querySelectorAll('.filter-pill');
    const items = document.querySelectorAll('.notif-item');
    const emptyState = document.getElementById('emptyState');

    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        let visibleCount = 0;

        items.forEach(item => {
          const matches =
            filter === 'all' ||
            (filter === 'unread' && item.dataset.read === '0') ||
            item.dataset.type === filter;
          item.classList.toggle('d-none', !matches);
          if (matches) visibleCount++;
        });

        emptyState.classList.toggle('d-none', visibleCount !== 0);
      });
    });
  </script>
</body>

</html>