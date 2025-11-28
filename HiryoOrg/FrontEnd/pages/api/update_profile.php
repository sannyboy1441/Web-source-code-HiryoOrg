<?php
/**
 * Update Profile API
 * This script handles profile updates from the mobile app
 */

header('Content-Type: application/json');

// Start output buffering
ob_start();

// Suppress warnings
error_reporting(E_ERROR | E_PARSE);

// Clear any output
ob_clean();

// Include database connection
require_once '../datab_try.php';

try {
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Handle both GET and POST requests (workaround for server issue)
    $username = trim($_POST['username'] ?? $_GET['username'] ?? '');
    $password = trim($_POST['password'] ?? $_GET['password'] ?? '');
    $field = trim($_POST['field'] ?? $_GET['field'] ?? '');
    $value = trim($_POST['value'] ?? $_GET['value'] ?? '');

    if (empty($username) || empty($password) || empty($field) || empty($value)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Verify user credentials
    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
        exit;
    }

    // Validate field name to prevent SQL injection
    $allowedFields = ['firstName', 'lastName', 'username', 'email', 'contact_number', 'address'];
    if (!in_array($field, $allowedFields)) {
        echo json_encode(['success' => false, 'message' => 'Invalid field name']);
        exit;
    }

    // Update the field
    $sql = "UPDATE users SET $field = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$value, $user['user_id']]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => ucfirst($field) . ' updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

ob_end_flush();
?>