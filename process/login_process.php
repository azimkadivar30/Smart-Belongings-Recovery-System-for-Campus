<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    $_SESSION['login_error'] = "Please enter both email and password.";
    header("Location: ../login.php");
    exit();
}

// No role is submitted from the form — the account's own role decides
// where the person lands. One login page for everyone, students and admins.
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = "Invalid email or password.";
    header("Location: ../login.php");
    exit();
}

if (!$user['email_verified']) {
    $_SESSION['login_error'] = "Please verify your email before logging in. Check your inbox for the verification link.";
    $_SESSION['unverified_email'] = $user['email'];
    header("Location: ../login.php");
    exit();
}

$_SESSION['user_id']   = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email']     = $user['email'];
$_SESSION['role']      = $user['role'];

if ($user['role'] === 'admin') {
    header("Location: ../admin/dashboard.php");
} else {
    header("Location: ../dashboard.php");
}
exit();
