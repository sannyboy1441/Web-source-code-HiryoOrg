<?php
/**
 * User Admin API - Legacy endpoint redirect
 * This file handles any cached requests to the old API name
 */

header('Content-Type: application/json');

// Log the request for debugging
error_log("Legacy API called: user_admin_api.php");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Action: " . ($_GET['action'] ?? $_POST['action'] ?? 'none'));

// Redirect to the main admin API
@include_once 'admin_api.php';
?>
