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
    header("Location: ../dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$item_id = (int)($_POST['item_id'] ?? 0);
$code    = trim($_POST['code'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM items WHERE id = ? AND user_id = ?");
$stmt->execute([$item_id, $user_id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: ../my_items.php");
    exit();
}

if ($item['status'] !== 'found' || $item['owner_verified_at']) {
    // Nothing to verify -- either not marked Found, or already verified
    header("Location: ../item_details.php?id=" . $item_id);
    exit();
}

if (!$code || $code !== $item['verification_code']) {
    $_SESSION['verify_error'] = "That code doesn't match. Double-check the email and try again.";
    header("Location: ../item_details.php?id=" . $item_id);
    exit();
}

$stmt = $pdo->prepare("UPDATE items SET owner_verified_at = NOW(), recovery_status = 'recovered' WHERE id = ?");
$stmt->execute([$item_id]);

header("Location: ../item_details.php?id=" . $item_id);
exit();
