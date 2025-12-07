<nav class="main-nav member-nav">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="dashboard.php">OUSL Sports Club</a>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="events.php">Events</a></li>
            <li><a href="equipment.php">Equipment</a></li>
            <li><a href="my_bookings.php">My Bookings</a></li>
            <li><a href="messages.php">Messages</a></li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?> ▼
                </a>
                <ul class="dropdown-menu">
                    <li><a href="profile.php">My Profile</a></li>
                    <li><a href="change_password.php">Change Password</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
        
        <div class="nav-toggle" onclick="toggleNav()">☰</div>
    </div>
</nav>