<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $equipment_name = clean_input($_POST['equipment_name']);
    $equipment_type = clean_input($_POST['equipment_type']);
    $quantity_total = (int)clean_input($_POST['quantity_total']);
    $condition_status = clean_input($_POST['condition_status']);
    $location = clean_input($_POST['location']);
    
    // Validation
    if (empty($equipment_name) || empty($equipment_type) || empty($location)) {
        $error = "All required fields must be filled!";
    } elseif ($quantity_total < 1) {
        $error = "Quantity must be at least 1!";
    } else {
        $sql = "INSERT INTO equipment (equipment_name, equipment_type, quantity_total, quantity_available, condition_status, location) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiiss", $equipment_name, $equipment_type, $quantity_total, $quantity_total, $condition_status, $location);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Equipment added successfully!";
            redirect('manage_equipment.php');
        } else {
            $error = "Failed to add equipment!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Equipment - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_admin.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>Add New Equipment</h1>
            <a href="manage_equipment.php" class="btn btn-secondary">← Back to Equipment</a>
        </div>
        
        <?php if (!empty($error)) echo show_message($error, 'danger'); ?>
        
        <div class="form-container">
            <form method="POST" action="" class="admin-form">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Equipment Name *</label>
                        <input type="text" name="equipment_name" required 
                               placeholder="e.g., Football">
                    </div>
                    
                    <div class="form-group col-md-6">
                        <label>Equipment Type *</label>
                        <input type="text" name="equipment_type" required 
                               placeholder="e.g., Ball">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Quantity *</label>
                        <input type="number" name="quantity_total" required 
                               min="1" value="1">
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label>Condition *</label>
                        <select name="condition_status" required>
                            <option value="good">Good</option>
                            <option value="fair">Fair</option>
                            <option value="poor">Poor</option>
                        </select>
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label>Location *</label>
                        <input type="text" name="location" required 
                               placeholder="e.g., Equipment Room A">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Equipment</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>