<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hiryo Admin Dashboard</title>
    
    <link rel="icon" type="image/png" href="../images/Hiryo.png">
    
    <link rel="stylesheet" href="../styles/vars.css">
    <link rel="stylesheet" href="../styles/components/body.css">
    <link rel="stylesheet" href="../styles/components/dashboard.css">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .app { background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e8 100%); }
        .sidebar { background: linear-gradient(180deg, #ffffff 0%, #f8f9fa 100%); border-right: 2px solid #e8f5e8; padding-top: 20px; box-sizing: border-box; }
        .topbar { background: rgba(255, 255, 255, 0.95); border-bottom: 2px solid #e8f5e8; }
        .content { background: #f1f8e9; }
        .sidebar .brand { display: flex; flex-direction: column; align-items: center; padding: 10px 0 20px 0; }
        .sidebar .brand img { max-width: 150px; height: auto; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="app">
        <aside class="sidebar" role="navigation">
            <div class="brand">
                <img src="../images/Hiryo.png" alt="Hiryo Organics Logo">
            </div>
            
            <nav class="nav" id="nav" role="menubar">
                <a href="#" class="nav-link active" data-page="dashboard" role="menuitem"><i>📊</i><span>Dashboard</span></a>
                <a href="#" class="nav-link" data-page="products" role="menuitem"><i>📦</i><span>Products</span></a>
                <a href="#" class="nav-link" data-page="users" role="menuitem"><i>👥</i><span>Users</span></a>
                <a href="#" class="nav-link" data-page="orders" role="menuitem"><i>🛒</i><span>Orders</span></a>
                <a href="#" class="nav-link" data-page="transactions" role="menuitem"><i>💰</i><span>Transactions</span></a>
                <a href="#" class="nav-link" data-page="announcements" role="menuitem"><i>📢</i><span>Announcements</span></a>
            </nav>
        </aside>

        <main class="main">
            <header class="topbar" role="banner">
                <div class="page-title">
                    <h1 id="currentPageTitle">Admin Panel</h1>
                </div>
                
                <div class="actions">
                    <button class="btn secondary" id="notificationsBtn" title="View notifications">
                        🔔 Notifications
                        <span id="notificationBadge" class="notification-badge" style="display: none;">0</span>
                    </button>
                    <button class="btn secondary" id="profileBtn" title="View your profile">
                        👤 Profile
                    </button>
                    <button class="btn danger" id="logoutBtn" title="Sign out of admin panel">
                        Sign out
                    </button>
                </div>
            </header>

            <div id="content" class="content" role="main" aria-live="polite">
                </div>
        </main>
    </div>
    
    <script src="script_optimized.js"></script>
</body>
</html>