<?php
require_once 'config/constants.php';
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/functions.php';

init_csrf();

// Debug: Check session data
error_log("Session data in new-password: " . print_r($_SESSION, true));

// Check if OTP is verified
if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    error_log("OTP not verified. Session: " . print_r($_SESSION, true));
    header('Location: forgot-password.php');
    exit;
}

// Check if reset email is set
if (!isset($_SESSION['reset_email'])) {
    error_log("Reset email not set. Session: " . print_r($_SESSION, true));
    header('Location: forgot-password.php');
    exit;
}

$error = '';
$success = '';

if (isset($_POST['save'])) {
    validate_csrf();
    
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $email = $_SESSION['reset_email'];
    
    // Validate password strength
    if (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number';
    } elseif ($password !== $cpassword) {
        $error = 'Passwords do not match';
    } else {
        if (resetPassword($email, $password)) {
            // Clear session data
            unset($_SESSION['reset_email']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['otp_verified_time']);
            
            $success = 'Password changed successfully! Redirecting to login...';
            echo "<script>
                alert('Password changed successfully!');
                setTimeout(function() {
                    window.location.href = 'login.php';
                }, 2000);
            </script>";
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Password - AssetFlow</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:#f4f7fc;display:flex;justify-content:center;align-items:center;height:100vh;padding:20px;}
.box{width:430px;background:white;padding:35px;border-radius:20px;box-shadow:0 10px 25px rgba(0,0,0,.15);}
.logo{text-align:center;margin-bottom:20px;}
.logo img{width:120px;}
h2{text-align:center;color:#1565C0;margin-bottom:10px;font-size:26px;}
.subtitle{text-align:center;color:#666;margin-bottom:25px;font-size:14px;}
.alert{padding:12px;border-radius:8px;margin-bottom:15px;text-align:center;}
.alert-error{background:#ffebee;color:#c62828;border:1px solid #ef9a9a;}
.alert-success{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;}
.input-group{position:relative;margin-bottom:15px;}
.input-group input{width:100%;padding:14px 45px 14px 15px;border:2px solid #ddd;border-radius:10px;font-size:16px;outline:none;transition:border-color 0.3s;}
.input-group input:focus{border-color:#1565C0;}
.input-group .icon{position:absolute;right:15px;top:50%;transform:translateY(-50%);color:#999;}
.input-group .toggle-password{position:absolute;right:45px;top:50%;transform:translateY(-50%);cursor:pointer;color:#999;background:none;border:none;font-size:16px;}
button{width:100%;padding:15px;background:#1565C0;color:white;border:none;border-radius:10px;cursor:pointer;font-size:18px;transition:background 0.3s;margin-top:5px;}
button:hover{background:#0d47a1;}
button:disabled{opacity:0.6;cursor:not-allowed;}
.links{text-align:center;margin-top:20px;}
.links a{color:#1565C0;text-decoration:none;font-weight:500;display:inline-block;margin:0 10px;}
.links a:hover{text-decoration:underline;}
.steps{display:flex;justify-content:center;margin-bottom:25px;}
.step{display:flex;align-items:center;color:#999;}
.step.active{color:#1565C0;font-weight:600;}
.step .number{width:30px;height:30px;border-radius:50%;background:#eee;display:flex;align-items:center;justify-content:center;margin:0 5px;font-weight:600;}
.step.active .number{background:#1565C0;color:white;}
.step-line{width:50px;height:2px;background:#ddd;margin:0 5px;}
.step-line.active{background:#1565C0;}
.password-requirements{margin:10px 0 15px 0;padding:10px;background:#f8f9fa;border-radius:8px;}
.password-requirements p{margin:5px 0;font-size:13px;color:#666;}
.password-requirements .req{display:flex;align-items:center;gap:8px;margin:4px 0;font-size:13px;}
.password-requirements .req .check{color:#4caf50;font-weight:bold;}
.password-requirements .req .cross{color:#f44336;font-weight:bold;}
</style>
</head>
<body>
<div class="box">
    <div class="logo">
        <img src="image/logo.png" alt="Logo">
    </div>
    
    <h2>Create New Password</h2>
    <p class="subtitle">Enter your new password below</p>
    
    <!-- Progress Steps -->
    <div class="steps">
        <div class="step">
            <span class="number">1</span>
            <span>Email</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
            <span class="number">2</span>
            <span>OTP</span>
        </div>
        <div class="step-line active"></div>
        <div class="step active">
            <span class="number">3</span>
            <span>New Pass</span>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form method="POST" id="passwordForm">
        <?php echo csrf_field(); ?>
        
        <div class="input-group">
            <input type="password" id="password" name="password" placeholder="New Password" required minlength="8">
            <span class="icon">🔒</span>
            <button type="button" class="toggle-password" onclick="togglePassword('password')">👁</button>
        </div>
        
        <div class="input-group">
            <input type="password" id="cpassword" name="cpassword" placeholder="Confirm Password" required>
            <span class="icon">🔐</span>
            <button type="button" class="toggle-password" onclick="togglePassword('cpassword')">👁</button>
        </div>
        
        <!-- Password Requirements -->
        <div class="password-requirements" id="requirements">
            <p><strong>Password must contain:</strong></p>
            <div class="req" id="req-length">
                <span class="cross">✗</span> At least 8 characters
            </div>
            <div class="req" id="req-uppercase">
                <span class="cross">✗</span> At least one uppercase letter
            </div>
            <div class="req" id="req-lowercase">
                <span class="cross">✗</span> At least one lowercase letter
            </div>
            <div class="req" id="req-number">
                <span class="cross">✗</span> At least one number
            </div>
            <div class="req" id="req-match">
                <span class="cross">✗</span> Passwords match
            </div>
        </div>
        
        <button name="save" type="submit" id="submitBtn">Save Password</button>
    </form>
    
    <div class="links">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}

// Password validation
const password = document.getElementById('password');
const cpassword = document.getElementById('cpassword');
const submitBtn = document.getElementById('submitBtn');

function validatePassword() {
    const pwd = password.value;
    const cpwd = cpassword.value;
    
    // Check length
    const lengthReq = document.getElementById('req-length');
    if (pwd.length >= 8) {
        lengthReq.querySelector('span').textContent = '✓';
        lengthReq.querySelector('span').className = 'check';
        lengthReq.style.color = '#4caf50';
    } else {
        lengthReq.querySelector('span').textContent = '✗';
        lengthReq.querySelector('span').className = 'cross';
        lengthReq.style.color = '#f44336';
    }
    
    // Check uppercase
    const upperReq = document.getElementById('req-uppercase');
    if (/[A-Z]/.test(pwd)) {
        upperReq.querySelector('span').textContent = '✓';
        upperReq.querySelector('span').className = 'check';
        upperReq.style.color = '#4caf50';
    } else {
        upperReq.querySelector('span').textContent = '✗';
        upperReq.querySelector('span').className = 'cross';
        upperReq.style.color = '#f44336';
    }
    
    // Check lowercase
    const lowerReq = document.getElementById('req-lowercase');
    if (/[a-z]/.test(pwd)) {
        lowerReq.querySelector('span').textContent = '✓';
        lowerReq.querySelector('span').className = 'check';
        lowerReq.style.color = '#4caf50';
    } else {
        lowerReq.querySelector('span').textContent = '✗';
        lowerReq.querySelector('span').className = 'cross';
        lowerReq.style.color = '#f44336';
    }
    
    // Check number
    const numReq = document.getElementById('req-number');
    if (/[0-9]/.test(pwd)) {
        numReq.querySelector('span').textContent = '✓';
        numReq.querySelector('span').className = 'check';
        numReq.style.color = '#4caf50';
    } else {
        numReq.querySelector('span').textContent = '✗';
        numReq.querySelector('span').className = 'cross';
        numReq.style.color = '#f44336';
    }
    
    // Check match
    const matchReq = document.getElementById('req-match');
    if (pwd === cpwd && pwd.length > 0) {
        matchReq.querySelector('span').textContent = '✓';
        matchReq.querySelector('span').className = 'check';
        matchReq.style.color = '#4caf50';
    } else {
        matchReq.querySelector('span').textContent = '✗';
        matchReq.querySelector('span').className = 'cross';
        matchReq.style.color = '#f44336';
    }
    
    // Enable submit if all requirements met
    const allValid = pwd.length >= 8 && 
                    /[A-Z]/.test(pwd) && 
                    /[a-z]/.test(pwd) && 
                    /[0-9]/.test(pwd) && 
                    pwd === cpwd && 
                    pwd.length > 0;
    
    submitBtn.disabled = !allValid;
}

password.addEventListener('input', validatePassword);
cpassword.addEventListener('input', validatePassword);

// Initial validation
validatePassword();

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
</body>
</html>