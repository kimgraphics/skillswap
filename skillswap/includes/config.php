<?php
// Error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session management
session_start();

// Base URL configuration
define('BASE_URL', 'http://localhost/skillswap/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'skillswap_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// File upload configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('UPLOAD_DIR', 'assets/images/avatars/');
?>