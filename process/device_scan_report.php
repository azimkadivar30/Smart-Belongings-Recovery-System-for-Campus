<?php
/**
 * Handles the "I found this" form submitted from the public
 * device_scan.php page. No login required -- the finder is
 * anonymous by design, mirroring process/scan_report.php for items.
 * We only ever notify the owner; we never expose the owner's contact
 * info back to the finder directly (that stays inside the platform /
 * admin desk).
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../device_scan.php");
    exit();
}

$token          = trim($_POST['token'] ?? '');
$finder_name    = trim($_POST['finder_name'] ?? '');
$finder_contact = trim($_POST['finder_contact'] ?? '');   // contact number
$finder_email   = trim($_POST['finder_email'] ?? '');
$found_location = trim($_POST['found_location'] ?? '');
$message        = trim($_POST['message'] ?? '');

// Name, contact number, email and found location are required per the
// "Report Found" form spec; message stays optional.
if ($token === '' || $finder_name === '' || $finder_contact === ''
    || $finder_email === '' || $found_location === ''
    || !filter_var($finder_email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../device_scan.php?token=" . urlencode($token));
    exit();
}

$stmt = $pdo->prepare("SELECT devices.*, users.email AS owner_email, users.full_name AS owner_name
    FROM devices JOIN users ON devices.user_id = users.id
    WHERE devices.qr_token = ?");
$stmt->execute([$token]);
$device = $stmt->fetch();

if (!$device || $device['device_status'] === 'recovered') {
    header("Location: ../device_scan.php?token=" . urlencode($token));
    exit();
}

$adminMessage = $message !== '' ? $message : '(no additional message from finder)';

// --- goes straight to the admin queue for verification, same as an item
//     "found" report -- the finder's own details ride along as their own
//     columns so admin can see them without parsing free text. ---
try {
    $stmt = $pdo->prepare(
        "INSERT INTO reports (user_id, item_id, device_id, type, finder_name, subject, message, finder_contact, finder_email, found_location, status)
         VALUES (NULL, NULL, ?, 'found_alert', ?, ?, ?, ?, ?, ?, 'open')"
    );
    $stmt->execute([
        $device['id'],
        $finder_name,
        "Possible match found: \"{$device['device_name']}\" (device)",
        $adminMessage,
        $finder_contact,
        $finder_email,
        $found_location,
    ]);
} catch (PDOException $e) {
    // reports table missing one of the newer columns (schema not re-imported
    // yet) -- the owner notification below still goes out either way.
}

// --- lightweight heads-up to the student -- no dead end, but no raw finder contact either ---
$alert_email = email_found_alert($device['owner_name'] ?? 'there', $device['device_name']);
notify_user(
    $pdo,
    $device['user_id'],
    'found_alert',
    "Someone scanned your \"{$device['device_name']}\" tag and reported finding it. The admin team is verifying — you'll be notified once it's confirmed.",
    null,
    $device['owner_email'],
    $alert_email['subject'],
    $alert_email['html'],
    $alert_email['text']
);

header("Location: ../device_scan.php?token=" . urlencode($token) . "&reported=1");
exit();
