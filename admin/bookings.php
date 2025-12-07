<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Update overdue bookings
get_overdue_bookings($conn);

// Filter
$status_filter = isset($_GET['status']) ? clean_input($_GET['status']) : 'all';

// Get bookings
$sql = "SELECT eb.*, m.full_name, m.email, e.equipment_name 
        FROM equipment_bookings eb
        INNER JOIN members m ON eb.member_id = m.member_id
        INNER JOIN equipment e ON eb.equipment_id = e.equipment_id";

if ($status_filter != 'all') {
    $sql .= " WHERE eb.status = ?";
}

$sql .= " ORDER BY eb.booking_id DESC";

if ($status_filter != 'all') {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $bookings = $stmt->get_result();
} else {
    $bookings = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_admin.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>Equipment Bookings</h1>
        </div>
        
        <?php 
        if (isset($_SESSION['success'])) {
            echo show_message($_SESSION['success'], 'success');
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo show_message($_SESSION['error'], 'danger');
            unset($_SESSION['error']);
        }
        ?>
        
        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?status=all" class="tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All Bookings</a>
            <a href="?status=pending" class="tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="?status=approved" class="tab <?php echo $status_filter == 'approved' ? 'active' : ''; ?>">Approved</a>
            <a href="?status=returned" class="tab <?php echo $status_filter == 'returned' ? 'active' : ''; ?>">Returned</a>
            <a href="?status=overdue" class="tab <?php echo $status_filter == 'overdue' ? 'active' : ''; ?>">Overdue</a>
        </div>
        
        <!-- Bookings Table -->
        <div class="table-container">
            <?php if ($bookings->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Member</th>
                            <th>Equipment</th>
                            <th>Quantity</th>
                            <th>Booking Date</th>
                            <th>Return Date</th>
                            <th>Actual Return</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $bookings->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $booking['booking_id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($booking['full_name']); ?><br>
                                <small><?php echo htmlspecialchars($booking['email']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($booking['equipment_name']); ?></td>
                            <td><?php echo $booking['quantity']; ?></td>
                            <td><?php echo format_date($booking['booking_date']); ?></td>
                            <td><?php echo format_date($booking['return_date']); ?></td>
                            <td>
                                <?php 
                                echo $booking['actual_return_date'] ? format_date($booking['actual_return_date']) : '-';
                                ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td class="action-buttons">
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="approve_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                       class="btn btn-sm btn-success" 
                                       title="Approve">✓</a>
                                    <a href="reject_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                       class="btn btn-sm btn-danger" 
                                       title="Reject"
                                       onclick="return confirm('Are you sure you want to reject this booking?')">✗</a>
                                <?php elseif ($booking['status'] == 'approved' || $booking['status'] == 'overdue'): ?>
                                    <a href="mark_returned.php?id=<?php echo $booking['booking_id']; ?>" 
                                       class="btn btn-sm btn-primary" 
                                       title="Mark as Returned">↩️</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No bookings found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>