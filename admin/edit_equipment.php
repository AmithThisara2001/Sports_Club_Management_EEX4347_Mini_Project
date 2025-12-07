<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

if ($equipment_id == 0) {
    header('Location: manage_equipment.php');
    exit();
}

// Fetch equipment details
$sql = "SELECT * FROM equipment WHERE equipment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: manage_equipment.php');
    exit();
}

$equipment = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $equipment_name = trim($_POST['equipment_name']);
    $equipment_type = trim($_POST['equipment_type']);
    $quantity_total = (int)$_POST['quantity_total'];
    $quantity_available = (int)$_POST['quantity_available'];
    $condition_status = $_POST['condition_status'];
    $location = trim($_POST['location']);
    
    if (empty($equipment_name)) {
        $error = "Equipment name is required.";
    } elseif ($quantity_total < 0 || $quantity_available < 0) {
        $error = "Quantities cannot be negative.";
    } elseif ($quantity_available > $quantity_total) {
        $error = "Available quantity cannot exceed total quantity.";
    } else {
        $update_sql = "UPDATE equipment 
                      SET equipment_name = ?, equipment_type = ?, quantity_total = ?, 
                          quantity_available = ?, condition_status = ?, location = ? 
                      WHERE equipment_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ssiissi", $equipment_name, $equipment_type, $quantity_total, 
                                  $quantity_available, $condition_status, $location, $equipment_id);
        
        if ($update_stmt->execute()) {
            $success = "Equipment updated successfully!";
            // Refresh equipment data
            $stmt->execute();
            $result = $stmt->get_result();
            $equipment = $result->fetch_assoc();
        } else {
            $error = "Failed to update equipment.";
        }
    }
}

// Calculate usage statistics
$booked_qty = $equipment['quantity_total'] - $equipment['quantity_available'];
$usage_percent = $equipment['quantity_total'] > 0 ? 
                 round(($booked_qty / $equipment['quantity_total']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Equipment - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .equipment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-box {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-box .label {
            color: var(--light-text);
            font-size: 0.9em;
            margin-bottom: 10px;
        }
        
        .stat-box .value {
            font-size: 2em;
            font-weight: bold;
            color: var(--dark-text);
        }
        
        .condition-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .condition-good { background-color: #27ae60; }
        .condition-fair { background-color: #f39c12; }
        .condition-poor { background-color: #e74c3c; }
    </style>
</head>
<body>
    <?php include '../includes/nav_admin.php'; ?>

    <div class="admin-container">
        <a href="manage_equipment.php" class="btn btn-secondary">← Back to Equipment</a>

        <div class="form-container" style="margin-top: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Edit Equipment</h1>
                <button type="button" class="btn btn-danger" 
                        onclick="if(confirm('Are you sure you want to delete this equipment? This action cannot be undone.')) { 
                            window.location.href='manage_equipment.php?delete=<?php echo $equipment_id; ?>'; 
                        }">
                    Delete Equipment
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- Equipment Statistics -->
            <div class="equipment-stats">
                <div class="stat-box">
                    <div class="label">Total Quantity</div>
                    <div class="value"><?php echo $equipment['quantity_total']; ?></div>
                </div>
                <div class="stat-box">
                    <div class="label">Available</div>
                    <div class="value" style="color: var(--success-color);">
                        <?php echo $equipment['quantity_available']; ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="label">Currently Booked</div>
                    <div class="value" style="color: var(--warning-color);">
                        <?php echo $booked_qty; ?>
                    </div>
                </div>
                <div class="stat-box">
                    <div class="label">Usage Rate</div>
                    <div class="value" style="color: var(--info-color);">
                        <?php echo $usage_percent; ?>%
                    </div>
                </div>
            </div>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="equipment_name">Equipment Name *</label>
                        <input type="text" id="equipment_name" name="equipment_name" 
                               value="<?php echo htmlspecialchars($equipment['equipment_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="equipment_type">Equipment Type</label>
                        <input type="text" id="equipment_type" name="equipment_type" 
                               value="<?php echo htmlspecialchars($equipment['equipment_type']); ?>">
                        <small>e.g., Ball, Bat, Racket, Net</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="quantity_total">Total Quantity *</label>
                        <input type="number" id="quantity_total" name="quantity_total" min="0"
                               value="<?php echo $equipment['quantity_total']; ?>" required>
                        <small>Total number of items owned</small>
                    </div>

                    <div class="form-group">
                        <label for="quantity_available">Available Quantity *</label>
                        <input type="number" id="quantity_available" name="quantity_available" min="0"
                               value="<?php echo $equipment['quantity_available']; ?>" required>
                        <small>Number currently available for booking</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="condition_status">Condition Status *</label>
                        <select id="condition_status" name="condition_status" required>
                            <option value="good" <?php echo $equipment['condition_status'] == 'good' ? 'selected' : ''; ?>>
                                Good Condition
                            </option>
                            <option value="fair" <?php echo $equipment['condition_status'] == 'fair' ? 'selected' : ''; ?>>
                                Fair Condition
                            </option>
                            <option value="poor" <?php echo $equipment['condition_status'] == 'poor' ? 'selected' : ''; ?>>
                                Poor Condition
                            </option>
                        </select>
                        <div style="margin-top: 10px; font-size: 0.9em;">
                            <span class="condition-indicator condition-good"></span> Good - Excellent working condition<br>
                            <span class="condition-indicator condition-fair"></span> Fair - Usable but shows wear<br>
                            <span class="condition-indicator condition-poor"></span> Poor - Needs repair/replacement
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Storage Location</label>
                        <input type="text" id="location" name="location" 
                               value="<?php echo htmlspecialchars($equipment['location']); ?>">
                        <small>Where this equipment is stored</small>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Equipment</button>
                    <a href="manage_equipment.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>