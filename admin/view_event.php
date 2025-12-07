<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id == 0) {
    $_SESSION['error'] = "No event ID provided.";
    redirect('manage_events.php');
}

// Fetch event details with creator info
$event_sql = "SELECT e.*, a.full_name as admin_name, a.email as admin_email
              FROM events e 
              LEFT JOIN admin a ON e.created_by = a.admin_id 
              WHERE e.event_id = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event_result = $event_stmt->get_result();

if ($event_result->num_rows == 0) {
    $_SESSION['error'] = "Event not found.";
    redirect('manage_events.php');
}

$event = $event_result->fetch_assoc();

// Fetch registered members
$members_sql = "SELECT m.member_id, m.username, m.full_name, m.email, m.phone, m.address,
                       er.registration_id, er.registration_date, er.status
                FROM event_registrations er
                JOIN members m ON er.member_id = m.member_id
                WHERE er.event_id = ?
                ORDER BY er.registration_date DESC";
$members_stmt = $conn->prepare($members_sql);
$members_stmt->bind_param("i", $event_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();

// Count statistics
$total_registrations = 0;
$confirmed_count = 0;
$cancelled_count = 0;

$members_data = [];
while ($row = $members_result->fetch_assoc()) {
    $members_data[] = $row;
    $total_registrations++;
    if ($row['status'] == 'confirmed') {
        $confirmed_count++;
    } else {
        $cancelled_count++;
    }
}

$available_seats = $event['max_participants'] - $event['current_participants'];
$is_full = $available_seats <= 0;
$is_past = strtotime($event['event_date']) < strtotime(date('Y-m-d'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['event_name']); ?> - Event Details</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .event-detail-header {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .event-title-section {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .event-title-section h1 {
            color: var(--dark-text);
            margin: 0;
            flex: 1;
        }
        
        .title-badges {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .event-actions {
            display: flex;
            gap: 10px;
        }
        
        .event-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .meta-box {
            padding: 15px;
            background: var(--light-bg);
            border-radius: 6px;
            border-left: 4px solid var(--secondary-color);
        }
        
        .meta-box.success {
            border-left-color: var(--success-color);
        }
        
        .meta-box.danger {
            border-left-color: var(--danger-color);
        }
        
        .meta-box.warning {
            border-left-color: var(--warning-color);
        }
        
        .meta-label {
            font-size: 0.85em;
            color: var(--light-text);
            margin-bottom: 8px;
            display: block;
        }
        
        .meta-value {
            font-size: 1.3em;
            font-weight: bold;
            color: var(--dark-text);
        }
        
        .description-box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
        }
        
        .members-section {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        
        .members-header {
            padding: 20px 30px;
            background: var(--primary-color);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .registration-stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        
        .print-button {
            background: white;
            color: var(--primary-color);
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            
            .event-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav_admin.php'; ?>

    <div class="admin-container">
        <div class="no-print">
            <a href="manage_events.php" class="btn btn-secondary">← Back to Events</a>
        </div>

        <div class="event-detail-header">
            <div class="event-title-section">
                <div style="flex: 1;">
                    <h1>📅 <?php echo htmlspecialchars($event['event_name']); ?></h1>
                    <div class="title-badges" style="margin-top: 10px;">
                        <span class="badge badge-<?php echo strtolower($event['event_type']); ?>">
                            <?php echo ucfirst($event['event_type']); ?>
                        </span>
                        <?php if ($is_past): ?>
                            <span class="status-badge status-returned">Completed</span>
                        <?php elseif ($is_full): ?>
                            <span class="status-badge status-overdue">Full</span>
                        <?php else: ?>
                            <span class="status-badge status-approved">Open</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="event-actions no-print">
                    <a href="edit_event.php?id=<?php echo $event_id; ?>" class="btn btn-primary">
                        ✏️ Edit Event
                    </a>
                    <a href="delete_event.php?id=<?php echo $event_id; ?>" class="btn btn-danger">
                        🗑️ Delete
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary print-button">
                        🖨️ Print
                    </button>
                </div>
            </div>

            <div class="event-meta-grid">
                <div class="meta-box">
                    <span class="meta-label">📅 Date</span>
                    <div class="meta-value"><?php echo format_date($event['event_date']); ?></div>
                </div>
                <div class="meta-box">
                    <span class="meta-label">⏰ Time</span>
                    <div class="meta-value"><?php echo date('h:i A', strtotime($event['event_time'])); ?></div>
                </div>
                <div class="meta-box">
                    <span class="meta-label">📍 Location</span>
                    <div class="meta-value" style="font-size: 1em;">
                        <?php echo htmlspecialchars($event['location']); ?>
                    </div>
                </div>
                <div class="meta-box">
                    <span class="meta-label">👥 Max Capacity</span>
                    <div class="meta-value"><?php echo $event['max_participants']; ?></div>
                </div>
                <div class="meta-box success">
                    <span class="meta-label">✅ Current Participants</span>
                    <div class="meta-value" style="color: var(--success-color);">
                        <?php echo $event['current_participants']; ?>
                    </div>
                </div>
                <div class="meta-box <?php echo $available_seats > 0 ? 'success' : 'danger'; ?>">
                    <span class="meta-label">💺 Available Seats</span>
                    <div class="meta-value" style="color: <?php echo $available_seats > 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>;">
                        <?php echo $available_seats; ?>
                    </div>
                </div>
                <?php if ($event['admin_name']): ?>
                <div class="meta-box">
                    <span class="meta-label">👤 Created By</span>
                    <div class="meta-value" style="font-size: 0.9em;">
                        <?php echo htmlspecialchars($event['admin_name']); ?>
                    </div>
                </div>
                <?php endif; ?>
                <div class="meta-box">
                    <span class="meta-label">📆 Created Date</span>
                    <div class="meta-value" style="font-size: 0.9em;">
                        <?php echo format_date($event['created_date']); ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="description-box">
            <h2>📝 Description</h2>
            <p style="color: var(--light-text); line-height: 1.8; margin-top: 15px; white-space: pre-line;">
                <?php echo htmlspecialchars($event['description']); ?>
            </p>
        </div>

        <div class="members-section">
            <div class="members-header">
                <h2 style="margin: 0;">👥 Registered Members</h2>
                <div class="registration-stats">
                    <span class="stat-badge">
                        ✅ Confirmed: <?php echo $confirmed_count; ?>
                    </span>
                    <?php if ($cancelled_count > 0): ?>
                    <span class="stat-badge">
                        ❌ Cancelled: <?php echo $cancelled_count; ?>
                    </span>
                    <?php endif; ?>
                    <span class="stat-badge">
                        📊 Total: <?php echo $total_registrations; ?>
                    </span>
                </div>
            </div>

            <?php if (count($members_data) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members_data as $member): ?>
                            <tr>
                                <td><?php echo $member['member_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($member['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                <td><?php echo format_date($member['registration_date']); ?> 
                                    <br><small><?php echo date('h:i A', strtotime($member['registration_date'])); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $member['status'] == 'confirmed' ? 'status-approved' : 'status-overdue'; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <h3>No Registrations Yet</h3>
                    <p>No members have registered for this event.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (check_event_capacity($conn, $event_id)): ?>
        <div class="info-box" style="margin-top: 20px;">
            <strong>ℹ️ Registration Status:</strong> This event is accepting registrations. 
            Members can register through the member portal.
        </div>
        <?php else: ?>
        <div class="alert alert-warning" style="margin-top: 20px;">
            <strong>⚠️ Event Full:</strong> This event has reached maximum capacity. 
            No new registrations can be accepted.
        </div>
        <?php endif; ?>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php
$event_stmt->close();
$members_stmt->close();
$conn->close();
?>