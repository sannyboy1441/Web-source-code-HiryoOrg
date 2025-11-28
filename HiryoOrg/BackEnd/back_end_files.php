<?php
/**
 * BackEnd Files Handler
 * 
 * This file serves as the main backend handler for the Hiryo Organization system.
 * It contains database operations, API endpoints, and server-side logic.
 * 
 * @author HiryoOrg Development Team
 * @version 1.0
 */

// Database configuration and connection
require_once '../FrontEnd/pages/datab_try.php';

// Session management
session_start();

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header("Location: ../FrontEnd/pages/login.php");
        exit();
    }
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Handle API requests
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_users':
            handleGetUsers();
            break;
            
        case 'get_products':
            handleGetProducts();
            break;
            
        case 'get_orders':
            handleGetOrders();
            break;
            
        case 'get_transactions':
            handleGetTransactions();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
}

/**
 * Get all users from database
 */
function handleGetUsers() {
    global $conn;
    
    try {
        $query = "SELECT id, username, email, role, status, created_at FROM users ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $users = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = $row;
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $users]);
        } else {
            throw new Exception('Database query failed');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch users: ' . $e->getMessage()]);
    }
}

/**
 * Get all products from database
 */
function handleGetProducts() {
    global $conn;
    
    try {
        $query = "SELECT * FROM products ORDER BY updated_at DESC";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $products = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = $row;
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $products]);
        } else {
            throw new Exception('Database query failed');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch products: ' . $e->getMessage()]);
    }
}

/**
 * Get all orders from database
 */
function handleGetOrders() {
    global $conn;
    
    try {
        $query = "SELECT * FROM orders ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $orders = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $orders[] = $row;
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $orders]);
        } else {
            throw new Exception('Database query failed');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch orders: ' . $e->getMessage()]);
    }
}

/**
 * Get all transactions from database
 */
function handleGetTransactions() {
    global $conn;
    
    try {
        $query = "SELECT * FROM transactions ORDER BY created_at DESC";
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            $transactions = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $transactions[] = $row;
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'data' => $transactions]);
        } else {
            throw new Exception('Database query failed');
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch transactions: ' . $e->getMessage()]);
    }
}

/**
 * Log system activities
 * @param string $action
 * @param string $details
 */
function logActivity($action, $details = '') {
    global $conn;
    
    if (isAuthenticated()) {
        $user_id = $_SESSION['user_id'];
        $timestamp = date('Y-m-d H:i:s');
        
        $query = "INSERT INTO activity_logs (user_id, action, details, timestamp) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "isss", $user_id, $action, $details, $timestamp);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
}

/**
 * Generate secure random token
 * @param int $length
 * @return string
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Hash password securely
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

?>
