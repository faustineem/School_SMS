<?php
session_start();
session_destroy();

// Optional: define BASE_URL if not already defined elsewhere
$base_url = '/School_SMS'; 

// Redirect to login page
header("Location: {$base_url}/users/login.php");
exit();
?>
