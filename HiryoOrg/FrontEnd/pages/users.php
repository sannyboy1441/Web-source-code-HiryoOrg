<?php include '../php/session_admin.php'; ?>
<link rel="stylesheet" href="../styles/users.css">

<section class="page" id="users">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <div class="header-title">
                <h1>👥 Users</h1>
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
                    <input type="text" id="userSearch" placeholder="Search users by name, email, or contact number..." class="search-input">
                    <button class="search-clear" id="clearUserSearch" onclick="clearUserSearch()">✕</button>
                </div>
            </div>

            <!-- Filter Controls -->
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="userRoleFilter">Role:</label>
                    <select id="userRoleFilter" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="customer"> Customer</option>
                        <option value="Admin"> Admin</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="userStatusFilter">Status:</label>
                    <select id="userStatusFilter" class="filter-select">
                        <option value="">All Statuses</option>
                        <option value="Active">✅ Active</option>
                        <option value="Suspended">🚫 Suspended</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="userSort">Sort By:</label>
                    <select id="userSort" class="filter-select">
                        <option value="name-asc">📝 Name A-Z</option>
                        <option value="name-desc">📝 Name Z-A</option>
                        <option value="date-desc">📅 Newest First</option>
                        <option value="date-asc">📅 Oldest First</option>
                        <option value="email-asc">📧 Email A-Z</option>
                        <option value="email-desc">📧 Email Z-A</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <button class="btn secondary" onclick="clearUserFilters()">
                        <span class="btn-icon">🔄</span>
                        Clear All Filters
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Summary -->
        <div class="results-summary">
            <div class="results-info">
                <span id="usersCount">Loading users...</span>
                <span id="userFilterStatus" class="filter-status"></span>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="content-panel">
        <div class="table-container">
            <div class="table-responsive">
                <table class="data-table users-table">
                    <thead>
                        <tr>
                            <th class="id-column">
                                <span class="column-header sortable" onclick="sortUsers('user_id')">
                                    <span class="column-icon">🆔</span>
                                    ID
                                    <span class="sort-indicator" id="sort-user_id">↕️</span>
                                </span>
                            </th>
                            <th class="name-column">
                                <span class="column-header sortable" onclick="sortUsers('name')">
                                    <span class="column-icon">👤</span>
                                    Name
                                    <span class="sort-indicator" id="sort-name">↕️</span>
                                </span>
                            </th>
                            <th class="email-column">
                                <span class="column-header sortable" onclick="sortUsers('email')">
                                    <span class="column-icon">📧</span>
                                    Email
                                    <span class="sort-indicator" id="sort-email">↕️</span>
                                </span>
                            </th>
                            <th class="role-column">
                                <span class="column-header sortable" onclick="sortUsers('role')">
                                    <span class="column-icon">👑</span>
                                    Role
                                    <span class="sort-indicator" id="sort-role">↕️</span>
                                </span>
                            </th>
                            <th class="status-column">
                                <span class="column-header sortable" onclick="sortUsers('status')">
                                    <span class="column-icon">📊</span>
                                    Status
                                    <span class="sort-indicator" id="sort-status">↕️</span>
                                </span>
                            </th>
                            <th class="joined-column">
                                <span class="column-header sortable" onclick="sortUsers('created_at')">
                                    <span class="column-icon">📅</span>
                                    Joined
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
                    <tbody id="userRows">
                        <tr>
                            <td colspan="7" class="loading-row">
                                <div class="loading-spinner">
                                    <span class="spinner">⏳</span>
                                    Loading users...
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="emptyUserState" class="empty-state" style="display: none;">
                <div class="empty-icon">👥</div>
                <h3>No Users Found</h3>
                <p>No users match your current filters. Try adjusting your search criteria or add a new user.</p>
                <button class="btn brand" onclick="showAddUserModal()">
                    <span class="btn-icon">➕</span>
                    Add Your First User
                </button>
            </div>
        </div>
    </div>
</section>

<!-- Edit User Status Modal -->
<div id="userModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="userModalTitle">Edit User Status</h2>
            <span class="close" onclick="closeUserModal()">&times;</span>
        </div>
        
        <form id="userForm">
            <input type="hidden" id="userId" name="user_id">
            
            <div class="form-group">
                <label for="userName">User Name</label>
                <input type="text" id="userName" name="userName" readonly 
                       style="background-color: #f5f5f5; color: #666;">
            </div>
            
            <div class="form-group">
                <label for="userEmail">Email</label>
                <input type="email" id="userEmail" name="userEmail" readonly 
                       style="background-color: #f5f5f5; color: #666;">
            </div>
            
            <div class="form-group">
                <label for="userStatus">Status *</label>
                <select id="userStatus" name="status" required>
                    <option value="">Select Status</option>
                    <option value="Active">✅ Active</option>
                    <option value="Inactive">❌ Inactive</option>
                </select>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeUserModal()">
                    Cancel
                </button>
                <button type="submit" class="btn brand" id="userSubmitBtn">
                    <span class="btn-icon">💾</span>
                    Update Status
                </button>
            </div>
        </form>
    </div>
</div>