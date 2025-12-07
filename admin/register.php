<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as admin
if (is_admin_logged_in()) {
    redirect('dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    
    // Validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
        $error = "All required fields must be filled!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Check if username already exists
        $check_sql = "SELECT admin_id FROM admin WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            // Insert new admin
            $hashed_password = hash_password($password);
            $sql = "INSERT INTO admin (username, password, full_name, email, role) 
                    VALUES (?, ?, ?, ?, 'admin')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $hashed_password, $full_name, $email);
            
            if ($stmt->execute()) {
                $success = "Admin registration successful! You can now login.";
            } else {
                $error = "Registration failed. Please try again.";
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
    <title>Admin Registration - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2>Admin Registration</h2>
            
            <?php 
            if (!empty($error)) echo show_message($error, 'danger');
            if (!empty($success)) echo show_message($success, 'success');
            ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
                <a href="login.php" class="btn btn-secondary">Already have an account? Login</a>
                <a href="../index.php" class="btn btn-link">Back to Home</a>
            </form>
        </div>
    </div>
    
    <script>
        // Simple client-side validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>