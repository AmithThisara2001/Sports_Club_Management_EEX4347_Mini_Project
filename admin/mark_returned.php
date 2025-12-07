<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id == 0) {
    redirect('bookings.php');
}

// Get booking details
$sql = "SELECT * FROM equipment_bookings 
        WHERE booking_id = ? 
        AND status IN ('approved', 'overdue')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Booking not found or already returned!";
    redirect('bookings.php');
}

$booking = $result->fetch_assoc();

// Begin transaction
$conn->begin_transaction();

try {
    // Update booking status
    $today = date('Y-m-d');
    $sql = "UPDATE equipment_bookings 
            SET status = 'returned', actual_return_date = ? 
            WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $today, $booking_id);
    $stmt->execute();
    
    // Return equipment to available pool
    $sql = "UPDATE equipment 
            SET quantity_available = quantity_available + ? 
            WHERE equipment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking['quantity'], $booking['equipment_id']);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Equipment marked as returned successfully!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Failed to mark equipment as returned!";
}

redirect('bookings.php');
?>