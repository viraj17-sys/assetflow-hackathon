<?php
session_start();

if(isset($_POST['sendotp']))
{
    $email=$_POST['email'];

    $otp=rand(100000,999999);

    $_SESSION['otp']=$otp;
    $_SESSION['email']=$email;

    echo "<script>
    alert('Your OTP is: $otp');
    window.location='verify-otp.php';
    </script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:#f4f7fc;display:flex;justify-content:center;align-items:center;height:100vh;padding:20px;}
.box{width:430px;background:white;padding:35px;border-radius:20px;box-shadow:0 10px 25px rgba(0,0,0,.15);}
.logo{text-align:center;margin-bottom:20px;}
.logo img{width:120px;}
h2{text-align:center;color:#1565C0;margin-bottom:20px;font-size:26px;}
input{width:100%;padding:14px;border:2px solid #ddd;border-radius:10px;font-size:16px;outline:none;margin-bottom:12px;}
input:focus{border-color:#1565C0;}
button{width:100%;padding:15px;background:#1565C0;color:white;border:none;border-radius:10px;cursor:pointer;font-size:18px;margin-top:8px;}
button:hover{background:#0d47a1;}
a{text-decoration:none;color:#1565C0;font-weight:600;display:block;text-align:center;margin-top:15px;}
</style>
</head>
<body>

<div class="box">

<div class="logo">
    <img src="image/logo.png" alt="Logo">
</div>

<h2>Forgot Password</h2>

<form method="POST">

<input
type="email"
name="email"
placeholder="Enter Email"
required>

<button name="sendotp">
Send OTP
</button>

</form>

<br>

<center>

<a href="login.php">
Back To Login
</a>

</center>

</div>

</body>
</html>