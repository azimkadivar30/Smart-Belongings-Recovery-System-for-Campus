<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) {
    session_unset(); session_destroy();
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile.php");
    exit();
}

$user_id          = $_SESSION['user_id'];
$current_password = $_POST['current_password'] ?? '';
$new_password      = $_POST['new_password'] ?? '';
$confirm_password  = $_POST['confirm_password'] ?? '';

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user || !password_verify($current_password, $user['password'])) {
    $_SESSION['profile_pwd_error'] = "Your current password is incorrect.";
    header("Location: ../profile.php");
    exit();
}

if (strlen($new_password) < 6) {
    $_SESSION['profile_pwd_error'] = "New password must be at least 6 characters.";
    header("Location: ../profile.php");
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['profile_pwd_error'] = "New password and confirmation don't match.";
    header("Location: ../profile.php");
    exit();
}

$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt->execute([$hashed, $user_id]);

$_SESSION['profile_pwd_success'] = "Your password has been updated.";
header("Location: ../profile.php");
exit();
