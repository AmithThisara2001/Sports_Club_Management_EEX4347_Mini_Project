<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_admin_logged_in()) {
    redirect('login.php');
}

// Report type
$report_type = isset($_GET['type']) ? clean_input($_GET['type']) : 'attendance';
$date_from = isset($_GET['date_from']) ? clean_input($_GET['date_from']) : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? clean_input($_GET['date_to']) : date('Y-m-d');

// Generate Report Data
$report_data = [];

switch ($report_type) {
    case 'attendance':
        // Event Attendance Report
        $sql = "SELECT e.event_name, e.event_type, e.event_date, 
                       COUNT(er.registration_id) as total_registrations,
                       e.max_participants
                FROM events e
                LEFT JOIN event_registrations er ON e.event_id = er.event_id AND er.status = 'confirmed'
                WHERE e.event_date BETWEEN ? AND ?
                GROUP BY e.event_id
                ORDER BY e.event_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data = $stmt->get_result();
        break;

    case 'equipment':
        // Equipment Usage Report
        $sql = "SELECT e.equipment_name, e.equipment_type,
                       COUNT(eb.booking_id) as total_bookings,
                       SUM(CASE WHEN eb.status = 'returned' THEN 1 ELSE 0 END) as returned_count,
                       SUM(CASE WHEN eb.status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
                FROM equipment e
                LEFT JOIN equipment_bookings eb ON e.equipment_id = eb.equipment_id 
                    AND eb.booking_date BETWEEN ? AND ?
                GROUP BY e.equipment_id
                ORDER BY total_bookings DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $date_from, $date_to);
        $stmt->execute();
        $report_data = $stmt->get_result();
        break;

    case 'participation':
        // Member Participation Report
        $sql = "SELECT m.full_name, m.email,
                       COUNT(DISTINCT er.event_id) as events_attended,
                       COUNT(DISTINCT eb.booking_id) as equipment_bookings
                FROM members m
                LEFT JOIN event_registrations er ON m.member_id = er.member_id 
                    AND er.status = 'confirmed'
                LEFT JOIN event_registrations er2 ON er.event_id = er2.event_id
                LEFT JOIN events e ON er.event_id = e.event_id 
                    AND e.event_date BETWEEN ? AND ?
                LEFT JOIN equipment_bookings eb ON m.member_id = eb.member_id 
                    AND eb.booking_date BETWEEN ? AND ?
                WHERE m.status = 'active'
                GROUP BY m.member_id
                ORDER BY events_attended DESC, equipment_bookings DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $date_from, $date_to, $date_from, $date_to);
        $stmt->execute();
        $report_data = $stmt->get_result();
        break;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Sports Club Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        @media print {
            .no-print {
                display: none;
            }

            body {
                background: white;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_admin.php'; ?>

    <div class="admin-container">
        <div class="page-header no-print">
            <h1>Generate Reports</h1>
            <button onclick="window.print()" class="btn btn-primary">🖨️ Print Report</button>
        </div>

        <!-- Report Filter Form -->
        <div class="report-filter no-print">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Report Type</label>
                        <select name="type" onchange="this.form.submit()">
                            <option value="attendance" <?php echo $report_type == 'attendance' ? 'selected' : ''; ?>>
                                Event Attendance Report
                            </option>
                            <option value="equipment" <?php echo $report_type == 'equipment' ? 'selected' : ''; ?>>
                                Equipment Usage Report
                            </option>
                            <option value="participation" <?php echo $report_type == 'participation' ? 'selected' : ''; ?>>
                                Member Participation Report
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                    </div>

                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                    </div>

                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Report Header -->
        <div class="report-header">
            <h2>Sports Club Management System</h2>
            <h3>
                <?php
                switch ($report_type) {
                    case 'attendance':
                        echo 'Event Attendance Report';
                        break;
                    case 'equipment':
                        echo 'Equipment Usage Report';
                        break;
                    case 'participation':
                        echo 'Member Participation Report';
                        break;
                }
                ?>
            </h3>
            <p>Period: <?php echo format_date($date_from); ?> to <?php echo format_date($date_to); ?></p>
            <p>Generated on: <?php echo date('d M Y h:i A'); ?></p>
        </div>

        <!-- Report Data -->
        <div class="report-content">
            <?php if ($report_data->num_rows > 0): ?>
                <table class="report-table">
                    <thead>
                        <?php if ($report_type == 'attendance'): ?>
                            <tr>
                                <th>Event Name</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Registrations</th>
                                <th>Capacity</th>
                                <th>Attendance %</th>
                            </tr>
                        <?php elseif ($report_type == 'equipment'): ?>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Type</th>
                                <th>Total Bookings</th>
                                <th>Returned</th>
                                <th>Overdue</th>
                                <th>Return Rate %</th>
                            </tr>
                        <?php elseif ($report_type == 'participation'): ?>
                            <tr>
                                <th>Member Name</th>
                                <th>Email</th>
                                <th>Events Attended</th>
                                <th>Equipment Bookings</th>
                                <th>Total Participation</th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php while ($row = $report_data->fetch_assoc()): ?>
                            <tr>
                                <?php if ($report_type == 'attendance'): ?>
                                    <td><?php echo htmlspecialchars($row['event_name']); ?></td>
                                    <td><?php echo ucfirst($row['event_type']); ?></td>
                                    <td><?php echo format_date($row['event_date']); ?></td>
                                    <td><?php echo $row['total_registrations']; ?></td>
                                    <td><?php echo $row['max_participants']; ?></td>
                                    <td>
                                        <?php
                                        $percentage = $row['max_participants'] > 0
                                            ? round(($row['total_registrations'] / $row['max_participants']) * 100, 2)
                                            : 0;
                                        echo $percentage . '%';
                                        ?>
                                    </td>
                                <?php elseif ($report_type == 'equipment'): ?>
                                    <td><?php echo htmlspecialchars($row['equipment_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['equipment_type']); ?></td>
                                    <td><?php echo $row['total_bookings']; ?></td>
                                    <td><?php echo $row['returned_count']; ?></td>
                                    <td><?php echo $row['overdue_count']; ?></td>
                                    <td>
                                        <?php
                                        $return_rate = $row['total_bookings'] > 0
                                            ? round(($row['returned_count'] / $row['total_bookings']) * 100, 2)
                                            : 0;
                                        echo $return_rate . '%';
                                        ?>
                                    </td>
                                <?php elseif ($report_type == 'participation'): ?>
                                    <td><?php echo htmlspecialchars($row['full_name']); ?> </td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo $row['events_attended']; ?></td>
                                    <td><?php echo $row['equipment_bookings']; ?></td>
                                    <td><?php echo ($row['events_attended'] + $row['equipment_bookings']); ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">No data available for the selected period.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>