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
    header("Location: ../my_items.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$item_id = (int)($_POST['item_id'] ?? 0) ?: null;
$message = trim($_POST['message'] ?? '');

if (!$message) {
    header("Location: ../item_details.php?id=" . $item_id);
    exit();
}

$subject = "Issue reported on item #" . ($item_id ?? 'N/A');

try {
    $stmt = $pdo->prepare("INSERT INTO reports (user_id, item_id, subject, message, status) VALUES (?, ?, ?, ?, 'open')");
    $stmt->execute([$user_id, $item_id, $subject, $message]);
} catch (PDOException $e) {
    // Most likely the `reports` table is missing -- re-import database/schema.sql
    header("Location: ../item_details.php?id=" . $item_id);
    exit();
}

header("Location: ../item_details.php?id=" . $item_id . "&complaint=sent");
exit();
