<?php
/**
 * Global Application Configuration & Helper Functions
 * Employee Management System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Configure secure session cookie settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// Application settings
define('APP_NAME', 'StaffPulse EMS');
define('APP_VERSION', '1.0.0');

// Calculate dynamic base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

// Traverse up if inside subfolders (e.g. /employees, /auth, /roles, /attendance, /leaves, /payroll, /departments, /notices)
$appRoot = preg_replace('/\/(employees|roles|auth|attendance|leaves|payroll|departments|notices)$/', '', $scriptDir);
$appRoot = rtrim($appRoot, '/');
define('BASE_URL', $protocol . $host . $appRoot);

// Include database & auth helpers
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Escape HTML output to prevent XSS attacks.
 *
 * @param string|null $string
 * @return string
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to an internal application path.
 *
 * @param string $path (relative to BASE_URL, e.g. '/dashboard.php')
 */
function redirect(string $path): void {
    $cleanPath = ltrim($path, '/');
    header('Location: ' . BASE_URL . '/' . $cleanPath);
    exit;
}

/**
 * Store a flash message for the next request.
 *
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear the flash message.
 *
 * @return array|null
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format currency amount.
 *
 * @param float|string $amount
 * @return string
 */
function formatCurrency($amount): string {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Format standard date (e.g. Oct 27, 2026).
 *
 * @param string|null $date
 * @return string
 */
function formatDate(?string $date): string {
    if (!$date) return 'N/A';
    return date('M d, Y', strtotime($date));
}

/**
 * Generate CSRF Token for form protection.
 *
 * @return string
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token from POST request.
 *
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token): bool {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Enforce CSRF check or terminate with HTTP 403.
 */
function enforceCsrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($token)) {
            http_response_code(403);
            die('
            <div style="font-family: sans-serif; max-width: 500px; margin: 40px auto; padding: 20px; border: 1px solid #ef4444; border-radius: 8px; background: #fff5f5; color: #b91c1c;">
                <h3>403 Forbidden - Invalid CSRF Token</h3>
                <p>The security validation token for this request was missing or expired. Please refresh the page and try again.</p>
                <a href="javascript:history.back()" style="color: #2563eb;">&larr; Go Back</a>
            </div>');
        }
    }
}
