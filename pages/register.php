<?php
// File Path: qr-container-lock/pages/register.php

session_start();
require_once '../config/config.php';

// Only allow admin registration if a valid admin code is provided (optional security measure)
$admin_code = 'SUPERADMIN123'; // Replace with a secure code in production

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $is_admin = isset($_POST['is_admin']) && $_POST['admin_code'] === $admin_code ? 1 : 0;
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters.";
    } elseif ($is_admin && $_POST['admin_code'] !== $admin_code) {
        $_SESSION['error'] = "Invalid admin code.";
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT id FROM companies WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already registered.";
        } else {
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO companies (name, email, password, plan_id, is_admin) VALUES (?, ?, ?, 1, ?)");
            if ($stmt->execute([$name, $email, $password_hash, $is_admin])) {
                $_SESSION['success'] = "Registration successful! Please log in.";
                header("Location: login.php");
                exit;
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
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
    <title>Register - QR Container Lock</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="../assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="container max-w-md mx-auto p-6">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Company Registration</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow-md rounded-lg p-6">
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="register">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Company Name</label>
                    <input type="text" name="name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">
                        <input type="checkbox" name="is_admin" class="mr-2 h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        Register as Admin
                    </label>
                </div>
                <div id="admin-code-field" class="hidden">
                    <label class="block text-sm font-medium text-gray-700">Admin Code</label>
                    <input type="text" name="admin_code" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Enter admin code">
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700">Register</button>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">
                Already have an account? <a href="login.php" class="text-indigo-600 hover:underline">Login here</a>
            </p>
        </div>
    </div>
    <script>
        // Show/hide admin code field based on checkbox
        const adminCheckbox = document.querySelector('input[name="is_admin"]');
        const adminCodeField = document.getElementById('admin-code-field');
        adminCheckbox.addEventListener('change', () => {
            adminCodeField.classList.toggle('hidden', !adminCheckbox.checked);
        });
    </script>
</body>
</html>
?>