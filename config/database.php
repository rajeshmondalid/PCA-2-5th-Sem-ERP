<?php
/**
 * Database Connection Configuration using PDO
 * Employee Management System
 */

// Database Credentials - update these to match your local or production MySQL server
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'employee_management_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a singleton PDO database connection instance.
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Detailed message for developers, safe fallback for users
            die('
            <div style="font-family: system-ui, sans-serif; max-width: 650px; margin: 50px auto; padding: 25px; border-radius: 8px; border: 1px solid #f87171; background: #fef2f2; color: #991b1b;">
                <h2 style="margin-top:0;">Database Connection Failed</h2>
                <p>Could not connect to the database <strong>' . htmlspecialchars(DB_NAME) . '</strong> on host <strong>' . htmlspecialchars(DB_HOST) . '</strong>.</p>
                <p><strong>Error Details:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>
                <hr style="border: 0; border-top: 1px solid #fca5a5; margin: 15px 0;">
                <p style="margin-bottom: 0;"><strong>Setup Instructions:</strong></p>
                <ol style="margin-top: 5px; padding-left: 20px;">
                    <li>Ensure your MySQL service is running in XAMPP, WAMP, or your local server.</li>
                    <li>Import <code>database/schema.sql</code> and <code>database/seed.sql</code> into your MySQL server.</li>
                    <li>Check credentials in <code>config/database.php</code> or run <a href="install.php" style="color: #1d4ed8;">install.php</a>.</li>
                </ol>
            </div>');
        }
    }

    return $pdo;
}
