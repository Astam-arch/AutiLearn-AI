<?php
// includes/config.php

// 1. Debugging Mode (Uncomment during development to see hidden PHP errors)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// 2. Configure & Start Session globally
if (session_status() === PHP_SESSION_NONE) {
    // Scope session cookies to root '/' so sessions persist across all subfolders (/student, /parent, etc.)
    session_set_cookie_params([
        'lifetime' => 86400, // 24 Hours
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// 3. System Constants
define('SITE_NAME', 'Spark Steps');
define('SITE_TAGLINE', 'Empowering Neurodiverse Children Through Inclusive Spark Steps Learning.');

// 4. Robust BASE_URL Resolution
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host     = $_SERVER['HTTP_HOST'];

// Normalize physical paths (replaces Windows backslashes with forward slashes)
$docRoot     = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$projectRoot = str_replace('\\', '/', dirname(__DIR__)); // Go up 1 level from 'includes'

// Compute relative path from server root to project folder
$relativeSubpath = str_replace($docRoot, '', $projectRoot);
$relativeSubpath = '/' . trim($relativeSubpath, '/') . '/';
if ($relativeSubpath === '//') {
    $relativeSubpath = '/';
}

define('BASE_URL', $protocol . '://' . $host . $relativeSubpath);

// 5. Navigation Helper
function isActivePage($pageName) {
    $currentPage = basename($_SERVER['PHP_SELF']);
    return ($currentPage === $pageName) ? 'active' : '';
}
?>