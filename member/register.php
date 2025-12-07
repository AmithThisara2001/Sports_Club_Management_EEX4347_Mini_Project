<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = clean_input($_POST['username']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    
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
        $check_sql = "SELECT member_id FROM members WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            // Insert new member
            $hashed_password = hash_password($password);
            $sql = "INSERT INTO members (username, password, full_name, email, phone, address) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $phone, $address);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
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
    <title>Member Registration - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="registration-form">
            <h2>Member Registration</h2>
            
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
                    <label>Phone</label>
                    <input type="tel" name="phone" pattern="[0-9]{10}">
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"></textarea>
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
            </form>
        </div>
    </div>
    
    <script src="../js/validation.js"></script>
</body>
</html>