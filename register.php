<?php
require_once 'config/constants.php';
require_once 'config/session.php';
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'config/csrf.php';
require_once 'config/functions.php';

init_csrf();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validate_csrf();
    
    $fullName = clean($_POST['fullname']);
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $result = registerUser($fullName, $email, $password, $confirmPassword);
    
    if ($result['success']) {
        $success = $result['message'] . ' Redirecting to login...';
        echo "<meta http-equiv='refresh' content='2;url=login.php'>";
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AssetFlow - Register</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:#f4f7fc;display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px;}
.card{width:450px;background:#fff;padding:35px;border-radius:20px;box-shadow:0 10px 25px rgba(0,0,0,.15);}
.header{background:#1565C0;color:#fff;text-align:center;padding:15px;border-radius:12px;font-size:28px;font-weight:700;margin-bottom:25px;}
.logo{text-align:center;margin-bottom:20px;}
.logo img{width:120px;}
.alert{padding:12px;border-radius:8px;margin-bottom:15px;text-align:center;}
.alert-error{background:#ffebee;color:#c62828;border:1px solid #ef9a9a;}
.alert-success{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;}
label{display:block;font-weight:600;margin-top:15px;margin-bottom:8px;}
input{width:100%;padding:14px;border:2px solid #ddd;border-radius:10px;font-size:16px;outline:none;}
input:focus{border-color:#1565C0;}
button{width:100%;padding:15px;background:#1565C0;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;margin-top:20px;transition:background 0.3s;}
button:hover{background:#0d47a1;}
.login-link{text-align:center;margin-top:15px;}
.login-link p{color:#555;margin-bottom:5px;}
.login-link a{color:#1565C0;text-decoration:none;font-weight:600;}
.login-link a:hover{text-decoration:underline;}
.password-requirements{font-size:12px;color:#666;margin-top:5px;}
</style>
</head>
<body>
<div class="card">
    <div class="header">AssetFlow - Register</div>
    
    <div class="logo">
        <img src="image/logo.png" alt="Logo">
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form action="register.php" method="POST">
        <?php echo csrf_field(); ?>
        
        <label>Full Name</label>
        <input type="text" name="fullname" placeholder="Enter your full name" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
        
        <label>Email</label>
        <input type="email" name="email" placeholder="name@company.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        
        <label>Password</label>
        <input type="password" id="password" name="password" placeholder="********" required minlength="8">
        <div class="password-requirements">Minimum 8 characters</div>
        
        <label>Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="********" required>
        
        <button type="submit">Create Account</button>
    </form>
    
    <div class="login-link">
        <p>Already have an account?</p>
        <a href="login.php">Login here</a>
    </div>
</div>
</body>
</html>