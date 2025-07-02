<?php
// File Path: qr-container-lock/pages/company/dashboard.php

session_start();
require_once '../../includes/auth.php';
require_once '../../config/config.php';

requireLogin(); // Restrict to logged-in users
if (isAdmin()) {
    header("Location: ../admin/dashboard.php");
    exit;
}

// Fetch company's containers
$stmt = $pdo->prepare("SELECT c.*, p.name as plan_name 
                       FROM containers c 
                       JOIN plans p ON c.company_id = ? 
                       JOIN companies co ON co.id = c.company_id 
                       WHERE c.company_id = ? 
                       ORDER BY c.created_at DESC");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$containers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch company plan details
$stmt = $pdo->prepare("SELECT p.* FROM companies c JOIN plans p ON c.plan_id = p.id WHERE c.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Dashboard - QR Container Lock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container max-w-6xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Company Dashboard - <?php echo htmlspecialchars($_SESSION['user_name']); ?></h1>
            <a href="../../pages/logout.php" class="bg-red-600 text-white py-2 px-4 rounded-md hover:bg-red-700">Logout</a>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Plan Details -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Your Plan: <?php echo htmlspecialchars($plan['name']); ?></h2>
            <p class="text-gray-600">Containers per Month: <?php echo $plan['containers_per_month'] ?: 'Unlimited'; ?></p>
            <p class="text-gray-600">Invoice Size Limit: <?php echo $plan['invoice_size_limit_mb']; ?> MB</p>
            <p class="text-gray-600">Photos per Container: <?php echo $plan['photos_per_container']; ?></p>
            <p class="text-gray-600">Video Upload: <?php echo $plan['video_upload_allowed'] ? 'Allowed' : 'Not Allowed'; ?></p>
            <p class="text-gray-600">Trial Days Remaining: <?php echo $plan['trial_days']; ?></p>
        </div>
        
        <!-- Containers Table -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">Your Containers</h2>
                <a href="create_container.php" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Create New Container</a>
            </div>
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-600">
                        <th class="p-2">Container ID</th>
                        <th class="p-2">Description</th>
                        <th class="p-2">Destination</th>
                        <th class="p-2">Status</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($containers): ?>
                        <?php foreach ($containers as $container): ?>
                            <tr class="border-t">
                                <td class="p-2"><?php echo htmlspecialchars($container['container_id']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars(substr($container['description'], 0, 50)) . (strlen($container['description']) > 50 ? '...' : ''); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($container['destination']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($container['status']); ?></td>
                                <td class="p-2">
                                    <a href="view_history.php?container_id=<?php echo $container['id']; ?>" class="text-blue-600 hover:underline">View History</a>
                                    <?php if ($container['status'] === 'approved' && $container['qr_path']): ?>
                                        <a href="<?php echo htmlspecialchars($container['qr_path']); ?>" download class="ml-2 text-green-600 hover:underline">Download QR</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="p-2 text-center text-gray-600">No containers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
?>