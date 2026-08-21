<?php
/**
 * Handles the "I found this" form submitted from the public scan.php page.
 * No login required -- the finder is anonymous by design. We only ever
 * notify the owner; we never expose the owner's contact info back to
 * the finder directly (that stays inside the platform / admin desk).
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../scan.php");
    exit();
}

$tag            = trim($_POST['tag'] ?? '');
$found_location = trim($_POST['found_location'] ?? '');
$finder_contact = trim($_POST['finder_contact'] ?? '');
$message        = trim($_POST['message'] ?? '');

if ($tag === '' || $found_location === '') {
    header("Location: ../scan.php?tag=" . urlencode($tag));
    exit();
}

$stmt = $pdo->prepare("SELECT items.*, users.email AS owner_email
    FROM items JOIN users ON items.user_id = users.id
    WHERE items.qr_code = ?");
$stmt->execute([$tag]);
$item = $stmt->fetch();

if (!$item || $item['status'] === 'collected') {
    header("Location: ../scan.php?tag=" . urlencode($tag));
    exit();
}

$parts = ["Found at: $found_location."];
if ($message) $parts[] = "Finder's note: \"$message\"";
$adminMessage = implode(' ', $parts);

// --- goes to admin for verification, same as a complaint, just a different type ---
try {
    $stmt = $pdo->prepare(
        "INSERT INTO reports (user_id, item_id, type, subject, message, finder_contact, status)
         VALUES (NULL, ?, 'found_alert', ?, ?, ?, 'open')"
    );
    $stmt->execute([
        $item['id'],
        "Possible match found: \"{$item['item_name']}\"",
        $adminMessage,
        $finder_contact ?: null,
    ]);
} catch (PDOException $e) {
    // reports table missing/outdated -- the student notification below still goes out,
    // so the loop isn't fully broken even if admin can't see it in reports.php yet
}

// --- lightweight heads-up to the student -- no dead end, but no raw finder contact either ---
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$item['user_id']]);
$owner_name = $stmt->fetchColumn() ?: 'there';

$alert_email = email_found_alert($owner_name, $item['item_name']);
notify_user(
    $pdo,
    $item['user_id'],
    'found_alert',
    "Someone scanned your \"{$item['item_name']}\" tag and reported finding it. The admin team is verifying — you'll be notified once it's confirmed.",
    $item['id'],
    $item['owner_email'],
    $alert_email['subject'],
    $alert_email['html'],
    $alert_email['text']
);

header("Location: ../scan.php?tag=" . urlencode($tag) . "&reported=1");
exit();
