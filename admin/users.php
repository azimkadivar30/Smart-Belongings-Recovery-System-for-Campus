<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

// --- handle delete (before any HTML output) ---
if (isset($_GET['delete'])) {
    $target_id = (int)$_GET['delete'];
    if ($target_id !== (int)$_SESSION['user_id']) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$target_id]);
        $target = $stmt->fetch();
        if ($target && $target['role'] !== 'admin') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$target_id]);
        }
    }
    header("Location: users.php");
    exit();
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalAdmins  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users | Admin | Smart Belonging System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
  .search-wrap { position: relative; }
  .search-wrap i { position: absolute; left: 0.9rem; top: 50%; transform: translateY(-50%); color: var(--ink-soft); }
  .search-input { border-radius: var(--radius-sm); border: 1.5px solid #E1E7EA; padding: 0.6rem 1rem 0.6rem 2.4rem; background: var(--white); min-width: 260px; }
  .role-chip {
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
  }
  .role-chip.student { background: rgba(122,170,206,0.18); color: var(--deep); }
  .role-chip.admin { background: rgba(53,88,114,0.9); color: var(--white); }
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
      <a class="nav-link active" href="users.php"><i class="bi bi-people"></i> Manage Users</a>
      <a class="nav-link" href="reports.php"><i class="bi bi-flag"></i> Reports / Complaints</a>
      <hr style="border-color:rgba(255,255,255,0.15)">
      <a class="nav-link" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
  </div>

  <!-- ===== Main content ===== -->
  <div class="flex-grow-1">
    <div class="dash-topbar">
      <div>
        <h5 class="mb-0" style="color:var(--deep)">Manage Users</h5>
        <div class="text-secondary small">View and manage all registered students and admins</div>
      </div>
      <form class="search-wrap" method="GET">
        <i class="bi bi-search"></i>
        <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" class="search-input" placeholder="Search by name, email, enrollment...">
      </form>
    </div>

    <div class="container-fluid p-4">

      <div class="row g-4 mb-4">
        <div class="col-md-4">
          <div class="stat-card">
            <div class="stat-icon icon-total"><i class="bi bi-people"></i></div>
            <div><div class="stat-value"><?php echo $totalUsers; ?></div><div class="stat-label">Total Users</div></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card" style="border-left-color:#6FCF97">
            <div class="stat-icon icon-found"><i class="bi bi-mortarboard"></i></div>
            <div><div class="stat-value"><?php echo $totalStudents; ?></div><div class="stat-label">Students</div></div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stat-card" style="border-left-color:var(--deep)">
            <div class="stat-icon icon-total"><i class="bi bi-shield-lock"></i></div>
            <div><div class="stat-value"><?php echo $totalAdmins; ?></div><div class="stat-label">Admins</div></div>
          </div>
        </div>
      </div>

      <div class="table-card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>User</th>
                <th>Enrollment No.</th>
                <th>Department</th>
                <th>Role</th>
                <th>Joined</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($users)): ?>
                <tr><td colspan="6" class="text-center text-secondary py-4">No users match this search.</td></tr>
              <?php endif; ?>
              <?php foreach ($users as $u):
                $initials = '';
                foreach (explode(' ', $u['full_name']) as $part) $initials .= strtoupper(substr($part, 0, 1));
                $initials = substr($initials, 0, 2);
              ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar-sm"><?php echo htmlspecialchars($initials); ?></div>
                    <div>
                      <div class="fw-semibold"><?php echo htmlspecialchars($u['full_name']); ?></div>
                      <div class="text-secondary small"><?php echo htmlspecialchars($u['email']); ?></div>
                    </div>
                  </div>
                </td>
                <td><?php echo htmlspecialchars($u['enrollment_no'] ?: '—'); ?></td>
                <td><?php echo htmlspecialchars($u['department'] ?: '—'); ?></td>
                <td><span class="role-chip <?php echo $u['role']; ?>"><?php echo ucfirst($u['role']); ?></span></td>
                <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                <td class="text-end">
                  <?php if ($u['role'] === 'admin'): ?>
                    <span class="icon-btn" style="opacity:0.35;cursor:not-allowed" title="Admin accounts can't be deleted here"><i class="bi bi-trash"></i></span>
                  <?php else: ?>
                    <a href="?delete=<?php echo $u['id']; ?><?php echo $search ? '&q=' . urlencode($search) : ''; ?>" class="icon-btn danger" title="Delete" onclick="return confirm('Remove this user? This cannot be undone.');"><i class="bi bi-trash"></i></a>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="text-secondary small mt-3">Showing <?php echo count($users); ?> of <?php echo $totalUsers; ?> users</div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
