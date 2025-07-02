<?php
// File Path: qr-container-lock/index.php

session_start();
require_once 'config/config.php';
require_once 'includes/auth.php';

// Redirect authenticated users to their respective dashboards
if (isLoggedIn()) {
    header("Location: pages/" . (isAdmin() ? "admin/dashboard.php" : "company/dashboard.php"));
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Container Lock - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="container max-w-4xl mx-auto p-6">
        <h1 class="text-4xl font-bold text-center text-gray-800 mb-6">Welcome to QR Container Lock</h1>
        <p class="text-lg text-center text-gray-600 mb-8">A secure SaaS platform for export companies to manage container content and generate unique QR code locks.</p>
        
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
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Registration Card -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Get Started</h2>
                <p class="text-gray-600 mb-4">Register your company to start managing containers and generating secure QR codes.</p>
                <a href="pages/register.php" class="block w-full bg-indigo-600 text-white text-center py-2 px-4 rounded-md hover:bg-indigo-700">Register Now</a>
            </div>
            
            <!-- Login Card -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Already Registered?</h2>
                <p class="text-gray-600 mb-4">Log in to access your company dashboard and manage your containers.</p>
                <a href="pages/login.php" class="block w-full bg-indigo-600 text-white text-center py-2 px-4 rounded-md hover:bg-indigo-700">Login</a>
            </div>
        </div>
        
        <!-- Features Section -->
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Why Choose Us?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow-md rounded-lg p-6 text-center">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Secure QR Codes</h3>
                    <p class="text-gray-600">Generate unique, token-based QR codes for each container, accessible only by authorized users.</p>
                </div>
                <div class="bg-white shadow-md rounded-lg p-6 text-center">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Multi-Tenant SaaS</h3>
                    <p class="text-gray-600">Manage multiple companies with isolated data and customizable subscription plans.</p>
                </div>
                <div class="bg-white shadow-md rounded-lg p-6 text-center">
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">Easy Container Management</h3>
                    <p class="text-gray-600">Upload invoices, photos, and videos, and track container activity with detailed logs.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
?>