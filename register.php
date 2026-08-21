<?php
session_start();
$error = $_SESSION['register_error'] ?? '';
$success = $_SESSION['register_success'] ?? '';
$old = $_SESSION['register_old'] ?? [];
unset($_SESSION['register_error'], $_SESSION['register_success'], $_SESSION['register_old']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | Smart Belonging System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

  <div class="auth-wrapper">
    <div class="row g-0 w-100">
      <div class="col-md-5 auth-visual d-none d-md-flex">
        <div class="d-flex align-items-center gap-2">
          <span class="brand-tag"><i class="bi bi-qr-code"></i></span>
          <span class="fw-semibold">Smart Belonging System</span>
        </div>
        <div>
          <h2 class="text-white mb-3" style="font-size:2rem">Tag it. Lose it less.</h2>
          <p style="color:rgba(255,255,255,0.75)">Create your student account to start reporting lost items and
            generating QR tags for your gadgets.</p>
        </div>
        <div class="small" style="color:rgba(255,255,255,0.55)">&copy; <?php echo date('Y'); ?> Smart Belonging System
          for Campus</div>
      </div>

      <div class="col-md-7 d-flex">
        <div class="auth-card" style="max-width:520px">
          <a href="index.php" class="d-inline-flex align-items-center gap-2 mb-4 text-decoration-none d-md-none">
            <span class="brand-tag" style="background:linear-gradient(145deg,var(--deep),var(--mid))"><i
                class="bi bi-qr-code text-white"></i></span>
            <span class="fw-semibold" style="color:var(--deep)">Smart Belonging System</span>
          </a>

          <h3 class="mb-1">Create your student account</h3>
          <p class="text-secondary mb-4">It only takes a minute</p>

          <?php if ($success): ?>
            <div class="text-center py-4">
              <i class="bi bi-envelope-check" style="font-size:2.5rem;color:#2E8A5E"></i>
              <h5 class="mt-3 mb-2" style="color:var(--deep)">Check your inbox</h5>
              <p class="text-secondary small"><?php echo htmlspecialchars($success); ?></p>
              <a href="login.php" class="btn btn-brand mt-2">Go to Login</a>
            </div>
          <?php else: ?>

            <?php if ($error): ?>
              <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="process/register_process.php" method="POST" novalidate>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Full Name</label>
                  <input type="text" name="full_name" class="form-control"
                    value="<?php echo htmlspecialchars($old['full_name'] ?? ''); ?>" placeholder="Enter Name" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Enrollment No.</label>
                  <input type="text" name="enrollment_no" class="form-control"
                    value="<?php echo htmlspecialchars($old['enrollment_no'] ?? ''); ?>" placeholder="Enter Enrollment"
                    required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">University Email Address</label>
                  <input type="email" name="email" class="form-control"
                    value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" placeholder="Enter Email"
                    pattern="^[^\s@]+@marwadiuniversity\.(ac\.in|edu\.in)$"
                    title="Use your Marwadi University email (@marwadiuniversity.ac.in or @marwadiuniversity.edu.in)"
                    required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Phone Number</label>
                  <input type="text" name="phone" class="form-control"
                    value="<?php echo htmlspecialchars($old['phone'] ?? ''); ?>" placeholder="Enter Phone Number"
                    required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Department</label>
                  <select name="department" class="form-control" required>
                    <option value="">Select department</option>
                    <?php
                    $depts = ['Computer Engineering', 'Information Technology', 'Electronics', 'Mechanical', 'Civil', 'MBA'];
                    foreach ($depts as $d) {
                      $sel = (($old['department'] ?? '') === $d) ? 'selected' : '';
                      echo "<option value=\"" . htmlspecialchars($d) . "\" $sel>" . htmlspecialchars($d) . "</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Password</label>
                  <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
                </div>
              </div>

              <button type="submit" class="btn btn-brand w-100 py-2 mt-4">Create Account</button>
            </form>

          <?php endif; ?>

          <p class="text-center text-secondary small mt-4 mb-0">
            Already have an account? <a href="login.php" class="fw-semibold">Sign in</a>
          </p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>