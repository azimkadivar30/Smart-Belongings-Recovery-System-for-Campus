<?php
/**
 * Email / SMTP Configuration
 * Smart Belonging System for Campus
 *
 * ============================================================
 * HOW TO GET A GMAIL APP PASSWORD (takes ~2 minutes)
 * ============================================================
 * Gmail will NOT let you send through a normal account password
 * from an app like this -- you need an "App Password" instead.
 *
 * 1. Go to https://myaccount.google.com/security
 * 2. Turn on "2-Step Verification" if it isn't already on
 *    (App Passwords only appear once 2FA is enabled)
 * 3. Go to https://myaccount.google.com/apppasswords
 * 4. Under "App name", type something like "Smart Belonging System"
 *    and click Create
 * 5. Google shows you a 16-character password like: abcd efgh ijkl mnop
 *    Copy it WITHOUT spaces: abcdefghijklmnop
 * 6. Paste it below as MAIL_SMTP_PASSWORD
 *
How it should create an account so how much tau is basically mention output it different than one types of documents are tested for all details
 * (it can be your own personal/college Gmail -- it's just the sender).
 * ============================================================
 */

// The Gmail address that will SEND the emails (e.g. your own Gmail)
define('MAIL_SMTP_USERNAME', 'smartbelongingsystemadmin@gmail.com');

// The 16-character App Password from step 5 above (NOT your normal Gmail password)
define('MAIL_SMTP_PASSWORD', 'prywzgvfotevctxj');

// What recipients see as the sender name + reply-to address
define('MAIL_FROM_NAME', 'Smart Belonging System');
define('MAIL_FROM_ADDRESS', 'smartbelongingsystemadmin@gmail.com'); // Gmail requires From = the authenticated account

// Gmail SMTP settings -- these don't need to change
define('MAIL_SMTP_HOST', 'smtp.gmail.com');
define('MAIL_SMTP_PORT', 587);
define('MAIL_SMTP_SECURE', 'tls');

// Set to true only while debugging delivery issues -- prints raw SMTP
// conversation to the page, which is noisy and NOT safe to leave on
// in front of anyone else (it can expose parts of the handshake).
define('MAIL_DEBUG', false);
