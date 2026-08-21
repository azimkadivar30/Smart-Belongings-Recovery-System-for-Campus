<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

// --- handle status actions (before any HTML output) ---
if (isset($_GET['resolve'])) {
    try {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_at = NOW() WHERE id = ?");
        $stmt->execute([(int)$_GET['resolve']]);
    } catch (PDOException $e) { /* table missing -- handled below on page load */ }
    header("Location: reports.php");
    exit();
}
if (isset($_GET['escalate'])) {
    try {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'escalated' WHERE id = ?");
        $stmt->execute([(int)$_GET['escalate']]);
    } catch (PDOException $e) { /* table missing -- handled below on page load */ }
    header("Location: reports.php");
    exit();
}

$filter = $_GET['status'] ?? 'all';
$typeFilter = $_GET['type'] ?? 'all';
$db_setup_error = null;
$reports = [];
$counts = ['all_count' => 0, 'open_count' => 0, 'resolved_count' => 0, 'escalated_count' => 0, 'complaint_count' => 0, 'found_alert_count' => 0];

try {
    $sql = "SELECT reports.*, users.full_name AS filer, items.item_name
        FROM reports
        LEFT JOIN users ON reports.user_id = users.id
        LEFT JOIN items ON reports.item_id = items.id
        WHERE 1=1";
    $params = [];
    if (in_array($filter, ['open', 'resolved', 'escalated'])) {
        $sql .= " AND reports.status = ?";
        $params[] = $filter;
    }
    if (in_array($typeFilter, ['complaint', 'found_alert'])) {
        $sql .= " AND reports.type = ?";
        $params[] = $typeFilter;
    }
    $sql .= " ORDER BY reports.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

    $counts = $pdo->query("SELECT
        COUNT(*) AS all_count,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
        SUM(CASE WHEN status = 'escalated' THEN 1 ELSE 0 END) AS escalated_count,
        SUM(CASE WHEN type = 'complaint' THEN 1 ELSE 0 END) AS complaint_count,
        SUM(CASE WHEN type = 'found_alert' THEN 1 ELSE 0 END) AS found_alert_count
        FROM reports")->fetch();
} catch (PDOException $e) {
    // Most likely cause: the `reports` table doesn't exist yet, or exists in its
    // older form (before `type`/`finder_contact` columns were added).
    $db_setup_error = "Couldn't load reports — your `reports` table may be missing or out of date. Re-import database/schema.sql to update it, then refresh this page.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports &amp; Complaints | Admin | Smart Belonging System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
  .filter-pill {
    border: 1.5px solid #E1E7EA;
    background: var(--white);
    color: var(--ink-soft);
    font-weight: 600;
    font-size: 0.85rem;
    padding: 0.4rem 1rem;
    border-radius: 30px;
    text-decoration: none;
    display: inline-block;
  }
  .filter-pill.active, .filter-pill:hover { background: var(--deep); color: var(--white); border-color: var(--deep); }
  .complaint-tag {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
  }
  .tag-open { background: rgba(224,162,59,0.15); color: #B4791E; }
  .tag-resolved { background: rgba(76,175,131,0.15); color: #2E8A5E; }
  .tag-escalated { background: rgba(214,84,84,0.14); color: #C23A3A; }
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
      <a class="nav-link" href="manage_devices.php"><i class="bi bi-cpu"></i> Manage Devices</a>
      <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Manage Users</a>
      <a class="nav-link active" href="reports.php"><i class="bi bi-flag"></i> Reports / Complaints</a>
      <hr style="border-color:rgba(255,255,255,0.15)">
      <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
  </div>

  <!-- ===== Main content ===== -->
  <div class="flex-grow-1">
    <div class="dash-topbar">
      <div>
        <h5 class="mb-0" style="color:var(--deep)">Reports &amp; Complaints</h5>
        <div class="text-secondary small">Handle misuse reports, disputes, or issues raised by students</div>
      </div>
    </div>

    <div class="container-fluid p-4">

      <div class="d-flex flex-wrap gap-2 mb-2">
        <a class="filter-pill <?php echo $typeFilter === 'all' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_filter(['status' => $filter !== 'all' ? $filter : null])); ?>">All Types (<?php echo (int)$counts['all_count']; ?>)</a>
        <a class="filter-pill <?php echo $typeFilter === 'found_alert' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_filter(['status' => $filter !== 'all' ? $filter : null, 'type' => 'found_alert'])); ?>"><i class="bi bi-qr-code-scan me-1"></i>Found Reports (<?php echo (int)$counts['found_alert_count']; ?>)</a>
        <a class="filter-pill <?php echo $typeFilter === 'complaint' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_filter(['status' => $filter !== 'all' ? $filter : null, 'type' => 'complaint'])); ?>"><i class="bi bi-flag me-1"></i>Complaints (<?php echo (int)$counts['complaint_count']; ?>)</a>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="filter-pill <?php echo $filter === 'all' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_filter(['type' => $typeFilter !== 'all' ? $typeFilter : null])); ?>">All Status (<?php echo (int)$counts['all_count']; ?>)</a>
        <a class="filter-pill <?php echo $filter === 'open' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_filter(['status' => 'open', 'type' => $typeFilter !== 'all' ? $typeFilter : null])); ?>">Open (<?php echo (int)$counts['open_count']; ?>)</a>
        <a class="filter-pill <?php echo $filter === 'resolved' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_filter(['status' => 'resolved', 'type' => $typeFilter !== 'all' ? $typeFilter : null])); ?>">Resolved (<?php echo (int)$counts['resolved_count']; ?>)</a>
        <a class="filter-pill <?php echo $filter === 'escalated' ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_filter(['status' => 'escalated', 'type' => $typeFilter !== 'all' ? $typeFilter : null])); ?>">Escalated (<?php echo (int)$counts['escalated_count']; ?>)</a>
      </div>

      <?php if ($db_setup_error): ?>
        <div class="feature-card text-center py-5">
          <i class="bi bi-exclamation-triangle" style="font-size:2rem;color:#E0A23B"></i>
          <p class="text-secondary mt-2 mb-0"><?php echo htmlspecialchars($db_setup_error); ?></p>
        </div>
      <?php elseif (empty($reports)): ?>
        <div class="feature-card text-center py-5">
          <i class="bi bi-flag" style="font-size:2rem;color:var(--mid)"></i>
          <p class="text-secondary mt-2 mb-0">No reports in this view.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($reports as $r): ?>
        <div class="complaint-card <?php echo $r['status']; ?>">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
            <div>
              <div class="fw-semibold" style="color:var(--ink)">
                <?php if ($r['type'] === 'found_alert'): ?>
                  <i class="bi bi-qr-code-scan me-1" style="color:var(--deep)"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($r['subject']); ?><?php echo ($r['type'] === 'complaint' && $r['item_name']) ? ' — ' . htmlspecialchars($r['item_name']) : ''; ?>
              </div>
              <div class="text-secondary small">
                <?php if ($r['type'] === 'found_alert'): ?>
                  Reported via QR scan by <?php echo htmlspecialchars($r['finder_name'] ?: 'unnamed finder'); ?>
                <?php else: ?>
                  Filed by <?php echo htmlspecialchars($r['filer'] ?? 'Unknown'); ?>
                <?php endif; ?>
                &nbsp;·&nbsp; <?php echo date('d M Y', strtotime($r['created_at'])); ?>
              </div>
            </div>
            <span class="complaint-tag tag-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span>
          </div>
          <?php if ($r['type'] === 'found_alert'): ?>
            <div class="text-secondary small mb-2">
              <?php if ($r['found_location']): ?><i class="bi bi-geo-alt"></i> Found at: <?php echo htmlspecialchars($r['found_location']); ?><br><?php endif; ?>
              <?php if ($r['finder_contact']): ?><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($r['finder_contact']); ?><?php endif; ?>
              <?php if ($r['finder_email']): ?> &nbsp;·&nbsp; <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($r['finder_email']); ?><?php endif; ?>
            </div>
          <?php endif; ?>
          <p class="text-secondary small mb-3"><?php echo nl2br(htmlspecialchars($r['message'])); ?></p>
          <?php if ($r['status'] === 'resolved'): ?>
            <div class="text-secondary small"><i class="bi bi-check2-circle me-1"></i>Resolved on <?php echo date('d M Y', strtotime($r['resolved_at'])); ?></div>
          <?php else: ?>
            <div class="d-flex gap-2 flex-wrap">
              <?php if ($r['type'] === 'found_alert' && $r['item_id']): ?>
                <a href="update.php?id=<?php echo $r['item_id']; ?>" class="btn btn-brand btn-sm"><i class="bi bi-pencil-square me-1"></i>Review &amp; Update Item</a>
              <?php elseif ($r['type'] === 'found_alert' && $r['device_id']): ?>
                <a href="update_device.php?id=<?php echo $r['device_id']; ?>" class="btn btn-brand btn-sm"><i class="bi bi-shield-lock me-1"></i>Verify &amp; Recover Device</a>
              <?php else: ?>
                <a href="?resolve=<?php echo $r['id']; ?>" class="btn btn-brand btn-sm">Mark Resolved</a>
              <?php endif; ?>
              <?php if ($r['status'] !== 'escalated'): ?>
                <a href="?escalate=<?php echo $r['id']; ?>" class="btn btn-outline-brand btn-sm">Escalate</a>
              <?php endif; ?>
              <?php if ($r['type'] === 'complaint' && $r['filer']): ?>
                <a href="mailto:<?php echo htmlspecialchars($r['filer']); ?>" target="_top" class="btn btn-outline-brand btn-sm"><i class="bi bi-chat-left-text me-1"></i>Respond</a>
              <?php endif; ?>
              <?php if ($r['type'] === 'found_alert'): ?>
                <a href="?resolve=<?php echo $r['id']; ?>" class="btn btn-outline-brand btn-sm">Dismiss / Mark Resolved</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
