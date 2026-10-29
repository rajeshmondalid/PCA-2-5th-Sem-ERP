<?php
/**
 * User Logout Script
 * Employee Management System
 */
require_once __DIR__ . '/../config/config.php';

// Unset all session variables
$_SESSION = [];

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a fresh session to carry the flash message
session_start();
setFlash('info', 'You have been successfully signed out.');
redirect('auth/login.php');
