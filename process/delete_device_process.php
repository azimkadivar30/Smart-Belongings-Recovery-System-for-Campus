<?php
/**
 * Deletes one of the current student's own registered devices.
 * Ownership-scoped delete (id + user_id together), same pattern as
 * every other write in this app that touches a student's own row.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$device_id = (int)($_GET['id'] ?? 0);

if ($device_id) {
    $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ? AND user_id = ?");
    $stmt->execute([$device_id, $user_id]);
}

header("Location: ../register_device.php");
exit();
