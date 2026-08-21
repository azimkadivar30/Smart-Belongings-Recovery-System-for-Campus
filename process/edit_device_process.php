<?php
/**
 * Handles edits to an existing device. Ownership is re-checked here
 * (not just on the edit_device.php GET) since this is the endpoint
 * that actually writes to the database.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../register_device.php");
    exit();
}

$user_id       = $_SESSION['user_id'];
$device_id     = (int)($_POST['device_id'] ?? 0);
$device_name   = trim($_POST['device_name'] ?? '');
$brand         = trim($_POST['brand'] ?? '');
$model         = trim($_POST['model'] ?? '');
$serial_number = trim($_POST['serial_number'] ?? '');
$colour        = trim($_POST['colour'] ?? '');
$description   = trim($_POST['description'] ?? '');

$stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ? AND user_id = ?");
$stmt->execute([$device_id, $user_id]);
$device = $stmt->fetch();

if (!$device) {
    header("Location: ../register_device.php");
    exit();
}

if (!$device_name || !$brand || !$colour) {
    $_SESSION['edit_device_error'] = "Please fill in the device name, brand and colour.";
    header("Location: ../edit_device.php?id=" . $device_id);
    exit();
}

// --- optional replacement photo (same rules as registration) ---
$image_path = $device['image']; // keep existing unless a new one is uploaded
$image_hash = $device['image_hash']; // keep existing hash unless photo changes
if (!empty($_FILES['device_image']['name']) && $_FILES['device_image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['device_image']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) && $_FILES['device_image']['size'] <= 5 * 1024 * 1024) {
        $uploadDir = __DIR__ . '/../uploads/devices/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = 'device_' . $user_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['device_image']['tmp_name'], $uploadDir . $filename)) {
            $image_path = 'uploads/devices/' . $filename;
        }
    } elseif (!in_array($ext, $allowed)) {
        $_SESSION['edit_device_error'] = "Photo must be a PNG or JPG image.";
        header("Location: ../edit_device.php?id=" . $device_id);
        exit();
    } else {
        $_SESSION['edit_device_error'] = "Photo must be 5MB or smaller.";
        header("Location: ../edit_device.php?id=" . $device_id);
        exit();
    }
}

$stmt = $pdo->prepare(
    "UPDATE devices SET device_name = ?, brand = ?, model = ?, serial_number = ?, colour = ?, description = ?, image = ?, image_hash = ?
     WHERE id = ? AND user_id = ?"
);
$stmt->execute([
    $device_name, $brand, $model ?: null, $serial_number ?: null, $colour, $description ?: null, $image_path, $image_hash,
    $device_id, $user_id,
]);

header("Location: ../register_device.php?device_id=" . $device_id);
exit();
