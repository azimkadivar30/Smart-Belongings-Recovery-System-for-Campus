<?php
/**
 * Email Verification Landing Page
 * Smart Belonging System for Campus
 *
 * No login required -- this is what the link in the verification
 * email points to. Confirms the token, marks the account verified,
 * and handles expired/invalid links gracefully.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/mail.php';

$token = trim($_GET['token'] ?? '');
$state = 'invalid'; // invalid | expired | success
$expired_email = '';

if ($token !== '') {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ?");
  $stmt->execute([$token]);
  $user = $stmt->fetch();

  if ($user) {
    if ($user['email_verified']) {
      // Already verified (e.g. link clicked twice) -- treat as success
      $state = 'success';
    } elseif (strtotime($user['token_expires_at']) < time()) {
      $state = 'expired';
      $expired_email = $user['email'];
    } else {
      $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = ?");
      $stmt->execute([$user['id']]);
      $state = 'success';

      // Fresh verification (not a repeat click) -- send a short confirmation.
      $login_url = base_url(0) . '/login.php';
      $verified_email = email_verified_confirmation($user['full_name'], $login_url);
      send_notification_email($user['email'], $verified_email['subject'], $verified_email['html'], $verified_email['text']);
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email Verification | Smart Belonging System</title>
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

    .verify-card {
      background: var(--white);
      border-radius: var(--radius-md);
      box-shadow: var(--shadow-card);
      padding: 2.2rem 1.8rem;
      max-width: 440px;
      width: 100%;
      text-align: center;
    }

    .verify-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.7rem;
      margin: 0 auto 1rem;
    }

    .verify-icon.ok {
      background: rgba(76, 175, 131, 0.15);
      color: #2E8A5E;
    }

    .verify-icon.warn {
      background: rgba(224, 162, 59, 0.15);
      color: #B4791E;
    }

    .verify-icon.bad {
      background: rgba(214, 84, 84, 0.14);
      color: #C23A3A;
    }
  </style>
</head>

<body>

  <div class="verify-card">
    <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
      <span class="brand-tag"><i class="bi bi-qr-code"></i></span>
      <span class="fw-semibold" style="color:var(--deep)">Smart Belonging System</span>
    </div>

    <?php if ($state === 'success'): ?>
      <div class="verify-icon ok"><i class="bi bi-check-circle"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Email verified!</h5>
      <p class="text-secondary small mb-3">Your account is ready. You can now log in.</p>
      <a href="login.php" class="btn btn-brand w-100">Go to Login</a>

    <?php elseif ($state === 'expired'): ?>
      <div class="verify-icon warn"><i class="bi bi-hourglass-split"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Link expired</h5>
      <p class="text-secondary small mb-3">This verification link is more than 24 hours old. Request a new one below.</p>
      <form action="process/resend_verification.php" method="POST">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($expired_email); ?>">
        <button type="submit" class="btn btn-brand w-100">Resend Verification Email</button>
      </form>

    <?php else: ?>
      <div class="verify-icon bad"><i class="bi bi-x-circle"></i></div>
      <h5 class="mb-2" style="color:var(--deep)">Invalid link</h5>
      <p class="text-secondary small mb-0">This verification link isn't valid. If you still need to verify your account,
        try registering again or contact the admin desk.</p>
    <?php endif; ?>

  </div>

</body>

</html>