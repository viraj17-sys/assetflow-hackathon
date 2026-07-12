<?php
// Application constants
define("SITE_NAME", "AssetFlow");
define("SITE_URL", "http://localhost/assetflow");
define("UPLOAD_PATH", "uploads/");
define("IMAGE_PATH", "image/");
define("APP_EMAIL", "noreply@assetflow.com");
define("MIN_PASSWORD_LENGTH", 8);
define("OTP_EXPIRY", 300); // 5 minutes

// Timezone
date_default_timezone_set("Asia/Kolkata");

// Error reporting (development)
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/../logs/error.log");
?>