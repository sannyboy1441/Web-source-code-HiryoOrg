<?php
/**
 * Forgot Password API
 * Handles password reset requests and token generation
 */

// Start output buffering to prevent any unwanted output
ob_start();

// Suppress any warnings or notices that might interfere with JSON output
error_reporting(E_ERROR | E_PARSE);

header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Clear any output that might have been generated
ob_clean();

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../datab_try.php';

try {
    $conn = getDBConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'request_reset':
            handleRequestReset($conn);
            break;
            
        case 'verify_token':
            handleVerifyToken($conn);
            break;
            
        case 'reset_password':
            handleResetPassword($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified']);
            break;
    }

} catch (Exception $e) {
    // Clean any output and return proper error JSON
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
    error_log("Forgot Password API Error: " . $e->getMessage());
}

// End output buffering and flush
ob_end_flush();

/**
 * Handle password reset request
 */
function handleRequestReset($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email is required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        return;
    }

    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id, firstName, lastName, email FROM users WHERE email = ? AND status = 'Active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Don't reveal if email exists or not for security
            echo json_encode([
                'success' => true, 
                'message' => 'If an account with that email exists, a password reset link has been sent.'
            ]);
            return;
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

        // Store token in database
        $stmt = $conn->prepare("
            INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            token = VALUES(token), 
            expires_at = VALUES(expires_at), 
            created_at = NOW()
        ");
        $stmt->execute([$user['user_id'], $token, $expires]);

        // Send email
        $resetLink = "https://hiryoorganics.swuitapp.com/reset-password?token=" . $token;
        $emailSent = sendPasswordResetEmail($user['email'], $user['firstName'], $resetLink);

        // For local development, we'll simulate success even if email fails
        // In production, you should configure proper SMTP settings
        if ($emailSent) {
            echo json_encode([
                'success' => true, 
                'message' => 'Password reset link has been sent to your email address.'
            ]);
        } else {
            // For development: show the reset link in the response
            echo json_encode([
                'success' => true, 
                'message' => 'Password reset link generated. (Development Mode)',
                'reset_link' => $resetLink,
                'token' => $token,
                'note' => 'Email server not configured. Use the reset link above.'
            ]);
        }

    } catch (PDOException $e) {
        error_log("Database error in handleRequestReset: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Verify reset token
 */
function handleVerifyToken($conn) {
    $token = trim($_GET['token'] ?? '');
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Token is required']);
        return;
    }

    try {
        $stmt = $conn->prepare("
            SELECT prt.user_id, prt.expires_at, u.email, u.firstName 
            FROM password_reset_tokens prt 
            JOIN users u ON prt.user_id = u.user_id 
            WHERE prt.token = ? AND prt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
            return;
        }

        echo json_encode([
            'success' => true, 
            'message' => 'Token is valid',
            'user' => [
                'email' => $tokenData['email'],
                'firstName' => $tokenData['firstName']
            ]
        ]);

    } catch (PDOException $e) {
        error_log("Database error in handleVerifyToken: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Reset password with token
 */
function handleResetPassword($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }

    $token = trim($_POST['token'] ?? '');
    $newPassword = trim($_POST['password'] ?? '');
    
    if (empty($token) || empty($newPassword)) {
        echo json_encode(['success' => false, 'message' => 'Token and password are required']);
        return;
    }

    if (strlen($newPassword) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        return;
    }

    try {
        // Verify token and get user
        $stmt = $conn->prepare("
            SELECT prt.user_id 
            FROM password_reset_tokens prt 
            WHERE prt.token = ? AND prt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
            return;
        }

        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update user password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashedPassword, $tokenData['user_id']]);

        // Delete used token
        $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
        $stmt->execute([$token]);

        echo json_encode([
            'success' => true, 
            'message' => 'Password has been reset successfully. You can now log in with your new password.'
        ]);

    } catch (PDOException $e) {
        error_log("Database error in handleResetPassword: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error occurred']);
    }
}

/**
 * Send password reset email using Gmail SMTP
 */
function sendPasswordResetEmail($email, $firstName, $resetLink) {
    // Gmail SMTP Configuration
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    $smtp_username = 'your-email@gmail.com'; // Replace with your Gmail
    $smtp_password = 'your-app-password'; // Replace with your Gmail App Password
    
    $subject = "Password Reset Request - Hiryo Organics";
    
    $message = "
    <html>
    <head>
        <title>Password Reset Request</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f9f9f9; }
            .button { 
                display: inline-block; 
                padding: 12px 24px; 
                background-color: #4CAF50; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 20px 0;
            }
            .footer { padding: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Hiryo Organics</h2>
            </div>
            <div class='content'>
                <h3>Password Reset Request</h3>
                <p>Hello " . htmlspecialchars($firstName) . ",</p>
                <p>We received a request to reset your password for your Hiryo Organics account.</p>
                <p>Click the button below to reset your password:</p>
                <p><a href='" . $resetLink . "' class='button'>Reset Password</a></p>
                <p>If the button doesn't work, copy and paste this link into your browser:</p>
                <p style='word-break: break-all; color: #666;'>" . $resetLink . "</p>
                <p><strong>This link will expire in 1 hour for security reasons.</strong></p>
                <p>If you didn't request this password reset, please ignore this email.</p>
            </div>
            <div class='footer'>
                <p>This email was sent from Hiryo Organics. Please do not reply to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // Try to use PHPMailer if available, otherwise fall back to basic mail()
    if (function_exists('mail') && $smtp_username !== 'your-email@gmail.com') {
        // Configure PHP mail settings for Gmail SMTP
        ini_set('SMTP', $smtp_host);
        ini_set('smtp_port', $smtp_port);
        ini_set('sendmail_from', $smtp_username);
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Hiryo Organics <' . $smtp_username . '>',
            'Reply-To: ' . $smtp_username,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return @mail($email, $subject, $message, implode("\r\n", $headers));
    } else {
        // Development mode - return false to trigger development response
        return false;
    }
}
?>
