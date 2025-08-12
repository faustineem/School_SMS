<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// BASE_URL only if not already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', '/School_SMS');
}

// Include authentication and user data
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/roles.php';

$user = getUserData();
$menuItems = isLoggedIn() ? getMenuItems($_SESSION['role']) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nyampulukano Secondary School - Management System</title>

    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Local stylesheet -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard.php">
            <i class="fas fa-school"></i> Nyampulukano SMS
        </a>

        <?php if (isLoggedIn()): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php foreach ($menuItems as $item): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= BASE_URL . $item['url'] ?>">
                                <i class="fas fa-<?= $item['icon'] ?>"></i> <?= htmlspecialchars($item['title']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($user['full_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/users/profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>

<div class="container mt-4">
