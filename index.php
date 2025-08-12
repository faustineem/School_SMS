<?php
require_once 'includes/auth.php';

// Redirect to dashboard if already logged in
if (isLoggedIn()) {
    redirectToDashboard();
}

// Redirect to login page
header('Location: ./users/login.php');
exit();
?>
