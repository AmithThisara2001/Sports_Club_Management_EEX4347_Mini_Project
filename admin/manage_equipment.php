<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $equipment_id = (int)$_GET['delete'];
    
    // Check if equipment has active bookings
    $check_sql = "SELECT COUNT(*) as count FROM equipment_bookings 
                  WHERE equipment_id = ? AND status IN ('pending', 'approved')";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete equipment with active bookings!";
    } else {
        $sql = "DELETE FROM equipment WHERE equipment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $equipment_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Equipment deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete equipment!";
        }
    }
    
    redirect('manage_equipment.php');
}

// Get all equipment
$sql = "SELECT * FROM equipment ORDER BY equipment_name ASC";
$equipment_list = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Equipment - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_admin.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>Manage Equipment</h1>
            <a href="add_equipment.php" class="btn btn-primary">+ Add New Equipment</a>
        </div>
        
        <?php 
        if (isset($_SESSION['success'])) {
            echo show_message($_SESSION['success'], 'success');
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo show_message($_SESSION['error'], 'danger');
            unset($_SESSION['error']);
        }
        ?>
        
        <!-- Equipment Table -->
        <div class="table-container">
            <?php if ($equipment_list->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Equipment Name</th>
                            <th>Type</th>
                            <th>Total Quantity</th>
                            <th>Available</th>
                            <th>In Use</th>
                            <th>Condition</th>
                            <th>Location</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($equipment = $equipment_list->fetch_assoc()): ?>
                        <?php 
                        $in_use = $equipment['quantity_total'] - $equipment['quantity_available'];
                        $availability_percentage = ($equipment['quantity_available'] / $equipment['quantity_total']) * 100;
                        ?>
                        <tr>
                            <td><?php echo $equipment['equipment_id']; ?></td>
                            <td><?php echo htmlspecialchars($equipment['equipment_name']); ?></td>
                            <td><?php echo htmlspecialchars($equipment['equipment_type']); ?></td>
                            <td><?php echo $equipment['quantity_total']; ?></td>
                            <td>
                                <span class="availability-badge <?php echo $availability_percentage < 30 ? 'low' : ''; ?>">
                                    <?php echo $equipment['quantity_available']; ?>
                                </span>
                            </td>
                            <td><?php echo $in_use; ?></td>
                            <td>
                                <span class="condition-badge condition-<?php echo $equipment['condition_status']; ?>">
                                    <?php echo ucfirst($equipment['condition_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($equipment['location']); ?></td>
                            <td class="action-buttons">
                                <a href="edit_equipment.php?id=<?php echo $equipment['equipment_id']; ?>" 
                                   class="btn btn-sm btn-warning" 
                                   title="Edit">✏️</a>
                                <a href="?delete=<?php echo $equipment['equipment_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this equipment?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No equipment found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>