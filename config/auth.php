<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/functions.php';

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Login user
function loginUser($email, $password) {
    $conn = db();
    
    $stmt = prepare("SELECT id, full_name, email, password, role, status FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        if ($user['status'] !== 'active') {
            mysqli_stmt_close($stmt);
            return ['success' => false, 'message' => 'Account is inactive.'];
        }
        
        if (verifyPassword($password, $user['password'])) {
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            
            $updateStmt = prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            mysqli_stmt_bind_param($updateStmt, "i", $user['id']);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
            
            mysqli_stmt_close($stmt);
            return ['success' => true, 'message' => 'Login successful'];
        }
    }
    
    mysqli_stmt_close($stmt);
    return ['success' => false, 'message' => 'Invalid email or password'];
}

// Register user
function registerUser($fullName, $email, $password, $confirmPassword) {
    if (empty($fullName) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }
    
    if ($password !== $confirmPassword) {
        return ['success' => false, 'message' => 'Passwords do not match'];
    }
    
    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters'];
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    $conn = db();
    
    $checkStmt = prepare("SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($checkStmt, "s", $email);
    mysqli_stmt_execute($checkStmt);
    $checkResult = mysqli_stmt_get_result($checkStmt);
    
    if (mysqli_num_rows($checkResult) > 0) {
        mysqli_stmt_close($checkStmt);
        return ['success' => false, 'message' => 'Email already registered'];
    }
    mysqli_stmt_close($checkStmt);
    
    $hashedPassword = hashPassword($password);
    $stmt = prepare("INSERT INTO users (full_name, email, password, role, status, created_at) VALUES (?, ?, ?, 'user', 'active', NOW())");
    mysqli_stmt_bind_param($stmt, "sss", $fullName, $email, $hashedPassword);
    
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return ['success' => true, 'message' => 'Registration successful'];
    } else {
        mysqli_stmt_close($stmt);
        return ['success' => false, 'message' => 'Registration failed'];
    }
}

// Store OTP
function storeOTP($email, $otp) {
    $conn = db();
    
    $deleteStmt = prepare("DELETE FROM password_resets WHERE email = ?");
    mysqli_stmt_bind_param($deleteStmt, "s", $email);
    mysqli_stmt_execute($deleteStmt);
    mysqli_stmt_close($deleteStmt);
    
    $stmt = prepare("INSERT INTO password_resets (email, otp, expires_at, created_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 300 SECOND), NOW())");
    mysqli_stmt_bind_param($stmt, "ss", $email, $otp);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Verify OTP
function verifyOTP($email, $otp) {
    $conn = db();
    
    $stmt = prepare("SELECT id FROM password_resets WHERE email = ? AND otp = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $email, $otp);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        mysqli_stmt_close($stmt);
        return true;
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

// Reset password
function resetPassword($email, $newPassword) {
    $conn = db();
    
    $hashedPassword = hashPassword($newPassword);
    $stmt = prepare("UPDATE users SET password = ? WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $hashedPassword, $email);
    
    if (mysqli_stmt_execute($stmt)) {
        $deleteStmt = prepare("DELETE FROM password_resets WHERE email = ?");
        mysqli_stmt_bind_param($deleteStmt, "s", $email);
        mysqli_stmt_execute($deleteStmt);
        mysqli_stmt_close($deleteStmt);
        
        mysqli_stmt_close($stmt);
        return true;
    }
    
    mysqli_stmt_close($stmt);
    return false;
}

// Logout
function logoutUser() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Get user by ID
function getUserById($id) {
    $conn = db();
    $stmt = prepare("SELECT id, full_name, email, role, status, created_at, last_login FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }
    
    mysqli_stmt_close($stmt);
    return null;
}
?>