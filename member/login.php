<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    
    if (empty($username) || empty($password)) {
        $error = "Please enter username and password!";
    } else {
        $sql = "SELECT member_id, username, password, full_name, status FROM members WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if ($user['status'] == 'inactive') {
                $error = "Your account has been deactivated. Please contact admin.";
            } elseif (verify_password($password, $user['password'])) {
                // Login successful
                $_SESSION['member_id'] = $user['member_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = 'member';
                
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
    <title>Member Login - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h2>Member Login</h2>
            
            <?php if (!empty($error)) echo show_message($error, 'danger'); ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
                <a href="register.php" class="btn btn-secondary">Register New Account</a>
                <a href="../index.php" class="btn btn-link">Back to Home</a>
            </form>
        </div>
    </div>
</body>
</html>