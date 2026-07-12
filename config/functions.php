<?php
require_once __DIR__ . '/session.php';

// Clean/sanitize input
function clean($data) {
    return htmlspecialchars(trim(strip_tags($data)));
}

// Redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Generate OTP - KEEP ONLY HERE (REMOVED FROM auth.php)
function generateOTP() {
    return rand(100000, 999999);
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Display flash message
function flashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'success';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        
        $class = $type === 'error' ? 'alert-error' : 'alert-success';
        return "<div class='alert {$class}'>{$message}</div>";
    }
    return '';
}

// Set flash message
function setFlash($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user name
function getCurrentUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
?>