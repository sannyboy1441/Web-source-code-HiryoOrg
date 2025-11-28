<?php
/**
 * Orders API V2 - Clean working version
 */

// Set headers first
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
$db_path = '../datab_try.php';
if (!@include_once $db_path) {
    echo json_encode(['success' => false, 'message' => 'Database connection file not found']);
    exit;
}

try {
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get action
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_order_details':
            handleGetOrderDetails($conn);
            break;
            
        case 'update_order_status':
            handleUpdateOrderStatus($conn);
            break;
            
        case 'get_all_orders_for_admin':
            handleGetAllOrdersForAdmin($conn);
            break;
            
        case 'place_order':
            handlePlaceOrder($conn);
            break;
            
        case 'get_user_orders':
            handleGetUserOrders($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred: ' . $e->getMessage(),
        'error_details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'message' => $e->getMessage()
        ]
    ]);
}

function handleGetOrderDetails($conn) {
    $order_id = (int)($_GET['order_id'] ?? 0);
    
    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valid order ID is required']);
        return;
    }
    
    // Get order details
    $stmt_order = $conn->prepare("
        SELECT 
            o.*, 
            u.firstName, 
            u.lastName, 
            u.username,
            u.email, 
            u.contact_number as contact_number,
            u.address,
            COALESCE(o.delivery_fee, 0) as delivery_fee,
            COALESCE(o.subtotal, 0) as subtotal,
            COALESCE(o.total_amount, 0) as total_amount
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        WHERE o.order_id = ?
    ");
    
    $stmt_order->execute([$order_id]);
    $order = $stmt_order->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }
    
    // Get order items
    $stmt_items = $conn->prepare("
        SELECT oi.*, p.product_name, p.image_url 
        FROM order_items oi 
        LEFT JOIN products p ON oi.product_id = p.product_id 
        WHERE oi.order_id = ?
    ");
    
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    // Post-process the order data
    // Build full name from user data
    $firstName = $order['firstName'] ?? '';
    $lastName = $order['lastName'] ?? '';
    $username = $order['username'] ?? '';
    
    if (!empty($firstName) && !empty($lastName)) {
        $order['customer_name'] = $firstName . ' ' . $lastName;
    } elseif (!empty($firstName)) {
        $order['customer_name'] = $firstName;
    } elseif (!empty($username)) {
        $order['customer_name'] = $username;
    } else {
        $order['customer_name'] = 'Unknown Customer';
    }
    
    // Ensure we have a delivery_fee
    if (empty($order['delivery_fee'])) {
        $order['delivery_fee'] = 0;
    }
    
    // Ensure we have a subtotal
    if (empty($order['subtotal'])) {
        $order['subtotal'] = 0;
    }
    
    // Ensure we have a total_amount
    if (empty($order['total_amount'])) {
        $order['total_amount'] = 0;
    }
    
    $order['items'] = $items;
    
    echo json_encode(['success' => true, 'order' => $order]);
}

function handleUpdateOrderStatus($conn) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($order_id <= 0 || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Valid order ID and status are required']);
        return;
    }
    
    // Check if connection is valid
    if (!$conn || !($conn instanceof PDO)) {
        echo json_encode(['success' => false, 'message' => 'Database connection is not valid']);
        return;
    }
    
    try {
        // If status is 'completed' or 'cancelled', handle the full transfer process
        if (strtolower($status) === 'completed' || strtolower($status) === 'cancelled') {
            
            // Get order details FIRST before updating status
            $order_stmt = $conn->prepare("
                SELECT o.*, u.firstName, u.lastName, u.email, u.contact_number
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.user_id 
                WHERE o.order_id = ?
            ");
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                throw new Exception('Order not found');
            }
            
            // Get order items
            $items_stmt = $conn->prepare("
                SELECT oi.*, p.product_name, p.price as product_price, 
                       COALESCE(oi.price_at_purchase, p.price) as item_price
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = ?
            ");
            $items_stmt->execute([$order_id]);
            $items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Create transactions table if it doesn't exist
            $conn->exec("
                CREATE TABLE IF NOT EXISTS transactions (
                    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
                    order_id INT NOT NULL,
                    user_id INT NOT NULL,
                    customer_name VARCHAR(255),
                    customer_email VARCHAR(255),
                    customer_contact VARCHAR(20),
                    delivery_method VARCHAR(20),
                    payment_method VARCHAR(50),
                    shipping_address TEXT,
                    subtotal DECIMAL(10,2),
                    delivery_fee DECIMAL(10,2),
                    amount DECIMAL(10,2),
                    status VARCHAR(20) DEFAULT 'Completed',
                    created_at DATETIME,
                    items TEXT
                )
            ");
            
            // Add status column if it doesn't exist (for existing tables)
            try {
                $conn->exec("ALTER TABLE transactions ADD COLUMN status VARCHAR(20) DEFAULT 'Completed'");
            } catch (Exception $e) {
                // Column might already exist, ignore error
            }
            
            // Insert into transactions table
            $trans_stmt = $conn->prepare("
                INSERT INTO transactions (
                    order_id, user_id, customer_name, customer_email, customer_contact,
                    delivery_method, payment_method, shipping_address, subtotal, 
                    delivery_fee, amount, status, created_at, items
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            
            $customer_name = trim($order['firstName'] . ' ' . $order['lastName']);
            $items_json = json_encode($items);
            
            // Set status: 'Completed' or 'Cancelled'
            $transaction_status = ucfirst(strtolower($status));
            
            $trans_result = $trans_stmt->execute([
                $order_id,
                $order['user_id'],
                $customer_name,
                $order['email'],
                $order['contact_number'],
                $order['delivery_method'],
                $order['payment_method'],
                $order['shipping_address'],
                $order['subtotal'],
                $order['delivery_fee'],
                $order['total_amount'],
                $transaction_status,
                $items_json
            ]);
            
            if (!$trans_result) {
                throw new Exception('Failed to insert into transactions table');
            }
            
            // Update order status
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $result = $stmt->execute([$status, $order_id]);
            
            if (!$result) {
                throw new Exception('Failed to update order status');
            }
            
            // Delete from orders table
            $delete_stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
            $delete_result = $delete_stmt->execute([$order_id]);
            
            if (!$delete_result) {
                throw new Exception('Failed to delete from orders table');
            }
            
            // Delete order items
            $delete_items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
            $delete_items_result = $delete_items_stmt->execute([$order_id]);
            
            if (!$delete_items_result) {
                throw new Exception('Failed to delete order items');
            }
            
        } else {
            // For other statuses, just update the status
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $result = $stmt->execute([$status, $order_id]);
            
            if (!$result) {
                throw new Exception('Failed to update order status');
            }
        }
        
        echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to update order status: ' . $e->getMessage()]);
    }
}

function handleGetAllOrdersForAdmin($conn) {
    // Only return active orders (exclude completed and cancelled orders)
    // Completed and cancelled orders should be shown in Transactions table
    $stmt = $conn->prepare("
        SELECT 
            o.*,
            u.firstName,
            u.lastName,
            u.username,
            u.email,
            u.contact_number,
            u.address,
            COALESCE(o.delivery_fee, 0) as delivery_fee,
            COALESCE(o.subtotal, 0) as subtotal,
            COALESCE(o.total_amount, 0) as total_amount
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        WHERE o.status NOT IN ('Completed', 'Cancelled')
        ORDER BY o.order_date DESC
    ");
    
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Post-process the data to ensure we have all needed fields
    foreach ($orders as &$order) {
        // Build full name from user data
        $firstName = $order['firstName'] ?? '';
        $lastName = $order['lastName'] ?? '';
        $username = $order['username'] ?? '';
        
        if (!empty($firstName) && !empty($lastName)) {
            $order['customer_name'] = $firstName . ' ' . $lastName;
        } elseif (!empty($firstName)) {
            $order['customer_name'] = $firstName;
        } elseif (!empty($username)) {
            $order['customer_name'] = $username;
        } else {
            $order['customer_name'] = 'Unknown Customer';
        }
        
        // Ensure we have a delivery_fee
        if (empty($order['delivery_fee'])) {
            $order['delivery_fee'] = 0;
        }
        
        // Ensure we have a subtotal
        if (empty($order['subtotal'])) {
            $order['subtotal'] = 0;
        }
        
        // Ensure we have a total_amount
        if (empty($order['total_amount'])) {
            $order['total_amount'] = 0;
        }
    }
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}

function handlePlaceOrder($conn) {
    // Get POST data
    $user_id = $_POST['user_id'] ?? null;
    $delivery_method = $_POST['delivery_method'] ?? 'Delivery';
    $payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';
    $shipping_address = $_POST['shipping_address'] ?? '';
    $items_json = $_POST['items'] ?? '';
    
    // Get the values calculated by the checkout (this ensures receipt matches checkout exactly)
    $total_amount_from_checkout = floatval($_POST['total_amount'] ?? 0);
    $delivery_fee_from_checkout = floatval($_POST['delivery_fee'] ?? 0);
    
    // Decode items JSON string to array
    $items = json_decode($items_json, true);
    
    if (!$user_id || empty($items) || !is_array($items)) {
        echo json_encode(['success' => false, 'message' => 'User ID and valid items are required', 'debug' => ['items_json' => $items_json, 'items_decoded' => $items]]);
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        // Debug logging
        error_log("Place Order Debug - User ID: $user_id, Items JSON: $items_json, Items Count: " . count($items));
        error_log("Checkout values - Total Amount: $total_amount_from_checkout, Delivery Fee: $delivery_fee_from_checkout");
        
        // Use the values calculated by the checkout instead of recalculating
        // This ensures the receipt matches the checkout exactly
        if ($total_amount_from_checkout > 0 && $delivery_fee_from_checkout >= 0) {
            // Use checkout values
            $total_amount = $total_amount_from_checkout;
            $delivery_fee = $delivery_fee_from_checkout;
            $subtotal = $total_amount - $delivery_fee;
            
            error_log("Using checkout values - Subtotal: $subtotal, Delivery Fee: $delivery_fee, Total: $total_amount");
        } else {
            // Fallback to calculation if checkout values are invalid
            $subtotal = 0;
            $delivery_fee = $delivery_method === 'Delivery' ? 115.00 : 0.00;
            
            foreach ($items as $item) {
                $item_price = floatval($item['price'] ?? 0);
                $item_quantity = intval($item['quantity'] ?? 0);
                $item_total = $item_price * $item_quantity;
                $subtotal += $item_total;
                
                error_log("Item: " . json_encode($item) . ", Price: $item_price, Qty: $item_quantity, Total: $item_total");
            }
            
            $total_amount = $subtotal + $delivery_fee;
            error_log("Using fallback calculation - Subtotal: $subtotal, Delivery Fee: $delivery_fee, Total: $total_amount");
        }
        
        // Insert order
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, delivery_method, payment_method, shipping_address, subtotal, delivery_fee, total_amount, status, order_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $delivery_method,
            $payment_method,
            $shipping_address,
            $subtotal,
            $delivery_fee,
            $total_amount
        ]);
        
        $order_id = $conn->lastInsertId();
        
        // Insert order items and update stock
        $stmt_items = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) 
            VALUES (?, ?, ?, ?)
        ");
        
        // Prepare statement to update product stock
        $stmt_update_stock = $conn->prepare("
            UPDATE products 
            SET stock_quantity = GREATEST(0, stock_quantity - ?), 
                updated_at = NOW() 
            WHERE product_id = ?
        ");
        
        foreach ($items as $item) {
            $product_id = $item['product_id'] ?? $item['id'] ?? 0;
            $quantity = $item['quantity'] ?? 0;
            
            // Insert order item
            $stmt_items->execute([
                $order_id,
                $product_id,
                $quantity,
                $item['price'] ?? $item['price_at_purchase'] ?? 0
            ]);
            
            // Update product stock
            $stmt_update_stock->execute([$quantity, $product_id]);
        }
        
        $conn->commit();
        
        // Send notification to admin about new order
        sendNewOrderNotification($conn, $order_id, $user_id, $total_amount);
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $order_id,
            'total_amount' => $total_amount
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to place order: ' . $e->getMessage()]);
    }
}

function handleGetUserOrders($conn) {
    $user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    
    if (!$user_id) {
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        return;
    }
    
    $stmt = $conn->prepare("
        SELECT o.*, u.firstName, u.lastName, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.user_id 
        WHERE o.user_id = ? 
        ORDER BY o.order_date DESC
    ");
    
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'orders' => $orders]);
}

function sendNewOrderNotification($conn, $order_id, $user_id, $total_amount) {
    try {
        // Create notifications table if it doesn't exist
        $create_notifications_table = "
            CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                order_id INT,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                type ENUM('order', 'system', 'promotion') DEFAULT 'order',
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_order_id (order_id),
                INDEX idx_created_at (created_at)
            )
        ";
        $conn->exec($create_notifications_table);
        
        // Send notification to admin (user_id = 1 or admin users)
        $admin_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at) 
            VALUES (1, ?, 'New Order Received', ?, 'order', 0, NOW())
        ");
        $admin_message = "New order #{$order_id} has been placed with total amount ₱{$total_amount}. Please review and process.";
        $admin_stmt->execute([$order_id, $admin_message]);
        
        // Send notification to customer
        $customer_stmt = $conn->prepare("
            INSERT INTO notifications (user_id, order_id, title, message, type, is_read, created_at) 
            VALUES (?, ?, 'Order Confirmed', ?, 'order', 0, NOW())
        ");
        $customer_message = "Your order #{$order_id} has been placed successfully and is pending confirmation. Total amount: ₱{$total_amount}";
        $customer_stmt->execute([$user_id, $order_id, $customer_message]);
        
        error_log("Notifications sent for order #{$order_id} - Admin and Customer notified");
        
    } catch (Exception $e) {
        error_log("Failed to send notifications for order #{$order_id}: " . $e->getMessage());
    }
}
?>
