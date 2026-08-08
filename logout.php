<?php
// logout.php
require_once __DIR__ . '/includes/config.php';

// Clear all session variables stored in memory
$_SESSION = array();

// If session cookie deletion is enabled, wipe the PHPSESSID cookie using configured params
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session storage on the server
session_destroy();

// Redirect to home page or login screen using absolute BASE_URL
$redirectUrl = defined('BASE_URL') ? BASE_URL . 'login.php?logout=success' : 'login.php';
header("Location: {$redirectUrl}");
exit();
?>