<?php
/**
 * Admin Session Security Check
 * This file starts the session and ensures that only a logged-in admin can access the page.
 * If no admin is logged in, it redirects to the login page.
 */

// Start the session if it's not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the admin_id session variable is NOT set
if (!isset($_SESSION['admin_id'])) {
    // If not logged in, stop everything and redirect to the login page.
    // This path goes up one level from /php/ and then down into /pages/
    header("Location: ../pages/login.php"); 
    exit(); // IMPORTANT: Always call exit() after a header redirect.
}
?>