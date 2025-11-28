<?php include '../php/session_admin.php'; ?>
<link rel="stylesheet" href="../styles/announcements.css">

<section class="page" id="announcements">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-title">
                <h1>📢 Announcements</h1>
            </div>
        </div>
    </div>

    <!-- Send New Announcement Section -->
    <div class="announcement-form-section">
        <div class="form-header">
            <h2>📝 Send New Announcement</h2>
            <p>Create and send announcements to all users. They will receive notifications automatically.</p>
        </div>
        <form id="announcementForm" class="announcement-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="announcementTitle">Title *</label>
                    <input type="text" id="announcementTitle" name="title" required 
                           placeholder="Enter announcement title...">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="announcementMessage">Message *</label>
                    <textarea id="announcementMessage" name="message" rows="4" required 
                              placeholder="Enter announcement message..."></textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn secondary" onclick="clearAnnouncementForm()">
                    <span class="btn-icon">🔄</span>
                    Clear Form
                </button>
                <button type="submit" class="btn brand" id="sendAnnouncementBtn">
                    <span class="btn-icon">📤</span>
                    Send Announcement
                </button>
            </div>
        </form>
    </div>

    <!-- Search and Filter Section -->
    <div class="filter-section">
        <div class="filter-container">
            <!-- Search Bar -->
            <div class="search-container">
                <div class="search-input-group">
                    <span class="search-icon">🔍</span>
                    <input type="text" id="announcementSearch" placeholder="Search announcements by title or message..." class="search-input">
                    <button class="search-clear" id="clearAnnouncementSearch" onclick="clearAnnouncementSearch()">✕</button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="announcementDateFilter">Date Range:</label>
                    <select id="announcementDateFilter" class="filter-select">
                        <option value="">All Time</option>
                        <option value="today">📅 Today</option>
                        <option value="week">📅 This Week</option>
                        <option value="month">📅 This Month</option>
                        <option value="quarter">📅 This Quarter</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="announcementSort">Sort By:</label>
                    <select id="announcementSort" class="filter-select">
                        <option value="date-desc">📅 Newest First</option>
                        <option value="date-asc">📅 Oldest First</option>
                        <option value="title-asc">📝 Title A-Z</option>
                        <option value="title-desc">📝 Title Z-A</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn secondary" onclick="clearAnnouncementFilters()">
                        <span class="btn-icon">🔄</span>
                        Clear All Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <div class="results-info">
                <span id="announcementsCount">Loading announcements...</span>
                <span id="announcementFilterStatus" class="filter-status"></span>
            </div>
        </div>
    </div>

    <!-- Announcements Table -->
    <div class="content-panel">
        <div class="table-container">
            <div class="table-responsive">
                <table class="data-table announcements-table">
                    <thead>
                        <tr>
                            <th class="title-column">
                                <span class="column-header sortable" onclick="sortAnnouncements('title')">
                                    <span class="column-icon">📝</span>
                                    Title
                                    <span class="sort-indicator" id="sort-title">↕️</span>
                                </span>
                            </th>
                            <th class="message-column">
                                <span class="column-header">
                                    <span class="column-icon">💬</span>
                                    Message
                                </span>
                            </th>
                            <th class="date-column">
                                <span class="column-header sortable" onclick="sortAnnouncements('created_at')">
                                    <span class="column-icon">📅</span>
                                    Date
                                    <span class="sort-indicator" id="sort-created_at">↕️</span>
                                </span>
                            </th>
                            <th class="actions-column">
                                <span class="column-header">
                                    <span class="column-icon">⚙️</span>
                                    Actions
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="announcementRows">
                        <tr>
                            <td colspan="4" class="loading-row">
                                <div class="loading-spinner">
                                    <span class="spinner">⏳</span>
                                    Loading announcements...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="emptyAnnouncementState" class="empty-state" style="display: none;">
                <div class="empty-icon">📢</div>
                <h3>No Announcements Found</h3>
                <p>No announcements match your current filters. Try adjusting your search criteria or create a new announcement.</p>
                <button class="btn brand" onclick="scrollToAnnouncementForm()">
                    <span class="btn-icon">📝</span>
                    Create Announcement
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Announcement Details Modal -->
<div id="announcementModal" class="modal" style="display: none;">
    <div class="modal-content announcement-modal-content">
        <div class="modal-header">
            <h2 id="announcementModalTitle">Announcement Details</h2>
            <span class="close" onclick="closeAnnouncementModal()">&times;</span>
        </div>
        
        <div id="announcementDetailsContent" class="announcement-details-content">
            <!-- Announcement details will be loaded here -->
        </div>
    </div>
</div>