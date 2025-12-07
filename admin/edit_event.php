<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id == 0) {
    redirect('manage_events.php');
}

// Get event details
$sql = "SELECT * FROM events WHERE event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Event not found!";
    redirect('manage_events.php');
}

$event = $result->fetch_assoc();
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $event_name = clean_input($_POST['event_name']);
    $event_type = clean_input($_POST['event_type']);
    $event_date = clean_input($_POST['event_date']);
    $event_time = clean_input($_POST['event_time']);
    $location = clean_input($_POST['location']);
    $max_participants = (int)clean_input($_POST['max_participants']);
    $description = clean_input($_POST['description']);

    // Validation
    if (empty($event_name) || empty($event_type) || empty($event_date) || empty($event_time) || empty($location)) {
        $error = "All required fields must be filled!";
    } elseif ($max_participants < $event['current_participants']) {
        $error = "Maximum participants cannot be less than current participants (" . $event['current_participants'] . ")!";
    } else {
        $sql = "UPDATE events 
                SET event_name = ?, event_type = ?, event_date = ?, event_time = ?, 
                    location = ?, max_participants = ?, description = ?
                WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $event_name, $event_type, $event_date, $event_time, $location, $max_participants, $description, $event_id);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Event updated successfully!";
            redirect('manage_events.php');
        } else {
            $error = "Failed to update event!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_admin.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <h1>Edit Event</h1>
            <a href="manage_events.php" class="btn btn-secondary">← Back to Events</a>
        </div>

        <?php if (!empty($error)) echo show_message($error, 'danger'); ?>

        <div class="form-container">
            <form method="POST" action="" class="admin-form">
                <div class="form-row">
                    <div class="form-group col-md-8">
                        <label>Event Name *</label>
                        <input type="text" name="event_name" required
                            value="<?php echo htmlspecialchars($event['event_name']); ?>">
                    </div>

                    <div class="form-group col-md-4">
                        <label>Event Type *</label>
                        <select name="event_type" required>
                            <option value="football" <?php echo $event['event_type'] == 'football' ? 'selected' : ''; ?>>Football</option>
                            <option value="cricket" <?php echo $event['event_type'] == 'cricket' ? 'selected' : ''; ?>>Cricket</option>
                            <option value="tournament" <?php echo $event['event_type'] == 'tournament' ? 'selected' : ''; ?>>Tournament</option>
                            <option value="other" <?php echo $event['event_type'] == 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Event Date *</label>
                        <input type="date" name="event_date" required
                            value="<?php echo $event['event_date']; ?>">
                    </div>

                    <div class="form-group col-md-4">
                        <label>Event Time *</label>
                        <input type="time" name="event_time" required
                            value="<?php echo $event['event_time']; ?>">
                    </div>

                    <div class="form-group col-md-4">
                        <label>Max Participants *</label>
                        <input type="number" name="max_participants" required
                            min="<?php echo $event['current_participants']; ?>"
                            value="<?php echo $event['max_participants']; ?>">
                        <small>Current: <?php echo $event['current_participants']; ?></small>
                    </div>
                </div>

                <div class="form-group">
                    <label>Location *</label>
                    <input type="text" name="location" required
                        value="<?php echo htmlspecialchars($event['location']); ?>">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"><?php echo htmlspecialchars($event['description']); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Event</button>
                    <a href="manage_events.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>

</html>