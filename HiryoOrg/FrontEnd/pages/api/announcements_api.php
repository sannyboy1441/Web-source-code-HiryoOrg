<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../datab_try.php';

try {
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_GET['action'] ?? $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get_announcements':
        case 'get_all_announcements':
            handleGetAllAnnouncements($conn);
            break;
            
        case 'create_announcement':
            handleCreateAnnouncement($conn, $input);
            break;
            
        case 'update_announcement':
            handleUpdateAnnouncement($conn, $input);
            break;
            
        case 'delete_announcement':
            handleDeleteAnnouncement($conn, $input);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGetAllAnnouncements($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                announcement_id,
                title,
                message,
                created_at
            FROM announcements 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'announcements' => $announcements
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch announcements']);
    }
}

function handleCreateAnnouncement($pdo, $input) {
    try {
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        
        if (empty($title) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Title and message are required']);
            return;
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // 1. Insert into announcements table
        $stmt = $pdo->prepare("
            INSERT INTO announcements (title, message, created_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$title, $message]);
        $announcementId = $pdo->lastInsertId();
        
        // 2. Get all active users
        $usersStmt = $pdo->prepare("
            SELECT user_id FROM users 
            WHERE status = 'Active'
        ");
        $usersStmt->execute();
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Create notification for each user
        $notificationStmt = $pdo->prepare("
            INSERT INTO notifications (
                user_id,
                order_id,
                title, 
                message, 
                type, 
                is_read, 
                created_at
            ) VALUES (?, NULL, ?, ?, 'general', 0, NOW())
        ");
        
        $notificationsCreated = 0;
        foreach ($users as $user) {
            $notificationStmt->execute([
                $user['user_id'],
                $title,
                $message
            ]);
            $notificationsCreated++;
        }
        
        // Commit transaction
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => "Announcement created and sent to {$notificationsCreated} users",
            'announcement_id' => $announcementId,
            'notifications_sent' => $notificationsCreated
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to create announcement: ' . $e->getMessage()]);
    }
}

function handleUpdateAnnouncement($pdo, $input) {
    try {
        $announcementId = $input['announcement_id'] ?? 0;
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        
        if (empty($announcementId) || empty($title) || empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Announcement ID, title and message are required']);
            return;
        }
        
        // Update announcement
        $stmt = $pdo->prepare("
            UPDATE announcements 
            SET title = ?, message = ?, updated_at = NOW()
            WHERE announcement_id = ?
        ");
        $result = $stmt->execute([$title, $message, $announcementId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Announcement updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Announcement not found or no changes made']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update announcement']);
    }
}

function handleDeleteAnnouncement($pdo, $input) {
    try {
        $announcementId = $input['announcement_id'] ?? 0;
        
        if (empty($announcementId)) {
            echo json_encode(['success' => false, 'message' => 'Announcement ID is required']);
            return;
        }
        
        // Delete announcement
        $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
        $result = $stmt->execute([$announcementId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Announcement deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Announcement not found']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to delete announcement']);
    }
}
?>
