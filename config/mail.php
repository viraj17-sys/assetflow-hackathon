<?php
// Check if PHPMailer autoload exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Send OTP via email
function sendOTPEmail($to, $otp, $name = '') {
    // Try PHPMailer first
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'dhruvchauhan9805@gmail.com';  // Change this
            $mail->Password   = 'mllm uiaa afkl qkwi';      // Change this
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            
            // Recipients
            $mail->setFrom('noreply@assetflow.com', 'AssetFlow');
            $mail->addAddress($to, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset OTP - AssetFlow';
            
            $body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #1565C0; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f4f7fc; }
                    .otp-code { font-size: 32px; font-weight: bold; color: #1565C0; text-align: center; padding: 20px; }
                    .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>AssetFlow - Password Reset</h2>
                    </div>
                    <div class='content'>
                        <p>Hello " . htmlspecialchars($name) . ",</p>
                        <p>You requested to reset your password. Use this OTP:</p>
                        <div class='otp-code'>" . $otp . "</div>
                        <p>This OTP is valid for 5 minutes.</p>
                        <p>If you didn't request this, ignore this email.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " AssetFlow. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->Body = $body;
            $mail->AltBody = "Your OTP for password reset is: " . $otp . ". Valid for 5 minutes.";
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("PHPMailer error: " . $mail->ErrorInfo);
            // Fallback to simple mail
            return sendOTPEmailSimple($to, $otp, $name);
        }
    }
    
    // Fallback to simple mail
    return sendOTPEmailSimple($to, $otp, $name);
}

// Simple mail fallback
function sendOTPEmailSimple($to, $otp, $name = '') {
    $subject = "Password Reset OTP - AssetFlow";
    $message = "Dear " . $name . ",\n\n";
    $message .= "You requested to reset your password.\n";
    $message .= "Your OTP is: " . $otp . "\n";
    $message .= "This OTP is valid for 5 minutes.\n\n";
    $message .= "If you didn't request this, ignore this email.\n\n";
    $message .= "Regards,\nAssetFlow Team";
    
    $headers = "From: noreply@assetflow.com\r\n";
    $headers .= "Reply-To: noreply@assetflow.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
}
?>