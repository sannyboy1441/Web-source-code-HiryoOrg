<?php
/**
 * SIMPLIFIED Transactions API - Version 5
 * This version focuses on simplicity and reliability
 */

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../datab_try.php';

// --- Main API Router ---
try {
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'get_all_transactions':
            handleGetAllTransactions($conn);
            break;
            
        case 'get_user_transactions':
            handleGetUserTransactions($conn);
            break;
            
        case 'get_completed_order_details':
            handleGetCompletedOrderDetails($conn);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or missing action.']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGetAllTransactions($conn) {
    try {
        // First, ensure the transactions table exists
        $create_table_sql = "
            CREATE TABLE IF NOT EXISTS transactions (
                transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                user_id INT NOT NULL,
                customer_name VARCHAR(255),
                customer_email VARCHAR(255),
                customer_contact VARCHAR(20),
                delivery_method VARCHAR(20) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                shipping_address TEXT,
                subtotal DECIMAL(10,2) NOT NULL,
                delivery_fee DECIMAL(10,2) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(20) DEFAULT 'Completed',
                created_at DATETIME NOT NULL,
                items TEXT
            )
        ";
        
        $conn->exec($create_table_sql);
        
        // Add status column if it doesn't exist (for existing tables)
        try {
            $conn->exec("ALTER TABLE transactions ADD COLUMN status VARCHAR(20) DEFAULT 'Completed'");
        } catch (Exception $e) {
            // Column might already exist, ignore error
        }
        
        error_log("Transactions table ensured to exist");
        
        // Now get all transactions
        $query = "SELECT * FROM transactions ORDER BY order_id DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($transactions) . " transactions in database");
        
        // Also check if there are any completed orders that should be in transactions
        $ordersQuery = "SELECT order_id, status FROM orders WHERE status IN ('Completed', 'Cancelled') ORDER BY order_id DESC";
        $ordersStmt = $conn->prepare($ordersQuery);
        $ordersStmt->execute();
        $completedOrders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($completedOrders) . " completed orders in orders table");
        error_log("Completed orders: " . json_encode($completedOrders));
        
        // Process each transaction
        foreach ($transactions as &$transaction) {
            // Ensure we have all required fields
            $transaction['user_name'] = $transaction['customer_name'] ?? 'Unknown Customer';
            $transaction['user_email'] = $transaction['customer_email'] ?? 'unknown@email.com';
            // Use status from database, default to 'Completed' if not set
            $transaction['order_status'] = $transaction['status'] ?? 'Completed';
            
            // Ensure amount field
            if (empty($transaction['amount']) && !empty($transaction['total_amount'])) {
                $transaction['amount'] = $transaction['total_amount'];
            }
            
            // Ensure created_at field
            if (empty($transaction['created_at']) && !empty($transaction['completion_date'])) {
                $transaction['created_at'] = $transaction['completion_date'];
            }
            
            // Parse items JSON
            if (!empty($transaction['items'])) {
                $transaction['items'] = json_decode($transaction['items'], true);
            }
        }
        
        echo json_encode([
            'success' => true,
            'transactions' => $transactions,
            'message' => count($transactions) > 0 ? 'Transactions loaded successfully' : 'No completed transactions found',
            'debug_info' => [
                'transactions_count' => count($transactions),
                'completed_orders_count' => count($completedOrders),
                'completed_orders' => $completedOrders
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Error in handleGetAllTransactions: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load transactions: ' . $e->getMessage()
        ]);
    }
}

function handleGetUserTransactions($conn) {
    $user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    try {
        // Ensure the transactions table exists
        $create_table_sql = "
            CREATE TABLE IF NOT EXISTS transactions (
                transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                user_id INT NOT NULL,
                customer_name VARCHAR(255),
                customer_email VARCHAR(255),
                customer_contact VARCHAR(20),
                delivery_method VARCHAR(20) NOT NULL,
                payment_method VARCHAR(50) NOT NULL,
                shipping_address TEXT,
                subtotal DECIMAL(10,2) NOT NULL,
                delivery_fee DECIMAL(10,2) NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                status VARCHAR(20) DEFAULT 'Completed',
                created_at DATETIME NOT NULL,
                items TEXT
            )
        ";
        
        $conn->exec($create_table_sql);
        
        // Add status column if it doesn't exist (for existing tables)
        try {
            $conn->exec("ALTER TABLE transactions ADD COLUMN status VARCHAR(20) DEFAULT 'Completed'");
        } catch (Exception $e) {
            // Column might already exist, ignore error
        }
        
        $query = "SELECT * FROM transactions WHERE user_id = ? ORDER BY order_id DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_id]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process each transaction
        foreach ($transactions as &$transaction) {
            $transaction['user_name'] = $transaction['customer_name'] ?? 'Unknown Customer';
            $transaction['user_email'] = $transaction['customer_email'] ?? 'unknown@email.com';
            // Use status from database, default to 'Completed' if not set
            $transaction['order_status'] = $transaction['status'] ?? 'Completed';
            
            if (empty($transaction['amount']) && !empty($transaction['total_amount'])) {
                $transaction['amount'] = $transaction['total_amount'];
            }
            
            // Parse items JSON
            if (!empty($transaction['items'])) {
                $transaction['items'] = json_decode($transaction['items'], true);
            }
        }
        
        echo json_encode([
            'success' => true,
            'transactions' => $transactions
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load user transactions: ' . $e->getMessage()
        ]);
    }
}

function handleGetCompletedOrderDetails($conn) {
    $order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? null;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'message' => 'Order ID is required']);
        return;
    }
    
    try {
        $query = "SELECT * FROM transactions WHERE order_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$order_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            echo json_encode(['success' => false, 'message' => 'Transaction not found']);
            return;
        }
        
        // Process transaction data and map to Order class structure
        $transaction['user_name'] = $transaction['customer_name'] ?? 'Unknown Customer';
        $transaction['user_email'] = $transaction['customer_email'] ?? 'unknown@email.com';
        // Use status from database, default to 'Completed' if not set
        $transaction['order_status'] = $transaction['status'] ?? 'Completed';
        
        // Map transaction fields to Order class expected fields
        if (empty($transaction['order_date']) && !empty($transaction['created_at'])) {
            $transaction['order_date'] = $transaction['created_at'];
        }
        
        if (empty($transaction['total_amount']) && !empty($transaction['amount'])) {
            $transaction['total_amount'] = $transaction['amount'];
        }
        
        // Ensure subtotal and delivery_fee are properly set
        // Don't override with 0 if they exist, preserve original values
        if (!isset($transaction['subtotal'])) {
            $transaction['subtotal'] = 0;
        }
        if (!isset($transaction['delivery_fee'])) {
            $transaction['delivery_fee'] = 0;
        }
        
        // Parse items JSON and fix prices
        if (!empty($transaction['items'])) {
            $transaction['items'] = json_decode($transaction['items'], true);
            
            // If items don't have correct prices, fetch them from order_items and products
            if (is_array($transaction['items'])) {
                $fixed_items = [];
                foreach ($transaction['items'] as $item) {
                    // If price is missing or 0, fetch the correct price
                    if (empty($item['price']) || $item['price'] == 0) {
                        $price_stmt = $conn->prepare("
                            SELECT oi.*, p.product_name, p.price as product_price, 
                                   COALESCE(oi.price_at_purchase, p.price) as item_price
                            FROM order_items oi 
                            LEFT JOIN products p ON oi.product_id = p.product_id 
                            WHERE oi.order_id = ? AND oi.product_id = ?
                        ");
                        $price_stmt->execute([$order_id, $item['product_id']]);
                        $price_data = $price_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($price_data) {
                            $item['price'] = $price_data['item_price'];
                            $item['product_price'] = $price_data['product_price'];
                        }
                    }
                    $fixed_items[] = $item;
                }
                $transaction['items'] = $fixed_items;
            }
        }
        
        echo json_encode([
            'success' => true,
            'order' => $transaction  // Mobile app expects 'order' field
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to load transaction details: ' . $e->getMessage()
        ]);
    }
}
?>
