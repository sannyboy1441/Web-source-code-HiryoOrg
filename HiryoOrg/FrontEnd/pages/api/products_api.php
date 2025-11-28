<?php
/**
 * UNIFIED Products API - Serves BOTH Web Admin and Mobile App
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
require_once '../datab_try.php'; // Make sure this path is correct

$response = ['success' => false, 'message' => 'Invalid action.'];
$conn = getDBConnection();

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// Use $_REQUEST to handle both GET and POST
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    // --- FOR MOBILE APP ---
    case 'get_all_products':
        handleGetAllProducts($conn);
        break;

    // ADDED: For fetching a single product's details in the app
    case 'get_product_details':
        handleGetProductDetails($conn);
        break;

    case 'get_products_by_category':
        handleGetProductsByCategory($conn);
        break;

    case 'search_products':
        handleSearchProducts($conn);
        break;

    // --- FOR WEB ADMIN PANEL ---
    case 'get_products_for_admin':
        handleGetProductsForAdmin($conn);
        break;

    case 'add_product':
        handleAddProduct($conn);
        break;

    case 'update_product':
        handleUpdateProduct($conn);
        break;

    // This will now be a "soft delete"
    case 'delete_product':
        handleDeleteProduct($conn);
        break;

    default:
        http_response_code(400);
        echo json_encode($response);
        break;
}

// ----------------------------------------------------
// MOBILE APP FUNCTIONS
// ----------------------------------------------------

function handleGetAllProducts($conn) {
    try {
        // UPDATED: Added more columns needed by the mobile app
        $stmt = $conn->prepare("
            SELECT product_id, product_name, description, category, price, image_url, stock_quantity, product_sku, weight_value, weight_unit, status
            FROM products 
            WHERE status = 'Active' 
            ORDER BY product_name ASC
        ");
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert relative image URLs to full URLs for mobile app
        foreach ($products as &$product) {
            if (!empty($product['image_url']) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                // If it's a relative path, convert to full URL
                $product['image_url'] = getBaseUrl() . '/' . $product['image_url'];
            }
        }
        
        echo json_encode(['success' => true, 'products' => $products, 'count' => count($products), 'total_count' => count($products), 'message' => 'Products loaded successfully']);
    } catch (PDOException $e) {
        error_log("Mobile API Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch products: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("Mobile API General Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

// ADDED: New function from the old mobile API file
function handleGetProductDetails($conn) {
    $productId = intval($_REQUEST['product_id'] ?? 0);
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or missing product_id.']);
        return;
    }
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND status = 'Active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Convert relative image URL to full URL for mobile app
            if (!empty($product['image_url']) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                $product['image_url'] = getBaseUrl() . '/' . $product['image_url'];
            }
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found or is inactive.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}


function handleGetProductsByCategory($conn) {
    $category = $_REQUEST['category'] ?? '';
    try {
        if (empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Category parameter is required.']);
            return;
        }
        $stmt = $conn->prepare("
            SELECT product_id, product_name, description, category, price, image_url, stock_quantity, product_sku, weight_value, weight_unit, status
            FROM products 
            WHERE status = 'Active' AND category = ?
            ORDER BY product_name ASC
        ");
        $stmt->execute([$category]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert relative image URLs to full URLs for mobile app
        foreach ($products as &$product) {
            if (!empty($product['image_url']) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                $product['image_url'] = getBaseUrl() . '/' . $product['image_url'];
            }
        }
        
        echo json_encode([
            'success' => true, 
            'products' => $products
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to fetch products by category.']);
    }
}

function handleSearchProducts($conn) {
    // This function remains the same as it's well-written
    try {
        $searchQuery = $_REQUEST['search_query'] ?? '';
        if (empty($searchQuery)) {
            echo json_encode(['success' => false, 'message' => 'Search query is required.']);
            return;
        }
        $stmt = $conn->prepare("
            SELECT product_id, product_name, description, category, price, image_url, stock_quantity, product_sku, weight_value, weight_unit, status
            FROM products 
            WHERE status = 'Active' 
            AND (product_name LIKE ? OR description LIKE ? OR category LIKE ?)
            ORDER BY product_name ASC
        ");
        $searchTerm = '%' . $searchQuery . '%';
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert relative image URLs to full URLs for mobile app
        foreach ($products as &$product) {
            if (!empty($product['image_url']) && !filter_var($product['image_url'], FILTER_VALIDATE_URL)) {
                $product['image_url'] = getBaseUrl() . '/' . $product['image_url'];
            }
        }
        
        echo json_encode([
            'success' => true, 
            'products' => $products
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to search products.']);
    }
}


// ----------------------------------------------------
// WEB ADMIN FUNCTIONS
// ----------------------------------------------------

function handleGetProductsForAdmin($conn) {
    try {
        // Try to get products with created_at first, fallback to product_id if created_at doesn't exist
        $query = "SELECT * FROM products";
        
        // Check if created_at column exists by trying the query
        try {
            $testStmt = $conn->prepare("SELECT created_at FROM products LIMIT 1");
            $testStmt->execute();
            $query .= " ORDER BY created_at DESC";
        } catch (PDOException $e) {
            // created_at column doesn't exist, use product_id instead
            $query .= " ORDER BY product_id DESC";
        }
        
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'products' => $products]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function handleAddProduct($conn) {
    try {
        // UPDATED: Added new fields from mobile API
        $productName = trim($_POST['product_name'] ?? '');
        $productSku = trim($_POST['product_sku'] ?? '') ?: null; // SKU can be optional, convert empty string to null
        $category = trim($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $weightValue = floatval($_POST['weight_value'] ?? null);
        $weightUnit = trim($_POST['weight_unit'] ?? null);
        
        if (empty($productName) || empty($category) || $price <= 0) {
            echo json_encode(['success' => false, 'message' => 'Product name, category, and price are required.']);
            return;
        }
        
        $imageUrl = null;
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $imageUrl = handleImageUpload($_FILES['product_image']);
            if (!$imageUrl) {
                echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
                return;
            }
        }
        
        // Ensure status is always set to a valid value
        $status = 'Active'; // Default to Active for new products
        if ($stockQuantity <= 0) {
            $status = 'Out of Stock';
        }
        
        // UPDATED: Added new columns to the INSERT statement
        $stmt = $conn->prepare("
            INSERT INTO products (product_name, product_sku, category, price, stock_quantity, description, image_url, status, weight_value, weight_unit, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            $productName, $productSku, $category, $price, $stockQuantity, $description, $imageUrl, $status, $weightValue, $weightUnit
        ]);
        
        if ($result) {
            $productId = $conn->lastInsertId();
            
            // Fetch the saved product data for direct table update
            $fetchStmt = $conn->prepare("
                SELECT product_id, product_name, product_sku, category, price, stock_quantity, 
                       description, image_url, status, weight_value, weight_unit, created_at, updated_at
                FROM products WHERE product_id = ?
            ");
            $fetchStmt->execute([$productId]);
            $savedProduct = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product added successfully!',
                'product' => $savedProduct
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product.']);
        }
    } catch (PDOException $e) {
        // Check for duplicate entry error
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Database error: Product name or SKU already exists.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

function handleUpdateProduct($conn) {
    try {
        $productId = intval($_POST['product_id'] ?? 0);
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID.']);
            return;
        }

        // Dynamically build the update query
        $updateFields = [];
        $params = [];
        
        // List of all possible fields to update
        $allowedFields = ['product_name', 'product_sku', 'category', 'price', 'stock_quantity', 'description', 'weight_value', 'weight_unit'];
        
        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $updateFields[] = "$field = ?";
                $params[] = trim($_POST[$field]);
            }
        }

        if (empty($updateFields) && !isset($_FILES['product_image'])) {
            echo json_encode(['success' => false, 'message' => 'No data provided to update.']);
            return;
        }

        // Handle image upload separately
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $imageUrl = handleImageUpload($_FILES['product_image']);
            if ($imageUrl) {
                $updateFields[] = 'image_url = ?';
                $params[] = $imageUrl;
            }
        }
        
        // Update status based on stock if stock is being updated
        if (isset($_POST['stock_quantity'])) {
            $stockQuantity = intval($_POST['stock_quantity']);
            $status = $stockQuantity > 0 ? 'Active' : 'Out of Stock';
            $updateFields[] = 'status = ?';
            $params[] = $status;
        }

        $updateFields[] = 'updated_at = NOW()';
        $params[] = $productId; // Add product ID for the WHERE clause
        
        $sql = "UPDATE products SET " . implode(', ', $updateFields) . " WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            // Fetch the updated product data for direct table update
            $fetchStmt = $conn->prepare("
                SELECT product_id, product_name, product_sku, category, price, stock_quantity, 
                       description, image_url, status, weight_value, weight_unit, created_at, updated_at
                FROM products WHERE product_id = ?
            ");
            $fetchStmt->execute([$productId]);
            $updatedProduct = $fetchStmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Product updated successfully!',
                'product' => $updatedProduct
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update product or no changes were made.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

// CHANGED: Implemented "Hard Delete" - actually removes the product
function handleDeleteProduct($conn) {
    try {
        // Get product ID from POST data
        $productId = intval($_POST['product_id'] ?? 0);
        
        // Debug logging
        error_log("DELETE DEBUG: Product ID received: " . $productId);
        error_log("DELETE DEBUG: POST data: " . print_r($_POST, true));
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product ID: ' . $productId]);
            return;
        }
        
        // First check if product exists
        $checkStmt = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
        $checkStmt->execute([$productId]);
        $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("DELETE DEBUG: Product found: " . ($product ? 'YES' : 'NO'));
        if ($product) {
            error_log("DELETE DEBUG: Product name: " . $product['product_name']);
        }
        
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product with ID ' . $productId . ' not found.']);
            return;
        }
        
        // Check for foreign key constraints (orders that reference this product)
        $orderCheckStmt = $conn->prepare("SELECT COUNT(*) as count FROM order_items WHERE product_id = ?");
        $orderCheckStmt->execute([$productId]);
        $orderCount = $orderCheckStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        error_log("DELETE DEBUG: Orders referencing this product: " . $orderCount);
        
        if ($orderCount > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete product "' . $product['product_name'] . '" because it has ' . $orderCount . ' orders referencing it. Products with orders cannot be deleted for data integrity.']);
            return;
        }
        
        // Hard delete - actually remove the product from database
        $deleteStmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $result = $deleteStmt->execute([$productId]);
        $rowsAffected = $deleteStmt->rowCount();
        
        error_log("DELETE DEBUG: Delete result: " . ($result ? 'SUCCESS' : 'FAILED'));
        error_log("DELETE DEBUG: Rows affected: " . $rowsAffected);
        
        if ($result && $rowsAffected > 0) {
            echo json_encode(['success' => true, 'message' => 'Product "' . $product['product_name'] . '" has been permanently deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete product. Result: ' . ($result ? 'true' : 'false') . ', Rows affected: ' . $rowsAffected]);
        }
    } catch (PDOException $e) {
        error_log("DELETE DEBUG: PDO Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("DELETE DEBUG: General Exception: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}


// This function is great, no changes needed
function handleImageUpload($file) {
    // Use absolute path from the project root
    $uploadDir = __DIR__ . '/../../../public_images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        error_log("Invalid file type: " . $file['type']);
        return false;
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        error_log("File too large: " . $file['size']);
        return false;
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    error_log("Attempting to upload to: " . $filepath);
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return the web-accessible path
        $webPath = 'public_images/' . $filename;
        error_log("Upload successful: " . $webPath);
        return $webPath;
    }
    
    error_log("Upload failed");
    return false;
}

/**
 * Get the base URL for the application
 * This ensures mobile app gets full URLs for images
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // For mobile app compatibility, use the specific IP address instead of localhost
    if ($host === 'localhost' || $host === '127.0.0.1') {
        $host = '192.168.1.46';
    }
    
    // Return the base URL pointing to the project root where public_images is located
    return $protocol . '://' . $host . '/HiryoOrg';
}

?>