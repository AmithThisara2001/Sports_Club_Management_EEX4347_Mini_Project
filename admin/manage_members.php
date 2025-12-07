<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Handle Delete Member
if (isset($_GET['delete'])) {
    $member_id = (int)$_GET['delete'];
    
    // Delete member (cascade will handle registrations and bookings)
    $delete_sql = "DELETE FROM members WHERE member_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $member_id);
    
    if ($delete_stmt->execute()) {
        $success = "Member deleted successfully!";
    } else {
        $error = "Failed to delete member.";
    }
}

// Handle Add/Edit Member
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $status = $_POST['status'];
    
    if (empty($username) || empty($full_name) || empty($email)) {
        $error = "Username, full name and email are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif ($member_id == 0 && empty($password)) {
        $error = "Password is required for new members.";
    } else {
        // Check username/email uniqueness
        if ($member_id > 0) {
            $check_sql = "SELECT member_id FROM members WHERE (username = ? OR email = ?) AND member_id != ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ssi", $username, $email, $member_id);
        } else {
            $check_sql = "SELECT member_id FROM members WHERE username = ? OR email = ?";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("ss", $username, $email);
        }
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            if ($member_id > 0) {
                // Update existing member
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $update_sql = "UPDATE members SET username = ?, full_name = ?, email = ?, phone = ?, address = ?, password = ?, status = ? WHERE member_id = ?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("sssssssi", $username, $full_name, $email, $phone, $address, $hashed_password, $status, $member_id);
                } else {
                    $update_sql = "UPDATE members SET username = ?, full_name = ?, email = ?, phone = ?, address = ?, status = ? WHERE member_id = ?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("ssssssi", $username, $full_name, $email, $phone, $address, $status, $member_id);
                }
                
                if ($stmt->execute()) {
                    $success = "Member updated successfully!";
                } else {
                    $error = "Failed to update member.";
                }
            } else {
                // Add new member
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_sql = "INSERT INTO members (username, password, full_name, email, phone, address, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("sssssss", $username, $hashed_password, $full_name, $email, $phone, $address, $status);
                
                if ($stmt->execute()) {
                    $success = "Member added successfully!";
                } else {
                    $error = "Failed to add member.";
                }
            }
        }
    }
}

// Fetch all members
$sql = "SELECT m.*, 
        (SELECT COUNT(*) FROM event_registrations WHERE member_id = m.member_id AND status = 'confirmed') as event_count
        FROM members m 
        ORDER BY m.registration_date DESC";
$result = $conn->query($sql);

// Get member for editing
$edit_member = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM members WHERE member_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    $edit_member = $edit_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/nav_admin.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1>Manage Members</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-container">
            <h2><?php echo $edit_member ? 'Edit Member' : 'Add New Member'; ?></h2>
            <form method="POST" action="">
                <?php if ($edit_member): ?>
                    <input type="hidden" name="member_id" value="<?php echo $edit_member['member_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" 
                               value="<?php echo $edit_member ? htmlspecialchars($edit_member['username']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" 
                               value="<?php echo $edit_member ? htmlspecialchars($edit_member['full_name']) : ''; ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" 
                               value="<?php echo $edit_member ? htmlspecialchars($edit_member['email']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" 
                               value="<?php echo $edit_member ? htmlspecialchars($edit_member['phone']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="2"><?php echo $edit_member ? htmlspecialchars($edit_member['address']) : ''; ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Password <?php echo $edit_member ? '(leave blank to keep current)' : '*'; ?></label>
                        <input type="password" name="password" <?php echo !$edit_member ? 'required' : ''; ?>>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo ($edit_member && $edit_member['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($edit_member && $edit_member['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <?php echo $edit_member ? 'Update Member' : 'Add Member'; ?>
                    </button>
                    <?php if ($edit_member): ?>
                        <a href="manage_members.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Events</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($member = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $member['member_id']; ?></td>
                                <td><?php echo htmlspecialchars($member['username']); ?></td>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                <td><?php echo $member['event_count']; ?></td>
                                <td>
                                    <span class="status-badge <?php echo $member['status'] == 'active' ? 'status-approved' : 'status-pending'; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($member['registration_date'])); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $member['member_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <a href="?delete=<?php echo $member['member_id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to delete this member? This will also delete their event registrations and bookings.');">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="no-data">No members found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php
$conn->close();
?>