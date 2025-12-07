<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Handle delete
if (isset($_GET['delete'])) {
    $event_id = (int)$_GET['delete'];
    
    // Check if event has registrations
    $check_sql = "SELECT COUNT(*) as count FROM event_registrations WHERE event_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete event with existing registrations!";
    } else {
        $sql = "DELETE FROM events WHERE event_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $event_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Event deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete event!";
        }
    }
    
    redirect('manage_events.php');
}

// Get all events
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$sql = "SELECT e.*, a.full_name as created_by_name 
        FROM events e
        LEFT JOIN admin a ON e.created_by = a.admin_id";

if ($filter == 'upcoming') {
    $sql .= " WHERE e.event_date >= CURDATE()";
} elseif ($filter == 'past') {
    $sql .= " WHERE e.event_date < CURDATE()";
}

$sql .= " ORDER BY e.event_date DESC, e.event_time DESC";

$events = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_admin.php'; ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>Manage Events</h1>
            <a href="add_event.php" class="btn btn-primary">+ Add New Event</a>
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
            <a href="?filter=all" class="tab <?php echo $filter == 'all' ? 'active' : ''; ?>">All Events</a>
            <a href="?filter=upcoming" class="tab <?php echo $filter == 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
            <a href="?filter=past" class="tab <?php echo $filter == 'past' ? 'active' : ''; ?>">Past</a>
        </div>
        
        <!-- Events Table -->
        <div class="table-container">
            <?php if ($events->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Event Name</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Participants</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($event = $events->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $event['event_id']; ?></td>
                            <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                            <td><span class="badge badge-<?php echo $event['event_type']; ?>"><?php echo ucfirst($event['event_type']); ?></span></td>
                            <td>
                                <?php echo format_date($event['event_date']); ?><br>
                                <small><?php echo date('h:i A', strtotime($event['event_time'])); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                            <td>
                                <span class="participant-count">
                                    <?php echo $event['current_participants']; ?> / <?php echo $event['max_participants']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($event['created_by_name']); ?></td>
                            <td class="action-buttons">
                                <a href="view_event.php?id=<?php echo $event['event_id']; ?>" 
                                   class="btn btn-sm btn-info" 
                                   title="View Details">👁️</a>
                                <a href="edit_event.php?id=<?php echo $event['event_id']; ?>" 
                                   class="btn btn-sm btn-warning" 
                                   title="Edit">✏️</a>
                                <a href="delete_event.php?id=<?php echo $event['event_id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   title="Delete">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No events found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>