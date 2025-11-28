<?php
/**
 * User Logout Handler
 * 
 * This file handles user logout functionality for the Hiryo Organization system.
 * It properly destroys the user session and redirects to the login page.
 * 
 * @author HiryoOrg Development Team
 * @version 1.0
 */

// Start session to access session variables
session_start();

// Log the logout activity (optional)
if (isset($_SESSION['username'])) {
    error_log("User logout: " . $_SESSION['username'] . " at " . date('Y-m-d H:i:s'));
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Clear session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Redirect to login page
header("Location: login.php");
exit();
?>
