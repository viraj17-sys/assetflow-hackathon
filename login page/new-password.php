<?php
session_start();

if(!isset($_SESSION['otp']))
{
header("Location:forgot-password.php");
}

if(isset($_POST['save']))
{

$password=$_POST['password'];
$cpassword=$_POST['cpassword'];

if($password==$cpassword)
{

unset($_SESSION['otp']);

echo "<script>

alert('Password Changed Successfully');

window.location='login.php';

</script>";

}
else
{

echo "<script>alert('Password does not match');</script>";

}

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Password</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,sans-serif;}
body{background:#f4f7fc;display:flex;justify-content:center;align-items:center;height:100vh;padding:20px;}
.box{width:430px;background:white;padding:35px;border-radius:20px;box-shadow:0 10px 25px rgba(0,0,0,.15);}
.logo{text-align:center;margin-bottom:20px;}
.logo img{width:120px;}
h2{text-align:center;color:#1565C0;margin-bottom:20px;font-size:26px;}
label{display:block;font-weight:600;margin-top:12px;margin-bottom:8px;}
input{width:100%;padding:14px;border:2px solid #ddd;border-radius:10px;font-size:16px;outline:none;margin-bottom:10px;}
input:focus{border-color:#1565C0;}
button{width:100%;padding:15px;background:#1565C0;color:white;border:none;border-radius:10px;font-size:18px;cursor:pointer;margin-top:10px;}
button:hover{background:#0d47a1;}
a{text-decoration:none;color:#1565C0;font-weight:600;display:block;text-align:center;margin-top:15px;}
</style>
</head>
<body>
<div class="box">
  <div class="logo">
    <img src="image/logo.png" alt="Logo">
  </div>
  <h2>Create New Password</h2>
  <form method="POST">
    <label>New Password</label>
    <input type="password" name="password" placeholder="New Password" required>
    <label>Confirm Password</label>
    <input type="password" name="cpassword" placeholder="Confirm Password" required>
    <button name="save">Save Password</button>
  </form>
  <a href="login.php">Back to Login</a>
</div>
</body>
</html>