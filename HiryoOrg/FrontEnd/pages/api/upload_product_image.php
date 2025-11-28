<?php
/**
 * Product Image Upload API - Base64 Version
 * Stores images as base64 data instead of files
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['image'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$fileType = mime_content_type($file['tmp_name']);

if ($fileType === false) {
    echo json_encode(['success' => false, 'message' => 'Unable to determine file type']);
    exit;
}

if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP are allowed.']);
    exit;
}

// Validate file size (max 2MB for base64)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds limit of 2MB for base64 encoding.']);
    exit;
}

// Convert image to base64
$imageData = file_get_contents($file['tmp_name']);
if ($imageData === false) {
    echo json_encode(['success' => false, 'message' => 'Failed to read uploaded file']);
    exit;
}

$base64Image = base64_encode($imageData);
$dataUrl = 'data:' . $fileType . ';base64,' . $base64Image;

// Generate a simple filename for reference
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'product_' . uniqid() . '_' . time() . '.' . $extension;

echo json_encode([
    'success' => true,
    'message' => 'Image uploaded successfully',
    'data' => [
        'filename' => $filename,
        'url' => $dataUrl,
        'size' => $file['size'],
        'type' => $fileType,
        'base64' => $base64Image
    ]
]);
?>