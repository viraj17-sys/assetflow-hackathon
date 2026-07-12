<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: login.php');
    exit;
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
label{display:block;font-weight:600;margin-top:15px;margin-bottom:8px;}
input{width:100%;padding:14px;border:2px solid #ddd;border-radius:10px;font-size:16px;outline:none;}
input:focus{border-color:#1565C0;}
button{width:100%;padding:15px;background:#1565C0;color:#fff;border:none;border-radius:10px;font-size:18px;cursor:pointer;margin-top:20px;}
button:hover{background:#0d47a1;}
.login-link{text-align:center;margin-top:15px;}
.login-link a{color:#1565C0;text-decoration:none;font-weight:600;}
</style>
</head>
<body>
<div class="card">
  <div class="header">AssetFlow - Register</div>
  <div class="logo">
    <img src="image/logo.png" alt="Logo">
  </div>
  <form action="register.php" method="POST">
    <label>Full Name</label>
    <input type="text" name="fullname" placeholder="Enter your full name" required>

    <label>Email</label>
    <input type="email" name="email" placeholder="name@company.com" required>

    <label>Password</label>
    <input type="password" id="password" name="password" placeholder="********" required>

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
