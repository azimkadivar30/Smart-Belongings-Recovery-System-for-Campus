<?php
/**
 * Converts a registered device into an actual `items` lost-report,
 * and marks the device itself as lost. Additive use of items.device_id
 * (see database/schema.sql) -- every other item-report code path is
 * untouched since device_id defaults to NULL everywhere else.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../mail.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) {
    session_unset(); session_destroy();
    $_SESSION['login_error'] = "Your session has expired. Please sign in again.";
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../register_device.php");
    exit();
}

$user_id     = $_SESSION['user_id'];
$device_id   = (int)($_POST['device_id'] ?? 0);
$location    = trim($_POST['location'] ?? '');
$item_date   = trim($_POST['item_date'] ?? '');
$extra_notes = trim($_POST['extra_notes'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ? AND user_id = ?");
$stmt->execute([$device_id, $user_id]);
$device = $stmt->fetch();

if (!$device || $device['device_status'] === 'lost') {
    header("Location: ../register_device.php");
    exit();
}

if (!$location || !$item_date) {
    $_SESSION['lost_device_error'] = "Please fill in the last seen location and date.";
    header("Location: ../report_device_lost.php?device_id=" . $device_id);
    exit();
}

// Build the item description from the device's own registered details
$descParts = [];
if ($device['model']) $descParts[] = "Model: " . $device['model'];
if ($device['serial_number']) $descParts[] = "Serial No.: " . $device['serial_number'];
$descParts[] = "Colour: " . $device['colour'];
if ($device['description']) $descParts[] = $device['description'];
if ($extra_notes) $descParts[] = $extra_notes;
$description = implode("\n", $descParts);

$stmt = $pdo->prepare(
    "INSERT INTO items (user_id, device_id, item_name, category, description, location, item_date, report_type, status, image_path)
     VALUES (?, ?, ?, 'Electronics (Phone / Laptop)', ?, ?, ?, 'lost', 'pending', ?)"
);
$stmt->execute([
    $user_id, $device_id, $device['device_name'], $description, $location, $item_date, $device['image'],
]);
$item_id = $pdo->lastInsertId();

// Mark the device itself as lost
$stmt = $pdo->prepare("UPDATE devices SET device_status = 'lost' WHERE id = ?");
$stmt->execute([$device_id]);

// --- confirmation notification, same pattern as report_process.php ---
$submitted_email = email_item_submitted(
    $_SESSION['full_name'] ?? 'there',
    $device['device_name'],
    'lost',
    $location,
    $item_date
);
notify_user(
    $pdo,
    $user_id,
    'review',
    "Your device \"{$device['device_name']}\" has been reported lost and is under review.",
    $item_id,
    $_SESSION['email'] ?? null,
    $submitted_email['subject'],
    $submitted_email['html'],
    $submitted_email['text']
);

header("Location: ../item_details.php?id=" . $item_id);
exit();
