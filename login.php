<?php
session_start();
$error = $_SESSION['login_error'] ?? '';
$unverified_email = $_SESSION['unverified_email'] ?? '';
$resent = isset($_GET['resent']) && $_GET['resent'] === '1';
unset($_SESSION['login_error'], $_SESSION['unverified_email']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Smart Belonging System</title>
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
          <h2 class="text-white mb-3" style="font-size:2rem">Welcome back to campus care.</h2>
          <p style="color:rgba(255,255,255,0.75)">Sign in to report a lost item, track its status, or manage recoveries.
            Admin accounts sign in right here too — no separate panel needed.</p>
        </div>
        <div class="small" style="color:rgba(255,255,255,0.55)">&copy; <?php echo date('Y'); ?> Smart Belonging System
          for Campus</div>
      </div>

      <div class="col-md-7 d-flex">
        <div class="auth-card">
          <a href="index.php" class="d-inline-flex align-items-center gap-2 mb-4 text-decoration-none d-md-none">
            <span class="brand-tag" style="background:linear-gradient(145deg,var(--deep),var(--mid))"><i
                class="bi bi-qr-code text-white"></i></span>
            <span class="fw-semibold" style="color:var(--deep)">Smart Belonging System</span>
          </a>

          <h3 class="mb-1">Sign in to your account</h3>
          <p class="text-secondary mb-4">Students and admins both sign in here — your role is detected automatically.
          </p>

          <?php if ($resent): ?>
            <div class="alert alert-success py-2 small">A new verification link has been sent — check your inbox.</div>
          <?php endif; ?>

          <?php if ($error): ?>
            <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($error); ?></div>
            <?php if ($unverified_email): ?>
              <form action="process/resend_verification.php" method="POST" class="mb-3">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($unverified_email); ?>">
                <button type="submit" class="btn btn-outline-brand btn-sm w-100">Resend verification email</button>
              </form>
            <?php endif; ?>
          <?php endif; ?>

          <form action="process/login_process.php" method="POST" novalidate>
            <div class="mb-3">
              <label class="form-label">Email address</label>
              <input type="email" name="email" class="form-control" placeholder="Enter Email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                <label class="form-check-label small text-secondary" for="remember">Remember me</label>
              </div>
              <a href="#" class="small">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-brand w-100 py-2">Sign In</button>
          </form>

          <p class="text-center text-secondary small mt-4 mb-0">
            Don't have an account? <a href="register.php" class="fw-semibold">Register here</a>
          </p>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>