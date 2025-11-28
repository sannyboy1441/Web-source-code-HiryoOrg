<?php
/**
 * UNIFIED Admin API - Serves BOTH Web Admin Panel and Mobile App
 */

// Set headers for JSON response
header('Content-Type: application/json');

// --- Standardized Database Connection ---
// This single line replaces the hardcoded credentials from your mobile app file.
require_once '../datab_try.php';

// --- Main API Router ---

$conn = getDBConnection();

if (!$conn) {
    // Set a 500 Internal Server Error status code for database connection failure
    http_response_code(500); 
    echo json_encode(['success' => false, 'message' => 'Could not connect to the database.']);
    exit;
}

// Use $_REQUEST to handle both GET and POST for flexibility
$action = $_REQUEST['action'] ?? '';

// The switch now contains all actions from both files
switch ($action) {
    case 'admin_login':
        handleAdminLogin($conn);
        break;
    
    case 'admin_register':
        handleAdminRegister($conn);
        break;
        
    case 'get_admin_profile':
        handleGetAdminProfile($conn);
        break;
        
    case 'update_admin_profile':
        handleUpdateAdminProfile($conn);
        break;
        
    case 'get_all_admins':
        handleGetAllAdmins($conn);
        break;
        
    case 'update_admin_status':
        handleUpdateAdminStatus($conn);
        break;
        
    case 'delete_admin':
        handleDeleteAdmin($conn);
        break;
        
    case 'add_admin':
        handleAddAdmin($conn);
        break;
        
    case 'add_admin_to_users':
        handleAddAdminToUsers($conn);
        break;

    default:
        // Set a 400 Bad Request status code for an invalid action
        http_response_code(400); 
        echo json_encode(['success' => false, 'message' => 'Invalid or missing action for admin API.']);
        break;
}


// ----------------------------------------------------
// Action Handlers
// ----------------------------------------------------

/**
 * Handles admin login for both web and mobile.
 * Includes the important status check.
 */
function handleAdminLogin($conn) {
    $response = ['success' => false];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Only POST method is allowed.';
        echo json_encode($response);
        return;
    }

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $response['message'] = 'Missing email or password.';
        echo json_encode($response);
        return;
    }

    try {
        $stmt = $conn->prepare("SELECT admin_id, full_name, email, password_hash, role, status FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // MERGED: Added the crucial status check from the web admin file
            if ($admin['status'] !== 'Active') {
                $response['message'] = 'Your account is suspended. Please contact the administrator.';
                echo json_encode($response);
                return;
            }
            
            // Update last_login timestamp
            $update_stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
            $update_stmt->execute([$admin['admin_id']]);

            $response['success'] = true;
            $response['message'] = 'Login successful!';
            unset($admin['password_hash']); // Never send the hash to the client
            $response['admin'] = $admin;

        } else {
            $response['message'] = 'Invalid email or password.';
        }

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Login failed due to a server error.';
        error_log("Admin login failed: " . $e->getMessage());
    }
    
    echo json_encode($response);
}

/**
 * Handles new admin registration.
 * Includes status and created_at from the web admin file.
 */
function handleAdminRegister($conn) {
    $response = ['success' => false];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Only POST method is allowed.';
        echo json_encode($response);
        return;
    }
    
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Staff'; // Default to 'Staff'

    if (empty($full_name) || empty($email) || empty($password)) {
        $response['message'] = 'Full name, email, and password are required fields.';
        echo json_encode($response);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        return;
    }
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        return;
    }

    try {
        // Check if email already exists
        $stmt_check = $conn->prepare("SELECT admin_id FROM admins WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            $response['message'] = 'An account with this email already exists.';
            echo json_encode($response);
            return;
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // MERGED: Using the more complete INSERT statement from the web admin file
        $stmt_insert = $conn->prepare("INSERT INTO admins (full_name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, 'Active', NOW())");
        $stmt_insert->execute([$full_name, $email, $password_hash, $role]);

        $response['success'] = true;
        $response['message'] = 'Admin account created successfully!';

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Registration failed due to a server error.';
        error_log("Admin registration failed: " . $e->getMessage());
    }

    echo json_encode($response);
}

/**
 * Get admin profile information.
 */
function handleGetAdminProfile($conn) {
    $response = ['success' => false];
    $admin_id = $_GET['admin_id'] ?? 0;
    
    if (!$admin_id) {
        $response['message'] = 'Admin ID is required.';
        echo json_encode($response);
        return;
    }

    try {
        $stmt = $conn->prepare("SELECT admin_id, full_name, email, role, status, created_at, last_login FROM admins WHERE admin_id = ?");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            $response['success'] = true;
            $response['admin'] = $admin;
        } else {
            $response['message'] = 'Admin not found.';
        }

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Failed to retrieve admin profile.';
        error_log("Get admin profile failed: " . $e->getMessage());
    }
    
    echo json_encode($response);
}

/**
 * Update admin profile.
 */
function handleUpdateAdminProfile($conn) {
    $response = ['success' => false];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Only POST method is allowed.';
        echo json_encode($response);
        return;
    }

    $admin_id = $_POST['admin_id'] ?? 0;
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');

    if (!$admin_id) {
        $response['message'] = 'Admin ID is required.';
        echo json_encode($response);
        return;
    }

    try {
        // Start transaction to update both database and session
        $conn->beginTransaction();
        
        $fields = [];
        $params = [];
        
        if (!empty($full_name)) { $fields[] = "full_name = ?"; $params[] = $full_name; }
        if (!empty($email)) { $fields[] = "email = ?"; $params[] = $email; }
        if (!empty($role)) { $fields[] = "role = ?"; $params[] = $role; }
        if (!empty($password)) { 
            if (strlen($password) < 8) {
                echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters.']);
                return;
            }
            $fields[] = "password_hash = ?"; $params[] = password_hash($password, PASSWORD_BCRYPT); 
        }
        
        if (empty($fields)) {
            $response['message'] = 'No fields to update.';
            echo json_encode($response);
            return;
        }
        
        $fields[] = "updated_at = NOW()";
        $params[] = $admin_id;
        
        $sql = "UPDATE admins SET " . implode(', ', $fields) . " WHERE admin_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        
        // Update session if this is the current admin (session should already be started by session_admin.php)
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $admin_id) {
            if (!empty($full_name)) $_SESSION['full_name'] = $full_name;
            if (!empty($email)) $_SESSION['email'] = $email;
            if (!empty($role)) $_SESSION['role'] = $role;
        }
        
        $conn->commit();

        $response['success'] = true;
        $response['message'] = 'Profile updated successfully!';

    } catch (PDOException $e) {
        $conn->rollBack();
        http_response_code(500);
        $response['message'] = 'Failed to update admin profile.';
        error_log("Update admin profile failed: " . $e->getMessage());
    }
    
    echo json_encode($response);
}

/**
 * Get all admins for admin management.
 */
function handleGetAllAdmins($conn) {
    $response = ['success' => false];
    
    try {
        // First check if admins table exists
        $check_table = $conn->prepare("SHOW TABLES LIKE 'admins'");
        $check_table->execute();
        
        if ($check_table->rowCount() > 0) {
            // Table exists, get admins
            $stmt = $conn->prepare("SELECT admin_id, full_name, email, role, status, created_at, last_login FROM admins ORDER BY created_at DESC");
            $stmt->execute();
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response['success'] = true;
            $response['admins'] = $admins;
        } else {
            // Table doesn't exist, return empty array
            $response['success'] = true;
            $response['admins'] = [];
            $response['message'] = 'No admin accounts found.';
        }

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Failed to retrieve admins.';
        error_log("Get all admins failed: " . $e->getMessage());
    }
    
    echo json_encode($response);
}

/**
 * Update admin status (Active/Suspended).
 */
function handleUpdateAdminStatus($conn) {
    $response = ['success' => false];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Only POST method is allowed.';
        echo json_encode($response);
        return;
    }

    $admin_id = $_POST['admin_id'] ?? 0;
    $status = $_POST['status'] ?? '';

    if (!$admin_id || !in_array($status, ['Active', 'Suspended'])) {
        $response['message'] = 'Invalid admin ID or status provided.';
        echo json_encode($response);
        return;
    }

    try {
        $stmt = $conn->prepare("UPDATE admins SET status = ?, updated_at = NOW() WHERE admin_id = ?");
        $stmt->execute([$status, $admin_id]);

        $response['success'] = true;
        $response['message'] = 'Admin status updated successfully!';

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Failed to update admin status.';
        error_log("Update admin status failed: " . $e->getMessage());
    }
    
    echo json_encode($response);
}

/**
 * Deletes an admin (soft delete by setting status to Suspended).
 */
function handleDeleteAdmin($conn) {
    $response = ['success' => false];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Only POST method is allowed.';
        echo json_encode($response);
        return;
    }

    $admin_id = $_POST['admin_id'] ?? 0;

    if (!$admin_id) {
        $response['message'] = 'Admin ID is required.';
        echo json_encode($response);
        return;
    }

    try {
        // Soft delete for data integrity
        $stmt = $conn->prepare("UPDATE admins SET status = 'Suspended', updated_at = NOW() WHERE admin_id = ?");
        $stmt->execute([$admin_id]);

        $response['success'] = true;
        $response['message'] = 'Admin account deactivated successfully!';

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Failed to deactivate admin account.';
        error_log("Delete admin failed: " . $e->getMessage());
    }
    
    echo json_encode($response);
}

/**
 * Add new admin user to admins table.
 */
function handleAddAdmin($conn) {
    $response = ['success' => false];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Only POST method is allowed.';
        echo json_encode($response);
        return;
    }
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Administrator';

    if (empty($username) || empty($email) || empty($password)) {
        $response['message'] = 'Username, email, and password are required fields.';
        echo json_encode($response);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        return;
    }
    
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        return;
    }

    try {
        // Check if email already exists in admins table
        $stmt_check = $conn->prepare("SELECT admin_id FROM admins WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            $response['message'] = 'An admin account with this email already exists.';
            echo json_encode($response);
            return;
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert into admins table
        $stmt_insert = $conn->prepare("INSERT INTO admins (full_name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, 'Active', NOW())");
        $stmt_insert->execute([$username, $email, $password_hash, $role]);

        $response['success'] = true;
        $response['message'] = 'Admin account created successfully!';

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Failed to create admin account due to a server error.';
        error_log("Add admin failed: " . $e->getMessage());
    }

    echo json_encode($response);
}

/**
 * Add new admin user to users table with admin role.
 */
function handleAddAdminToUsers($conn) {
    $response = ['success' => false];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Only POST method is allowed.';
        echo json_encode($response);
        return;
    }
    
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Administrator';

    if (empty($fullName) || empty($email) || empty($password)) {
        $response['message'] = 'Full name, email, and password are required fields.';
        echo json_encode($response);
        return;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format.';
        echo json_encode($response);
        return;
    }
    
    if (strlen($password) < 8) {
        $response['message'] = 'Password must be at least 8 characters long.';
        echo json_encode($response);
        return;
    }

    try {
        // Check if email already exists in users table
        $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            $response['message'] = 'A user account with this email already exists.';
            echo json_encode($response);
            return;
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert into users table with admin role
        $stmt_insert = $conn->prepare("INSERT INTO users (firstName, lastName, email, password, roles, status, created_at) VALUES (?, '', ?, ?, ?, 'Active', NOW())");
        $stmt_insert->execute([$fullName, $email, $password_hash, $role]);

        $response['success'] = true;
        $response['message'] = 'Admin user created successfully in Users table!';

    } catch (PDOException $e) {
        http_response_code(500);
        $response['message'] = 'Failed to create admin user due to a server error.';
        error_log("Add admin to users failed: " . $e->getMessage());
    }

    echo json_encode($response);
}
?>