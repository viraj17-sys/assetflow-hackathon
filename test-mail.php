<?php

// Include mail configuration
require_once "config/mail.php";

// Email address where you want to receive the test email
$recipientEmail = "dhruvchauhan9805@gmail.com";   // Change this
$recipientName  = "Viraj";

// Generate a random OTP
$otp = rand(100000, 999999);

// Send Email
$result = sendOTPEmail($recipientEmail, $otp, $recipientName);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AssetFlow - Mail Test</title>

    <style>

        body{
            font-family:Arial,sans-serif;
            background:#f4f7fc;
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
        }

        .card{
            width:500px;
            background:white;
            padding:40px;
            border-radius:15px;
            box-shadow:0 10px 25px rgba(0,0,0,.15);
            text-align:center;
        }

        h2{
            color:#1565C0;
        }

        .success{
            color:green;
            font-size:22px;
            font-weight:bold;
        }

        .error{
            color:red;
            font-size:22px;
            font-weight:bold;
        }

        .otp{
            margin-top:20px;
            font-size:30px;
            font-weight:bold;
            color:#1565C0;
        }

        .info{
            margin-top:20px;
            color:#555;
            line-height:1.7;
        }

    </style>

</head>

<body>

<div class="card">

<h2>AssetFlow Mail Test</h2>

<?php

if($result)
{
    echo "<p class='success'>✅ Email Sent Successfully</p>";

    echo "<div class='otp'>OTP : $otp</div>";

    echo "<div class='info'>
            Recipient : <b>$recipientEmail</b><br><br>
            Check your Inbox and Spam folder.
          </div>";
}
else
{
    echo "<p class='error'>❌ Email Sending Failed</p>";

    echo "<div class='info'>
            Please check:<br><br>

            ✔ Gmail App Password<br>
            ✔ Internet Connection<br>
            ✔ SMTP Username & Password<br>
            ✔ PHPMailer Installation<br>
            ✔ vendor/autoload.php exists
          </div>";
}

?>

</div>

</body>
</html>