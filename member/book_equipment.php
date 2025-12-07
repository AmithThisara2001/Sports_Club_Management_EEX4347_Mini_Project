<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$member_id = $_SESSION['member_id'];
$equipment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($equipment_id == 0) {
    redirect('equipment.php');
}

// Get equipment details
$sql = "SELECT * FROM equipment WHERE equipment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $equipment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Equipment not found!";
    redirect('equipment.php');
}

$equipment = $result->fetch_assoc();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_date = clean_input($_POST['booking_date']);
    $return_date = clean_input($_POST['return_date']);
    $quantity = (int)clean_input($_POST['quantity']);

    // Validation
    if (empty($booking_date) || empty($return_date) || $quantity < 1) {
        $error = "All fields are required!";
    } elseif ($booking_date < date('Y-m-d')) {
        $error = "Booking date cannot be in the past!";
    } elseif ($return_date <= $booking_date) {
        $error = "Return date must be after booking date!";
    } elseif ($quantity > $equipment['quantity_available']) {
        $error = "Requested quantity not available!";
    } else {
        // Begin transaction
        $conn->begin_transaction();

        try {
            // Insert booking
            $sql = "INSERT INTO equipment_bookings (member_id, equipment_id, booking_date, return_date, quantity, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iissi", $member_id, $equipment_id, $booking_date, $return_date, $quantity);
            $stmt->execute();

            // Update equipment availability
            $sql = "UPDATE equipment SET quantity_available = quantity_available - ? WHERE equipment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $quantity, $equipment_id);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            $_SESSION['success'] = "Equipment booking request submitted successfully! Waiting for admin approval.";
            redirect('my_bookings.php');
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = "Booking failed. Please try again.";
        }
    }
}

// Calculate min and max dates
$min_date = date('Y-m-d');
$max_date = date('Y-m-d', strtotime('+30 days'));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Equipment - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_member.php'; ?>

    <div class="container">
        <h1>Book Equipment</h1>

        <?php
        if (!empty($error)) echo show_message($error, 'danger');
        if (!empty($success)) echo show_message($success, 'success');
        ?>

        <div class="booking-container">
            <div class="equipment-info">
                <h2><?php echo htmlspecialchars($equipment['equipment_name']); ?></h2>
                <p><strong>Type:</strong> <?php echo htmlspecialchars($equipment['equipment_type']); ?></p>
                <p><strong>Available Quantity:</strong> <?php echo $equipment['quantity_available']; ?></p>
                <p><strong>Condition:</strong> <?php echo ucfirst($equipment['condition_status']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($equipment['location']); ?></p>
            </div>

            <div class="booking-form">
                <form method="POST" action="" id="bookingForm">
                    <div class="form-group">
                        <label>Booking Date *</label>
                        <input type="date"
                            name="booking_date"
                            min="<?php echo $min_date; ?>"
                            max="<?php echo $max_date; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Return Date *</label>
                        <input type="date"
                            name="return_date"
                            min="<?php echo $min_date; ?>"
                            max="<?php echo $max_date; ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Quantity *</label>
                        <input type="number"
                            name="quantity"
                            min="1"
                            max="<?php echo $equipment['quantity_available']; ?>"
                            value="1"
                            required>
                        <small>Maximum available: <?php echo $equipment['quantity_available']; ?></small>
                    </div>

                    <div class="info-box">
                        <p><strong>Important Notes:</strong></p>
                        <ul>
                            <li>Bookings require admin approval</li>
                            <li>Please return equipment on time to avoid penalties</li>
                            <li>Equipment must be returned in good condition</li>
                            <li>Maximum booking period is 30 days</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Submit Booking Request</button>
                        <a href="equipment.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Validate return date is after booking date
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            var bookingDate = new Date(document.querySelector('input[name="booking_date"]').value);
            var returnDate = new Date(document.querySelector('input[name="return_date"]').value);

            if (returnDate <= bookingDate) {
                e.preventDefault();
                alert('Return date must be after booking date!');
            }
        });
    </script>
    </body>
</html>
