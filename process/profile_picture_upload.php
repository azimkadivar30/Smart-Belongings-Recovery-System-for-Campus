<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id, profile_pic FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    session_unset(); session_destroy();
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../profile.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (empty($_FILES['profile_pic']['name']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['profile_pic_error'] = "Please choose an image to upload.";
    header("Location: ../profile.php");
    exit();
}

$allowed = ['jpg', 'jpeg', 'png'];
$ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    $_SESSION['profile_pic_error'] = "Only JPG or PNG images are allowed.";
    header("Location: ../profile.php");
    exit();
}

if ($_FILES['profile_pic']['size'] > 5 * 1024 * 1024) {
    $_SESSION['profile_pic_error'] = "Image must be 5MB or smaller.";
    header("Location: ../profile.php");
    exit();
}

$uploadDir = __DIR__ . '/../uploads/profile_pics/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = 'user_' . $user_id . '_' . time() . '.' . $ext;

if (!move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadDir . $filename)) {
    $_SESSION['profile_pic_error'] = "Something went wrong uploading the image. Please try again.";
    header("Location: ../profile.php");
    exit();
}

$relative_path = 'uploads/profile_pics/' . $filename;

// remove the old picture file, if any, now that the new one is saved
$old_pic = $user['profile_pic'];
if ($old_pic && file_exists(__DIR__ . '/../' . $old_pic)) {
    @unlink(__DIR__ . '/../' . $old_pic);
}

$stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
$stmt->execute([$relative_path, $user_id]);

header("Location: ../profile.php");
exit();
