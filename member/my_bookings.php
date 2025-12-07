<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../member/login.php');
}

$member_id = $_SESSION['member_id'];

// Fetch member details
$member_sql = "SELECT * FROM members WHERE member_id = ?";
$member_stmt = $conn->prepare($member_sql);
$member_stmt->bind_param("i", $member_id);
$member_stmt->execute();
$member = $member_stmt->get_result()->fetch_assoc();

// Fetch event registrations
$events_sql = "SELECT r.registration_id, r.registration_date, r.status as reg_status,
                      e.event_id, e.event_name, e.event_type, e.event_date, e.event_time, 
                      e.location, e.max_participants, e.current_participants, e.description
               FROM event_registrations r
               JOIN events e ON r.event_id = e.event_id
               WHERE r.member_id = ?
               ORDER BY e.event_date DESC, e.event_time DESC";
$events_stmt = $conn->prepare($events_sql);
$events_stmt->bind_param("i", $member_id);
$events_stmt->execute();
$events_result = $events_stmt->get_result();

// Fetch equipment bookings
$equipment_sql = "SELECT b.booking_id, b.booking_date, b.return_date, b.actual_return_date, 
                         b.quantity, b.status,
                         eq.equipment_name, eq.equipment_type, eq.condition_status
                  FROM equipment_bookings b
                  JOIN equipment eq ON b.equipment_id = eq.equipment_id
                  WHERE b.member_id = ?
                  ORDER BY b.booking_date DESC";
$equipment_stmt = $conn->prepare($equipment_sql);
$equipment_stmt->bind_param("i", $member_id);
$equipment_stmt->execute();
$equipment_result = $equipment_stmt->get_result();

// Calculate statistics
$total_events = 0;
$upcoming_events = 0;
$total_equipment = 0;
$pending_equipment = 0;
$today = date('Y-m-d');

$events_data = [];
while ($row = $events_result->fetch_assoc()) {
    $events_data[] = $row;
    if ($row['reg_status'] == 'confirmed') {
        $total_events++;
        if ($row['event_date'] >= $today) {
            $upcoming_events++;
        }
    }
}

$equipment_data = [];
while ($row = $equipment_result->fetch_assoc()) {
    $equipment_data[] = $row;
    $total_equipment++;
    if ($row['status'] == 'pending' || $row['status'] == 'approved') {
        $pending_equipment++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/nav_member.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1>My Bookings</h1>
            <p>Welcome back, <?php echo htmlspecialchars($member['full_name']); ?>!</p>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <p>Total Event Registrations</p>
                <h3><?php echo $total_events; ?></h3>
                <a href="#events-section" class="stat-link">View Details →</a>
            </div>
            <div class="stat-card green">
                <p>Upcoming Events</p>
                <h3><?php echo $upcoming_events; ?></h3>
            </div>
            <div class="stat-card orange">
                <p>Equipment Bookings</p>
                <h3><?php echo $total_equipment; ?></h3>
                <a href="#equipment-section" class="stat-link">View Details →</a>
            </div>
            <div class="stat-card purple">
                <p>Pending Equipment</p>
                <h3><?php echo $pending_equipment; ?></h3>
            </div>
        </div>

        <!-- Event Registrations Section -->
        <div class="section" id="events-section">
            <h2>📅 Event Registrations</h2>
            
            <?php if (count($events_data) > 0): ?>
                <div class="events-grid">
                    <?php foreach ($events_data as $event): ?>
                        <?php 
                        $is_past = strtotime($event['event_date']) < strtotime($today);
                        $is_cancelled = $event['reg_status'] == 'cancelled';
                        $available_seats = $event['max_participants'] - $event['current_participants'];
                        ?>
                        <div class="event-card">
                            <div class="event-header">
                                <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                <span class="badge badge-<?php echo strtolower($event['event_type']); ?>">
                                    <?php echo ucfirst($event['event_type']); ?>
                                </span>
                            </div>
                            
                            <div class="event-body">
                                <p class="event-description">
                                    <?php echo htmlspecialchars($event['description']); ?>
                                </p>
                                
                                <div class="event-details">
                                    <div class="detail-item">
                                        <span class="icon">📅</span>
                                        <strong>Date:</strong> <?php echo format_date($event['event_date']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="icon">⏰</span>
                                        <strong>Time:</strong> <?php echo date('h:i A', strtotime($event['event_time'])); ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="icon">📍</span>
                                        <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <span class="icon">👥</span>
                                        <strong>Participants:</strong> 
                                        <span class="participant-count">
                                            <?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?>
                                        </span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="icon">✅</span>
                                        <strong>Registered:</strong> <?php echo format_date($event['registration_date']); ?>
                                    </div>
                                    <div class="detail-item">
                                        <strong>Status:</strong>
                                        <?php if ($is_cancelled): ?>
                                            <span class="status-badge status-overdue">CANCELLED</span>
                                        <?php elseif ($is_past): ?>
                                            <span class="status-badge status-returned">COMPLETED</span>
                                        <?php else: ?>
                                            <span class="status-badge status-approved">CONFIRMED</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!$is_past && !$is_cancelled): ?>
                                <div class="event-footer">
                                    <form method="POST" action="cancel_registration.php" 
                                          onsubmit="return confirm('Are you sure you want to cancel this registration?');" style="margin: 0;">
                                        <input type="hidden" name="registration_id" value="<?php echo $event['registration_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Cancel Registration</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>You haven't registered for any events yet.</p>
                    <a href="events.php" class="btn btn-primary">Browse Events</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Equipment Bookings Section -->
        <div class="section" id="equipment-section">
            <h2>🏏 Equipment Bookings</h2>
            
            <?php if (count($equipment_data) > 0): ?>
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Equipment</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Booking Date</th>
                                <th>Return Date</th>
                                <th>Actual Return</th>
                                <th>Condition</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment_data as $booking): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($booking['equipment_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['equipment_type']); ?></td>
                                    <td><?php echo $booking['quantity']; ?></td>
                                    <td><?php echo format_date($booking['booking_date']); ?></td>
                                    <td><?php echo format_date($booking['return_date']); ?></td>
                                    <td>
                                        <?php 
                                        echo $booking['actual_return_date'] 
                                            ? format_date($booking['actual_return_date']) 
                                            : '-'; 
                                        ?>
                                    </td>
                                    <td>
                                        <span class="condition-badge condition-<?php echo $booking['condition_status']; ?>">
                                            <?php echo ucfirst($booking['condition_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <p>You haven't booked any equipment yet.</p>
                    <a href="equipment.php" class="btn btn-primary">Browse Equipment</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php
$member_stmt->close();
$events_stmt->close();
$equipment_stmt->close();
$conn->close();
?>