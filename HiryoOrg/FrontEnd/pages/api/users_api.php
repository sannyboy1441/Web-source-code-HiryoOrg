<?php
/**
 * UNIFIED User API
 * Serves the Web Admin Panel for all user management tasks.
 */

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
require_once '../datab_try.php'; // Use the standard secure connection

// --- Main API Router ---
try {
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get_all_users': // Renamed for clarity
        case 'get_users_for_admin': // Legacy support for cached requests
            handleGetAllUsers($conn);
            break;

        case 'get_user_details': // ADDED: For viewing a single user
            handleGetUserDetails($conn);
            break;

        case 'add_user':
            handleAddUser($conn);
            break;

        case 'update_user_status': // ADDED: For suspending/activating users
            handleUpdateUserStatus($conn);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid user action.']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log("User API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}

/**
 * FOR WEB ADMIN: Retrieves a list of all users.
 */
function handleGetAllUsers($conn) {
    try {
        // Get regular users from users table
        $stmt_users = $conn->prepare("
            SELECT user_id, CONCAT(firstName, ' ', lastName) as name, username, email, roles, status, created_at, 'mobile_user' as user_type
            FROM users 
            ORDER BY created_at DESC
        ");
        $stmt_users->execute();
        $regular_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);
        
        // Get admin users from admins table (only if table exists and has records)
        $admin_users = [];
        try {
            // First check if admins table exists
            $check_table = $conn->prepare("SHOW TABLES LIKE 'admins'");
            $check_table->execute();
            
            if ($check_table->rowCount() > 0) {
                // Table exists, now get admin users
                $stmt_admins = $conn->prepare("
                    SELECT admin_id as user_id, full_name as name, email, role as roles, status, created_at, 'admin_user' as user_type
                    FROM admins 
                    WHERE status = 'Active'
                    ORDER BY created_at DESC
                ");
                $stmt_admins->execute();
                $admin_users = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            // If there's an error accessing admins table, just skip it
            error_log("Error accessing admins table: " . $e->getMessage());
            $admin_users = [];
        }
        
        // Combine both arrays
        $all_users = array_merge($admin_users, $regular_users);
        
        // Sort by created_at descending
        usort($all_users, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        echo json_encode(['success' => true, 'users' => $all_users]);
    } catch (PDOException $e) {
        error_log("Users API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Users API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * FOR WEB ADMIN: Retrieves full details for a single user.
 */
function handleGetUserDetails($conn) {
    $user_id = (int)($_REQUEST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid user ID is required.']);
        return;
    }

    // CORRECTED: Fetches user by 'user_id' with correct column names
    $stmt = $conn->prepare("SELECT user_id, firstName, lastName, username, email, roles, status, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }
}

/**
 * FOR WEB ADMIN: Adds a new user with validation.
 */
function handleAddUser($conn) {
    // Handle both GET and POST requests (workaround for server issue)
    $firstName = trim($_POST['firstName'] ?? $_GET['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? $_GET['lastName'] ?? '');
    $username = trim($_POST['username'] ?? $_GET['username'] ?? '');
    $email = trim($_POST['email'] ?? $_GET['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? $_GET['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? $_GET['address'] ?? '');
    $password = $_POST['password'] ?? $_GET['password'] ?? '';
    $role = $_POST['role'] ?? $_GET['role'] ?? 'customer';

    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'First name, last name, username, email, and password are required.']);
        return;
    }
    if (strlen($password) < 8) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long.']);
        return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format.']);
        return;
    }

    // --- ADDED: Check for duplicate username or email ---
    $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt_check->execute([$username, $email]);
    if ($stmt_check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email is already taken.']);
        return;
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare(
        "INSERT INTO users (firstName, lastName, username, email, contact_number, address, password, roles, status, created_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Active', NOW())"
    );
    
    if ($stmt->execute([$firstName, $lastName, $username, $email, $contact_number, $address, $password_hash, $role])) {
        // Get the newly created user data
        $user_id = $conn->lastInsertId();
        $user_data = [
            'user_id' => $user_id,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'username' => $username,
            'email' => $email,
            'contact_number' => $contact_number,
            'address' => $address
        ];
        
        echo json_encode([
            'success' => true, 
            'message' => ucfirst(strtolower($role)) . ' registered successfully!',
            'user' => $user_data
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user account.']);
    }
}

/**
 * FOR WEB ADMIN: Updates a user's status (e.g., Active/Suspended).
 */
function handleUpdateUserStatus($conn) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $valid_statuses = ['Active', 'Suspended'];

    if ($user_id <= 0 || !in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID or status provided.']);
        return;
    }

    // CORRECTED: Updates user by 'id'
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->execute([$status, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'User status updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found or status was not changed.']);
    }
}
?>
