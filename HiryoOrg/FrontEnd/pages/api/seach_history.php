<?php
header("Content-Type: application/json");

// --- Standardized Database Connection ---
require_once '../datab_try.php'; // Using the web admin's connection method

// --- Helper Function ---
function sendJsonResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
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
    sendJsonResponse(false, "Database connection failed.");
}

// --- Main Logic ---
$user_id = $_REQUEST['user_id'] ?? 0;
$action = $_REQUEST['action'] ?? '';

if ($user_id == 0) {
    sendJsonResponse(false, "User ID is required.");
}

try {
    switch ($action) {
        case 'save':
            $query_term = $_POST['query_term'] ?? '';
            if (empty($query_term)) {
                sendJsonResponse(false, "Search query term is required.");
            }

            // Upsert logic: Delete existing to move it to the top, then insert new.
            $deleteStmt = $conn->prepare("DELETE FROM search_history WHERE user_id = ? AND query_term = ?");
            $deleteStmt->execute([$user_id, $query_term]);
            
            $insertStmt = $conn->prepare("INSERT INTO search_history (user_id, query_term) VALUES (?, ?)");
            $insertStmt->execute([$user_id, $query_term]);
            
            sendJsonResponse(true, "Search term saved.");
            break;

        case 'get':
            $stmt = $conn->prepare("SELECT query_term FROM search_history WHERE user_id = ? ORDER BY id DESC LIMIT 10");
            $stmt->execute([$user_id]);
            $history = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            sendJsonResponse(true, "History fetched.", ['history' => $history]);
            break;

        case 'delete':
            $query_term = $_POST['query_term'] ?? '';
            
            if (!empty($query_term)) {
                // Delete a specific term
                $stmt = $conn->prepare("DELETE FROM search_history WHERE user_id = ? AND query_term = ?");
                $stmt->execute([$user_id, $query_term]);
                sendJsonResponse(true, "Search term deleted.");
            } else {
                // Delete ALL history for the user
                $stmt = $conn->prepare("DELETE FROM search_history WHERE user_id = ?");
                $stmt->execute([$user_id]);
                sendJsonResponse(true, "All search history deleted.");
            }
            break;

        default:
            sendJsonResponse(false, "Invalid action.");
    }
} catch (PDOException $e) {
    error_log("Search history error: " . $e->getMessage());
    http_response_code(500);
    sendJsonResponse(false, "An internal server error occurred.");
}
?>
