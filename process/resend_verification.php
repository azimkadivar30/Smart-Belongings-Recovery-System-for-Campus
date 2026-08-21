<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$email = trim($_POST['email'] ?? '');

if ($email) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Only actually do anything if the account exists and isn't already
    // verified -- but don't reveal that distinction to the caller either
    // way, so this endpoint can't be used to check which emails are registered.
    if ($user && !$user['email_verified']) {
        $verification_token = bin2hex(random_bytes(32));
        $token_expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?");
        $stmt->execute([$verification_token, $token_expires_at, $user['id']]);

        $verify_link = base_url(1) . '/verify_email.php?token=' . $verification_token;

        $resend_email = email_resend_verification($user['full_name'], $verify_link);
        send_notification_email($email, $resend_email['subject'], $resend_email['html'], $resend_email['text']);
    }
}

header("Location: ../login.php?resent=1");
exit();
