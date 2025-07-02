<?php
// File Path: qr-container-lock/includes/auth.php

require_once __DIR__ . '/../config/config.php';

function login($email, $password) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT id, name, password, is_admin FROM companies WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['is_admin'] = $user['is_admin'];
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
    session_start(); // Restart session for new messages
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Please log in to access this page.";
        header("Location: ../pages/login.php");
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        $_SESSION['error'] = "Admin access required.";
        header("Location: ../pages/login.php");
        exit;
    }
}
?>