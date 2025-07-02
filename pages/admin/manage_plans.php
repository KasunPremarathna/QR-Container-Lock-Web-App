<?php
// File Path: qr-container-lock/pages/admin/manage_plans.php

session_start();
require_once '../../includes/auth.php';
require_once '../../config/config.php';

requireAdmin();

// Fetch all plans
$stmt = $pdo->query("SELECT * FROM plans ORDER BY monthly_price ASC");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle plan creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $monthly_price = filter_input(INPUT_POST, 'monthly_price', FILTER_VALIDATE_FLOAT);
    $containers_per_month = filter_input(INPUT_POST, 'containers_per_month', FILTER_VALIDATE_INT);
    $invoice_size_limit_mb = filter_input(INPUT_POST, 'invoice_size_limit_mb', FILTER_VALIDATE_INT);
    $photos_per_container = filter_input(INPUT_POST, 'photos_per_container', FILTER_VALIDATE_INT);
    $video_upload_allowed = isset($_POST['video_upload_allowed']) ? 1 : 0;
    $trial_days = filter_input(INPUT_POST, 'trial_days', FILTER_VALIDATE_INT);
    
    if ($name && $monthly_price !== false && $containers_per_month !== false && $invoice_size_limit_mb !== false && $photos_per_container !== false && $trial_days !== false) {
        $stmt = $pdo->prepare("INSERT INTO plans (name, monthly_price, containers_per_month, invoice_size_limit_mb, photos_per_container, video_upload_allowed, trial_days) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $monthly_price, $containers_per_month, $invoice_size_limit_mb, $photos_per_container, $video_upload_allowed, $trial_days])) {
            $_SESSION['success'] = "Plan created successfully.";
        } else {
            $_SESSION['error'] = "Failed to create plan.";
        }
    } else {
        $_SESSION['error'] = "Invalid input data.";
    }
    
    header("Location: manage_plans.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Plans - QR Container Lock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container max-w-6xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Manage Plans</h1>
            <a href="dashboard.php" class="bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Back to Dashboard</a>
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
        
        <!-- Create Plan Form -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Create New Plan</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Plan Name</label>
                    <input type="text" name="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Monthly Price ($)</label>
                    <input type="number" name="monthly_price" step="0.01" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Containers per Month (0 for unlimited)</label>
                    <input type="number" name="containers_per_month" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Invoice Size Limit (MB)</label>
                    <input type="number" name="invoice_size_limit_mb" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Photos per Container</label>
                    <input type="number" name="photos_per_container" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Allow Video Upload</label>
                    <input type="checkbox" name="video_upload_allowed" class="mt-1 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Trial Days</label>
                    <input type="number" name="trial_days" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Create Plan</button>
            </form>
        </div>
        
        <!-- Plans Table -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Existing Plans</h2>
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-600">
                        <th class="p-2">Name</th>
                        <th class="p-2">Price ($)</th>
                        <th class="p-2">Containers/Month</th>
                        <th class="p-2">Invoice Size (MB)</th>
                        <th class="p-2">Photos/Container</th>
                        <th class="p-2">Video Allowed</th>
                        <th class="p-2">Trial Days</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($plans): ?>
                        <?php foreach ($plans as $plan): ?>
                            <tr class="border-t">
                                <td class="p-2"><?php echo htmlspecialchars($plan['name']); ?></td>
                                <td class="p-2"><?php echo number_format($plan['monthly_price'], 2); ?></td>
                                <td class="p-2"><?php echo $plan['containers_per_month'] ?: 'Unlimited'; ?></td>
                                <td class="p-2"><?php echo $plan['invoice_size_limit_mb']; ?></td>
                                <td class="p-2"><?php echo $plan['photos_per_container']; ?></td>
                                <td class="p-2"><?php echo $plan['video_upload_allowed'] ? 'Yes' : 'No'; ?></td>
                                <td class="p-2"><?php echo $plan['trial_days']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-2 text-center text-gray-600">No plans found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
?>