<nav class="main-nav admin-nav">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="dashboard.php">Admin Panel</a>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage_members.php">Members</a></li>
            <li><a href="manage_events.php">Events</a></li>
            <li><a href="manage_equipment.php">Equipment</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="messages.php">Messages</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li class="dropdown">
                <a href="#" class="dropdown-toggle">
                    <?php echo htmlspecialchars($_SESSION['admin_name']); ?> ▼
                </a>
                <ul class="dropdown-menu">
                    <li><a href="settings.php">Settings</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
        
        <div class="nav-toggle" onclick="toggleNav()">☰</div>
    </div>
</nav>