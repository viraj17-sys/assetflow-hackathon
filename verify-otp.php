<?php
require_once 'config/constants.php';
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/functions.php';
require_once 'config/mail.php';

// Initialize CSRF token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if reset email is set
if (!isset($_SESSION['reset_email'])) {
    header('Location: forgot-password.php');
    exit;
}

$error = '';
$success = '';

if (isset($_POST['verify'])) {
    // Validate CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF validation failed.');
    }
    
    $otp = clean($_POST['otp']);
    $email = $_SESSION['reset_email'];
    
    if (verifyOTP($email, $otp)) {
        // Set session flag
        $_SESSION['otp_verified'] = true;
        $_SESSION['otp_verified_time'] = time();
        
        echo "<script>
            alert('OTP Verified Successfully!');
            window.location.href = 'new-password.php';
        </script>";
        exit;
    } else {
        $error = 'Invalid or expired OTP. Please try again.';
    }
}

// Handle resend OTP
if (isset($_POST['resend'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF validation failed.');
    }
    
    $email = $_SESSION['reset_email'];
    
    $conn = db();
    $stmt = prepare("SELECT full_name FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $otp = generateOTP();
    
    if (storeOTP($email, $otp)) {
        if (sendOTPEmail($email, $otp, $user['full_name'] ?? 'User')) {
            $success = 'New OTP sent successfully! Check your email.';
        } else {
            $error = 'Failed to send OTP. Please try again.';
        }
    } else {
        $error = 'Failed to generate OTP.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify OTP - AssetFlow</title>
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
.input-group input{width:100%;padding:14px 45px 14px 15px;border:2px solid #ddd;border-radius:10px;font-size:20px;outline:none;text-align:center;letter-spacing:10px;transition:border-color 0.3s;}
.input-group input:focus{border-color:#1565C0;}
.input-group input::placeholder{letter-spacing:0;font-size:16px;}
.input-group .icon{position:absolute;right:15px;top:50%;transform:translateY(-50%);color:#999;}
button{width:100%;padding:15px;background:#1565C0;color:white;border:none;border-radius:10px;cursor:pointer;font-size:18px;transition:background 0.3s;margin-top:5px;}
button:hover{background:#0d47a1;}
button.btn-outline{background:transparent;color:#1565C0;border:2px solid #1565C0;}
button.btn-outline:hover{background:#1565C0;color:white;}
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
.timer{text-align:center;color:#666;font-size:14px;margin:15px 0;}
.timer span{color:#1565C0;font-weight:600;}
.resend-area{display:flex;justify-content:center;gap:15px;margin-top:10px;}
.resend-area button{width:auto;padding:10px 20px;font-size:14px;}
</style>
</head>
<body>
<div class="box">
    <div class="logo">
        <img src="image/logo.png" alt="Logo">
    </div>
    
    <h2>Verify OTP</h2>
    <p class="subtitle">Enter the 6-digit code sent to your email</p>
    
    <!-- Progress Steps -->
    <div class="steps">
        <div class="step">
            <span class="number">1</span>
            <span>Email</span>
        </div>
        <div class="step-line active"></div>
        <div class="step active">
            <span class="number">2</span>
            <span>OTP</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
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
    
    <form method="POST" id="otpForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="input-group">
            <input type="text" name="otp" id="otpInput" placeholder="Enter OTP" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autofocus>
            <span class="icon">🔑</span>
        </div>
        <button name="verify" type="submit" id="verifyBtn">Verify OTP</button>
    </form>
    
    <div class="timer" id="timerDisplay">
        <span id="timer">05:00</span> remaining
    </div>
    
    <div class="resend-area">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <button name="resend" type="submit" class="btn-outline" id="resendBtn">Resend OTP</button>
        </form>
    </div>
    
    <div class="links">
        <a href="forgot-password.php">← Back to Email</a>
    </div>
</div>

<script>
// Auto-submit when 6 digits entered
document.getElementById('otpInput').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length === 6) {
        document.getElementById('verifyBtn').click();
    }
});

// Timer functionality
let timeLeft = 300;
const timerDisplay = document.getElementById('timer');
const resendBtn = document.getElementById('resendBtn');

function updateTimer() {
    if (timeLeft <= 0) {
        timerDisplay.textContent = '00:00';
        resendBtn.disabled = false;
        resendBtn.style.opacity = '1';
        resendBtn.style.cursor = 'pointer';
        return;
    }
    
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    timeLeft--;
    setTimeout(updateTimer, 1000);
}

updateTimer();

// Prevent form resubmission on page refresh
if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
}
</script>
</body>
</html>