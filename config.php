<?php
ob_start();
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mediconnect360');

// URL Configuration
define('BASE_URL', 'http://localhost/mediconnect360/');
define('ASSETS_URL', BASE_URL . 'assets/');
define('UPLOADS_URL', BASE_URL . 'uploads/');
define('UPLOADS_PATH', __DIR__ . '/uploads/');

// Jitsi Configuration (self-hosted + JWT)
// Update these values for your deployment.
define('JITSI_DOMAIN', 'jitsi.localhost');
define('JITSI_APP_ID', 'mediconnect360');
define('JITSI_ISSUER', 'mediconnect360');
define('JITSI_JWT_SECRET', 'change_this_to_a_strong_secret');

// Database Connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Include Core Functions
require_once __DIR__ . '/includes/functions.php';
?>
