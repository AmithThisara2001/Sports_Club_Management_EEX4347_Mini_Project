<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$member_id = $_SESSION['member_id'];
$event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

if ($event_id == 0) {
    redirect('events.php');
}

// Get event details
$sql = "SELECT * FROM events WHERE event_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Event not found!";
    redirect('events.php');
}

$event = $result->fetch_assoc();

// Check if already registered
$sql = "SELECT * FROM event_registrations WHERE event_id = ? AND member_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $event_id, $member_id);
$stmt->execute();
$check = $stmt->get_result();

if ($check->num_rows > 0) {
    $_SESSION['error'] = "You are already registered for this event!";
    redirect('events.php');
}

// Check if event has capacity
if (!check_event_capacity($conn, $event_id)) {
    $_SESSION['error'] = "Sorry, this event is full!";
    redirect('events.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Insert registration
        $sql = "INSERT INTO event_registrations (member_id, event_id, status) VALUES (?, ?, 'confirmed')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $member_id, $event_id);
        $stmt->execute();
        
        // Update event participants count
        $sql = "UPDATE events SET current_participants = current_participants + 1 WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Successfully registered for the event!";
        redirect('events.php');
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $error = "Registration failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Event - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_member.php'; ?>
    
    <div class="container">
        <h1>Confirm Event Registration</h1>
        
        <?php if (isset($error)) echo show_message($error, 'danger'); ?>
        
        <div class="event-details-card">
            <h2><?php echo htmlspecialchars($event['event_name']); ?></h2>
            
            <div class="detail-group">
                <label>Event Type:</label>
                <span class="badge"><?php echo ucfirst($event['event_type']); ?></span>
            </div>
            
            <div class="detail-group">
                <label>Date:</label>
                <span><?php echo format_date($event['event_date']); ?></span>
            </div>
            
            <div class="detail-group">
                <label>Time:</label>
                <span><?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
            </div>
            
            <div class="detail-group">
                <label>Location:</label>
                <span><?php echo htmlspecialchars($event['location']); ?></span>
            </div>
            
            <div class="detail-group">
                <label>Description:</label>
                <p><?php echo htmlspecialchars($event['description']); ?></p>
            </div>
            
            <div class="detail-group">
                <label>Available Slots:</label>
                <span><?php echo ($event['max_participants'] - $event['current_participants']); ?> / <?php echo $event['max_participants']; ?></span>
            </div>
            
            <form method="POST" action="">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Confirm Registration</button>
                    <a href="events.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>