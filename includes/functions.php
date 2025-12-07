<?php
// Sanitize input data
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['member_id']);
}

// Check if admin is logged in
function is_admin_logged_in() {
    return isset($_SESSION['admin_id']);
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit();
}

// Display alert message
function show_message($message, $type = 'success') {
    return "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
            </div>";
}

// Hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Format date
function format_date($date) {
    return date('d M Y', strtotime($date));
}

// Check equipment availability
function check_equipment_availability($conn, $equipment_id, $quantity) {
    $sql = "SELECT quantity_available FROM equipment WHERE equipment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return ($row['quantity_available'] >= $quantity);
}

// Check event capacity
function check_event_capacity($conn, $event_id) {
    $sql = "SELECT max_participants, current_participants FROM events WHERE event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return ($row['current_participants'] < $row['max_participants']);
}

// Get overdue bookings
function get_overdue_bookings($conn) {
    $today = date('Y-m-d');
    $sql = "UPDATE equipment_bookings 
            SET status = 'overdue' 
            WHERE return_date < ? 
            AND status = 'approved' 
            AND actual_return_date IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $today);
    return $stmt->execute();
}
?>