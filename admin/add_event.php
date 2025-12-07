<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = clean_input($_POST['event_name']);
    $event_type = clean_input($_POST['event_type']);
    $event_date = clean_input($_POST['event_date']);
    $event_time = clean_input($_POST['event_time']);
    $location = clean_input($_POST['location']);
    $max_participants = (int)clean_input($_POST['max_participants']);
    $description = clean_input($_POST['description']);
    $created_by = $_SESSION['admin_id'];
    
    // Validation
    if (empty($event_name) || empty($event_type) || empty($event_date) || empty($event_time) || empty($location)) {
        $error = "All required fields must be filled!";
    } elseif ($event_date < date('Y-m-d')) {
        $error = "Event date cannot be in the past!";
    } elseif ($max_participants < 1) {
        $error = "Maximum participants must be at least 1!";
    } else {
        $sql = "INSERT INTO events (event_name, event_type, event_date, event_time, location, max_participants, description, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $event_name, $event_type, $event_date, $event_time, $location, $max_participants, $description, $created_by);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Event added successfully!";
            redirect('manage_events.php');
        } else {
            $error = "Failed to add event!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_admin.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>Add New Event</h1>
            <a href="manage_events.php" class="btn btn-secondary">← Back to Events</a>
        </div>
        
        <?php 
        if (!empty($error)) echo show_message($error, 'danger');
        if (!empty($success)) echo show_message($success, 'success');
        ?>
        
        <div class="form-container">
            <form method="POST" action="" class="admin-form">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Event Name *</label>
                        <input type="text" name="event_name" required 
                               placeholder="e.g., Annual Football Tournament">
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label>Event Type *</label>
                        <select name="event_type" required>
                            <option value="">Select Type</option>
                            <option value="football">Football</option>
                            <option value="cricket">Cricket</option>
                            <option value="tournament">Tournament</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label>Event Time *</label>
                        <input type="time" name="event_time" required>
                    </div>
                    
                    <div class="form-group col-md-4">
                        <label>Max Participants *</label>
                        <input type="number" name="max_participants" required 
                               min="1" value="20">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" name="location" required 
                           placeholder="e.g., Main Sports Ground">
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" 
                              placeholder="Enter event description..."></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Add Event</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>