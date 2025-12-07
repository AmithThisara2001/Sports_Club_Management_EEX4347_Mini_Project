<?php
require_once 'includes/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OUSL Sports Club Management System</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 20px;
            text-align: center;
        }
        
        .hero-section h1 {
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        .hero-section p {
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .hero-buttons .btn {
            padding: 15px 30px;
            font-size: 1.1em;
        }
        
        .features-section {
            padding: 60px 20px;
            background: white;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            font-size: 3em;
            margin-bottom: 15px;
        }
        
        .feature-card h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .feature-card p {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <h1>OUSL Sports Club Management System</h1>
        <p>Manage OUSL Sports Club efficiently with the comprehensive management system</p>
        <div class="hero-buttons">
            <a href="member/register.php" class="btn btn-primary">Join as Member</a>
            <a href="member/login.php" class="btn btn-success">Member Login</a>
            <a href="admin/login.php" class="btn btn-warning">Admin Login</a>
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="features-section">
        <h2 style="text-align: center; margin-bottom: 50px; color: #2c3e50;">Our Features</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📅</div>
                <h3>Event Management</h3>
                <p>Register for football, cricket, and tournament events easily</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">⚽</div>
                <h3>Equipment Booking</h3>
                <p>Check availability and book sports equipment for practice</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔔</div>
                <h3>Notifications</h3>
                <p>Receive reminders for equipment return deadlines</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">💬</div>
                <h3>Messaging System</h3>
                <p>Communicate directly with club administrators</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Reports & Analytics</h3>
                <p>Track attendance, equipment usage, and participation</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3>Secure & Reliable</h3>
                <p>Your data is safe and accessible 24/7</p>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Sports Club Management</h3>
                    <p>Managing sports activities and equipment efficiently since 2024.</p>
                </div>
                
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="member/register.php">Register</a></li>
                        <li><a href="member/login.php">Member Login</a></li>
                        <li><a href="admin/login.php">Admin Login</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Info</h3>
                    <p>Email: info@sportsclub.com</p>
                    <p>Phone: +94 11 234 5678</p>
                    <p>Address: Colombo, Sri Lanka</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Sports Club Management System. All rights reserved.</p>
                <p>Developed as a mini project for EEX4347</p>
            </div>
        </div>
    </footer>
</body>
</html>