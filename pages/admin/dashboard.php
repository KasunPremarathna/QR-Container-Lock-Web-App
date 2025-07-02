<?php
// File Path: qr-container-lock/pages/admin/dashboard.php

session_start();
require_once '../../includes/auth.php';
require_once '../../config/config.php';

requireAdmin(); // Restrict to admin users only

// Fetch company count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM companies WHERE is_admin = FALSE");
$company_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Fetch pending container count
$stmt = $pdo->query("SELECT COUNT(*) as count FROM containers WHERE status = 'pending'");
$pending_container_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Fetch recent access logs
$stmt = $pdo->query("SELECT al.*, c.container_id, co.name as company_name 
                     FROM access_logs al 
                     JOIN containers c ON al.container_id = c.id 
                     JOIN companies co ON c.company_id = co.id 
                     ORDER BY al.accessed_at DESC LIMIT 5");
$recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - QR Container Lock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container max-w-6xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
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
        
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Companies</h2>
                <p class="text-3xl font-bold text-indigo-600"><?php echo $company_count; ?></p>
                <a href="manage_companies.php" class="mt-4 block text-indigo-600 hover:underline">Manage Companies</a>
            </div>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-700 mb-2">Pending Containers</h2>
                <p class="text-3xl font-bold text-indigo-600"><?php echo $pending_container_count; ?></p>
                <a href="manage_containers.php" class="mt-4 block text-indigo-600 hover:underline">Manage Containers</a>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Recent Activity</h2>
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-600">
                        <th class="p-2">Container ID</th>
                        <th class="p-2">Company</th>
                        <th class="p-2">Action</th>
                        <th class="p-2">Details</th>
                        <th class="p-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_logs): ?>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr class="border-t">
                                <td class="p-2"><?php echo htmlspecialchars($log['container_id']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($log['company_name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($log['access_type']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($log['details'] ?: 'N/A'); ?></td>
                                <td class="p-2"><?php echo date('Y-m-d H:i', strtotime($log['accessed_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="p-2 text-center text-gray-600">No recent activity.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Quick Actions -->
        <div class="mt-6">
            <a href="manage_plans.php" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 mr-4">Manage Plans</a>
            <a href="manage_containers.php" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Manage Containers</a>
        </div>
    </div>
</body>
</html>
?>