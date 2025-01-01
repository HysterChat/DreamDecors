<?php
// Basic server configuration
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('Asia/Kolkata');

// Session configuration
session_start();

// Define constants
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Create upload directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Allowed file types
$ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];

// Email configuration
$EMAIL_CONFIG = [
    'from' => 'your-email@gmail.com',
    'reply_to' => 'your-email@gmail.com',
    'admin_email' => 'your-email@gmail.com'
];

// Function to validate file upload
function validateFile($file) {
    global $ALLOWED_EXTENSIONS;
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXTENSIONS)) {
        return false;
    }
    
    return true;
} 