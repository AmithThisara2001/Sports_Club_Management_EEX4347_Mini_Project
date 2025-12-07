<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!is_admin_logged_in()) {
    redirect('login.php');
}

$admin_id = $_SESSION['admin_id'];
$error = '';
$success = '';

// Handle Reply to Message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $message_id = (int)$_POST['message_id'];
    $reply_text = clean_input($_POST['reply_text']);
    
    if (empty($reply_text)) {
        $error = "Reply message cannot be empty.";
    } else {
        // Update message with reply and mark as read
        $reply_sql = "UPDATE messages SET reply_text = ?, read_status = TRUE WHERE message_id = ?";
        $reply_stmt = $conn->prepare($reply_sql);
        $reply_stmt->bind_param("si", $reply_text, $message_id);
        
        if ($reply_stmt->execute()) {
            $_SESSION['success'] = "Reply sent successfully!";
            redirect('messages.php');
        } else {
            $error = "Failed to send reply: " . $conn->error;
        }
    }
}

// Handle Mark as Read
if (isset($_GET['mark_read'])) {
    $message_id = (int)$_GET['mark_read'];
    $mark_sql = "UPDATE messages SET read_status = TRUE WHERE message_id = ?";
    $mark_stmt = $conn->prepare($mark_sql);
    $mark_stmt->bind_param("i", $message_id);
    
    if ($mark_stmt->execute()) {
        $_SESSION['success'] = "Message marked as read.";
    }
    redirect('messages.php');
}

// Handle Delete Message
if (isset($_GET['delete'])) {
    $message_id = (int)$_GET['delete'];
    $delete_sql = "DELETE FROM messages WHERE message_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $message_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success'] = "Message deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete message.";
    }
    redirect('messages.php');
}

// Filter messages
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';

// Build query
$where_conditions = [];
$params = [];
$types = '';

if ($filter == 'unread') {
    $where_conditions[] = "m.read_status = FALSE";
} elseif ($filter == 'read') {
    $where_conditions[] = "m.read_status = TRUE";
} elseif ($filter == 'replied') {
    $where_conditions[] = "m.reply_text IS NOT NULL";
} elseif ($filter == 'unreplied') {
    $where_conditions[] = "m.reply_text IS NULL";
}

if (!empty($search)) {
    $where_conditions[] = "(m.subject LIKE ? OR m.message_text LIKE ? OR mem.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Fetch messages
$sql = "SELECT m.*, mem.full_name as sender_name, mem.email as sender_email, mem.phone as sender_phone
        FROM messages m
        JOIN members mem ON m.sender_id = mem.member_id
        $where_clause
        ORDER BY m.sent_date DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_messages,
    SUM(CASE WHEN read_status = FALSE THEN 1 ELSE 0 END) as unread_messages,
    SUM(CASE WHEN reply_text IS NOT NULL THEN 1 ELSE 0 END) as replied_messages,
    SUM(CASE WHEN reply_text IS NULL THEN 1 ELSE 0 END) as pending_messages
    FROM messages";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Get selected message for reply
$selected_message = null;
if (isset($_GET['reply'])) {
    $reply_id = (int)$_GET['reply'];
    $select_sql = "SELECT m.*, mem.full_name as sender_name, mem.email as sender_email
                   FROM messages m
                   JOIN members mem ON m.sender_id = mem.member_id
                   WHERE m.message_id = ?";
    $select_stmt = $conn->prepare($select_sql);
    $select_stmt->bind_param("i", $reply_id);
    $select_stmt->execute();
    $select_result = $select_stmt->get_result();
    $selected_message = $select_result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .messages-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .message-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--info-color);
            transition: transform 0.2s;
        }
        
        .message-card:hover {
            transform: translateX(5px);
        }
        
        .message-card.unread {
            border-left-color: var(--danger-color);
            background: #fff9f9;
        }
        
        .message-card.replied {
            border-left-color: var(--success-color);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .message-sender {
            flex: 1;
        }
        
        .message-sender h3 {
            margin: 0 0 5px 0;
            color: var(--dark-text);
        }
        
        .message-sender .sender-info {
            color: var(--light-text);
            font-size: 0.9em;
        }
        
        .message-meta {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .message-subject {
            font-size: 1.1em;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 10px;
        }
        
        .message-text {
            color: var(--light-text);
            line-height: 1.6;
            margin-bottom: 15px;
            padding: 15px;
            background: var(--light-bg);
            border-radius: 4px;
        }
        
        .message-reply {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            border-left: 3px solid var(--info-color);
            margin-top: 15px;
        }
        
        .message-reply h4 {
            margin: 0 0 10px 0;
            color: var(--info-color);
            font-size: 0.9em;
        }
        
        .message-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .reply-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            border: 2px solid var(--secondary-color);
        }
        
        .reply-form h3 {
            margin: 0 0 20px 0;
            color: var(--secondary-color);
        }
        
        .original-message {
            background: var(--light-bg);
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
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
    <?php include '../includes/nav_admin.php'; ?>

    <div class="admin-container">
        <div class="page-header">
            <div>
                <h1>💬 Messages</h1>
                <p>View and respond to member messages</p>
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
            <div class="stat-card red">
                <p>Unread Messages</p>
                <h3><?php echo $stats['unread_messages']; ?></h3>
            </div>
            <div class="stat-card green">
                <p>Replied Messages</p>
                <h3><?php echo $stats['replied_messages']; ?></h3>
            </div>
            <div class="stat-card orange">
                <p>Pending Replies</p>
                <h3><?php echo $stats['pending_messages']; ?></h3>
            </div>
        </div>

        <!-- Reply Form (if replying to a message) -->
        <?php if ($selected_message): ?>
        <div class="reply-form">
            <h3>📝 Reply to Message</h3>
            
            <div class="original-message">
                <strong>From:</strong> <?php echo htmlspecialchars($selected_message['sender_name']); ?><br>
                <strong>Subject:</strong> <?php echo htmlspecialchars($selected_message['subject']); ?><br>
                <strong>Message:</strong><br>
                <p style="margin-top: 10px;"><?php echo nl2br(htmlspecialchars($selected_message['message_text'])); ?></p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="message_id" value="<?php echo $selected_message['message_id']; ?>">
                
                <div class="form-group">
                    <label for="reply_text">Your Reply *</label>
                    <textarea id="reply_text" name="reply_text" rows="6" required 
                              placeholder="Type your reply here..."><?php echo $selected_message['reply_text'] ? htmlspecialchars($selected_message['reply_text']) : ''; ?></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="reply_message" class="btn btn-success">
                        ✉️ Send Reply
                    </button>
                    <a href="messages.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" action="" class="filter-form">
                <div class="filter-group">
                    <label for="search">Search Messages</label>
                    <input type="text" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="Search by subject, message, or sender...">
                </div>
                
                <div class="filter-group">
                    <label for="filter">Filter by Status</label>
                    <select id="filter" name="filter">
                        <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Messages</option>
                        <option value="unread" <?php echo $filter == 'unread' ? 'selected' : ''; ?>>Unread Only</option>
                        <option value="read" <?php echo $filter == 'read' ? 'selected' : ''; ?>>Read Only</option>
                        <option value="replied" <?php echo $filter == 'replied' ? 'selected' : ''; ?>>Replied</option>
                        <option value="unreplied" <?php echo $filter == 'unreplied' ? 'selected' : ''; ?>>Pending Reply</option>
                    </select>
                </div>
                
                <div class="filter-group" style="display: flex; gap: 10px; align-items: end;">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <a href="messages.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Messages List -->
        <div class="section">
            <h2>📬 All Messages</h2>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="messages-layout">
                    <?php while ($message = $result->fetch_assoc()): ?>
                        <div class="message-card <?php echo !$message['read_status'] ? 'unread' : ''; ?> <?php echo $message['reply_text'] ? 'replied' : ''; ?>">
                            <div class="message-header">
                                <div class="message-sender">
                                    <h3>👤 <?php echo htmlspecialchars($message['sender_name']); ?></h3>
                                    <div class="sender-info">
                                        📧 <?php echo htmlspecialchars($message['sender_email']); ?>
                                        <?php if ($message['sender_phone']): ?>
                                            | 📱 <?php echo htmlspecialchars($message['sender_phone']); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="message-meta">
                                    <?php if (!$message['read_status']): ?>
                                        <span class="status-badge status-overdue">NEW</span>
                                    <?php endif; ?>
                                    <?php if ($message['reply_text']): ?>
                                        <span class="status-badge status-approved">REPLIED</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">PENDING</span>
                                    <?php endif; ?>
                                    <span style="color: var(--light-text); font-size: 0.9em;">
                                        📅 <?php echo format_date($message['sent_date']); ?>
                                        ⏰ <?php echo date('h:i A', strtotime($message['sent_date'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="message-subject">
                                📋 <?php echo htmlspecialchars($message['subject']); ?>
                            </div>
                            
                            <div class="message-text">
                                <?php echo nl2br(htmlspecialchars($message['message_text'])); ?>
                            </div>
                            
                            <?php if ($message['reply_text']): ?>
                                <div class="message-reply">
                                    <h4>✉️ Your Reply:</h4>
                                    <?php echo nl2br(htmlspecialchars($message['reply_text'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="message-actions">
                                <?php if (!$message['reply_text']): ?>
                                    <a href="?reply=<?php echo $message['message_id']; ?>" class="btn btn-primary btn-sm">
                                        ✏️ Reply
                                    </a>
                                <?php else: ?>
                                    <a href="?reply=<?php echo $message['message_id']; ?>" class="btn btn-secondary btn-sm">
                                        ✏️ Edit Reply
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (!$message['read_status']): ?>
                                    <a href="?mark_read=<?php echo $message['message_id']; ?>" class="btn btn-info btn-sm">
                                        ✓ Mark as Read
                                    </a>
                                <?php endif; ?>
                                
                                <a href="?delete=<?php echo $message['message_id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this message?');">
                                    🗑️ Delete
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <?php if (!empty($search) || $filter !== 'all'): ?>
                        📭 No messages found matching your criteria.
                        <br><a href="messages.php" class="btn btn-primary btn-sm" style="margin-top: 10px;">Clear Filters</a>
                    <?php else: ?>
                        📭 No messages yet. Messages from members will appear here.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="info-box">
            <strong>ℹ️ Message Management Tips:</strong>
            <ul>
                <li>🔴 Messages with a red border are unread</li>
                <li>🟢 Messages with a green border have been replied to</li>
                <li>Use the "Reply" button to respond to member inquiries</li>
                <li>Mark messages as read to keep track of what you've reviewed</li>
                <li>Use filters to quickly find unread or pending messages</li>
                <li>Members will see your reply when they check their messages</li>
            </ul>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>