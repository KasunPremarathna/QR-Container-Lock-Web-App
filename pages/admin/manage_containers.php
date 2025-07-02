<?php
// File Path: qr-container-lock/pages/admin/manage_containers.php

session_start();
require_once '../../includes/auth.php';
require_once '../../config/config.php';
require_once '../../vendor/autoload.php'; // For QR code library
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

requireAdmin();

// Fetch all containers
$stmt = $pdo->query("SELECT c.*, co.name as company_name 
                     FROM containers c 
                     JOIN companies co ON c.company_id = co.id 
                     ORDER BY c.created_at DESC");
$containers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $container_id = filter_input(INPUT_POST, 'container_id', FILTER_VALIDATE_INT);
    
    if ($_POST['action'] === 'approve' && $container_id) {
        $stmt = $pdo->prepare("SELECT token, company_id FROM containers WHERE id = ?");
        $stmt->execute([$container_id]);
        $container = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($container) {
            // Generate QR code
            $qr_url = BASE_URL . "pages/public/view.php?token=" . $container['token'];
            $qrCode = QrCode::create($qr_url)
                ->setSize(300)
                ->setMargin(10);
            $writer = new PngWriter();
            $result = $writer->write($qrCode);
            
            $qr_path = QRCODES_DIR . $container['token'] . '.png';
            $result->saveToFile($qr_path);
            
            // Update container status and QR path
            $stmt = $pdo->prepare("UPDATE containers SET qr_path = ?, status = 'approved' WHERE id = ?");
            $stmt->execute([$qr_path, $container_id]);
            
            // Log action
            $stmt = $pdo->prepare("INSERT INTO access_logs (container_id, access_type, details) VALUES (?, 'admin_action', 'Container approved')");
            $stmt->execute([$container_id]);
            
            $_SESSION['success'] = "Container approved and QR code generated.";
        } else {
            $_SESSION['error'] = "Invalid container ID.";
        }
    } elseif ($_POST['action'] === 'reject' && $container_id) {
        $stmt = $pdo->prepare("UPDATE containers SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$container_id]);
        
        // Log action
        $stmt = $pdo->prepare("INSERT INTO access_logs (container_id, access_type, details) VALUES (?, 'admin_action', 'Container rejected')");
        $stmt->execute([$container_id]);
        
        $_SESSION['success'] = "Container rejected.";
    } else {
        $_SESSION['error'] = "Invalid action.";
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
                        <th class="p-2">Container ID</th>
                        <th class="p-2">Company</th>
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
                                <td class="p-2"><?php echo htmlspecialchars($container['company_name']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars(substr($container['description'], 0, 50)) . (strlen($container['description']) > 50 ? '...' : ''); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($container['destination']); ?></td>
                                <td class="p-2"><?php echo htmlspecialchars($container['status']); ?></td>
                                <td class="p-2">
                                    <?php if ($container['status'] === 'pending'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="container_id" value="<?php echo $container['id']; ?>">
                                            <button type="submit" class="text-green-600 hover:underline">Approve</button>
                                        </form>
                                        <form method="POST" class="inline ml-2">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="container_id" value="<?php echo $container['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:underline">Reject</button>
                                        </form>
                                    <?php elseif ($container['status'] === 'approved'): ?>
                                        <a href="<?php echo htmlspecialchars($container['qr_path']); ?>" download class="text-blue-600 hover:underline">Download QR</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="p-2 text-center text-gray-600">No containers found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
?>