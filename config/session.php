<?php
// Session management - COMPLETELY FIXED

// Security settings - MUST be set BEFORE session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.gc_maxlifetime', 1800);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ONLY regenerate session ID if session is active AND user is logged in
function regenerateSessionIfNeeded() {
    // Check if session is active
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Only regenerate if user is logged in or we're in a secure part of the app
        if (isset($_SESSION['user_id']) || isset($_SESSION['reset_email'])) {
            if (!isset($_SESSION['session_created'])) {
                session_regenerate_id(true);
                $_SESSION['session_created'] = time();
            } elseif (time() - $_SESSION['session_created'] > 300) {
                session_regenerate_id(true);
                $_SESSION['session_created'] = time();
            }
        }
    }
}

// Call the regeneration function only when needed
regenerateSessionIfNeeded();

// Check session timeout (30 minutes)
function checkSessionTimeout() {
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > 1800) {
            session_unset();
            session_destroy();
            header('Location: ../login.php?timeout=1');
            exit;
        }
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['last_activity'] = time();
    }
}

// Require login
function requireLogin() {
    checkSessionTimeout();
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit;
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role && $_SESSION['user_role'] !== 'admin') {
        header('Location: ../dashboard.php?error=unauthorized');
        exit;
    }
}

// Debug function to check session
function debugSession() {
    echo "<pre>";
    echo "Session Status: " . session_status() . "\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session Data: \n";
    print_r($_SESSION);
    echo "</pre>";
}
?>