<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/db.php';

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

$counts = ['all' => count($items), 'pending' => 0, 'found' => 0, 'not_found' => 0, 'collected' => 0];
foreach ($items as $it) {
  if (isset($counts[$it['status']]))
    $counts[$it['status']]++;
}

function item_icon($category)
{
  $c = strtolower($category);
  if (strpos($c, 'electronic') !== false || strpos($c, 'laptop') !== false || strpos($c, 'phone') !== false)
    return 'bi-laptop';
  if (strpos($c, 'id card') !== false || strpos($c, 'document') !== false)
    return 'bi-person-vcard';
  if (strpos($c, 'bag') !== false || strpos($c, 'wallet') !== false)
    return 'bi-wallet2';
  if (strpos($c, 'bottle') !== false || strpos($c, 'lunch') !== false)
    return 'bi-cup-straw';
  if (strpos($c, 'stationery') !== false || strpos($c, 'book') !== false)
    return 'bi-journal-bookmark';
  if (strpos($c, 'clothing') !== false || strpos($c, 'accessor') !== false)
    return 'bi-bag';
  return 'bi-box-seam';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Items | Smart Belonging System</title>
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

    .item-card {
      margin-bottom: 1.1rem;
    }

    .item-thumb-lg {
      width: 58px;
      height: 58px;
      border-radius: 14px;
      background: linear-gradient(145deg, var(--light), var(--mid));
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--deep);
      font-size: 1.4rem;
      flex-shrink: 0;
    }

    .tracker {
      display: flex;
      align-items: center;
      margin-top: 0.9rem;
    }

    .tracker .dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #E1E7EA;
      flex-shrink: 0;
      position: relative;
    }

    .tracker .dot.active {
      background: var(--mid);
    }

    .tracker .dot.done {
      background: var(--deep);
    }

    .tracker .line {
      flex: 1;
      height: 2px;
      background: #E1E7EA;
    }

    .tracker .line.done {
      background: var(--deep);
    }

    .tracker-labels {
      display: flex;
      justify-content: space-between;
      font-size: 0.7rem;
      color: var(--ink-soft);
      margin-top: 0.3rem;
    }

    .search-input {
      border-radius: var(--radius-sm);
      border: 1.5px solid #E1E7EA;
      padding: 0.6rem 1rem 0.6rem 2.4rem;
      background: var(--white);
    }

    .search-wrap {
      position: relative;
    }

    .search-wrap i {
      position: absolute;
      left: 0.9rem;
      top: 50%;
      transform: translateY(-50%);
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
          <h5 class="mb-0" style="color:var(--deep)">My Items</h5>
          <div class="text-secondary small">Track the status of everything you've reported</div>
        </div>
        <a href="report.php" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Report Item</a>
      </div>

      <div class="container-fluid p-4">

        <!-- Filters + search -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
          <div class="d-flex flex-wrap gap-2">
            <button class="filter-pill active" data-filter="all">All (<?php echo $counts['all']; ?>)</button>
            <button class="filter-pill" data-filter="pending">Pending (<?php echo $counts['pending']; ?>)</button>
            <button class="filter-pill" data-filter="found">Found (<?php echo $counts['found']; ?>)</button>
            <button class="filter-pill" data-filter="not_found">Not Found (<?php echo $counts['not_found']; ?>)</button>
            <button class="filter-pill" data-filter="collected">Collected (<?php echo $counts['collected']; ?>)</button>
          </div>
          <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="itemSearch" class="search-input" placeholder="Search your items...">
          </div>
        </div>

        <?php if (empty($items)): ?>
          <div class="feature-card text-center py-5">
            <i class="bi bi-inbox" style="font-size:2.2rem;color:var(--mid)"></i>
            <p class="text-secondary mt-2 mb-3">You haven't reported any items yet.</p>
            <a href="report.php" class="btn btn-brand btn-sm">Report your first item</a>
          </div>
        <?php else: ?>
          <?php foreach ($items as $item):
            $status = $item['status'];
            // tracker step state
            $step1 = 'done';
            $step2 = $status === 'pending' ? 'active' : 'done';
            $step3 = in_array($status, ['found', 'collected']) ? 'done' : ($status === 'not_found' ? '' : ($status === 'pending' ? '' : 'active'));
            $label3 = $status === 'not_found' ? 'Closed' : ($status === 'collected' ? 'Collected' : 'Ready for Pickup');
            ?>
            <div class="feature-card item-card" data-status="<?php echo htmlspecialchars($status); ?>"
              data-name="<?php echo htmlspecialchars(strtolower($item['item_name'])); ?>">
              <div class="d-flex gap-3">
                <div class="item-thumb-lg"><i class="bi <?php echo item_icon($item['category']); ?>"></i></div>
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                      <a href="item_details.php?id=<?php echo (int) $item['id']; ?>" class="fw-semibold text-decoration-none"
                        style="color:var(--ink)"><?php echo htmlspecialchars($item['item_name']); ?></a>
                      <div class="text-secondary small"><i class="bi bi-geo-alt"></i>
                        <?php echo htmlspecialchars($item['location']); ?> &nbsp;·&nbsp; Reported
                        <?php echo date('d M Y', strtotime($item['created_at'])); ?></div>
                    </div>
                    <span
                      class="badge-status badge-<?php echo htmlspecialchars($status); ?>"><?php echo str_replace('_', ' ', ucfirst($status)); ?></span>
                  </div>
                  <div class="tracker">
                    <div class="dot <?php echo $step1; ?>"></div>
                    <div class="line <?php echo $step2 === 'done' ? 'done' : ''; ?>"></div>
                    <div class="dot <?php echo $step2; ?>"></div>
                    <div class="line <?php echo $step3 === 'done' ? 'done' : ''; ?>"></div>
                    <div class="dot <?php echo $step3; ?>"></div>
                  </div>
                  <div class="tracker-labels">
                    <span>Reported</span><span>Under Review</span><span><?php echo $label3; ?></span>
                  </div>
                  <?php if ($status === 'found' && $item['collection_details']): ?>
                    <div class="small mt-2 p-2" style="background:rgba(76,175,131,0.1);border-radius:8px;color:#2E8A5E">
                      <i class="bi bi-check-circle me-1"></i>
                      <?php echo nl2br(htmlspecialchars($item['collection_details'])); ?>
                    </div>
                  <?php elseif ($status === 'not_found'): ?>
                    <div class="small mt-2 p-2" style="background:rgba(214,84,84,0.1);border-radius:8px;color:#C23A3A">
                      <i class="bi bi-x-circle me-1"></i> Not located after admin review. You may re-report with more details.
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const pills = document.querySelectorAll('.filter-pill');
    const cards = document.querySelectorAll('.item-card');
    const searchInput = document.getElementById('itemSearch');
    let activeFilter = 'all';

    function applyFilters() {
      const term = searchInput.value.trim().toLowerCase();
      cards.forEach(card => {
        const matchesStatus = activeFilter === 'all' || card.dataset.status === activeFilter;
        const matchesSearch = !term || card.dataset.name.includes(term);
        card.classList.toggle('d-none', !(matchesStatus && matchesSearch));
      });
    }

    pills.forEach(btn => {
      btn.addEventListener('click', () => {
        pills.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeFilter = btn.dataset.filter;
        applyFilters();
      });
    });

    searchInput.addEventListener('input', applyFilters);
  </script>
</body>

</html>