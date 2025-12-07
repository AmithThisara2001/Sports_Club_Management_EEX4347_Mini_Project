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
$sql = "SELECT eb.*, e.equipment_name 
        FROM equipment_bookings eb
        INNER JOIN equipment e ON eb.equipment_id = e.equipment_id
        WHERE eb.booking_id = ? AND eb.status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Booking not found or already processed!";
    redirect('bookings.php');
}

$booking = $result->fetch_assoc();

// Begin transaction
$conn->begin_transaction();

try {
    // Delete booking
    $sql = "DELETE FROM equipment_bookings WHERE booking_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    
    // Return equipment to available pool
    $sql = "UPDATE equipment SET quantity_available = quantity_available + ? WHERE equipment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $booking['quantity'], $booking['equipment_id']);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "Booking rejected successfully!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Failed to reject booking!";
}

redirect('bookings.php');
?>