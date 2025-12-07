<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No event ID provided.";
    redirect('manage_events.php');
}

$event_id = (int)$_GET['id'];

// Fetch event details
$event_sql = "SELECT e.*, a.full_name as admin_name,
              (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id) as total_registrations,
              (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.event_id AND status = 'confirmed') as confirmed_registrations
              FROM events e 
              LEFT JOIN admin a ON e.created_by = a.admin_id 
              WHERE e.event_id = ?";
$event_stmt = $conn->prepare($event_sql);
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$result = $event_stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Event not found.";
    redirect('manage_events.php');
}

$event = $result->fetch_assoc();

// Check if event is in the past or future
$is_past = strtotime($event['event_date']) < strtotime(date('Y-m-d'));

// Fetch registered members for display
$members_sql = "SELECT m.full_name, m.email, er.status
                FROM event_registrations er
                JOIN members m ON er.member_id = m.member_id
                WHERE er.event_id = ?
                ORDER BY er.registration_date DESC
                LIMIT 5";
$members_stmt = $conn->prepare($members_sql);
$members_stmt->bind_param("i", $event_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();

$registered_members = [];
while ($member = $members_result->fetch_assoc()) {
    $registered_members[] = $member;
}

// Handle confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Start transaction for safety
    $conn->begin_transaction();
    
    try {
        // Delete event registrations first (if CASCADE is not set)
        $delete_registrations = "DELETE FROM event_registrations WHERE event_id = ?";
        $del_reg_stmt = $conn->prepare($delete_registrations);
        $del_reg_stmt->bind_param("i", $event_id);
        $del_reg_stmt->execute();
        
        // Delete the event
        $delete_event = "DELETE FROM events WHERE event_id = ?";
        $del_event_stmt = $conn->prepare($delete_event);
        $del_event_stmt->bind_param("i", $event_id);
        $del_event_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Event '{$event['event_name']}' and {$event['total_registrations']} registration(s) deleted successfully!";
        redirect('manage_events.php');
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $_SESSION['error'] = "Failed to delete event: " . $e->getMessage();
        redirect('manage_events.php');
    }
}

// Handle cancel
if (isset($_GET['cancel'])) {
    redirect('manage_events.php');
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Event - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .delete-container {
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .delete-card {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }
        
        .warning-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .warning-icon {
            font-size: 80px;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .delete-title {
            color: var(--danger-color);
            margin: 0;
            font-size: 2em;
        }
        
        .event-info-card {
            background: var(--light-bg);
            padding: 25px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 4px solid var(--danger-color);
        }
        
        .event-info-card h3 {
            margin: 0 0 15px 0;
            color: var(--dark-text);
            font-size: 1.4em;
        }
        
        .event-detail {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .event-detail:last-child {
            border-bottom: none;
        }
        
        .event-detail .label {
            font-weight: 600;
            color: var(--light-text);
        }
        
        .event-detail .value {
            color: var(--dark-text);
            font-weight: 500;
        }
        
        .warning-message {
            background: #fff3cd;
            border: 2px solid var(--warning-color);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .warning-message h4 {
            color: #856404;
            margin: 0 0 15px 0;
            font-size: 1.1em;
        }
        
        .danger-message {
            background: #f8d7da;
            border: 2px solid var(--danger-color);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }
        
        .danger-message h4 {
            color: #721c24;
            margin: 0 0 15px 0;
            font-size: 1.1em;
        }
        
        .impact-list {
            margin: 15px 0 0 20px;
        }
        
        .impact-list li {
            margin: 8px 0;
            color: #721c24;
            font-weight: 500;
        }
        
        .members-preview {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .members-preview h5 {
            margin: 0 0 10px 0;
            color: var(--dark-text);
        }
        
        .member-item {
            padding: 8px;
            background: var(--light-bg);
            margin: 5px 0;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .confirmation-section {
            background: #e7f3ff;
            border: 2px solid var(--info-color);
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: center;
        }
        
        .confirmation-section p {
            color: #0c5460;
            font-size: 1.1em;
            margin: 0;
            font-weight: 500;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-delete-confirm {
            background: var(--danger-color);
            color: white;
            padding: 15px 40px;
            font-size: 1.1em;
            font-weight: bold;
        }
        
        .btn-delete-confirm:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
        }
        
        .event-status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .status-upcoming {
            background: var(--success-color);
            color: white;
        }
        
        .status-past {
            background: var(--light-text);
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/nav_admin.php'; ?>

    <div class="delete-container">
        <div class="delete-card">
            <div class="warning-header">
                <div class="warning-icon">⚠️</div>
                <h1 class="delete-title">Delete Event</h1>
                <p style="color: var(--light-text); margin-top: 10px;">
                    This action is permanent and cannot be undone
                </p>
            </div>
            
            <!-- Event Information -->
            <div class="event-info-card">
                <h3>📅 <?php echo htmlspecialchars($event['event_name']); ?></h3>
                
                <div class="event-detail">
                    <span class="label">Event ID:</span>
                    <span class="value">#<?php echo $event_id; ?></span>
                </div>
                
                <div class="event-detail">
                    <span class="label">Event Type:</span>
                    <span class="value">
                        <span class="badge badge-<?php echo strtolower($event['event_type']); ?>">
                            <?php echo ucfirst($event['event_type']); ?>
                        </span>
                    </span>
                </div>
                
                <div class="event-detail">
                    <span class="label">Date:</span>
                    <span class="value"><?php echo format_date($event['event_date']); ?></span>
                </div>
                
                <div class="event-detail">
                    <span class="label">Time:</span>
                    <span class="value"><?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                </div>
                
                <div class="event-detail">
                    <span class="label">Location:</span>
                    <span class="value"><?php echo htmlspecialchars($event['location']); ?></span>
                </div>
                
                <div class="event-detail">
                    <span class="label">Capacity:</span>
                    <span class="value"><?php echo $event['current_participants']; ?> / <?php echo $event['max_participants']; ?> participants</span>
                </div>
                
                <div class="event-detail">
                    <span class="label">Status:</span>
                    <span class="value">
                        <span class="event-status-badge <?php echo $is_past ? 'status-past' : 'status-upcoming'; ?>">
                            <?php echo $is_past ? 'PAST EVENT' : 'UPCOMING EVENT'; ?>
                        </span>
                    </span>
                </div>
                
                <?php if ($event['admin_name']): ?>
                <div class="event-detail">
                    <span class="label">Created By:</span>
                    <span class="value"><?php echo htmlspecialchars($event['admin_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Danger Warning -->
            <div class="danger-message">
                <h4>🔴 CRITICAL WARNING</h4>
                <p><strong>Deleting this event will permanently remove:</strong></p>
                <ul class="impact-list">
                    <li>✖️ The event and all its details</li>
                    <li>✖️ All <?php echo $event['total_registrations']; ?> member registration(s)</li>
                    <li>✖️ <?php echo $event['confirmed_registrations']; ?> confirmed registration(s)</li>
                    <li>✖️ Complete event history and records</li>
                </ul>
                
                <?php if (count($registered_members) > 0): ?>
                <div class="members-preview">
                    <h5>👥 Registered Members (showing <?php echo min(5, count($registered_members)); ?> of <?php echo $event['total_registrations']; ?>):</h5>
                    <?php foreach ($registered_members as $member): ?>
                        <div class="member-item">
                            <span><?php echo htmlspecialchars($member['full_name']); ?></span>
                            <span class="status-badge <?php echo $member['status'] == 'confirmed' ? 'status-approved' : 'status-overdue'; ?>">
                                <?php echo ucfirst($member['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <?php if ($event['total_registrations'] > 5): ?>
                        <p style="text-align: center; margin-top: 10px; color: var(--light-text);">
                            ...and <?php echo $event['total_registrations'] - 5; ?> more
                        </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Additional Warning for Upcoming Events -->
            <?php if (!$is_past && $event['confirmed_registrations'] > 0): ?>
            <div class="warning-message">
                <h4>⚠️ NOTICE</h4>
                <p>
                    This is an <strong>upcoming event</strong> with <strong><?php echo $event['confirmed_registrations']; ?> confirmed participant(s)</strong>. 
                    Deleting it will remove their registrations without notification.
                </p>
                <p style="margin-top: 10px;">
                    <strong>Consider:</strong> Editing the event details instead of deleting it, or notifying members before deletion.
                </p>
            </div>
            <?php endif; ?>
            
            <!-- Confirmation Question -->
            <div class="confirmation-section">
                <p>
                    ❓ Are you <strong>absolutely sure</strong> you want to delete this event?
                </p>
            </div>
            
            <!-- Action Buttons -->
            <form method="POST" action="">
                <div class="button-group">
                    <button type="submit" name="confirm_delete" class="btn btn-danger btn-delete-confirm"
                            onclick="return confirm('⚠️ FINAL CONFIRMATION\n\nYou are about to permanently delete:\n• Event: <?php echo addslashes($event['event_name']); ?>\n• <?php echo $event['total_registrations']; ?> Registration(s)\n\nType OK if you\'re sure:') && prompt('Type DELETE to confirm:') === 'DELETE';">
                        🗑️ Yes, Delete Event Permanently
                    </button>
                    <a href="manage_events.php" class="btn btn-secondary" style="padding: 15px 40px; font-size: 1.1em;">
                        ← No, Keep Event
                    </a>
                </div>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: var(--light-text); font-size: 0.9em;">
                💡 Tip: You can also <a href="edit_event.php?id=<?php echo $event_id; ?>" style="color: var(--secondary-color);">edit this event</a> instead of deleting it.
            </p>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Additional JavaScript confirmation for extra safety
        document.querySelector('form').addEventListener('submit', function(e) {
            const confirmText = '<?php echo addslashes($event['event_name']); ?>';
            const userConfirm = prompt('⚠️ FINAL SAFETY CHECK\n\nTo confirm deletion, type the event name:\n\n' + confirmText);
            
            if (userConfirm !== confirmText) {
                e.preventDefault();
                alert('❌ Deletion cancelled. Event name did not match.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>