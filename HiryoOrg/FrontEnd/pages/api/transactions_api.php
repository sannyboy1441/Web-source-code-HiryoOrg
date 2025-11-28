<?php
/**
 * UNIFIED Transactions API
 * Serves BOTH the Web Admin Panel and the Mobile App.
 * This single file replaces transactions_api.php and transaction.php.
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
        // --- Actions for BOTH Admin and Mobile App ---
        case 'get_transaction_details':
            handleGetTransactionDetails($conn);
        break;
        
        case 'get_completed_order_details':
            handleGetCompletedOrderDetails($conn);
        break;
        
    case 'create_transaction':
            handleCreateTransaction($conn);
            break;

        // --- Actions primarily for the MOBILE APP ---
        case 'get_user_transactions':
            handleGetUserTransactions($conn);
            break;

        case 'get_order_transaction':
            handleGetOrderTransaction($conn);
            break;

        // --- Actions primarily for the WEB ADMIN PANEL ---
        case 'get_all_transactions':
            handleGetAllTransactions($conn);
        break;
        
    case 'update_transaction_status':
            handleUpdateTransactionStatus($conn);
        break;

    default:
            http_response_code(400); // Bad Request
            echo json_encode(['success' => false, 'message' => 'Invalid or missing action.']);
        break;
}

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Transactions API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A server error occurred.']);
}


/**
 * FOR MOBILE APP: Records a new transaction and updates the corresponding order status.
 * This is the primary function for creating a transaction.
 */
function handleCreateTransaction($conn) {
    // This function smartly handles both raw JSON (from web) and form data (from mobile)
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input)) {
        $input = $_POST;
    }

    $required = ['order_id', 'user_id', 'amount', 'payment_method', 'status'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            return;
        }
    }

    $order_id = (int)$input['order_id'];
    $user_id = (int)$input['user_id'];
    $amount = (float)$input['amount'];
    $payment_method = trim($input['payment_method']);
    $status = trim($input['status']);
    $transactionRef = 'TXN-' . time() . '-' . rand(1000, 9999);

    try {
        // --- CRITICAL LOGIC MERGED FROM MOBILE APP'S FILE ---
        // Start a database transaction to ensure both operations succeed or fail together
        $conn->beginTransaction();

        // 1. Insert the new transaction record
        $stmt = $conn->prepare("
            INSERT INTO transactions (order_id, user_id, amount, payment_method, status, transaction_reference, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$order_id, $user_id, $amount, $payment_method, $status, $transactionRef]);
        $transactionId = $conn->lastInsertId();

        // 2. Update the corresponding order's status
        // This logic is essential and was preserved from your mobile app's API.
        $order_status = ($status === 'Completed' || $status === 'Paid') ? 'Paid' : 'Payment Failed';
        $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $updateStmt->execute([$order_status, $order_id]);
        
        // If everything was successful, commit the changes
        $conn->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'Transaction recorded successfully!',
            'transaction_id' => $transactionId,
            'transaction_reference' => $transactionRef
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack(); // Roll back all changes if an error occurred
        throw $e; // Re-throw the exception to be caught by the main error handler
    }
}


/**
 * FOR MOBILE APP: Retrieves a specific user's transaction history.
 * Only shows actual transactions from the transactions table.
 */
function handleGetUserTransactions($conn) {
    $userId = (int)($_REQUEST['user_id'] ?? 0);
    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid user ID is required.']);
        return;
    }
    
    // Get completed orders from transactions table
    // Since completed orders are moved from orders table to transactions table,
    // we query the transactions table directly
    $stmt_transactions = $conn->prepare("
        SELECT 
            t.transaction_id, 
            t.order_id, 
            COALESCE(t.total_amount, t.amount) as amount, 
            t.payment_method, 
            COALESCE(t.completion_date, t.created_at) as created_at,
            'Completed' as order_status,
            COALESCE(t.customer_name, u.firstName, u.username) as customer_name,
            COALESCE(t.customer_email, u.email) as customer_email,
            t.delivery_method,
            t.shipping_address,
            t.items
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.user_id
        WHERE t.user_id = ? 
        ORDER BY COALESCE(t.completion_date, t.created_at) DESC
    ");
    $stmt_transactions->execute([$userId]);
    $transactions = $stmt_transactions->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'transactions' => $transactions]);
}

/**
 * FOR MOBILE APP: Retrieves transaction details for a specific order.
 */
function handleGetOrderTransaction($conn) {
    $order_id = (int)($_REQUEST['order_id'] ?? 0);
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid order ID is required.']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM transactions WHERE order_id = ? LIMIT 1");
    $stmt->execute([$order_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($transaction) {
        echo json_encode(['success' => true, 'transaction' => $transaction]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No transaction found for this order.']);
    }
}


/**
 * FOR WEB ADMIN: Retrieves all transactions with user and order details.
 */
function handleGetAllTransactions($conn) {
    try {
        // First check if transactions table exists
        $checkTableStmt = $conn->prepare("SHOW TABLES LIKE 'transactions'");
        $checkTableStmt->execute();
        $tableExists = $checkTableStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$tableExists) {
            // Transactions table doesn't exist yet - this is normal if no orders have been completed
            echo json_encode([
                'success' => true, 
                'transactions' => [],
                'message' => 'No completed transactions yet. Transactions will appear here when orders are completed.'
            ]);
            return;
        }
        
        // Get transactions with customer full names and order details
        // Since completed orders are moved to transactions table, we don't need to join with orders table
        $query = "
            SELECT 
                t.transaction_id,
            t.order_id,
                t.user_id,
            COALESCE(t.total_amount, t.amount) as amount,
                t.payment_method,
            COALESCE(t.completion_date, t.created_at) as created_at,
            COALESCE(t.customer_name, u.firstName, u.username) as user_name,
            COALESCE(t.customer_email, u.email) as user_email,
            t.customer_contact,
            t.delivery_method,
            t.shipping_address,
            t.subtotal,
            t.delivery_fee,
            t.items,
            'Completed' as order_status
            FROM transactions t
            LEFT JOIN users u ON t.user_id = u.user_id
        ORDER BY COALESCE(t.completion_date, t.created_at) DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'transactions' => $transactions,
            'message' => count($transactions) === 0 ? 'No completed transactions found. Transactions will appear here when orders are completed.' : null
        ]);
    } catch (PDOException $e) {
        error_log("Transactions API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Transactions API Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}


/**
 * FOR WEB ADMIN: Retrieves completed order details for modal display
 */
function handleGetCompletedOrderDetails($conn) {
    $order_id = (int)($_REQUEST['order_id'] ?? 0);
    
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid order ID is required']);
            return;
        }
        
    try {
        // Get completed order details from transactions table
        $stmt = $conn->prepare("
            SELECT 
                t.*,
                'Completed' as status
            FROM transactions t
            WHERE t.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            echo json_encode(['success' => false, 'message' => 'Completed order not found']);
            return;
        }
        
        // Parse items JSON
        $items = [];
        if (!empty($transaction['items'])) {
            $items = json_decode($transaction['items'], true) ?: [];
        }
        
        // Add items to transaction data
        $transaction['items'] = $items;
        
        echo json_encode(['success' => true, 'order' => $transaction]);
        
    } catch (Exception $e) {
        error_log("Get completed order details error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to get order details: ' . $e->getMessage()]);
    }
}

/**
 * FOR WEB ADMIN: Updates the status of a specific transaction.
 */
function handleUpdateTransactionStatus($conn) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $transactionId = (int)($input['transaction_id'] ?? 0);
    $status = trim($input['status'] ?? '');

    if ($transactionId <= 0 || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Valid transaction ID and status are required.']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE transaction_id = ?");
    $stmt->execute([$status, $transactionId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Transaction status updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found or status was not changed.']);
    }
}


/**
 * FOR BOTH: Gets the full details for a single transaction.
 */
function handleGetTransactionDetails($conn) {
    $transactionId = (int)($_REQUEST['transaction_id'] ?? 0);
    if ($transactionId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid transaction ID is required.']);
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT t.transaction_id, t.user_id, t.order_id, t.amount, t.payment_method, t.created_at, u.username as user_name, u.email as user_email, o.status as order_status, o.total_amount
        FROM transactions t
        LEFT JOIN users u ON t.user_id = u.user_id
        LEFT JOIN orders o ON t.order_id = o.order_id
        WHERE t.transaction_id = ?
    ");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($transaction) {
        echo json_encode(['success' => true, 'transaction' => $transaction]);
        } else {
        echo json_encode(['success' => false, 'message' => 'Transaction not found.']);
    }
}

?>
