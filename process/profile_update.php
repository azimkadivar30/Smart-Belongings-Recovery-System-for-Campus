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

$user_id       = $_SESSION['user_id'];
$full_name     = trim($_POST['full_name'] ?? '');
$enrollment_no = trim($_POST['enrollment_no'] ?? '');
$phone         = trim($_POST['phone'] ?? '');
$department    = trim($_POST['department'] ?? '');

if (!$full_name) {
    $_SESSION['profile_info_error'] = "Full name can't be empty.";
    header("Location: ../profile.php");
    exit();
}

$stmt = $pdo->prepare("UPDATE users SET full_name = ?, enrollment_no = ?, phone = ?, department = ? WHERE id = ?");
$stmt->execute([$full_name, $enrollment_no, $phone, $department, $user_id]);

$_SESSION['full_name'] = $full_name;
$_SESSION['profile_info_success'] = "Your profile has been updated.";
header("Location: ../profile.php");
exit();
