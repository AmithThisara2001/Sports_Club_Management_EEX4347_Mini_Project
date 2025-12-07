<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (is_admin_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter username and password!";
    } else {
        $sql = "SELECT admin_id, username, password, full_name, role FROM admin WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            
            if (verify_password($password, $admin['password'])) {
                // Login successful
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['user_type'] = 'admin';
                
                redirect('dashboard.php');
            } else {
                $error = "Invalid username or password!";
            }
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="admin-login-container">
        <div class="login-box">
            <div class="login-header">
                <h2>Admin Panel</h2>
                <p>Sports Club Management System</p>
            </div>
            
            <?php if (!empty($error)) echo show_message($error, 'danger'); ?>
            
            <form method="POST" action="" class="admin-login-form">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
                <a href="register.php" class="btn btn-secondary btn-block">Register New Admin Account</a>
                <a href="../index.php" class="btn btn-link btn-block">Back to Home</a>
            </form>
            
            <div class="info-box" style="margin-top: 20px;">
                <strong>Default Admin Credentials (if you haven't changed them):</strong><br>
                Username: <code>admin</code><br>
                Password: <code>admin123</code>
            </div>
        </div>
    </div>
</body>
</html>