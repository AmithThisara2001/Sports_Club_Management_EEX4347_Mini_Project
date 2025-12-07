<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['member_id'])) {
    header('Location: login.php');
    exit();
}

$member_id = $_SESSION['member_id'];

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registration_id'])) {
    $registration_id = (int)$_POST['registration_id'];
    
    // Verify this registration belongs to the user
    $check_sql = "SELECT r.registration_id, r.event_id 
                  FROM event_registrations r
                  WHERE r.registration_id = ? AND r.member_id = ? AND r.status = 'confirmed'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $registration_id, $member_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $registration = $result->fetch_assoc();
        
        // Update registration status to cancelled
        $cancel_sql = "UPDATE event_registrations SET status = 'cancelled' WHERE registration_id = ?";
        $cancel_stmt = $conn->prepare($cancel_sql);
        $cancel_stmt->bind_param("i", $registration_id);
        
        if ($cancel_stmt->execute()) {
            // Decrease current_participants in events table
            $update_event_sql = "UPDATE events SET current_participants = current_participants - 1 
                                WHERE event_id = ? AND current_participants > 0";
            $update_stmt = $conn->prepare($update_event_sql);
            $update_stmt->bind_param("i", $registration['event_id']);
            $update_stmt->execute();
            
            $_SESSION['success'] = "Event registration cancelled successfully!";
        } else {
            $_SESSION['error'] = "Failed to cancel registration. Please try again.";
        }
        
        $cancel_stmt->close();
    } else {
        $_SESSION['error'] = "Invalid registration or unauthorized access.";
    }
    
    $check_stmt->close();
    header('Location: dashboard.php');
    exit();
}

$conn->close();
header('Location: dashboard.php');
exit();
?>