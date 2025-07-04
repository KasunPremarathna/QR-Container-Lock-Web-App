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

// Handle container status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], array('approve', 'reject'))) {
    $container_id = filter_input(INPUT_POST, 'container_id', FILTER_VALIDATE_INT);
    $new_status = ($_POST['action'] === 'approve') ? 'approved' : 'rejected';

    if ($container_id) {
        try {
            $stmt = $pdo->prepare("UPDATE containers SET status = ? WHERE id = ?");
            if ($stmt->execute(array($new_status, $container_id))) {
                $_SESSION['success'] = "Container status updated to $new_status successfully.";
            } else {
                error_log("Error: Failed to update container status for ID $container_id in manage_containers.php");
                $_SESSION['error'] = "Failed to update container status.";
            }
        } catch (PDOException $e) {
            error_log("Database error (update status) in manage_containers.php: " . $e->getMessage());
            $_SESSION['error'] = "Database error: Unable to update container status.";
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
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
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