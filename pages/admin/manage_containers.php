<?php
// File Path: qr-container-lock/pages/admin/manage_containers.php

// Set session ini settings before session_start
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} else {
    error_log("Session already started or failed to start in manage_containers.php");
    $_SESSION = array();
}
require_once '../../includes/auth.php';
require_once '../../config/config.php';

// Include phpqrcode library
require_once '../../lib/phpqrcode/qrlib.php';

// Enable error logging
ini_set('display_errors', 0); // Disable for production
ini_set('log_errors', 1);
error_log("Starting manage_containers.php");

// Restrict to admins
requireLogin();
if (!isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Fetch all containers
$containers = array();
try {
    $stmt = $pdo->prepare("SELECT * FROM containers ORDER BY created_at DESC");
    $stmt->execute();
    $containers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error (fetch containers) in manage_containers.php: " . $e->getMessage());
    $_SESSION['error'] = "Database error: Unable to fetch containers.";
}

// Handle container actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $container_id = filter_input(INPUT_POST, 'container_id', FILTER_VALIDATE_INT);

    if ($container_id) {
        try {
            if ($_POST['action'] === 'approve') {
                $stmt = $pdo->prepare("UPDATE containers SET status = 'approved' WHERE id = ?");
                if ($stmt->execute(array($container_id))) {
                    $_SESSION['success'] = "Container status updated to approved successfully.";
                } else {
                    error_log("Error: Failed to approve container ID $container_id in manage_containers.php");
                    $_SESSION['error'] = "Failed to approve container.";
                }
            } elseif ($_POST['action'] === 'reject') {
                $stmt = $pdo->prepare("UPDATE containers SET status = 'rejected' WHERE id = ?");
                if ($stmt->execute(array($container_id))) {
                    $_SESSION['success'] = "Container status updated to rejected successfully.";
                } else {
                    error_log("Error: Failed to reject container ID $container_id in manage_containers.php");
                    $_SESSION['error'] = "Failed to reject container.";
                }
            } elseif ($_POST['action'] === 'generate_qr') {
                $stmt = $pdo->prepare("SELECT token FROM containers WHERE id = ? AND status = 'approved'");
                $stmt->execute(array($container_id));
                $container = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($container) {
                    $token = $container['token'];
                    $qr_code_url = BASE_URL . 'pages/public/view.php?token=' . $token;
                    $qr_code_path = QRCODES_DIR . 'qr_' . $container_id . '_' . time() . '.png';
                    QRcode::png($qr_code_url, $qr_code_path, QR_ECLEVEL_L, 4);
                    $view_link = '<a href="' . htmlspecialchars(BASE_URL . 'pages/public/view.php?token=' . $token) . '" target="_blank">View Container</a>';
                    $_SESSION['success'] = "QR code generated. $view_link or <a href='" . htmlspecialchars($qr_code_path) . "' download>Download QR</a>.";
                } else {
                    $_SESSION['error'] = "Cannot generate QR code for unapproved or invalid container.";
                }
            }
        } catch (PDOException $e) {
            error_log("Database error in manage_containers.php: " . $e->getMessage());
            $_SESSION['error'] = "Database error: Unable to process action.";
        } catch (Exception $e) {
            error_log("QR code generation error in manage_containers.php: " . $e->getMessage());
            $_SESSION['error'] = "Failed to generate QR code.";
        }
    } else {
        $_SESSION['error'] = "Invalid container ID.";
    }
    header("Location: manage_containers.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Containers - QR Container Lock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container max-w-6xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Manage Containers</h1>
            <a href="../company/dashboard.php" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Back to Dashboard</a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-md rounded-lg p-6">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Container ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Destination</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($containers as $container): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($container['id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($container['container_id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($container['description']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($container['destination']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($container['status']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($container['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline-block" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="container_id" value="<?php echo htmlspecialchars($container['id']); ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="bg-green-500 text-white py-1 px-2 rounded-md hover:bg-green-600">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline-block" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="container_id" value="<?php echo htmlspecialchars($container['id']); ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="bg-red-500 text-white py-1 px-2 rounded-md hover:bg-red-600">Reject</button>
                                    </form>
                                <?php elseif ($container['status'] === 'approved'): ?>
                                    <form method="POST" style="display:inline-block" onsubmit="return confirm('Generate QR code?');">
                                        <input type="hidden" name="container_id" value="<?php echo htmlspecialchars($container['id']); ?>">
                                        <input type="hidden" name="action" value="generate_qr">
                                        <button type="submit" class="bg-blue-500 text-white py-1 px-2 rounded-md hover:bg-blue-600">Generate QR Code</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-gray-500">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($containers)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No containers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
?>