<?php include '../php/session_admin.php'; ?>
<link rel="stylesheet" href="../styles/notifications.css">

<section class="page" id="notifications">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-title">
                <h1>🔔 Notifications</h1>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="filter-section">
        <div class="filter-container">
            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-input-group">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="notificationSearch" placeholder="Search notifications by title, message, or user..." class="search-input">
                    <button class="search-clear" id="clearNotificationSearch" onclick="clearNotificationSearch()">✕</button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="notificationStatusFilter">Status:</label>
                    <select id="notificationStatusFilter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="unread">📩 Unread</option>
                        <option value="read">✅ Read</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="notificationTypeFilter">Type:</label>
                    <select id="notificationTypeFilter" class="filter-select">
                        <option value="">All Types</option>
                        <option value="order">🛒 Order</option>
                        <option value="announcement">📢 Announcement</option>
                        <option value="system">⚙️ System</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="notificationDateFilter">Date Range:</label>
                    <select id="notificationDateFilter" class="filter-select">
                        <option value="">All Time</option>
                        <option value="today">📅 Today</option>
                        <option value="week">📅 This Week</option>
                        <option value="month">📅 This Month</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="notificationSort">Sort By:</label>
                    <select id="notificationSort" class="filter-select">
                        <option value="date-desc">📅 Newest First</option>
                        <option value="date-asc">📅 Oldest First</option>
                        <option value="title-asc">📝 Title A-Z</option>
                        <option value="title-desc">📝 Title Z-A</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn secondary" onclick="clearNotificationFilters()">
                        <span class="btn-icon">🔄</span>
                        Clear All Filters
                    </button>
                    <button class="btn brand" onclick="markAllAsRead()">
                        <span class="btn-icon">✅</span>
                        Mark All Read
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <div class="results-info">
                <span id="notificationsCount">Loading notifications...</span>
                <span id="notificationFilterStatus" class="filter-status"></span>
            </div>
        </div>
    </div>

    <!-- Notifications List -->
    <div class="content-panel">
        <div class="notifications-container">
            <div id="notificationsList" class="notifications-list">
                <div class="loading-spinner">
                    <span class="spinner">⏳</span>
                    Loading notifications...
                </div>
            </div>

            <!-- Empty State -->
            <div id="emptyNotificationState" class="empty-state" style="display: none;">
                <div class="empty-icon">🔔</div>
                <h3>No Notifications Found</h3>
                <p>No notifications match your current filters. Try adjusting your search criteria.</p>
            </div>
        </div>
    </div>
</section>

<!-- Notification Details Modal -->
<div id="notificationModal" class="modal" style="display: none;">
    <div class="modal-content notification-modal-content">
        <div class="modal-header">
            <h2 id="notificationModalTitle">Notification Details</h2>
            <span class="close" onclick="closeNotificationModal()">&times;</span>
        </div>
        
        <div id="notificationDetailsContent" class="notification-details-content">
            <!-- Notification details will be loaded here -->
        </div>
    </div>
</div>