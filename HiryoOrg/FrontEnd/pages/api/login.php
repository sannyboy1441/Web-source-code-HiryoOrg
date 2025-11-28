<?php
// Set headers for JSON response and CORS
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Standardized Database Connection ---
require_once '../datab_try.php'; // Using the web admin's connection method

// --- Helper function to send JSON response ---
function sendJsonResponse($success, $message, $data = []) {
    $response = ["success" => $success, "message" => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

// --- Get Database Connection ---
$conn = getDBConnection();
if (!$conn) {
    http_response_code(500);
    sendJsonResponse(false, "Could not connect to the server.");
}

// --- Main Login Logic ---

// Handle both GET and POST requests (workaround for server issue)
$login_id = trim($_POST['email'] ?? $_GET['email'] ?? ''); // This holds the username from the app
$password = $_POST['password'] ?? $_GET['password'] ?? '';

if (empty($login_id) || empty($password)) {
    sendJsonResponse(false, 'Please enter both username and password.');
}

try {
    // Select all necessary user data with correct column names
    $stmt = $conn->prepare("SELECT user_id, firstName, lastName, username, email, contact_number, address, password FROM users WHERE (username = ? OR email = ?)");
    
    $stmt->execute([$login_id, $login_id]);  
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Login is successful - format user data to match mobile app expectations
        $userData = [
            'user_id' => $user['user_id'],
            'firstName' => $user['firstName'],
            'lastName' => $user['lastName'],
            'username' => $user['username'],
            'email' => $user['email'],
            'contact_number' => $user['contact_number'] ?? '',
            'address' => $user['address'] ?? ''
        ];
        sendJsonResponse(true, 'Login successful!', ['user' => $userData]);
    } else {
        // Login failed
        sendJsonResponse(false, "Login failed. Invalid username or password.");
    }

} catch (PDOException $e) {
    error_log("Login failed during execution: " . $e->getMessage());
    http_response_code(500);
    sendJsonResponse(false, 'An internal error occurred during login.');
}
?>
