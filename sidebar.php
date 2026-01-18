<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        /* RESET */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

/* SIDEBAR */
.sidebar {
    width: 220px;
    height: 100vh;
    background-color: #1f2937;
    position: fixed;
    top: 0;
    left: 0;
    color: #ffffff;
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebar.collapsed {
    transform: translateX(-220px);
}

/* HEADER */
.sidebar-header {
    background-color: #111827;
    padding: 20px;
    font-size: 18px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* MENU */
.sidebar-menu {
    list-style: none;
    margin-top: 10px;
}

.sidebar-menu li {
    border-bottom: 1px solid #374151;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: #e5e7eb;
    text-decoration: none;
    transition: background-color 0.2s ease;
}

.sidebar-menu a:hover {
    background-color: #374151;
    color: #ffffff;
}

/* ACTIVE LINK */
.sidebar-menu a.active {
    background-color: #374151;
    color: #ffffff;
}

/* LOGOUT */
.sidebar-menu .logout a {
    color: #f87171;
}

.sidebar-menu .logout a:hover {
    background-color: #7f1d1d;
}

/* TOGGLE BUTTON */
.toggle-btn {
    position: fixed;
    top: 15px;
    left: 180px;
    background-color: rgba(31, 41, 55, 0.1);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 8px 12px;
    cursor: pointer;
    border-radius: 4px;
    z-index: 1001;
    font-size: 16px;
    transition: left 0.3s ease;
    backdrop-filter: blur(2px);
}

.toggle-btn:hover {
    background-color: rgba(55, 65, 81, 0.3);
}

.toggle-btn.collapsed {
    left: 10px;
}

/* MAIN CONTENT */
.main-content {
    margin-left: 220px;
    padding: 20px;
    transition: margin-left 0.3s ease;
}

.main-content.expanded {
    margin-left: 0;
}

    </style>
</head>
<body>
    
<!-- TOGGLE BUTTON -->
<button class="toggle-btn" id="toggleSidebar">
    <i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <i class="fa-solid fa-scissors"></i>
        <span>Parlor System</span>
    </div>

    <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
    <ul class="sidebar-menu">
        <li>
            <a href="dashboard.php" class="<?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span><?php echo $_SESSION['role'] === 'admin' ? 'Dashboard' : 'My Dashboard'; ?></span>
            </a>
        </li>

        <li>
            <a href="services.php" class="<?php echo $currentPage === 'services.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-scissors"></i>
                <span><?php echo $_SESSION['role'] === 'admin' ? 'Manage Services' : 'View Services'; ?></span>
            </a>
        </li>

        <li>
            <a href="transaction_new.php" class="<?php echo $currentPage === 'transaction_new.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-cash-register"></i>
                <span>New Transaction</span>
            </a>
        </li>

        <li>
            <a href="transactions.php" class="<?php echo $currentPage === 'transactions.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-receipt"></i>
                <span><?php echo $_SESSION['role'] === 'admin' ? 'All Transactions' : 'My Transactions'; ?></span>
            </a>
        </li>

        <li>
            <a href="quota_set.php" class="<?php echo $currentPage === 'quota_set.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-bullseye"></i>
                <span>Daily Quota</span>
            </a>
        </li>

        <?php if ($_SESSION['role'] === 'admin'): ?>
        <li>
            <a href="users.php" class="<?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>Manage Users</span>
            </a>
        </li>
        <?php endif; ?>

        <li class="logout">
            <a href="logout.php" class="<?php echo $currentPage === 'logout.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div>

<script>
    // Toggle sidebar functionality
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');
    
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('collapsed');
        toggleBtn.classList.toggle('collapsed');
        
        // Update main content margin
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.toggle('expanded');
        }
    });
</script>

    
</body>
</html>
