<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../register.php");
    exit();
}

$full_name      = trim($_POST['full_name'] ?? '');
$enrollment_no  = trim($_POST['enrollment_no'] ?? '');
$email          = trim($_POST['email'] ?? '');
$phone          = trim($_POST['phone'] ?? '');
$department     = trim($_POST['department'] ?? '');
$password       = $_POST['password'] ?? '';

$old = compact('full_name', 'enrollment_no', 'email', 'phone', 'department');

// --- basic validation ---
if (!$full_name || !$enrollment_no || !$email || !$phone || !$department || !$password) {
    $_SESSION['register_error'] = "Please fill in all fields.";
    $_SESSION['register_old'] = $old;
    header("Location: ../register.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = "Please enter a valid email address.";
    $_SESSION['register_old'] = $old;
    header("Location: ../register.php");
    exit();
}

// --- restrict registration to Marwadi University email addresses ---
// Client-side pattern on the form is just a UX hint -- this is the check
// that actually matters, since anyone can bypass HTML validation.
$allowed_domains = ['marwadiuniversity.ac.in', 'marwadiuniversity.edu.in'];
$email_domain = strtolower(substr(strrchr($email, '@'), 1));

if (!in_array($email_domain, $allowed_domains, true)) {
    $_SESSION['register_error'] = "Registration is only open to Marwadi University email addresses (@marwadiuniversity.ac.in or @marwadiuniversity.edu.in).";
    $_SESSION['register_old'] = $old;
    header("Location: ../register.php");
    exit();
}

if (strlen($password) < 6) {
    $_SESSION['register_error'] = "Password must be at least 6 characters.";
    $_SESSION['register_old'] = $old;
    header("Location: ../register.php");
    exit();
}

// --- check duplicate email ---
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $_SESSION['register_error'] = "An account with this email already exists.";
    $_SESSION['register_old'] = $old;
    header("Location: ../register.php");
    exit();
}

// --- insert new student as UNVERIFIED -- they can't log in until they click the emailed link ---
$hashed = password_hash($password, PASSWORD_DEFAULT);
$verification_token = bin2hex(random_bytes(32));
$token_expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));

$stmt = $pdo->prepare(
    "INSERT INTO users (full_name, email, phone, enrollment_no, department, password, role, email_verified, verification_token, token_expires_at)
     VALUES (?, ?, ?, ?, ?, ?, 'student', 0, ?, ?)"
);
$stmt->execute([$full_name, $email, $phone, $enrollment_no, $department, $hashed, $verification_token, $token_expires_at]);

$new_user_id = $pdo->lastInsertId();

$verify_link = base_url(1) . '/verify_email.php?token=' . $verification_token;

$reg_email = email_registration($full_name, $verify_link);
send_notification_email($email, $reg_email['subject'], $reg_email['html'], $reg_email['text']);

// Waiting for them in-app once they verify + log in -- no duplicate email here,
// the verification email above already covers that.
notify_user(
    $pdo,
    $new_user_id,
    'system',
    "Welcome to Smart Belonging System, " . $full_name . "! Verify your email to get started."
);

$_SESSION['register_success'] = "Account created! We've sent a verification link to $email — please check your inbox (and spam folder) before logging in.";
header("Location: ../register.php");
exit();
