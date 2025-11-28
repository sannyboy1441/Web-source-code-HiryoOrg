<?php
/**
 * Dashboard API - Provides dashboard statistics and analytics
 * This API consolidates data from various sources for the admin dashboard
 */

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include database connection
@include_once '../datab_try.php';

// Get PDO connection for API functions
$conn = getDBConnection();

if (!$conn) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'data' => []
    ]);
    exit();
}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? 'get_dashboard_stats';
    
    switch ($action) {
        case 'get_dashboard_stats':
        case 'get_stats':
            handleGetDashboardStats();
            break;
            
        case 'get_sales_data':
            handleGetSalesData();
            break;
            
        case 'get_recent_activity':
            handleGetRecentActivity();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action specified',
                'data' => []
            ]);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'data' => []
    ]);
}

function handleGetDashboardStats() {
    global $conn;
    
    try {
        // Get total counts using PDO (not mysqli)
        $stats = [];
        
        // Total Users
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE roles = 'customer' AND status = 'Active'");
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Total Orders (only count active orders, not completed or cancelled)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE status NOT IN ('Completed', 'Cancelled')");
        $stmt->execute();
        $stats['total_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Total Products
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE status = 'Active'");
        $stmt->execute();
        $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Active Products (same as total for now)
        $stats['active_products'] = $stats['total_products'];
        
        // FIXED: Total Revenue - only count completed transactions, not all products
        $stmt = $conn->prepare("SELECT SUM(amount) as total FROM transactions");
        $stmt->execute();
        $stats['total_revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
        
        // Pending Orders (case insensitive)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE LOWER(status) = 'pending'");
        $stmt->execute();
        $stats['pending_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        // Recent Orders (last 7 days) - only active orders
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND status NOT IN ('Completed', 'Cancelled')");
        $stmt->execute();
        $stats['recent_orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        echo json_encode([
            'success' => true,
            'message' => 'Dashboard stats retrieved successfully',
            'data' => $stats
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving dashboard stats: ' . $e->getMessage(),
            'data' => []
        ]);
    }
}

function handleGetSalesData() {
    global $conn;
    
    try {
        $period = $_GET['period'] ?? 'thismonth';
        
        // Get sales data for the specified period
        $dateCondition = '';
        $groupBy = 'DATE(created_at)';
        $dateFormat = '%Y-%m-%d';
        
        // Handle different period options
        switch ($period) {
            case '7days':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            case 'thismonth':
                // This month from the 1st to today
                $dateCondition = "AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())";
                break;
            case 'thisquarter':
                // This quarter (Q1: Jan-Mar, Q2: Apr-Jun, Q3: Jul-Sep, Q4: Oct-Dec)
                $dateCondition = "AND YEAR(created_at) = YEAR(NOW()) AND QUARTER(created_at) = QUARTER(NOW())";
                break;
            case 'ytd':
                // Year to date (from January 1st of current year)
                $dateCondition = "AND YEAR(created_at) = YEAR(NOW())";
                $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
                $dateFormat = '%Y-%m-01'; // Group by month for YTD
                break;
            case 'custom':
                // Custom range - will need to handle date pickers in the future
                // For now, default to last 30 days
                $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            default:
                // Legacy numeric month support
                if (is_numeric($period)) {
                    $months = intval($period);
                    $dateCondition = "AND created_at >= DATE_SUB(NOW(), INTERVAL $months MONTH)";
                    
                    // For longer periods, group by month instead of day
                    if ($months >= 6) {
                        $groupBy = "DATE_FORMAT(created_at, '%Y-%m')";
                        $dateFormat = '%Y-%m-01';
                    }
                }
                break;
        }
        
        $query = "SELECT 
                    DATE_FORMAT(created_at, '$dateFormat') as date,
                    COUNT(*) as order_count,
                    SUM(amount) as daily_revenue
                  FROM transactions 
                  WHERE 1=1 $dateCondition
                  GROUP BY $groupBy
                  ORDER BY date ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $salesData = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $salesData[] = [
                'date' => $row['date'],
                'orders_count' => (int)$row['order_count'],
                'daily_revenue' => (float)$row['daily_revenue']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Sales data retrieved successfully',
            'data' => $salesData
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving sales data: ' . $e->getMessage(),
            'data' => []
        ]);
    }
}

function handleGetRecentActivity() {
    global $conn;
    
    try {
        $limit = $_GET['limit'] ?? 10;
        
        // Get recent orders
        $query = "SELECT 
                    o.order_id,
                    o.total_amount,
                    o.status,
                    o.order_date as created_at,
                    CONCAT(u.firstName, ' ', u.lastName) as customer_name
                  FROM orders o
                  JOIN users u ON o.user_id = u.user_id
                  ORDER BY o.order_date DESC
                  LIMIT ?";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([$limit]);
        $recentActivity = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recentActivity[] = [
                'type' => 'order',
                'id' => $row['order_id'],
                'customer' => $row['customer_name'],
                'amount' => (float)$row['total_amount'],
                'status' => $row['status'],
                'date' => $row['created_at']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Recent activity retrieved successfully',
            'data' => $recentActivity
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving recent activity: ' . $e->getMessage(),
            'data' => []
        ]);
    }
}
?>
