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
    
    $email = clean($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Check for timeout
if (isset($_GET['timeout'])) {
    $error = 'Your session has expired. Please login again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AssetFlow - Login</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:#f4f7fc;display:flex;justify-content:center;align-items:center;height:100vh;}
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
.forgot{text-align:right;margin:12px 0;}
.forgot a{color:#1565C0;text-decoration:none;font-size:14px;}
.forgot a:hover{text-decoration:underline;}
button{width:100%;padding:15px;background:#1565C0;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;transition:background 0.3s;}
button:hover{background:#0d47a1;}
hr{margin:25px 0;}
.signup{text-align:center;}
.signup h2{font-size:20px;color:#333;margin-bottom:10px;}
.signup p{margin:15px 0;color:#555;font-size:14px;}
.signup a{display:block;padding:15px;border:2px solid #1565C0;border-radius:10px;text-decoration:none;color:#1565C0;font-weight:bold;transition:all 0.3s;}
.signup a:hover{background:#1565C0;color:#fff;}
</style>
</head>
<body>
<div class="card">
    <div class="header">AssetFlow - Login</div>
    
    <div class="logo">
        <img src="image/logo.png" alt="Logo">
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <form action="login.php" method="POST">
        <?php echo csrf_field(); ?>
        
        <label>Email</label>
        <input type="email" name="email" placeholder="name@company.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        
        <label>Password</label>
        <input type="password" name="password" placeholder="********" required>
        
        <div class="forgot">
            <a href="forgot-password.php">Forgot Password?</a>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <hr>
    
    <div class="signup">
        <h2>New Here?</h2>
        <p>Sign up creates an employee account.<br>Admin roles assigned later.</p>
        <a href="register.php">Create Account</a>
    </div>
</div>
</body>
</html>