<?php
/**
 * Handles the "Email Admin" button on item_details.php.
 * Sends a real email to the admin inbox via Gmail SMTP (see mail.php),
 * with Reply-To set to the student's own email so the admin can just
 * hit Reply. Replaces the old mailto: link, which silently did nothing
 * on machines with no default mail client configured.
 */
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) {
    session_unset(); session_destroy();
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../my_items.php");
    exit();
}

$item_id = (int)($_POST['item_id'] ?? 0) ?: null;
$item_name = null;
$report_id = null;

if ($item_id) {
    // Only allow contacting admin about items the logged-in user actually owns.
    $stmt = $pdo->prepare("SELECT id, item_name FROM items WHERE id = ? AND user_id = ?");
    $stmt->execute([$item_id, $user['id']]);
    $item = $stmt->fetch();
    if ($item) {
        $item_name = $item['item_name'];
        $report_id = str_pad((string)$item['id'], 4, '0', STR_PAD_LEFT);
    } else {
        $item_id = null; // ignore a spoofed/foreign item_id rather than error out
    }
}

require_once __DIR__ . '/../mail.php';

$tpl = email_admin_contact_request($user['full_name'], $user['email'], $item_name, $report_id);
send_notification_email(
    MAIL_FROM_ADDRESS,      // admin inbox
    $tpl['subject'],
    $tpl['html'],
    $tpl['text'],
    $user['email'],         // Reply-To: the student
    $user['full_name']
);

// Always redirect back the same way regardless of SMTP success/failure --
// the admin desk email link is a courtesy, not something that should ever
// block or error out the page for the student.
$redirect = $item_id ? "../item_details.php?id={$item_id}&contact=sent" : "../my_items.php?contact=sent";
header("Location: " . $redirect);
exit();
