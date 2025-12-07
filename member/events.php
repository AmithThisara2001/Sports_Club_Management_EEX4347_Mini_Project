<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$member_id = $_SESSION['member_id'];

// Filter options
$event_type = isset($_GET['type']) ? clean_input($_GET['type']) : 'all';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query
$sql = "SELECT e.*, 
        (e.max_participants - e.current_participants) as available_slots,
        CASE WHEN er.registration_id IS NOT NULL THEN 1 ELSE 0 END as is_registered
        FROM events e
        LEFT JOIN event_registrations er ON e.event_id = er.event_id AND er.member_id = ?
        WHERE e.event_date >= CURDATE()";

$params = [$member_id];
$types = "i";

if ($event_type != 'all') {
    $sql .= " AND e.event_type = ?";
    $params[] = $event_type;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (e.event_name LIKE ? OR e.description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$sql .= " ORDER BY e.event_date ASC, e.event_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$events = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_member.php'; ?>

    <div class="container">
        <h1>Available Events</h1>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label>Event Type:</label>
                    <select name="type" onchange="this.form.submit()">
                        <option value="all" <?php echo $event_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="football" <?php echo $event_type == 'football' ? 'selected' : ''; ?>>Football</option>
                        <option value="cricket" <?php echo $event_type == 'cricket' ? 'selected' : ''; ?>>Cricket</option>
                        <option value="tournament" <?php echo $event_type == 'tournament' ? 'selected' : ''; ?>>Tournament</option>
                        <option value="other" <?php echo $event_type == 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>


            </form>
        </div>

        <!-- Events List -->
        <div class="events-grid">
            <?php if ($events->num_rows > 0): ?>
                <?php while ($event = $events->fetch_assoc()): ?>
                    <div class="event-card">
                        <div class="event-header">
                            <h3><?php echo htmlspecialchars($event['event_name']); ?></h3>
                            <span class="badge badge-<?php echo $event['event_type']; ?>">
                                <?php echo ucfirst($event['event_type']); ?>
                            </span>
                        </div>

                        <div class="event-body">
                            <p class="event-description"><?php echo htmlspecialchars($event['description']); ?></p>

                            <div class="event-details">
                                <div class="detail-item">
                                    <span class="icon">📅</span>
                                    <span><?php echo format_date($event['event_date']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="icon">🕐</span>
                                    <span><?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="icon">📍</span>
                                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="icon">👥</span>
                                    <span><?php echo $event['available_slots']; ?> slots available</span>
                                </div>
                            </div>
                        </div>

                        <div class="event-footer">
                            <?php if ($event['is_registered']): ?>
                                <button class="btn btn-success" disabled>✓ Registered</button>
                                <a href="cancel_registration.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to cancel this registration?')">
                                    Cancel
                                </a>
                            <?php elseif ($event['available_slots'] > 0): ?>
                                <a href="register_event.php?event_id=<?php echo $event['event_id']; ?>"
                                    class="btn btn-primary">
                                    Register Now
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Event Full</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-data">No events found matching your criteria.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>

</html>