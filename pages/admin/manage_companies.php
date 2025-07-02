<?php
// File Path: qr-container-lock/pages/admin/manage_companies.php

session_start();
require_once '../../includes/auth.php';
require_once '../../config/config.php';

requireAdmin();

// Fetch all companies
$stmt = $pdo->query("SELECT c.*, p.name as plan_name FROM companies c JOIN plans p ON c.plan_id = p.id WHERE c.is_admin = FALSE ORDER BY c.created_at DESC");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $company_id = filter_input(INPUT_POST, 'company_id', FILTER_VALIDATE_INT);
    
    if ($company_id) {
        // Delete associated containers and files first
        $stmt = $pdo->prepare("SELECT invoice_path, qr_path FROM containers WHERE company_id = ?");
        $stmt->execute([$company_id]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($files as $file) {
            if ($file['invoice_path'] && file_exists($file['invoice_path'])) {
                unlink($file['invoice_path']);
            }
            if ($file['qr_path'] && file_exists($file['qr_path'])) {
                unlink($file['qr_path']);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM containers WHERE company_id = ?");
        $stmt->execute([$company_id]);
        
        $stmt = $pdo->prepare("DELETE FROM companies WHERE id = ? AND is_admin = FALSE");
        $stmt->execute([$company_id]);
        
        $_SESSION['success'] = "Company deleted successfully.";
    } else {
        $_SESSION['error'] = "Invalid company ID.";
    }
    
    header("Location: manage_companies.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Companies - QR Container Lock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../../assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="container max-w-6xl mx-auto p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Manage Companies</h1>
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
        
        <div class="bg-white shadow-md rounded-lg p-6">
            <table class="w-full text-left">
                <thead>
                    <tr class="text-gray-600">
                        <th class="p-2">Company Name</th>
                        <th class="p-2">Email</th>
                        <th class="p-2">Plan</th>
                        <th class="p-2">Created At</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($companies): ?>
                        <?php foreach ($companies as $company): ?>
                            <tr class="border-t">
                                <td class="p-2"><?php echo htmlspecialchars($company['name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($company['email']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($company['plan_name']); ?></td>
                                <td class="p-2"><?php echo date('Y-m-d', strtotime($company['created_at'])); ?></td>
                                <td class="p-2">
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this company?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="company_id" value="<?php echo $company['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="p-2 text-center text-gray-600">No companies found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
?>