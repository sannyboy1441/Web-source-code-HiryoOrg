<?php
// Database configuration for Hiryo Organization - Hostinger Production
$host = "localhost"; // Use localhost for Hostinger shared hosting
$username = "u987478351_hiryoorganics";
$password = "hiryoOrganiccs456564";
$database = "u987478351_hiryoorg";

// Connect to database using mysqli (for compatibility with existing admin panel)
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8 for proper character encoding
mysqli_set_charset($conn, "utf8");

// PDO connection for API compatibility (matching mobile app configuration)
function getDBConnection() {
    global $host, $username, $password, $database;
    
    try {
        $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        return null;
    }
}
?>
