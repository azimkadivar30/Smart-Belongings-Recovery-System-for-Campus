<?php
/**
 * Email Handler
 * Smart Belonging System for Campus
 *
 * This is an include-only helper, not a page to visit directly.
 * Sends real email through Gmail's SMTP relay using PHPMailer, so
 * messages actually land in the recipient's Gmail inbox instead of
 * silently failing like PHP's bare mail() does on XAMPP.
 *
 * SETUP: open includes/mail_config.php and follow the instructions
 * there to add your Gmail address + App Password. Nothing else in
 * this file needs to change.
 */

if (!defined('SBS_APP')) {
    define('SBS_APP', true);
}

require_once __DIR__ . '/includes/mail_config.php';
require_once __DIR__ . '/includes/email_templates.php';
require_once __DIR__ . '/includes/PHPMailer/Exception.php';
require_once __DIR__ . '/includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Send a notification email through Gmail SMTP.
 *
 * @param string      $to        recipient email address
 * @param string      $subject   email subject
 * @param string      $htmlBody  branded HTML body (build with includes/email_templates.php)
 * @param string|null $textBody  plain-text fallback for clients that block HTML;
 *                                auto-derived from $htmlBody if omitted
 * @param string|null $replyTo   optional Reply-To address (e.g. the user's own email on an
 *                                admin-contact request), so replying goes straight to them
 * @param string|null $replyToName optional display name to pair with $replyTo
 * @return bool true if Gmail accepted the message for delivery, false otherwise
 */
function send_notification_email(string $to, string $subject, string $htmlBody, ?string $textBody = null, ?string $replyTo = null, ?string $replyToName = null): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_SMTP_USERNAME;
        $mail->Password   = MAIL_SMTP_PASSWORD;
        $mail->SMTPSecure = MAIL_SMTP_SECURE;
        $mail->Port       = MAIL_SMTP_PORT;
        $mail->SMTPDebug  = MAIL_DEBUG ? 2 : 0;
        $mail->Timeout    = 10; // seconds -- don't let a network/SMTP hiccup hang the whole page

        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);
        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($replyTo, $replyToName ?? '');
        }
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody ?? email_plain_fallback($htmlBody);

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        // Logged for the developer, but never crashes the page -- the
        // in-app notification (see notify_user() below) still goes
        // through even if the real email fails to send.
        error_log('[mail.php] Email failed to ' . $to . ': ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Convenience wrapper: log an in-app notification AND attempt an email,
 * so the two always stay in sync.
 *
 * @param PDO    $pdo
 * @param int    $user_id
 * @param string $type     one of: found, review, pickup, system, closed, found_alert
 * @param string $message  short in-app message (also used as the email body
 *                          if $email is set but no $email_html template is given)
 * @param int|null $item_id
 * @param string|null $email        if provided, also emails this address
 * @param string|null $email_subject
 * @param string|null $email_html   pre-built branded HTML body (see includes/email_templates.php);
 *                                   falls back to wrapping $message via email_generic_notice()
 * @param string|null $email_text   optional plain-text fallback to pair with $email_html
 * @param string|null $full_name    recipient's display name, used only by the generic-notice fallback
 */
function notify_user(
    PDO $pdo,
    int $user_id,
    string $type,
    string $message,
    ?int $item_id = null,
    ?string $email = null,
    ?string $email_subject = null,
    ?string $email_html = null,
    ?string $email_text = null,
    ?string $full_name = null
): void {
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, item_id, type, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $item_id, $type, $message]);

    if ($email) {
        if ($email_html === null) {
            $generic = email_generic_notice($full_name ?? 'there', $message);
            $email_html    = $generic['html'];
            $email_text    = $generic['text'];
            $email_subject = $email_subject ?? $generic['subject'];
        }
        send_notification_email($email, $email_subject ?? 'Smart Belonging System update', $email_html, $email_text);
    }
}
