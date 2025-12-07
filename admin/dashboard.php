<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Update overdue bookings
get_overdue_bookings($conn);

// Get statistics
$stats = [];

// Total members
$sql = "SELECT COUNT(*) as total FROM members WHERE status = 'active'";
$result = $conn->query($sql);
$stats['total_members'] = $result->fetch_assoc()['total'];

// Total events
$sql = "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()";
$result = $conn->query($sql);
$stats['upcoming_events'] = $result->fetch_assoc()['total'];

// Pending bookings
$sql = "SELECT COUNT(*) as total FROM equipment_bookings WHERE status = 'pending'";
$result = $conn->query($sql);
$stats['pending_bookings'] = $result->fetch_assoc()['total'];

// Overdue bookings
$sql = "SELECT COUNT(*) as total FROM equipment_bookings WHERE status = 'overdue'";
$result = $conn->query($sql);
$stats['overdue_bookings'] = $result->fetch_assoc()['total'];

// Unread messages
$sql = "SELECT COUNT(*) as total FROM messages WHERE read_status = 0";
$result = $conn->query($sql);
$stats['unread_messages'] = $result->fetch_assoc()['total'];

// Recent registrations
$sql = "SELECT m.full_name, m.email, m.registration_date 
        FROM members m 
        ORDER BY m.registration_date DESC 
        LIMIT 5";
$recent_members = $conn->query($sql);

// Upcoming events
$sql = "SELECT event_name, event_type, event_date, event_time, current_participants, max_participants 
        FROM events 
        WHERE event_date >= CURDATE() 
        ORDER BY event_date ASC 
        LIMIT 5";
$upcoming_events = $conn->query($sql);

// Recent equipment bookings
$sql = "SELECT eb.*, m.full_name, e.equipment_name 
        FROM equipment_bookings eb
        INNER JOIN members m ON eb.member_id = m.member_id
        INNER JOIN equipment e ON eb.equipment_id = e.equipment_id
        ORDER BY eb.booking_id DESC 
        LIMIT 5";
$recent_bookings = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_admin.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>!</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_members']; ?></h3>
                    <p>Active Members</p>
                </div>
                <a href="manage_members.php" class="stat-link">View Details →</a>
            </div>
            
            <div class="stat-card green">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <h3><?php echo $stats['upcoming_events']; ?></h3>
                    <p>Upcoming Events</p>
                </div>
                <a href="manage_events.php" class="stat-link">Manage Events →</a>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3><?php echo $stats['pending_bookings']; ?></h3>
                    <p>Pending Bookings</p>
                </div>
                <a href="bookings.php?status=pending" class="stat-link">Review Now →</a>
            </div>
            
            <div class="stat-card <?php echo $stats['overdue_bookings'] > 0 ? 'red' : 'gray'; ?>">
                <div class="stat-icon">⚠️</div>
                <div class="stat-content">
                    <h3><?php echo $stats['overdue_bookings']; ?></h3>
                    <p>Overdue Returns</p>
                </div>
                <a href="bookings.php?status=overdue" class="stat-link">View Details →</a>
            </div>
            
            <div class="stat-card purple">
                <div class="stat-icon">✉️</div>
                <div class="stat-content">
                    <h3><?php echo $stats['unread_messages']; ?></h3>
                    <p>Unread Messages</p>
                </div>
                <a href="messages.php" class="stat-link">Read Messages →</a>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions-section">
            <h2>Quick Actions</h2>
            <div class="action-grid">
                <a href="add_event.php" class="action-card">
                    <span class="action-icon">➕</span>
                    <span class="action-text">Add New Event</span>
                </a>
                <a href="add_equipment.php" class="action-card">
                    <span class="action-icon">⚽</span>
                    <span class="action-text">Add Equipment</span>
                </a>
                <a href="reports.php" class="action-card">
                    <span class="action-icon">📊</span>
                    <span class="action-text">Generate Reports</span>
                </a>
                <a href="manage_members.php" class="action-card">
                    <span class="action-icon">👤</span>
                    <span class="action-text">Manage Members</span>
                </a>
            </div>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="activity-section">
            <div class="activity-column">
                <h2>Recent Member Registrations</h2>
                <?php if ($recent_members->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($member = $recent_members->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo format_date($member['registration_date']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No recent registrations</p>
                <?php endif; ?>
            </div>
            
            <div class="activity-column">
                <h2>Upcoming Events</h2>
                <?php if ($upcoming_events->num_rows > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Participants</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($event = $upcoming_events->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($event['event_name']); ?>
                                    <span class="badge"><?php echo $event['event_type']; ?></span>
                                </td>
                                <td><?php echo format_date($event['event_date']); ?></td>
                                <td><?php echo $event['current_participants']; ?> / <?php echo $event['max_participants']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data">No upcoming events</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Bookings -->
        <div class="section">
            <h2>Recent Equipment Bookings</h2>
            <?php if ($recent_bookings->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Member</th>
                            <th>Equipment</th>
                            <th>Booking Date</th>
                            <th>Return Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $booking['booking_id']; ?></td>
                            <td><?php echo htmlspecialchars($booking['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($booking['equipment_name']); ?> (<?php echo $booking['quantity']; ?>)</td>
                            <td><?php echo format_date($booking['booking_date']); ?></td>
                            <td><?php echo format_date($booking['return_date']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $booking['status']; ?>">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="approve_booking.php?id=<?php echo $booking['booking_id']; ?>" 
                                       class="btn btn-sm btn-success">Approve</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No recent bookings</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>