<?php
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

// Create upload directories if they don't exist
foreach ([INVOICES_DIR, PHOTOS_DIR, VIDEOS_DIR, QRCODES_DIR] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Base URL for QR codes
define('BASE_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/');

// Security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 1); // Requires HTTPS

// File upload limits (in bytes)
define('MAX_INVOICE_SIZE', 20 * 1024 * 1024); // 20MB max for Pro plan
define('MAX_PHOTO_SIZE', 5 * 1024 * 1024);   // 5MB per photo
define('ALLOWED_FILE_TYPES', ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'mp4' => 'video/mp4']);
?>