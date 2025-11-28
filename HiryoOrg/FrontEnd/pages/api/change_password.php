<?php
/**
 * Change Password API
 * This script handles password changes from the mobile app
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
    $currentPassword = trim($_POST['currentPassword'] ?? $_GET['currentPassword'] ?? '');
    $newPassword = trim($_POST['newPassword'] ?? $_GET['newPassword'] ?? '');

    if (empty($username) || empty($currentPassword) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
        exit;
    }

    // Verify user credentials
    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid current password']);
        exit;
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $result = $stmt->execute([$hashedPassword, $user['user_id']]);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to change password']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

ob_end_flush();
?>