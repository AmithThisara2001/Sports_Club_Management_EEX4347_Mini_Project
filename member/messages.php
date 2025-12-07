<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$member_id = $_SESSION['member_id'];
$error = '';
$success = '';

// Handle Send New Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = clean_input($_POST['subject']);
    $message_text = clean_input($_POST['message_text']);
    
    if (empty($subject) || empty($message_text)) {
        $error = "Subject and message are required.";
    } elseif (strlen($message_text) < 10) {
        $error = "Message must be at least 10 characters long.";
    } else {
        // Insert new message
        $insert_sql = "INSERT INTO messages (sender_id, subject, message_text) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iss", $member_id, $subject, $message_text);
        
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Message sent successfully! Admin will reply soon.";
            redirect('messages.php');
        } else {
            $error = "Failed to send message: " . $conn->error;
        }
    }
}

// Handle Delete Message
if (isset($_GET['delete'])) {
    $message_id = (int)$_GET['delete'];
    
    // Verify message belongs to this member
    $check_sql = "SELECT message_id FROM messages WHERE message_id = ? AND sender_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $message_id, $member_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $delete_sql = "DELETE FROM messages WHERE message_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $message_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Message deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete message.";
        }
    } else {
        $_SESSION['error'] = "Unauthorized access.";
    }
    
    redirect('messages.php');
}

// Filter messages
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query
$where_conditions = ["sender_id = ?"];
$params = [$member_id];
$types = 'i';

if ($filter == 'replied') {
    $where_conditions[] = "reply_text IS NOT NULL";
} elseif ($filter == 'pending') {
    $where_conditions[] = "reply_text IS NULL";
} elseif ($filter == 'unread') {
    $where_conditions[] = "read_status = FALSE";
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

// Fetch member's messages
$sql = "SELECT m.*, a.full_name as admin_name
        FROM messages m
        LEFT JOIN admin a ON m.receiver_id = a.admin_id
        $where_clause
        ORDER BY m.sent_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_messages,
    SUM(CASE WHEN reply_text IS NOT NULL THEN 1 ELSE 0 END) as replied_messages,
    SUM(CASE WHEN reply_text IS NULL THEN 1 ELSE 0 END) as pending_messages,
    SUM(CASE WHEN read_status = FALSE THEN 1 ELSE 0 END) as unread_by_admin
    FROM messages
    WHERE sender_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $member_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get member info
$member_sql = "SELECT full_name FROM members WHERE member_id = ?";
$member_stmt = $conn->prepare($member_sql);
$member_stmt->bind_param("i", $member_id);
$member_stmt->execute();
$member = $member_stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Messages - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .messages-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .compose-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            border-top: 4px solid var(--secondary-color);
        }
        
        .compose-section h2 {
            margin: 0 0 20px 0;
            color: var(--secondary-color);
        }
        
        .message-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--light-text);
            transition: all 0.3s;
        }
        
        .message-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .message-card.has-reply {
            border-left-color: var(--success-color);
            background: #f8fff9;
        }
        
        .message-card.pending {
            border-left-color: var(--warning-color);
        }
        
        .message-card.unread-by-admin {
            border-left-color: var(--info-color);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .message-subject {
            font-size: 1.3em;
            font-weight: 600;
            color: var(--dark-text);
            margin: 0;
        }
        
        .message-meta {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .message-date {
            color: var(--light-text);
            font-size: 0.9em;
        }
        
        .message-content {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            line-height: 1.6;
        }
        
        .message-content p {
            margin: 0;
            color: var(--dark-text);
        }
        
        .admin-reply-section {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid var(--success-color);
            margin-top: 20px;
        }
        
        .admin-reply-section h4 {
            margin: 0 0 12px 0;
            color: var(--success-color);
            font-size: 1em;
        }
        
        .admin-reply-text {
            color: var(--dark-text);
            line-height: 1.6;
        }
        
        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .no-reply-notice {
            background: #fff9e6;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            color: #856404;
            border-left: 4px solid var(--warning-color);
        }
        
        .compose-toggle {
            background: var(--secondary-color);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .compose-toggle:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }
        
        .compose-form {
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .message-header {
                flex-direction: column;
            }
            
            .message-actions {
                flex-direction: column;
            }
            
            .message-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav_member.php'; ?>

    <div class="container">
        <div class="page-header">
            <div>
                <h1>💬 My Messages</h1>
                <p>Welcome back, <?php echo htmlspecialchars($member['full_name']); ?>!</p>
            </div>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
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
        if ($error) {
            echo show_message($error, 'danger');
        }
        ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <p>Total Messages</p>
                <h3><?php echo $stats['total_messages']; ?></h3>
            </div>
            <div class="stat-card green">
                <p>Replied by Admin</p>
                <h3><?php echo $stats['replied_messages']; ?></h3>
            </div>
            <div class="stat-card orange">
                <p>Pending Reply</p>
                <h3><?php echo $stats['pending_messages']; ?></h3>
            </div>
            <div class="stat-card purple">
                <p>Unread by Admin</p>
                <h3><?php echo $stats['unread_by_admin']; ?></h3>
            </div>
        </div>

        <!-- Compose New Message -->
        <div class="compose-section">
            <button class="compose-toggle" onclick="toggleComposeForm()">
                ✉️ Compose New Message
            </button>
            
            <div class="compose-form" id="composeForm" style="display: none;">
                <h2>📝 Send Message to Admin</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" 
                               placeholder="Brief subject of your message" required>
                        <small>Be clear and concise about your inquiry</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="message_text">Message *</label>
                        <textarea id="message_text" name="message_text" rows="8" required
                                  placeholder="Type your message here... (minimum 10 characters)"></textarea>
                        <small>Provide details about your question or concern</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="send_message" class="btn btn-success">
                            📤 Send Message
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="toggleComposeForm()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <div class="filter-tabs">
                <a href="?filter=all" class="tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                    📬 All Messages (<?php echo $stats['total_messages']; ?>)
                </a>
                <a href="?filter=replied" class="tab <?php echo $filter == 'replied' ? 'active' : ''; ?>">
                    ✅ Replied (<?php echo $stats['replied_messages']; ?>)
                </a>
                <a href="?filter=pending" class="tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                    ⏳ Pending (<?php echo $stats['pending_messages']; ?>)
                </a>
            </div>
        </div>

        <!-- Messages List -->
        <div class="section">
            <h2>📨 Your Messages</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="messages-container">
                    <?php while ($message = $result->fetch_assoc()): ?>
                        <div class="message-card 
                                    <?php echo $message['reply_text'] ? 'has-reply' : 'pending'; ?>
                                    <?php echo !$message['read_status'] ? 'unread-by-admin' : ''; ?>">
                            
                            <div class="message-header">
                                <h3 class="message-subject">
                                    📋 <?php echo htmlspecialchars($message['subject']); ?>
                                </h3>
                                <div class="message-meta">
                                    <?php if ($message['reply_text']): ?>
                                        <span class="status-badge status-approved">✅ REPLIED</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">⏳ PENDING</span>
                                    <?php endif; ?>
                                    
                                    <?php if (!$message['read_status']): ?>
                                        <span class="status-badge status-returned">👁️ UNREAD</span>
                                    <?php else: ?>
                                        <span class="status-badge status-approved">✓ READ</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="message-date">
                                📅 Sent on <?php echo format_date($message['sent_date']); ?>
                                at <?php echo date('h:i A', strtotime($message['sent_date'])); ?>
                            </div>
                            
                            <div class="message-content">
                                <p><strong>Your Message:</strong></p>
                                <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($message['message_text'])); ?></p>
                            </div>
                            
                            <?php if ($message['reply_text']): ?>
                                <div class="admin-reply-section">
                                    <h4>💬 Admin's Reply:</h4>
                                    <div class="admin-reply-text">
                                        <?php echo nl2br(htmlspecialchars($message['reply_text'])); ?>
                                    </div>
                                    <?php if ($message['admin_name']): ?>
                                        <p style="margin-top: 10px; color: var(--light-text); font-size: 0.9em;">
                                            - <?php echo htmlspecialchars($message['admin_name']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-reply-notice">
                                    <strong>⏳ Waiting for admin response</strong>
                                    <p style="margin: 5px 0 0 0; font-size: 0.9em;">
                                        Your message has been <?php echo $message['read_status'] ? 'read' : 'sent'; ?>. 
                                        Admin will reply soon.
                                    </p>
                                </div>
                            <?php endif; ?>
                            
                            <div class="message-actions">
                                <a href="?delete=<?php echo $message['message_id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this message?\n\nThis will also delete any admin replies.');">
                                    🗑️ Delete Message
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <?php if ($filter !== 'all'): ?>
                        📭 No <?php echo $filter; ?> messages found.
                        <br><a href="messages.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">View All Messages</a>
                    <?php else: ?>
                        📭 You haven't sent any messages yet.
                        <br><button class="btn btn-primary btn-sm" style="margin-top: 10px;" onclick="toggleComposeForm()">
                            Send Your First Message
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Help Section -->
        <div class="info-box">
            <strong>ℹ️ How to Use Messages:</strong>
            <ul>
                <li>💬 <strong>Send a Message:</strong> Click "Compose New Message" button above</li>
                <li>📨 <strong>Check Replies:</strong> Messages with admin replies have a green border</li>
                <li>⏳ <strong>Pending Messages:</strong> Messages waiting for reply have an orange border</li>
                <li>👁️ <strong>Read Status:</strong> You can see if admin has read your message</li>
                <li>🗑️ <strong>Delete:</strong> You can delete messages you no longer need</li>
                <li>📋 <strong>Track Status:</strong> Use filters to view replied or pending messages</li>
            </ul>
        </div>

        <!-- Quick Tips -->
        <div class="section">
            <h2>💡 Quick Tips for Better Responses</h2>
            <div class="events-grid">
                <div class="event-card">
                    <div class="event-header">
                        <h3>✍️ Be Clear</h3>
                    </div>
                    <div class="event-body">
                        <p>Use a descriptive subject line and provide clear details in your message.</p>
                    </div>
                </div>
                
                <div class="event-card">
                    <div class="event-header">
                        <h3>📝 Be Specific</h3>
                    </div>
                    <div class="event-body">
                        <p>Include relevant details like event names, dates, or booking IDs if applicable.</p>
                    </div>
                </div>
                
                <div class="event-card">
                    <div class="event-header">
                        <h3>⏰ Be Patient</h3>
                    </div>
                    <div class="event-body">
                        <p>Admin typically responds within 24-48 hours. Check back regularly for replies.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        function toggleComposeForm() {
            const form = document.getElementById('composeForm');
            if (form.style.display === 'none') {
                form.style.display = 'block';
                // Scroll to form
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                form.style.display = 'none';
            }
        }

        // Show compose form if there's an error (form was submitted but failed)
        <?php if ($error): ?>
            document.getElementById('composeForm').style.display = 'block';
        <?php endif; ?>
    </script>
</body>
</html>

<?php
$stmt->close();
$stats_stmt->close();
$member_stmt->close();
$conn->close();
?>