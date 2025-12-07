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
$sql = "SELECT * FROM equipment_bookings WHERE booking_id = ? AND status = 'pending'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Booking not found or already processed!";
    redirect('bookings.php');
}

// Update booking status
$sql = "UPDATE equipment_bookings SET status = 'approved' WHERE booking_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Booking approved successfully!";
} else {
    $_SESSION['error'] = "Failed to approve booking!";
}

redirect('bookings.php');
?>