<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$member_id = $_SESSION['member_id'];

// Get member statistics
$stats = [];

// Total events registered
$sql = "SELECT COUNT(*) as total FROM event_registrations WHERE member_id = ? AND status = 'confirmed'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_events'] = $result->fetch_assoc()['total'];

// Active equipment bookings
$sql = "SELECT COUNT(*) as total FROM equipment_bookings 
        WHERE member_id = ? AND status IN ('pending', 'approved')";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['active_bookings'] = $result->fetch_assoc()['total'];

// Overdue bookings
$sql = "SELECT COUNT(*) as total FROM equipment_bookings 
        WHERE member_id = ? AND status = 'overdue'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['overdue_bookings'] = $result->fetch_assoc()['total'];

// Upcoming events
$sql = "SELECT e.*, er.registration_date 
        FROM events e
        INNER JOIN event_registrations er ON e.event_id = er.event_id
        WHERE er.member_id = ? AND e.event_date >= CURDATE() 
        AND er.status = 'confirmed'
        ORDER BY e.event_date ASC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$upcoming_events = $stmt->get_result();

// Recent messages
$sql = "SELECT * FROM messages 
        WHERE sender_id = ? 
        ORDER BY sent_date DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$recent_messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_member.php'; ?>

    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>

        <!-- Statistics Cards -->
        <div class="stats-container">
            <div class="stat-card">
                <h3><?php echo $stats['total_events']; ?></h3>
                <p>Events Registered</p>
                <a href="events.php" class="btn-link">View All</a>
            </div>

            <div class="stat-card">
                <h3><?php echo $stats['active_bookings']; ?></h3>
                <p>Active Bookings</p>
                <a href="my_bookings.php" class="btn-link">View Details</a>
            </div>

            <div class="stat-card <?php echo $stats['overdue_bookings'] > 0 ? 'warning' : ''; ?>">
                <h3><?php echo $stats['overdue_bookings']; ?></h3>
                <p>Overdue Returns</p>
                <?php if ($stats['overdue_bookings'] > 0): ?>
                    <a href="my_bookings.php" class="btn-link">Return Now!</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="section">
            <h2>Upcoming Events</h2>
            <?php if ($upcoming_events->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><span class="badge"><?php echo $event['event_type']; ?></span></td>
                                <td><?php echo format_date($event['event_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($event['event_time'])); ?></td>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No upcoming events. <a href="events.php">Browse available events</a></p>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-buttons">
                <a href="events.php"
                    class="action-btn"
                    style="display: inline-flex; align-items: center; padding: 10px 15px; margin: 5px; border: 1px solid #3498db; border-radius: 4px; text-decoration: none; color: #2c3e50; background-color: #ecf0f1; transition: background-color 0.3s; cursor: pointer;"
                    onmouseover="this.style.backgroundColor='#2980b9'; this.style.color='white';"
                    onmouseout="this.style.backgroundColor='#ecf0f1'; this.style.color='#2c3e50';">
                    <span class="icon" style="margin-right: 8px;">📅</span>
                    <span>Browse Events</span>
                </a>
                <a href="equipment.php"
                    class="action-btn"
                    style="display: inline-flex; align-items: center; padding: 10px 15px; margin: 5px; border: 1px solid #3498db; border-radius: 4px; text-decoration: none; color: #2c3e50; background-color: #ecf0f1; transition: background-color 0.3s; cursor: pointer;"
                    onmouseover="this.style.backgroundColor='#2980b9'; this.style.color='white';"
                    onmouseout="this.style.backgroundColor='#ecf0f1'; this.style.color='#2c3e50';">
                    <span class="icon" style="margin-right: 8px;">⚽</span>
                    <span>Book Equipment</span>
                </a>
                <a href="messages.php"
                    class="action-btn"
                    style="display: inline-flex; align-items: center; padding: 10px 15px; margin: 5px; border: 1px solid #3498db; border-radius: 4px; text-decoration: none; color: #2c3e50; background-color: #ecf0f1; transition: background-color 0.3s; cursor: pointer;"
                    onmouseover="this.style.backgroundColor='#2980b9'; this.style.color='white';"
                    onmouseout="this.style.backgroundColor='#ecf0f1'; this.style.color='#2c3e50';">
                    <span class="icon" style="margin-right: 8px;">✉️</span>
                    <span>Send Message</span>
                </a>
                <a href="my_bookings.php"
                    class="action-btn"
                    style="display: inline-flex; align-items: center; padding: 10px 15px; margin: 5px; border: 1px solid #3498db; border-radius: 4px; text-decoration: none; color: #2c3e50; background-color: #ecf0f1; transition: background-color 0.3s; cursor: pointer;"
                    onmouseover="this.style.backgroundColor='#2980b9'; this.style.color='white';"
                    onmouseout="this.style.backgroundColor='#ecf0f1'; this.style.color='#2c3e50';">
                    <span class="icon" style="margin-right: 8px;">📋</span>
                    <span>My Bookings</span>
                </a>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>

</html>