<?php
session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = 'Login successful';
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

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:Poppins,sans-serif;
}

body{
background:#f4f7fc;
display:flex;
justify-content:center;
align-items:center;
height:100vh;
}

.card{
width:450px;
background:#fff;
padding:35px;
border-radius:20px;
box-shadow:0 10px 25px rgba(0,0,0,.15);
}

.header{
background:#1565C0;
color:#fff;
text-align:center;
padding:15px;
border-radius:12px;
font-size:28px;
font-weight:700;
margin-bottom:25px;
}

.logo{
text-align:center;
margin-bottom:20px;
}

.logo img{
width:120px;
}

label{
display:block;
font-weight:600;
margin-top:15px;
margin-bottom:8px;
}

input{
width:100%;
padding:14px;
border:2px solid #ddd;
border-radius:10px;
font-size:16px;
outline:none;
}

input:focus{
border-color:#1565C0;
}

.forgot{
text-align:right;
margin:12px 0;
}

.forgot a{
color:#1565C0;
text-decoration:none;
}

button{
width:100%;
padding:15px;
background:#1565C0;
color:#fff;
border:none;
border-radius:10px;
font-size:18px;
cursor:pointer;
}

button:hover{
background:#0d47a1;
}

hr{
margin:25px 0;
}

.signup{
text-align:center;
}

.signup p{
margin:15px 0;
color:#555;
}

.signup a{
display:block;
padding:15px;
border:2px solid #1565C0;
border-radius:10px;
text-decoration:none;
color:#1565C0;
font-weight:bold;
}

.signup a:hover{
background:#1565C0;
color:#fff;
}

</style>

</head>

<body>

<div class="card">

<div class="header">
AssetFlow - Login
</div>

<?php if (!empty($message)) { ?>
<script>
alert("<?php echo addslashes($message); ?>");
</script>
<?php } ?>

<div class="logo">
<img src="image/logo.png" alt="Logo">
</div>

<form action="login.php" method="POST">

<label>Email</label>

<input type="email"
name="email"
placeholder="name@company.com"
required>

<label>Password</label>

<input type="password"
name="password"
placeholder="********"
required>

<div class="forgot">
<a href="forgot-password.php">Forgot Password?</a>
</div>

<button type="submit">Login</button>

</form>

<hr>

<div class="signup">

<h2>New Here?</h2>

<p>
Sign up creates an employee account.<br>
Admin roles assigned later.
</p>

<a href="register.php">
Create Account
</a>

</div>

</div>

</body>
</html>