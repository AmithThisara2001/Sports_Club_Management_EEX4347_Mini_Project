<?php
// Start session
session_start();

// Clear all admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_name']);
unset($_SESSION['admin_role']);
unset($_SESSION['user_type']);

// Destroy the session
session_destroy();

// Set logout message
session_start();
$_SESSION['logout_message'] = "Admin session terminated.";

// Redirect to admin login page
header("Location: login.php");
exit();
?>