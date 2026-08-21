<?php
/**
 * HTML Email Templates
 * Smart Belonging System for Campus
 *
 * Every outbound email is built here so the whole app sends one
 * consistent, branded look instead of ad-hoc plain-text strings.
 * Email clients don't support external stylesheets or CSS variables,
 * so colors/spacing are hard-coded inline (mirrored from
 * assets/css/style.css: deep #355872, mid #7AAACE, paper #F7F8F0).
 *
 * Each `email_*()` function below returns ['subject' => ..., 'html' => ..., 'text' => ...]
 * ready to hand straight to send_notification_email() / notify_user().
 */

if (!defined('SBS_APP')) {
    define('SBS_APP', true);
}

/**
 * Wraps a block of body HTML in the shared branded shell (header bar,
 * white card, footer). $bodyHtml should be plain inline-styled HTML
 * (paragraphs, boxes, buttons) -- see the helper builders below.
 */
function email_layout(string $heading, string $bodyHtml): string
{
    $year = date('Y');
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$heading}</title>
</head>
<body style="margin:0; padding:0; background:#F7F8F0; font-family:'Segoe UI',Helvetica,Arial,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F8F0; padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background:#FFFFFF; border-radius:16px; overflow:hidden; box-shadow:0 4px 18px rgba(30,46,56,0.08);">
          <tr>
            <td style="background:linear-gradient(145deg,#355872,#7AAACE); padding:22px 28px;">
              <span style="color:#FFFFFF; font-size:17px; font-weight:700; letter-spacing:0.2px;">🎒 Smart Belonging System</span>
            </td>
          </tr>
          <tr>
            <td style="padding:30px 28px 10px;">
              <h2 style="margin:0 0 16px; color:#355872; font-size:20px; font-weight:700;">{$heading}</h2>
              {$bodyHtml}
            </td>
          </tr>
          <tr>
            <td style="padding:20px 28px 26px;">
              <hr style="border:none; border-top:1px solid #EEF1EC; margin:0 0 16px;">
              <p style="margin:0; color:#5B6C77; font-size:12px; line-height:1.6;">
                This is an automated message from Smart Belonging System for Campus. Please don't reply directly to this email.
                &copy; {$year} Smart Belonging System.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
HTML;
}

/** Standard body paragraph. */
function email_p(string $text): string
{
    return "<p style=\"margin:0 0 14px; color:#1E2E38; font-size:14.5px; line-height:1.65;\">{$text}</p>";
}

/** Rounded brand-colored call-to-action button, centered. */
function email_button(string $text, string $url): string
{
    return "<table role=\"presentation\" cellpadding=\"0\" cellspacing=\"0\" style=\"margin:20px 0;\"><tr><td style=\"border-radius:10px; background:#355872;\">"
        . "<a href=\"{$url}\" style=\"display:inline-block; padding:12px 26px; color:#FFFFFF; font-size:14px; font-weight:600; text-decoration:none; border-radius:10px;\">{$text}</a>"
        . "</td></tr></table>";
}

/** Large, spaced-out monospace code display -- used for OTP / verification codes. */
function email_code_box(string $code): string
{
    $spaced = implode(' ', str_split($code));
    return "<div style=\"margin:18px 0; padding:16px; background:#F7F8F0; border:1.5px dashed #D7E1E6; border-radius:12px; text-align:center;\">"
        . "<span style=\"font-family:'Courier New',monospace; font-size:26px; font-weight:700; letter-spacing:4px; color:#355872;\">{$spaced}</span>"
        . "</div>";
}

/** Muted key/value info box -- used for collection details, item summaries. */
function email_info_box(string $innerHtml): string
{
    return "<div style=\"margin:16px 0; padding:14px 16px; background:#F7F8F0; border-radius:12px; font-size:14px; color:#1E2E38; line-height:1.7;\">{$innerHtml}</div>";
}

/** Rough plain-text fallback derived from the HTML, for AltBody. */
function email_plain_fallback(string $html): string
{
    $text = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
    $text = preg_replace('/<(br|\/p|\/div|\/tr|\/h[1-6])\s*\/?>/i', "\n", $text);
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    return trim($text);
}

// ---------------------------------------------------------------------
// Per-event templates
// ---------------------------------------------------------------------

function email_registration(string $full_name, string $verify_link): array
{
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p("Thanks for registering with Smart Belonging System. Please verify your email address to activate your account.")
        . email_button("Verify My Email", $verify_link)
        . email_p("This link expires in <strong>24 hours</strong>. If you didn't create this account, you can safely ignore this email.");
    $html = email_layout("Verify your account", $body);
    return ['subject' => 'Verify your Smart Belonging System account', 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_resend_verification(string $full_name, string $verify_link): array
{
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p("Here's your new verification link, as requested.")
        . email_button("Verify My Email", $verify_link)
        . email_p("This link expires in <strong>24 hours</strong>.");
    $html = email_layout("Your new verification link", $body);
    return ['subject' => 'Verify your Smart Belonging System account', 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_verified_confirmation(string $full_name, string $login_url): array
{
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p("Your email address has been verified successfully. Your account is now active and ready to use.")
        . email_button("Go to Login", $login_url);
    $html = email_layout("You're verified! ✅", $body);
    return ['subject' => 'Your Smart Belonging System account is verified', 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_item_submitted(string $full_name, string $item_name, string $report_type, string $location, string $item_date): array
{
    $kind = $report_type === 'found' ? 'Found' : 'Lost';
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p("Your <strong>{$kind} item</strong> report has been submitted and is now under review by our admin team.")
        . email_info_box(
            "<strong>Item:</strong> " . htmlspecialchars($item_name) . "<br>"
            . "<strong>Type:</strong> {$kind}<br>"
            . "<strong>Location:</strong> " . htmlspecialchars($location) . "<br>"
            . "<strong>Date:</strong> " . htmlspecialchars($item_date)
        )
        . email_p("We'll email you again as soon as there's an update.");
    $html = email_layout("{$kind} item report received", $body);
    return ['subject' => "We've received your {$kind} item report: {$item_name}", 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_status_update(string $full_name, string $item_name, string $status, ?string $collection_details = null): array
{
    $name = '"<strong>' . htmlspecialchars($item_name) . '</strong>"';
    $copy = [
        'pending'   => ['heading' => 'Report under review', 'text' => "Your report for {$name} is still under review by our admin team."],
        'found'     => ['heading' => 'Good news -- item found! 🎉', 'text' => "Your item {$name} has been found! Verify ownership with the code we've emailed separately, then collect it using the details below."],
        'not_found' => ['heading' => 'No match found yet', 'text' => "Your item {$name} could not be located after admin review. You're welcome to re-report it with more details."],
        'collected' => ['heading' => 'Item collected -- case closed ✅', 'text' => "Your item {$name} has been marked as collected. Thanks for using Smart Belonging System!"],
    ];
    $c = $copy[$status] ?? $copy['pending'];

    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p($c['text']);

    if ($status === 'found' && $collection_details) {
        $body .= email_info_box(nl2br(htmlspecialchars($collection_details)));
    }

    $html = email_layout($c['heading'], $body);
    return ['subject' => "Update on your item: {$item_name}", 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_otp_code(string $full_name, string $item_name, string $code): array
{
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p("Your item <strong>\"" . htmlspecialchars($item_name) . "\"</strong> has been marked as Found. To confirm this is really yours, enter the code below on the item's page in Smart Belonging System.")
        . email_code_box($code)
        . email_p("An admin will need this confirmed before the item can be released to you at the collection desk.");
    $html = email_layout("Verify ownership", $body);
    return ['subject' => "Verify ownership of your {$item_name}", 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_device_otp(string $full_name, string $device_name, string $code): array
{
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p("An admin is verifying ownership of your device <strong>\"" . htmlspecialchars($device_name) . "\"</strong> before releasing it back to you. Read the code below to the admin at the collection desk -- do not share it with anyone else.")
        . email_code_box($code)
        . email_p("This code expires in <strong>10 minutes</strong> and can only be used once.");
    $html = email_layout("Your device recovery code", $body);
    return ['subject' => "Recovery code for {$device_name}", 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_device_registered(string $full_name, string $device_name, string $qr_token, string $scan_url): array
{
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p("Your gadget <strong>" . htmlspecialchars($device_name) . "</strong> has been registered in Smart Belonging System and a smart QR tag has been generated for it. Print it and stick it on the device -- anyone who scans it can let you know it's been found without ever seeing your personal details.")
        . email_info_box("<strong>Tag ID:</strong> " . htmlspecialchars($qr_token))
        . email_button("View Your Devices", $scan_url)
        . email_p("You can re-download this tag any time from the My Devices page in your dashboard.");
    $html = email_layout("Your device is registered", $body);
    return ['subject' => "Device registered: {$device_name}", 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_device_status_update(string $full_name, string $device_name, string $status): array
{
    $copy = [
        'active'    => ['heading' => 'Device marked active', 'text' => "Your device \"<strong>" . htmlspecialchars($device_name) . "</strong>\" is now marked as active in Smart Belonging System."],
        'lost'      => ['heading' => 'Device marked lost', 'text' => "Your device \"<strong>" . htmlspecialchars($device_name) . "</strong>\" has been marked as lost. If someone scans its QR tag, you'll be notified straight away."],
        'recovered' => ['heading' => 'Device recovered 🎉', 'text' => "Good news -- your device \"<strong>" . htmlspecialchars($device_name) . "</strong>\" has been marked as recovered."],
    ];
    $c = $copy[$status] ?? $copy['active'];
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p($c['text']);
    $html = email_layout($c['heading'], $body);
    return ['subject' => "Update on your device: {$device_name}", 'html' => $html, 'text' => email_plain_fallback($html)];
}

function email_found_alert(string $full_name, string $item_name): array
{
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p("Someone scanned the QR tag on your <strong>\"" . htmlspecialchars($item_name) . "\"</strong> and reported finding it.")
        . email_p("Our admin team is verifying the report -- you'll get another email as soon as it's confirmed.");
    $html = email_layout("Someone may have found your item", $body);
    return ['subject' => "Someone may have found your {$item_name}", 'html' => $html, 'text' => email_plain_fallback($html)];
}

/**
 * Sent TO the admin when a user clicks "Email Admin" on an item's detail
 * page. Reply-To is set to the user's own address (see process/contact_admin.php)
 * so the admin can just hit Reply instead of copying the address by hand.
 */
function email_admin_contact_request(string $full_name, string $user_email, ?string $item_name, ?string $report_id): array
{
    $itemLine = $item_name
        ? ("<strong>Item:</strong> " . htmlspecialchars($item_name) . "<br><strong>Report ID:</strong> #SBS-" . htmlspecialchars((string)$report_id))
        : "<em>General inquiry -- not tied to a specific item.</em>";

    $body = email_p("A student has requested to get in touch about an item report.")
        . email_info_box(
            "<strong>From:</strong> " . htmlspecialchars($full_name) . " (" . htmlspecialchars($user_email) . ")<br>"
            . $itemLine
        )
        . email_p("Reply directly to this email to respond to " . htmlspecialchars($full_name) . ".");
    $html = email_layout("New admin contact request", $body);
    $subject = $report_id ? "Contact request -- Report #SBS-{$report_id}" : "Contact request from {$full_name}";
    return ['subject' => $subject, 'html' => $html, 'text' => email_plain_fallback($html)];
}

/**
 * Fallback wrapper for callers that only have a short plain-text message
 * and no dedicated template (keeps every email branded, even ad-hoc ones).
 */
function email_generic_notice(string $full_name, string $message): array
{
    $body = email_p("Hi " . htmlspecialchars($full_name) . ",")
        . email_p(htmlspecialchars($message));
    $html = email_layout("Update from Smart Belonging System", $body);
    return ['subject' => 'Smart Belonging System update', 'html' => $html, 'text' => email_plain_fallback($html)];
}
