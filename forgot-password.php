<?php
require_once 'config/constants.php';
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/functions.php';
require_once 'config/mail.php';

// Initialize CSRF (but don't regenerate session)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if (isset($_POST['sendotp'])) {
    // Validate CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF validation failed.');
    }
    
    $email = clean($_POST['email']);
    
    if (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        $conn = db();
        $stmt = prepare("SELECT id, full_name FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            $otp = generateOTP();
            
            if (storeOTP($email, $otp)) {
                if (sendOTPEmail($email, $otp, $user['full_name'])) {
                    // Set session variables
                    $_SESSION['reset_email'] = $email;
                    $success = 'OTP sent successfully! Redirecting to verify page...';
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'verify-otp.php';
                        }, 1500);
                    </script>";
                } else {
                    $error = 'Failed to send OTP. Please try again later.';
                }
            } else {
                $error = 'Failed to generate OTP. Please try again.';
            }
        } else {
            $error = 'Email address not found in our system.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - AssetFlow</title>
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
button{width:100%;padding:15px;background:#1565C0;color:white;border:none;border-radius:10px;cursor:pointer;font-size:18px;transition:background 0.3s;margin-top:5px;}
button:hover{background:#0d47a1;}
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
</style>
</head>
<body>
<div class="box">
    <div class="logo">
        <img src="image/logo.png" alt="Logo">
    </div>
    
    <h2>Forgot Password</h2>
    <p class="subtitle">Enter your email to receive OTP</p>
    
    <!-- Progress Steps -->
    <div class="steps">
        <div class="step active">
            <span class="number">1</span>
            <span>Email</span>
        </div>
        <div class="step-line"></div>
        <div class="step">
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
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <div class="input-group">
            <input type="email" name="email" placeholder="Enter your email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            <span class="icon">✉</span>
        </div>
        <button name="sendotp" type="submit">Send OTP</button>
    </form>
    
    <div class="links">
        <a href="login.php">← Back to Login</a>
    </div>
</div>
</body>
</html>