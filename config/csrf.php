<?php
require_once __DIR__ . '/session.php';

// Generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Get CSRF token
function csrf() {
    return generateCSRFToken();
}

// Generate CSRF field for forms
function csrf_field() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// Verify CSRF token
function verify_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Validate CSRF for POST requests
function validate_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !verify_csrf($_POST['csrf_token'])) {
            http_response_code(403);
            die('CSRF validation failed. Please refresh the page and try again.');
        }
    }
}

// Initialize CSRF
function init_csrf() {
    generateCSRFToken();
}
?>