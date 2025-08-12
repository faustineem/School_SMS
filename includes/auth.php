<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: ./users/login.php');
        exit();
    }
}

function requireRole($allowed_roles) {
    requireAuth();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        header('Location: ../dashboard.php');
        exit();
    }
}

function getUserData() {
    if (!isLoggedIn()) {
        return null;
    }
    
    // require_once __DIR__ . './../config/db.php';
    
    try {
        $pdo = $GLOBALS['pdo'];
        $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

function redirectToDashboard() {
    header('Location: ../dashboard.php');
    exit();
}
?>
