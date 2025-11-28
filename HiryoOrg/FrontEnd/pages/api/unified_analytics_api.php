<?php
/**
 * Unified Analytics API
 * Provides all data for the admin dashboard, from high-level KPIs to detailed charts.
 * This single file replaces dashboard_summary.php and sales_analytics.php.
 *
 * --- HOW TO USE ---
 * For quick summary KPIs: /api/analytics_api.php?report=summary
 * For detailed chart data: /api/analytics_api.php?report=detailed&period=30days
 */

header('Content-Type: application/json');
require_once '../datab_try.php'; // Use the standard secure connection

// --- Main API Router ---
try {
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Determine the report type: 'summary' for KPIs, 'detailed' for charts
    $reportType = $_GET['report'] ?? 'summary';

    switch ($reportType) {
        case 'summary':
            handleSummaryReport($conn);
            break;
        case 'detailed':
            handleDetailedReport($conn);
            break;
        default:
            throw new Exception('Invalid report type specified.');
    }

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    error_log("Analytics API Error: " . $e->getMessage()); // Log the actual error
    echo json_encode(['success' => false, 'message' => 'An error occurred on the server.']);
}


/**
 * Handles the FAST summary report for dashboard KPIs.
 * Uses a single, highly efficient query to get all key numbers at once.
 */
function handleSummaryReport($conn) {
    // This single query is much faster than running 5 separate queries.
    $query = "
        SELECT
          (SELECT COUNT(*) FROM users) as total_users,
          (SELECT COUNT(*) FROM products) as total_products,
          (SELECT COUNT(*) FROM products WHERE status = 'Active' AND stock_quantity > 0) as active_products,
          (SELECT COUNT(*) FROM orders) as total_orders,
          (SELECT SUM(total_amount) FROM orders WHERE status IN ('Delivered', 'Completed')) as total_revenue
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Prepare a clean response
    $data = [
        'total_users'       => (int)($summary['total_users'] ?? 0),
        'total_products'    => (int)($summary['total_products'] ?? 0),
        'active_products'   => (int)($summary['active_products'] ?? 0),
        'total_orders'      => (int)($summary['total_orders'] ?? 0),
        'total_revenue'     => number_format((float)($summary['total_revenue'] ?? 0), 2)
    ];

    echo json_encode(['success' => true, 'data' => $data]);
}


/**
 * Handles the DETAILED report for analytics charts and tables.
 */
function handleDetailedReport($conn) {
    $period = $_GET['period'] ?? '7days';
    $days = 7; // Default
    switch ($period) {
        case 'today': $days = 1; break;
        case '30days': $days = 30; break;
        case '90days': $days = 90; break;
        case '6': $days = 180; break; // 6 months
        case '12': $days = 365; break; // 12 months
        case '24': $days = 730; break; // 24 months
    }

    // Fetch all data components for the detailed report
    $salesChart = getSalesChartData($conn, $days);
    $statusBreakdown = getStatusBreakdown($conn, $days);
    $topProducts = getTopProducts($conn, $days);
    $hourlyDistribution = getHourlyDistribution($conn); // Always for today

    // Calculate total sales from the chart data to avoid another query
    $totalSales = [
        'total_revenue' => array_sum(array_column($salesChart, 'daily_revenue')),
        'total_orders' => array_sum(array_column($salesChart, 'orders_count')),
        'avg_order_value' => 0
    ];
    if ($totalSales['total_orders'] > 0) {
        $totalSales['avg_order_value'] = $totalSales['total_revenue'] / $totalSales['total_orders'];
    }

    // Prepare the final combined response
    $response = [
        'success' => true,
        'period' => $period,
        'days' => $days,
        'data' => [
            'sales_chart'          => $salesChart,
            'total_sales'          => $totalSales, // This is the period-specific total
            'status_breakdown'     => $statusBreakdown,
            'top_products'         => $topProducts,
            'hourly_distribution'  => $hourlyDistribution
        ]
    ];
    echo json_encode($response);
}


// --- Helper Functions for Detailed Report ---

function getSalesChartData($conn, $days) {
    try {
        // For periods > 90 days, group by month; otherwise by day
        if ($days > 90) {
            $query = "
                SELECT DATE_FORMAT(order_date, '%Y-%m-01') as date, 
                       COUNT(*) as orders_count, 
                       SUM(total_amount) as daily_revenue
                FROM orders 
                WHERE order_date >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                  AND status IN ('Delivered', 'Completed')
                GROUP BY DATE_FORMAT(order_date, '%Y-%m')
                ORDER BY date ASC
            ";
        } else {
            $query = "
                SELECT DATE(order_date) as date, 
                       COUNT(*) as orders_count, 
                       SUM(total_amount) as daily_revenue
                FROM orders 
                WHERE order_date >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                  AND status IN ('Delivered', 'Completed')
                GROUP BY DATE(order_date) 
                ORDER BY date ASC
            ";
        }
        $stmt = $conn->prepare($query);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; } // Return empty on error
}

function getStatusBreakdown($conn, $days) {
    try {
        $query = "
            SELECT status, COUNT(*) as count, SUM(total_amount) as revenue
            FROM orders 
            WHERE order_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY status ORDER BY count DESC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

function getTopProducts($conn, $days) {
    try {
        $query = "
            SELECT p.product_name, p.category, SUM(oi.quantity) as order_count, SUM(oi.quantity * oi.price_at_purchase) as estimated_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            JOIN orders o ON oi.order_id = o.order_id
            WHERE o.order_date >= DATE_SUB(NOW(), INTERVAL ? DAY) AND o.status IN ('Delivered', 'Completed')
            GROUP BY p.product_id, p.product_name, p.category ORDER BY estimated_revenue DESC LIMIT 10
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

function getHourlyDistribution($conn) {
    try {
        $query = "
            SELECT HOUR(order_date) as hour, COUNT(*) as orders_count
            FROM orders 
            WHERE DATE(order_date) = CURDATE() AND status IN ('Delivered', 'Completed')
            GROUP BY HOUR(order_date) ORDER BY hour ASC
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { return []; }
}

?>
