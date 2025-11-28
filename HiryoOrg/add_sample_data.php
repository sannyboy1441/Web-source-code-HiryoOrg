<?php
require_once 'FrontEnd/pages/datab_try.php';

echo "<h1>Adding Sample Data to Database</h1>\n";

try {
    $conn = getDBConnection();
    
    if (!$conn) {
        echo "<p style='color: red;'>❌ Database connection failed!</p>\n";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Database connection successful!</p>\n";
    
    // Add sample products
    echo "<h2>Adding Sample Products:</h2>\n";
    $products = [
        ['Organic Fertilizer', 'Fertilizer', 150.00, 50, 'High-quality organic fertilizer for better crop yield'],
        ['Compost Soil', 'Soil', 200.00, 30, 'Nutrient-rich compost soil for gardening'],
        ['Plant Food', 'Fertilizer', 120.00, 40, 'Essential nutrients for healthy plant growth']
    ];
    
    foreach ($products as $product) {
        try {
            $stmt = $conn->prepare("INSERT INTO products (product_name, category, price, stock_quantity, description, status, created_at) VALUES (?, ?, ?, ?, ?, 'Active', NOW())");
            $stmt->execute($product);
            echo "<p>✅ Added product: {$product[0]}</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error adding product {$product[0]}: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Add sample announcements
    echo "<h2>Adding Sample Announcements:</h2>\n";
    $announcements = [
        ['Welcome to Hiryo Organics', 'We are excited to announce our new organic products for better farming!'],
        ['New Product Launch', 'Check out our latest organic fertilizers now available in our store.'],
        ['Seasonal Promotion', 'Get 20% off on all soil products this month!']
    ];
    
    foreach ($announcements as $announcement) {
        try {
            $stmt = $conn->prepare("INSERT INTO announcements (title, message, created_at) VALUES (?, ?, NOW())");
            $stmt->execute($announcement);
            echo "<p>✅ Added announcement: {$announcement[0]}</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error adding announcement {$announcement[0]}: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Add sample notifications
    echo "<h2>Adding Sample Notifications:</h2>\n";
    $notifications = [
        ['Order confirmed for your recent purchase.'],
        ['Your order has been shipped and is on its way.'],
        ['New products are now available in our store.']
    ];
    
    foreach ($notifications as $notification) {
        try {
            $stmt = $conn->prepare("INSERT INTO notifications (order_id, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
            $stmt->execute([1, $notification[0]]);
            echo "<p>✅ Added notification: {$notification[0]}</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Error adding notification {$notification[0]}: " . $e->getMessage() . "</p>\n";
        }
    }
    
    // Show final counts
    echo "<h2>Final Table Counts:</h2>\n";
    $tables = ['products', 'announcements', 'notifications', 'orders', 'transactions'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p><strong>$table</strong>: " . $result['count'] . " records</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'><strong>$table</strong>: ERROR - " . $e->getMessage() . "</p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
