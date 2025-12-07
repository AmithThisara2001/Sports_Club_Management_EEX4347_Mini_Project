<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit();
}

$member_id = $_SESSION['member_id'];
$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($full_name) || empty($email)) {
        $error = "Full name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email already exists for another member
        $check_sql = "SELECT member_id FROM members WHERE email = ? AND member_id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $member_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            // If password change is requested
            if (!empty($current_password) || !empty($new_password)) {
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    $error = "All password fields are required to change password.";
                } elseif ($new_password !== $confirm_password) {
                    $error = "New passwords do not match.";
                } elseif (strlen($new_password) < 6) {
                    $error = "New password must be at least 6 characters.";
                } else {
                    // Verify current password
                    $verify_sql = "SELECT password FROM members WHERE member_id = ?";
                    $verify_stmt = $conn->prepare($verify_sql);
                    $verify_stmt->bind_param("i", $member_id);
                    $verify_stmt->execute();
                    $verify_result = $verify_stmt->get_result();
                    $member = $verify_result->fetch_assoc();
                    
                    if (!password_verify($current_password, $member['password'])) {
                        $error = "Current password is incorrect.";
                    } else {
                        // Update profile with new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_sql = "UPDATE members SET full_name = ?, email = ?, phone = ?, address = ?, password = ? 
                                      WHERE member_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $hashed_password, $member_id);
                        
                        if ($update_stmt->execute()) {
                            $_SESSION['full_name'] = $full_name;
                            $success = "Profile and password updated successfully!";
                        } else {
                            $error = "Failed to update profile.";
                        }
                    }
                }
            } else {
                // Update profile without password change
                $update_sql = "UPDATE members SET full_name = ?, email = ?, phone = ?, address = ? 
                              WHERE member_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $member_id);
                
                if ($update_stmt->execute()) {
                    $_SESSION['full_name'] = $full_name;
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Failed to update profile.";
                }
            }
        }
    }
}

// Fetch current member data
$sql = "SELECT * FROM members WHERE member_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();

// Get member statistics
$stats_sql = "SELECT 
                (SELECT COUNT(*) FROM event_registrations WHERE member_id = ? AND status = 'confirmed') as total_events,
                (SELECT COUNT(*) FROM event_registrations er 
                 JOIN events e ON er.event_id = e.event_id 
                 WHERE er.member_id = ? AND er.status = 'confirmed' AND e.event_date >= CURDATE()) as upcoming_events";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("ii", $member_id, $member_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        .profile-sidebar {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            height: fit-content;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
            margin: 0 auto 20px;
            font-weight: bold;
        }
        
        .profile-name {
            text-align: center;
            font-size: 1.5em;
            color: var(--dark-text);
            margin-bottom: 10px;
        }
        
        .profile-role {
            text-align: center;
            color: var(--light-text);
            margin-bottom: 20px;
        }
        
        .profile-stats {
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--light-bg);
        }
        
        .stat-label {
            color: var(--light-text);
        }
        
        .stat-value {
            font-weight: bold;
            color: var(--dark-text);
        }
        
        .profile-main {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        .section-title {
            font-size: 1.5em;
            color: var(--dark-text);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-color);
        }
        
        .password-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid var(--light-bg);
        }
        
        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav_member.php'; ?>

    <div class="container">
        <h1>Edit Profile</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($member['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($member['full_name']); ?></div>
                <div class="profile-role">Member</div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">Username</span>
                        <span class="stat-value"><?php echo htmlspecialchars($member['username']); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total Events</span>
                        <span class="stat-value"><?php echo $stats['total_events']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Upcoming Events</span>
                        <span class="stat-value"><?php echo $stats['upcoming_events']; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Member Since</span>
                        <span class="stat-value"><?php echo date('M Y', strtotime($member['registration_date'])); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Status</span>
                        <span class="stat-value" style="color: var(--success-color);">
                            <?php echo ucfirst($member['status']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="profile-main">
                <h2 class="section-title">Personal Information</h2>
                
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" 
                                   value="<?php echo htmlspecialchars($member['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($member['email']); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo htmlspecialchars($member['phone']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($member['address']); ?></textarea>
                    </div>

                    <div class="password-section">
                        <h2 class="section-title">Change Password</h2>
                        <p style="color: var(--light-text); margin-bottom: 20px;">Leave blank if you don't want to change your password</p>

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                        <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php
$stmt->close();
$stats_stmt->close();
$conn->close();
?>