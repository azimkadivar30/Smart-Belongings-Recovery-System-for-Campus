<?php
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
    header("Location: ../report.php");
    exit();
}

$user_id      = $_SESSION['user_id'];
$report_type  = ($_POST['report_type'] ?? 'lost') === 'found' ? 'found' : 'lost';
$item_name    = trim($_POST['item_name'] ?? '');
$category     = trim($_POST['category'] ?? '');
$description  = trim($_POST['description'] ?? '');
$location     = trim($_POST['location'] ?? '');
$item_date    = trim($_POST['item_date'] ?? '');

if (!$item_name || !$category || !$description || !$location || !$item_date) {
    $_SESSION['report_error'] = "Please fill in all required fields.";
    header("Location: ../report.php");
    exit();
}

// --- optional image upload ---
$image_path = null;
$image_hash = null;
if (!empty($_FILES['item_image']['name']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION));

    if (in_array($ext, $allowed) && $_FILES['item_image']['size'] <= 5 * 1024 * 1024) {
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = 'item_' . $user_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $uploadDir . $filename)) {
            $image_path = 'uploads/' . $filename;
        }
    }
}

// --- insert item ---
$stmt = $pdo->prepare(
    "INSERT INTO items (user_id, item_name, category, description, location, item_date, report_type, status, image_path, image_hash)
     VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)"
);
$stmt->execute([$user_id, $item_name, $category, $description, $location, $item_date, $report_type, $image_path, $image_hash]);
$item_id = $pdo->lastInsertId();

// --- log a notification for the student themselves, plus a confirmation email ---
$submitted_email = email_item_submitted(
    $_SESSION['full_name'] ?? 'there',
    $item_name,
    $report_type,
    $location,
    $item_date
);
notify_user(
    $pdo,
    $user_id,
    'review',
    "Your report for \"$item_name\" has been submitted and is under review.",
    $item_id,
    $_SESSION['email'] ?? null,
    $submitted_email['subject'],
    $submitted_email['html'],
    $submitted_email['text']
);

header("Location: ../my_items.php");
exit();
