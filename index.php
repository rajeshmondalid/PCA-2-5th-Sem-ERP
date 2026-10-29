<?php
/**
 * Application Entry Point
 * Redirects authenticated users to dashboard and guests to login.
 */
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    redirect('dashboard.php');
} else {
    redirect('auth/login.php');
}
