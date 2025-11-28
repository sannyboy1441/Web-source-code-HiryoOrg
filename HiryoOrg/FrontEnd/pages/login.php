<?php
session_start();
include "datab_try.php";

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Prepare and execute query for admins table
        $stmt = $conn->prepare("SELECT admin_id, full_name, email, password_hash, role FROM admins WHERE email = ? OR full_name = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();

            // Check password (assuming it's hashed)
            if (password_verify($password, $admin['password_hash']) || $password === $admin['password_hash']) {
                // Store session data
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['username'] = $admin['full_name'];
                $_SESSION['email'] = $admin['email'];
                $_SESSION['role'] = $admin['role'];

                // Update last login
                $updateStmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
                $updateStmt->bind_param("i", $admin['admin_id']);
                $updateStmt->execute();

                // ✅ Redirect to index.php
                header("Location: index.php");
                exit; // 🚀 Always stop execution after redirect
            } else {
                $error = "Invalid username or password!";
            }
        } else {
            $error = "Admin not found!";
        }

        $stmt->close();
    } else {
        $error = "Please fill all fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hiryo Organization</title>
    <link rel="icon" type="image/png" href="../images/Hiryo.png">
    <link rel="stylesheet" href="../styles/vars.css">
    <link rel="stylesheet" href="../styles/components/body.css">
    <link rel="stylesheet" href="../styles/components/dashboard.css">
    <style>
        body {
            background: linear-gradient(135deg, #f1f8e9 0%, #e8f5e8 50%, #c8e6c9 100%);
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(46, 125, 50, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(76, 175, 80, 0.1) 0%, transparent 50%);
            min-height: 100vh;
            font-family: var(--font-family);
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(46, 125, 50, 0.15) 0%, transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(76, 175, 80, 0.15) 0%, transparent 30%),
                radial-gradient(circle at 50% 50%, rgba(139, 195, 74, 0.1) 0%, transparent 40%),
                linear-gradient(45deg, rgba(46, 125, 50, 0.05) 0%, transparent 50%),
                linear-gradient(-45deg, rgba(76, 175, 80, 0.05) 0%, transparent 50%);
            animation: backgroundShift 20s ease-in-out infinite;
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%234CAF50' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            animation: patternMove 30s linear infinite;
            z-index: 0;
        }
        
        @keyframes patternMove {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(60px) translateY(60px); }
        }
        
        @keyframes backgroundShift {
            0%, 100% {
                transform: translateX(0) translateY(0) scale(1);
                opacity: 1;
            }
            25% {
                transform: translateX(-20px) translateY(-10px) scale(1.05);
                opacity: 0.8;
            }
            50% {
                transform: translateX(20px) translateY(10px) scale(0.95);
                opacity: 0.9;
            }
            75% {
                transform: translateX(-10px) translateY(20px) scale(1.02);
                opacity: 0.85;
            }
        }
        
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        
        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }
        
        .leaf {
            position: absolute;
            width: 30px;
            height: 30px;
            background: rgba(76, 175, 80, 0.2);
            border-radius: 0 100% 0 100%;
            animation: leafFloat 12s infinite linear;
        }
        
        .leaf:nth-child(6) {
            top: 15%;
            left: 15%;
            animation-delay: -2s;
            animation-duration: 15s;
        }
        
        .leaf:nth-child(7) {
            top: 70%;
            left: 85%;
            animation-delay: -7s;
            animation-duration: 18s;
        }
        
        .leaf:nth-child(8) {
            top: 40%;
            left: 5%;
            animation-delay: -12s;
            animation-duration: 14s;
        }
        
        .leaf:nth-child(9) {
            top: 25%;
            left: 75%;
            animation-delay: -4s;
            animation-duration: 16s;
        }
        
        .leaf:nth-child(10) {
            top: 85%;
            left: 25%;
            animation-delay: -9s;
            animation-duration: 13s;
        }
        
        .leaf:nth-child(11) {
            top: 55%;
            left: 50%;
            animation-delay: -14s;
            animation-duration: 17s;
        }
        
        .leaf:nth-child(12) {
            top: 10%;
            left: 60%;
            animation-delay: -6s;
            animation-duration: 19s;
        }
        
        .leaf:nth-child(13) {
            top: 75%;
            left: 10%;
            animation-delay: -11s;
            animation-duration: 15s;
        }
        
        @keyframes leafFloat {
            0% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.6;
            }
            50% {
                transform: translateY(-30px) rotate(180deg);
                opacity: 1;
            }
            100% {
                transform: translateY(0px) rotate(360deg);
                opacity: 0.6;
            }
        }
        
        .shape:nth-child(1) {
            width: 80px;
            height: 80px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
            animation-duration: 20s;
        }
        
        .shape:nth-child(2) {
            width: 120px;
            height: 120px;
            top: 60%;
            left: 80%;
            animation-delay: -5s;
            animation-duration: 25s;
        }
        
        .shape:nth-child(3) {
            width: 60px;
            height: 60px;
            top: 80%;
            left: 20%;
            animation-delay: -10s;
            animation-duration: 18s;
        }
        
        .shape:nth-child(4) {
            width: 100px;
            height: 100px;
            top: 30%;
            left: 70%;
            animation-delay: -15s;
            animation-duration: 22s;
        }
        
        .shape:nth-child(5) {
            width: 40px;
            height: 40px;
            top: 10%;
            left: 50%;
            animation-delay: -8s;
            animation-duration: 16s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.7;
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
                opacity: 1;
            }
            100% {
                transform: translateY(0px) rotate(360deg);
                opacity: 0.7;
            }
        }
        
        .login-layout {
            display: flex;
            min-height: 100vh;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .logo-section {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-10);
        }
        
        .logo-section img {
            width: 500px;
            height: auto;
            transition: transform var(--transition-normal);
        }
        
        .logo-section img:hover {
            transform: scale(1.05);
        }
        
        .login-container {
            background: var(--bg-panel-glass);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: var(--space-10);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--border-light);
            position: relative;
            overflow: hidden;
            margin-right: var(--space-10);
            margin-left: var(--space-20);
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--brand-primary), var(--brand-secondary));
            border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: var(--space-8);
        }
        
        .login-header h1 {
            color: var(--text-primary);
            font-size: var(--font-size-3xl);
            font-weight: var(--font-weight-bold);
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .login-header p {
            color: var(--text-secondary);
            font-size: var(--font-size-base);
            margin: var(--space-2) 0 0 0;
            font-weight: var(--font-weight-normal);
        }
        
        .form-group {
            margin-bottom: var(--space-5);
        }
        
        .form-group label {
            display: block;
            margin-bottom: var(--space-2);
            color: var(--text-primary);
            font-weight: var(--font-weight-semibold);
            font-size: var(--font-size-sm);
        }
        
        .form-group input {
            width: 100%;
            padding: var(--space-4);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-lg);
            font-size: var(--font-size-base);
            transition: all var(--transition-fast);
            background: var(--bg-primary);
            color: var(--text-primary);
            height: var(--input-height-lg);
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--border-focus);
            background: var(--bg-primary);
            box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1);
            transform: translateY(-1px);
        }
        
        .login-btn {
            width: 100%;
            padding: var(--space-4);
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            font-size: var(--font-size-base);
            font-weight: var(--font-weight-semibold);
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-top: var(--space-3);
            height: 50px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left var(--transition-normal);
        }
        
        .login-btn:hover::before {
            left: 100%;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: rgba(244, 67, 54, 0.1);
            color: var(--color-error);
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-5);
            border-left: 4px solid var(--color-error);
            font-size: var(--font-size-sm);
            font-weight: var(--font-weight-medium);
        }
        
        @media (max-width: 768px) {
            .login-layout {
                flex-direction: column;
            }
            
            .logo-section {
                padding: var(--space-5);
            }
            
            .logo-section img {
                width: 200px;
                height: auto;
            }
            
            .login-container {
                margin: var(--space-5);
                padding: var(--space-8) var(--space-5);
                margin-right: var(--space-5);
            }
            
            .login-header h1 {
                font-size: var(--font-size-2xl);
            }
        }
    </style>
</head>
<body>
    <div class="login-layout">
        <div class="logo-section">
            <img src="../images/Hiryo.png" alt="Hiryo Organization Logo">
        </div>
        
    <div class="login-container">
            <div class="login-header">
                <h1>Admin Login</h1>
                <p>Sign in to your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Email</label>
                    <input type="email" id="username" name="username" required 
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password">
                </div>
                
                <button type="submit" class="login-btn">
                    Sign In
                </button>
            </form>
        </div>
    </div>
    
    <!-- Floating Background Shapes -->
    <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
        <div class="leaf"></div>
    </div>
</body>
</html>