<?php
// File Path: qr-container-lock/config/config.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'kasunpre_qr_container_lock');
define('DB_USER', 'kasunpre_qr_container_lock');
define('DB_PASS', 'Kasun0147');

// Upload directories
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('INVOICES_DIR', UPLOAD_DIR . 'invoices/');
define('PHOTOS_DIR', UPLOAD_DIR . 'photos/');
define('VIDEOS_DIR', UPLOAD_DIR . 'videos/');
define('QRCODES_DIR', UPLOAD_DIR . 'qrcodes/');

// Create upload directories if they don't exist with error handling
$dirs = [INVOICES_DIR, PHOTOS_DIR, VIDEOS_DIR, QRCODES_DIR];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0755, true)) {
            error_log("Error: Failed to create directory $dir");
            die("Server error: Unable to create upload directory.");
        }
    }
    if (!is_writable($dir)) {
        error_log("Error: Directory $dir is not writable");
        die("Server error: Upload directory not writable.");
    }
}

// Database connection with enhanced error handling
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed: " . $e->getMessage());
}

// Base URL for QR codes
define('BASE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/');

// File upload limits (in bytes)
define('MAX_INVOICE_SIZE', 20 * 1024 * 1024); // 20MB max for Pro plan
define('ALLOWED_FILE_TYPES', ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'mp4' => 'video/mp4']);
?>