<?php include '../php/session_admin.php'; ?>
<link rel="stylesheet" href="../styles/admin_profile.css">

<section id="profile" class="page">
    <!-- Enhanced Profile Header -->
    <div class="profile-header-section">
        <div class="header-content">
            <div class="profile-avatar">
                <div class="avatar-circle">
                    <span class="avatar-text"><?php echo strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)); ?></span>
                </div>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'); ?></h1>
                <p class="profile-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></p>
                <p class="profile-status">
                    <span class="status-indicator active"></span>
                    <?php echo htmlspecialchars($_SESSION['status'] ?? 'Active'); ?> Administrator
                </p>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn primary" onclick="showAddAdminModal()">
                <span class="btn-icon">👥</span>
                Add Admin User
            </button>
            <button class="btn secondary" onclick="editProfile()">
                <span class="btn-icon">✏️</span>
                Edit Profile
            </button>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="profile-content">
        <div class="profile-cards">
            <!-- Personal Information Card -->
            <div class="profile-card personal-info-card">
                <div class="card-header">
                    <h3>👤 Personal Information</h3>
                    <span class="card-icon">📋</span>
                </div>
                <div class="card-content">
                    <div class="info-item">
                        <label>Full Name</label>
                        <div class="info-value">
                            <span class="value-text"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin'); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Email Address</label>
                        <div class="info-value">
                            <span class="value-text"><?php echo htmlspecialchars($_SESSION['email'] ?? 'admin@hiryo.com'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Information Card -->
            <div class="profile-card account-info-card">
                <div class="card-header">
                    <h3>🔐 Account Information</h3>
                    <span class="card-icon">🛡️</span>
                </div>
                <div class="card-content">
                    <div class="info-item">
                        <label>Admin ID</label>
                        <div class="info-value">
                            <span class="value-text admin-id">ADMIN-<?php echo htmlspecialchars($_SESSION['admin_id'] ?? '001'); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Role</label>
                        <div class="info-value">
                            <span class="role-badge"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Account Status</label>
                        <div class="info-value">
                            <span class="status-badge active">✅ Active</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Card -->
            <div class="profile-card activity-card">
                <div class="card-header">
                    <h3>⚡ Recent Activity</h3>
                    <span class="card-icon">🕒</span>
                </div>
                <div class="card-content">
                    <div class="activity-item">
                        <div class="activity-icon">🔑</div>
                        <div class="activity-info">
                            <span class="activity-text">Logged in successfully</span>
                            <span class="activity-time">Just now</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">👥</div>
                        <div class="activity-info">
                            <span class="activity-text">Managed user accounts</span>
                            <span class="activity-time">5 minutes ago</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">📊</div>
                        <div class="activity-info">
                            <span class="activity-text">Viewed dashboard analytics</span>
                            <span class="activity-time">10 minutes ago</span>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">📢</div>
                        <div class="activity-info">
                            <span class="activity-text">Sent system announcement</span>
                            <span class="activity-time">1 hour ago</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Admin ID for JavaScript -->
<script>
    window.currentAdminId = <?php echo json_encode($_SESSION['admin_id'] ?? 1); ?>;
</script>

<!-- Add Admin User Modal -->
<div id="addAdminModal" class="modal" style="display: none;">
    <div class="modal-content admin-modal-content">
        <div class="modal-header">
            <h2 id="adminModalTitle">Add New Admin User</h2>
            <span class="close" onclick="closeAdminModal()">&times;</span>
        </div>
        
        <form id="adminForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="adminFullName">Full Name *</label>
                    <input type="text" id="adminFullName" name="full_name" required 
                           placeholder="Enter full name...">
                </div>
                
                <div class="form-group">
                    <label for="adminEmail">Email *</label>
                    <input type="email" id="adminEmail" name="email" required 
                           placeholder="Enter admin email...">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="adminPassword">Password *</label>
                    <input type="password" id="adminPassword" name="password" required 
                           placeholder="Enter admin password...">
                </div>
                
                <div class="form-group">
                    <label for="adminRole">Role *</label>
                    <select id="adminRole" name="role" required>
                        <option value="">Select Role</option>
                        <option value="Administrator">👑 Administrator</option>
                        <option value="Moderator">🛡️ Moderator</option>
                        <option value="Editor">✏️ Editor</option>
                    </select>
                </div>
            </div>
            
            
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeAdminModal()">
                    Cancel
                </button>
                <button type="submit" class="btn brand" id="adminSubmitBtn">
                    <span class="btn-icon">👥</span>
                    Add Admin User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="modal" style="display: none;">
    <div class="modal-content admin-modal-content">
        <div class="modal-header">
            <h2 id="editProfileModalTitle">Edit Profile</h2>
            <span class="close" onclick="closeEditProfileModal()">&times;</span>
        </div>
        
        <form id="editProfileForm">
            <input type="hidden" id="editAdminId" name="admin_id">
            <div class="form-row">
                <div class="form-group">
                    <label for="editFullName">Full Name *</label>
                    <input type="text" id="editFullName" name="full_name" required 
                           placeholder="Enter full name...">
                </div>
                
                <div class="form-group">
                    <label for="editEmail">Email *</label>
                    <input type="email" id="editEmail" name="email" required 
                           placeholder="Enter email address...">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="editPassword">New Password (leave blank to keep current)</label>
                    <input type="password" id="editPassword" name="password" 
                           placeholder="Enter new password (min 8 characters)...">
                </div>
                
                <div class="form-group">
                    <label for="editRole">Role *</label>
                    <select id="editRole" name="role" required>
                        <option value="">Select Role</option>
                        <option value="Administrator">👑 Administrator</option>
                        <option value="Moderator">🛡️ Moderator</option>
                        <option value="Editor">✏️ Editor</option>
                    </select>
                </div>
            </div>
            
            
            <div class="modal-actions">
                <button type="button" class="btn secondary" onclick="closeEditProfileModal()">
                    Cancel
                </button>
                <button type="submit" class="btn brand" id="editProfileSubmitBtn">
                    <span class="btn-icon">✏️</span>
                    Update Profile
                </button>
            </div>
        </form>
    </div>
</div>