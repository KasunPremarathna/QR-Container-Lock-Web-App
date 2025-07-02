<?php
// File Path: qr-container-lock/pages/company/create_container.php

session_start();
require_once '../../includes/auth.php';
require_once '../../config/config.php';

requireLogin();
if (isAdmin()) {
    header("Location: ../admin/dashboard.php");
    exit;
}

// Fetch company plan details
$stmt = $pdo->prepare("SELECT p.* FROM companies c JOIN plans p ON c.plan_id = p.id WHERE c.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle container creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_container') {
    $container_id = filter_input(INPUT_POST, 'container_id', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $destination = filter_input(INPUT_POST, 'destination', FILTER_SANITIZE_STRING);
    $token = bin2hex(random_bytes(16));
    
    // Validate inputs
    if (empty($container_id) || empty($description) || empty($destination)) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        // Check container limit
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM containers WHERE company_id = ? AND MONTH(created_at) = MONTH(NOW())");
        $stmt->execute([$_SESSION['user_id']]);
        $container_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        if ($plan['containers_per_month'] > 0 && $container_count >= $plan['containers_per_month']) {
            $_SESSION['error'] = "Monthly container limit reached.";
        } else {
            // Handle invoice upload
            $invoice_path = '';
            if (isset($_FILES['invoice']) && $_FILES['invoice']['error'] === UPLOAD_ERR_OK) {
                $file_size = $_FILES['invoice']['size'];
                $file_type = mime_content_type($_FILES['invoice']['tmp_name']);
                
                if ($file_size > $plan['invoice_size_limit_mb'] * 1024 * 1024) {
                    $_SESSION['error'] = "Invoice file exceeds size limit ({$plan['invoice_size_limit_mb']} MB).";
                } elseif (!in_array($file_type, [ALLOWED_FILE_TYPES['pdf']])) {
                    $_SESSION['error'] = "Only PDF files are allowed for invoices.";
                } else {
                    $invoice_path = INVOICES_DIR . time() . '_' . basename($_FILES['invoice']['name']);
                    move_uploaded_file($_FILES['invoice']['tmp_name'], $invoice_path);
                }
            }
            
            // Handle photo uploads
            $photo_paths = [];
            if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                $photo_count = count($_FILES['photos']['name']);
                if ($photo_count > $plan['photos_per_container']) {
                    $_SESSION['error'] = "Too many photos. Maximum allowed: {$plan['photos_per_container']}.";
                } else {
                    foreach ($_FILES['photos']['tmp_name'] as $index => $tmp_name) {
                        if ($_FILES['photos']['error'][$index] === UPLOAD_ERR_OK) {
                            $file_size = $_FILES['photos']['size'][$index];
                            $file_type = mime_content_type($tmp_name);
                            
                            if ($file_size > MAX_PHOTO_SIZE) {
                                $_SESSION['error'] = "Photo {$index + 1} exceeds size limit (5 MB).";
                                break;
                            } elseif (!in_array($file_type, [ALLOWED_FILE_TYPES['jpg'], ALLOWED_FILE_TYPES['png']])) {
                                $_SESSION['error'] = "Photo {$index + 1} must be JPG or PNG.";
                                break;
                            } else {
                                $photo_path = PHOTOS_DIR . time() . '_' . basename($_FILES['photos']['name'][$index]);
                                move_uploaded_file($tmp_name, $photo_path);
                                $photo_paths[] = $photo_path;
                            }
                        }
                    }
                }
            }
            
            // Handle video upload (if allowed)
            $video_path = '';
            if ($plan['video_upload_allowed'] && isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
                $file_size = $_FILES['video']['size'];
                $file_type = mime_content_type($_FILES['video']['tmp_name']);
                
                if ($file_size > MAX_PHOTO_SIZE) {
                    $_SESSION['error'] = "Video file exceeds size limit (5 MB).";
                } elseif (!in_array($file_type, [ALLOWED_FILE_TYPES['mp4']])) {
                    $_SESSION['error'] = "Only MP4 videos are allowed.";
                } else {
                    $video_path = VIDEOS_DIR . time() . '_' . basename($_FILES['video']['name']);
                    move_uploaded_file($_FILES['video']['tmp_name'], $video_path);
                }
            }
            
            // Insert container if no errors
            if (!isset($_SESSION['error'])) {
                $stmt = $pdo->prepare("INSERT INTO containers (company_id, container_id, description, destination, invoice_path, token, status) 
                                       VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                if ($stmt->execute([$_SESSION['user_id'], $container_id, $description, $destination, $invoice_path, $token])) {
                    $container_id = $pdo->lastInsertId();
                    
                    // Insert photos
                    foreach ($photo_paths as $photo_path) {
                        $stmt = $pdo->prepare("INSERT INTO photos (container_id, photo_path) VALUES (?, ?)");
                        $stmt->execute([$container_id, $photo_path]);
                    }
                    
                    // Insert video
                    if ($video_path) {
                        $stmt = $pdo->prepare("INSERT INTO videos (container_id, video_path) VALUES (?, ?)");
                        $stmt->execute([$container_id, $video_path]);
                    }
                    
                    $_SESSION['success'] = "Container created successfully! Awaiting admin approval.";
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $_SESSION['error'] = "Failed to create container.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Container - QR Container Lock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container max-w-4xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Create New Container</h1>
            <a href="dashboard.php" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Back to Dashboard</a>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow-md rounded-lg p-6">
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="create_container">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Container ID</label>
                    <input type="text" name="container_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Destination</label>
                    <input type="text" name="destination" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Invoice (PDF, max <?php echo $plan['invoice_size_limit_mb']; ?> MB)</label>
                    <input type="file" name="invoice" class="mt-1 block w-full text-gray-500" accept=".pdf">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Photos (JPG/PNG, max <?php echo $plan['photos_per_container']; ?> files, 5 MB each)</label>
                    <input type="file" name="photos[]" multiple class="mt-1 block w-full text-gray-500" accept=".jpg,.png">
                </div>
                <?php if ($plan['video_upload_allowed']): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Video (MP4, max 5 MB)</label>
                        <input type="file" name="video" class="mt-1 block w-full text-gray-500" accept=".mp4">
                    </div>
                <?php endif; ?>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Create Container</button>
            </form>
        </div>
    </div>
</body>
</html>
?>