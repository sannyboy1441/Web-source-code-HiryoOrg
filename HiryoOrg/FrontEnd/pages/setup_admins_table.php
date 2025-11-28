<?php
/**
 * Setup script to create admins table if it doesn't exist
 * and add a default admin account
 */

require_once 'datab_try.php';

$conn = getDBConnection();

if (!$conn) {
    die("Database connection failed");
}

try {
    // Check if admins table exists
    $check_table = $conn->prepare("SHOW TABLES LIKE 'admins'");
    $check_table->execute();
    
    if ($check_table->rowCount() == 0) {
        echo "Creating admins table...\n";
        
        // Create admins table
        $create_table = "
        CREATE TABLE admins (
            admin_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('Administrator', 'Moderator', 'Editor') DEFAULT 'Administrator',
            status ENUM('Active', 'Suspended') DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )";
        
        $conn->exec($create_table);
        echo "✅ Admins table created successfully!\n";
        
        // Add default admin account
        $default_admin = [
            'full_name' => 'System Administrator',
            'email' => 'admin@hiryo.org',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'Administrator',
            'status' => 'Active'
        ];
        
        $stmt = $conn->prepare("
            INSERT INTO admins (full_name, email, password_hash, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $default_admin['full_name'],
            $default_admin['email'],
            $default_admin['password_hash'],
            $default_admin['role'],
            $default_admin['status']
        ]);
        
        echo "✅ Default admin account created!\n";
        echo "📧 Email: admin@hiryo.org\n";
        echo "🔑 Password: admin123\n";
        echo "⚠️  Please change the password after first login!\n";
        
    } else {
        echo "✅ Admins table already exists!\n";
        
        // Check if there are any admin accounts
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM admins WHERE status = 'Active'");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            echo "⚠️  No active admin accounts found!\n";
            
            // Add default admin account
            $default_admin = [
                'full_name' => 'System Administrator',
                'email' => 'admin@hiryo.org',
                'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
                'role' => 'Administrator',
                'status' => 'Active'
            ];
            
            $stmt = $conn->prepare("
                INSERT INTO admins (full_name, email, password_hash, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $default_admin['full_name'],
                $default_admin['email'],
                $default_admin['password_hash'],
                $default_admin['role'],
                $default_admin['status']
            ]);
            
            echo "✅ Default admin account created!\n";
            echo "📧 Email: admin@hiryo.org\n";
            echo "🔑 Password: admin123\n";
        } else {
            echo "✅ Found {$result['count']} active admin account(s)\n";
        }
    }
    
    // Show existing admin accounts
    $stmt = $conn->prepare("SELECT admin_id, full_name, email, role, status, created_at FROM admins ORDER BY created_at DESC");
    $stmt->execute();
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($admins)) {
        echo "\n📋 Current admin accounts:\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($admins as $admin) {
            echo "ID: {$admin['admin_id']} | Name: {$admin['full_name']} | Email: {$admin['email']} | Role: {$admin['role']} | Status: {$admin['status']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Setup complete!\n";
?>
