<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Guard against a "stale" session: the browser still has a login cookie for a
// user_id that no longer exists in the database (e.g. the DB was reset or
// re-imported while someone was logged in). Without this check, any insert/
// query that relies on the session's user_id -- like reporting an item --
// crashes with a foreign key / not-found error instead of just asking the
// person to log in again.
$stmt = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$sessionUser = $stmt->fetch();

if (!$sessionUser) {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['login_error'] = "Your session has expired. Please sign in again.";
    header("Location: login.php");
    exit();
}

// Keep session data in sync with the database (covers profile edits too)
$_SESSION['full_name'] = $sessionUser['full_name'];
$_SESSION['email']     = $sessionUser['email'];
$_SESSION['role']      = $sessionUser['role'];

// Optional role guard: pass a role name to require it
function require_role($role) {
    if (($_SESSION['role'] ?? '') !== $role) {
        header("Location: login.php");
        exit();
    }
}
