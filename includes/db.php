<?php
// includes/db.php

// 1. Database Configuration (Uses config.php constants if defined, defaults to XAMPP)
$dbHost = defined('DB_HOST') ? DB_HOST : '127.0.0.1';
$dbPort = defined('DB_PORT') ? DB_PORT : '3306';
$dbName = defined('DB_NAME') ? DB_NAME : 'autilearn';
$dbUser = defined('DB_USER') ? DB_USER : 'root';
$dbPass = defined('DB_PASS') ? DB_PASS : '';

// 2. Prevent Duplicate Connection Attempts
if (!isset($pdo) || !($pdo instanceof PDO)) {
    try {
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    } catch (PDOException $e) {
        // Log details internally for server logs
        error_log("AutiLearn DB Error: " . $e->getMessage());

        // Styled diagnostic UI for local development
        die("
        <div style='max-width: 560px; margin: 60px auto; padding: 24px; border-radius: 16px; background-color: #fef2f2; border: 1px solid #fecaca; font-family: system-ui, -apple-system, sans-serif; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
            <h3 style='color: #dc2626; margin-top: 0; display: flex; align-items: center; gap: 8px;'>
                <span>⚠️</span> Database Connection Failed
            </h3>
            <p style='color: #7f1d1d; font-size: 14px; background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #fee2e2;'>
                <strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "
            </p>
            <hr style='border: none; border-top: 1px solid #fca5a5; margin: 16px 0;'>
            <p style='color: #991b1b; font-size: 13px; line-height: 1.6; margin-bottom: 0;'>
                <strong>Troubleshooting Checklist:</strong><br>
                • Ensure <strong>MySQL</strong> is turned ON in XAMPP Control Panel.<br>
                • Verify that database <code>" . htmlspecialchars($dbName) . "</code> is created in <a href='http://localhost/phpmyadmin' target='_blank' style='color: #b91c1c; font-weight: bold;'>phpMyAdmin</a>.<br>
                • Check if MySQL runs on a custom port (e.g. <code>3307</code> instead of <code>3306</code>).
            </p>
        </div>
        ");
    }
}
?>