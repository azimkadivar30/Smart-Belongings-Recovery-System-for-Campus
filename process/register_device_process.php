<?php
/**
 * Device Registration Handler
 * Smart Belonging System for Campus -- Feature 4
 *
 * Validates the "register a gadget" form, stores an optional photo,
 * assigns a secure random QR token, generates a QR image for it, and
 * inserts the device into the `devices` table. Mirrors the upload /
 * flash-message / notify_user() conventions already used by
 * process/report_process.php so it behaves consistently with the
 * rest of the app.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../mail.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Guard against a stale session (same check auth_check.php performs)
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

$user_id       = $_SESSION['user_id'];
$device_name   = trim($_POST['device_name'] ?? '');
$brand         = trim($_POST['brand'] ?? '');
$model         = trim($_POST['model'] ?? '');
$serial_number = trim($_POST['serial_number'] ?? '');
$colour        = trim($_POST['colour'] ?? '');
$description   = trim($_POST['description'] ?? '');

if (!$device_name || !$brand || !$colour) {
    $_SESSION['device_error'] = "Please fill in the device name, brand and colour.";
    header("Location: ../register_device.php");
    exit();
}

// --- optional photo upload (same rules as item photos: jpg/png, 5MB) ---
$image_path = null;
$image_hash = null;
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
        $_SESSION['device_error'] = "Photo must be a PNG or JPG image.";
        header("Location: ../register_device.php");
        exit();
    } else {
        $_SESSION['device_error'] = "Photo must be 5MB or smaller.";
        header("Location: ../register_device.php");
        exit();
    }
}

// --- secure random QR token (32 hex chars -- not guessable/enumerable) ---
$qr_token = bin2hex(random_bytes(16));

// --- server-side QR image generation ---
// The rest of the app (qr_generate.php) renders QR codes client-side and
// never stores an image file. This module needs a stored `qr_image` path
// (per spec), so we generate the PNG server-side here via a QR image
// service and save it locally. If the request fails for any reason
// (offline dev environment, DNS blocked, etc.) we don't fail the whole
// registration -- the device still saves with its qr_token, and
// register_device.php falls back to rendering the QR client-side with
// the same qrcodejs library qr_generate.php already uses.
$qr_image_path = null;
$scan_payload = base_url(1) . '/device_scan.php?token=' . urlencode($qr_token);

$qrDir = __DIR__ . '/../uploads/qr_devices/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0755, true);
}
$qrFilename = 'qr_' . $qr_token . '.png';
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($scan_payload);

$qrBinary = false;
if (ini_get('allow_url_fopen')) {
    $context = stream_context_create(['http' => ['timeout' => 5], 'https' => ['timeout' => 5]]);
    $qrBinary = @file_get_contents($qrApiUrl, false, $context);
}
if ($qrBinary !== false && strlen($qrBinary) > 0) {
    if (@file_put_contents($qrDir . $qrFilename, $qrBinary) !== false) {
        $qr_image_path = 'uploads/qr_devices/' . $qrFilename;
    }
}

// --- insert device ---
$stmt = $pdo->prepare(
    "INSERT INTO devices (user_id, device_name, brand, model, serial_number, colour, description, image, image_hash, qr_token, qr_image, device_status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')"
);
$stmt->execute([
    $user_id, $device_name, $brand, $model ?: null, $serial_number ?: null,
    $colour, $description ?: null, $image_path, $image_hash, $qr_token, $qr_image_path,
]);
$device_id = $pdo->lastInsertId();

// --- in-app notification + confirmation email (best-effort, same pattern as report_process.php) ---
$device_email = email_device_registered(
    $_SESSION['full_name'] ?? 'there',
    $device_name,
    $qr_token,
    base_url(1) . '/register_device.php'
);
notify_user(
    $pdo,
    $user_id,
    'system',
    "Your device \"$device_name\" has been registered and tagged with a QR code.",
    null,
    $_SESSION['email'] ?? null,
    $device_email['subject'],
    $device_email['html'],
    $device_email['text']
);

$_SESSION['device_success'] = "\"$device_name\" was registered successfully.";
header("Location: ../register_device.php?device_id=" . $device_id);
exit();
