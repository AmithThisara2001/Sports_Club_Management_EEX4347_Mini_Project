<?php
/**
 * Member Logout Script
 * Clears member session and redirects to login page
 */

// Start session
session_start();

// Clear all member session variables
unset($_SESSION['member_id']);
unset($_SESSION['username']);
unset($_SESSION['full_name']);
unset($_SESSION['user_type']);

// Destroy the session
session_destroy();

// Set logout message
session_start();
$_SESSION['logout_message'] = "You have been successfully logged out.";

// Redirect to login page
header("Location: login.php");
exit();
?>