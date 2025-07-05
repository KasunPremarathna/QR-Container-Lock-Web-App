<?php
// File Path: qr-container-lock/pages/public/view.php

// Enable error logging
ini_set('display_errors', 0); // Disable for production
ini_set('log_errors', 1);
error_log("Starting view.php");

require_once '../../config/config.php';

// Initialize container data
$container = null;
$photos = [];
$videos = [];
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if ($token) {
    try {
        // Fetch container details
        $stmt = $pdo->prepare("SELECT id, container_id, description, destination, status, invoice_path FROM containers WHERE token = ? AND status != 'rejected'");
        $stmt->execute(array($token));
        $container = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($container) {
            // Fetch photos
            $stmt = $pdo->prepare("SELECT path FROM photos WHERE container_id = ?");
            $stmt->execute(array($container['id']));
            $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch videos
            $stmt = $pdo->prepare("SELECT path FROM videos WHERE container_id = ?");
            $stmt->execute(array($container['id']));
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            error_log("Error: Invalid or rejected token $token in view.php");
            $error = "Invalid QR code or container not found.";
        }
    } catch (PDOException $e) {
        error_log("Database error (fetch container or media) in view.php: " . $e->getMessage());
        $error = "Server error: Unable to fetch container or media details.";
    }
} else {
    $error = "No QR code token provided.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Container - QR Container Lock</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container max-w-2xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Container Details</h1>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php elseif ($container): ?>
            <div class="bg-white shadow-md rounded-lg p-6">
                <p><strong>Container ID:</strong> <?php echo htmlspecialchars($container['container_id']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($container['description']); ?></p>
                <p><strong>Destination:</strong> <?php echo htmlspecialchars($container['destination']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($container['status']); ?></p>

                <?php if ($container['invoice_path']): ?>
                    <p class="mt-4">
                        <strong>Invoice:</strong>
                        <a href="<?php echo htmlspecialchars(BASE_URL . 'config/uploads/' . $container['invoice_path']); ?>" target="_blank" class="text-blue-500 hover:underline">
                            Download Invoice
                        </a>
                    </p>
                <?php endif; ?>

                <?php if (!empty($photos)): ?>
                    <p class="mt-4">
                        <strong>Photos:</strong>
                        <?php foreach ($photos as $photo): ?>
                            <img src="<?php echo htmlspecialchars(BASE_URL . 'config/uploads/' . $photo['path']); ?>" alt="Container Photo" class="mt-2 max-w-xs inline-block mr-2">
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($videos)): ?>
                    <p class="mt-4">
                        <strong>Videos:</strong>
                        <?php foreach ($videos as $video): ?>
                            <video controls class="mt-2 max-w-xs inline-block mr-2">
                                <source src="<?php echo htmlspecialchars(BASE_URL . 'config/uploads/' . $video['path']); ?>" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6" role="alert">
                Scan a QR code or enter a valid token in the URL (e.g., ?token=xyz).
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
?>