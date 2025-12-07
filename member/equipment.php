<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if (!is_logged_in()) {
    redirect('login.php');
}

// Get all available equipment
$sql = "SELECT * FROM equipment WHERE quantity_available > 0 ORDER BY equipment_name ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment - Sports Club</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav_member.php'; ?>

    <div class="container">
        <h1>Available Equipment</h1>

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

        <div class="equipment-grid">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($equipment = $result->fetch_assoc()): ?>
                    <div class="equipment-card" style="
    padding-top: 20px;
    padding-bottom: 10px;
    padding-left: 20px;
    padding-right: 20px;
">
                        <div class="equipment-icon">
                            <?php
                            // Display different icons based on equipment type
                            switch (strtolower($equipment['equipment_type'])) {
                                case 'ball':
                                    echo '⚽';
                                    break;
                                case 'bat':
                                    echo '🏏';
                                    break;
                                case 'racket':
                                    echo '🎾';
                                    break;
                                default:
                                    echo '🏃';
                            }
                            ?>
                        </div>

                        <h3><?php echo htmlspecialchars($equipment['equipment_name']); ?></h3>

                        <div class="equipment-details">
                            <div class="detail-row">
                                <span class="label">Type:</span>
                                <span class="value"><?php echo htmlspecialchars($equipment['equipment_type']); ?></span>
                            </div>

                            <div class="detail-row">
                                <span class="label">Available:</span>
                                <span class="value available-count"><?php echo $equipment['quantity_available']; ?> / <?php echo $equipment['quantity_total']; ?></span>
                            </div>

                            <div class="detail-row">
                                <span class="label">Condition:</span>
                                <span class="badge badge-<?php echo $equipment['condition_status']; ?>">
                                    <?php echo ucfirst($equipment['condition_status']); ?>
                                </span>
                            </div>

                            <div class="detail-row">
                                <span class="label">Location:</span>
                                <span class="value"><?php echo htmlspecialchars($equipment['location']); ?></span>
                            </div>
                        </div>

                        <div class="equipment-actions">
                            <?php if ($equipment['quantity_available'] > 0): ?>
                                <a href="book_equipment.php?id=<?php echo $equipment['equipment_id']; ?>"
                                    class="btn btn-primary">
                                    Book Now
                                </a>
                            <?php else: ?>
                                <button class="btn btn-secondary" disabled>Not Available</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-data">No equipment available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>

</html>