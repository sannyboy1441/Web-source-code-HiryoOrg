<?php
/**
 * UNIFIED Notifications API
 * Serves BOTH the Web Admin Panel and the Mobile App.
 * This single file replaces notifications_api.php and notifications.php.
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
        // --- Actions primarily for the WEB ADMIN PANEL ---
        case 'create_announcement':
            handleCreateAnnouncement($conn);
            break;
            
        case 'get_all_notifications_for_admin': // Renamed for clarity
        case 'get_notifications_for_admin': // Legacy support for cached requests
            handleGetAllNotificationsForAdmin($conn);
            break;
            
        case 'mark_notification_read':
            handleMarkNotificationRead($conn);
            break;
            
        case 'delete_notification':
        case 'delete': // For mobile app compatibility
            handleDeleteNotification($conn);
            break;

        // --- Actions primarily for the MOBILE APP ---
        case 'get_announcements':
            handleGetAnnouncements($conn);
            break;
            
        case 'get_all_announcements':
            handleGetAllAnnouncements($conn);
            break;

        case 'get_user_notifications': // ADDED: Essential function for the mobile app
            handleGetUserNotifications($conn);
            break;
            
        case 'create_notification':
            handleCreateNotification($conn);
            break;

        default:
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Invalid or missing action.']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Notifications API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}


/**
 * FOR WEB ADMIN: Creates a new global announcement for all mobile app users.
 */
function handleCreateAnnouncement($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Only POST method is allowed.']);
        return;
    }

    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($title) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Title and message cannot be empty.']);
        return;
    }

    try {
        $conn->beginTransaction();

        // Insert announcement into announcements table
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$title, $message]);
        $announcement_id = $conn->lastInsertId();

        // Get all active users (customers only, not admin)
        $stmt_users = $conn->prepare("SELECT user_id FROM users WHERE roles = 'customer' AND status = 'Active'");
        $stmt_users->execute();
        $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

        // Create a notification for each user
        $stmt_notification = $conn->prepare("
            INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at) 
            VALUES (?, NULL, ?, ?, 'general', 0, NOW())
        ");

        $notificationsSent = 0;
        foreach ($users as $user) {
            $stmt_notification->execute([
                $user['user_id'],
                $title,
                $message
            ]);
            $notificationsSent++;
        }

        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => "Announcement created and sent to $notificationsSent users!",
            'announcement_id' => $announcement_id,
            'notifications_sent' => $notificationsSent
        ]);

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Create announcement failed: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to create announcement: ' . $e->getMessage()
        ]);
    }
}


/**
 * FOR WEB ADMIN: Fetches all user-specific notifications for the admin panel.
 */
function handleGetAllNotificationsForAdmin($conn) {
    try {
        // Get all notifications including admin notifications (user_id = 0)
        $query = "
            SELECT 
                n.notification_id, n.user_id, n.order_id, n.title, n.message, n.type, n.is_read, n.created_at,
                o.user_id as order_user_id, u.firstName, u.lastName
            FROM notifications n
            LEFT JOIN orders o ON n.order_id = o.order_id
            LEFT JOIN users u ON o.user_id = u.user_id
            ORDER BY n.created_at DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert is_read from integer to boolean
        foreach ($notifications as &$notification) {
            $notification['is_read'] = (bool) $notification['is_read'];
        }

        echo json_encode(['success' => true, 'notifications' => $notifications]);
    } catch (PDOException $e) {
        error_log("Notifications API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Notifications API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}


/**
 * FOR WEB ADMIN: Marks a specific notification as read.
 */
function handleMarkNotificationRead($conn) {
    $notification_id = (int)($_POST['notification_id'] ?? 0);

    if ($notification_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid notification ID is required.']);
        return;
    }

    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
    $stmt->execute([$notification_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification marked as read!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found or already marked as read.']);
    }
}


/**
 * FOR WEB ADMIN: Deletes a specific notification.
 */
function handleDeleteNotification($conn) {
    $notification_id = (int)($_POST['notification_id'] ?? 0);

    if ($notification_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid notification ID is required.']);
        return;
    }

    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
    $stmt->execute([$notification_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Notification deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Notification not found.']);
    }
}


/**
 * FOR MOBILE APP: Fetches all global announcements.
 */
function handleGetAnnouncements($conn) {
    $stmt = $conn->prepare("SELECT announcement_id, title, message, created_at FROM announcements ORDER BY created_at DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'announcements' => $announcements]);
}

/**
 * FOR WEB ADMIN: Fetches all announcements for admin panel display.
 */
function handleGetAllAnnouncements($conn) {
    try {
        $stmt = $conn->prepare("SELECT announcement_id, title, message, created_at FROM announcements ORDER BY created_at DESC");
        $stmt->execute();
        $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'announcements' => $announcements]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch announcements: ' . $e->getMessage()]);
    }
}


/**
 * FOR MOBILE APP: Fetches notifications for a specific user.
 * This is a new, essential function that was missing from your original files.
 */
function handleGetUserNotifications($conn) {
    $user_id = (int)($_REQUEST['user_id'] ?? 0);

    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'A valid user ID is required.']);
        return;
    }

    // This query gets notifications for the specific user (including announcements sent to them)
    $query = "
        SELECT notification_id, order_id, title, message, type, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50 
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert is_read from integer to boolean
    foreach ($notifications as &$notification) {
        $notification['is_read'] = (bool) $notification['is_read'];
    }

    echo json_encode(['success' => true, 'notifications' => $notifications]);
}

/**
 * Creates a new notification for admin or user
 */
function handleCreateNotification($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Only POST method is allowed.']);
        return;
    }

    $user_id = (int)($_POST['user_id'] ?? 0);
    $order_id = (int)($_POST['order_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $type = trim($_POST['type'] ?? 'general');

    if (empty($title) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Title and message cannot be empty.']);
        return;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$user_id, $order_id, $title, $message, $type]);

        echo json_encode(['success' => true, 'message' => 'Notification created successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create notification: ' . $e->getMessage()]);
    }
}

?>
