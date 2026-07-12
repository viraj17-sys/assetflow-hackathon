<?php
// Error reporting for development
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set("log_errors", 1);

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $logMessage = date('Y-m-d H:i:s') . " - Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
    return true;
}

// Custom exception handler
function customExceptionHandler($exception) {
    $logMessage = date('Y-m-d H:i:s') . " - Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine() . "\n";
    error_log($logMessage, 3, __DIR__ . '/../logs/error.log');
    
    if (ini_get('display_errors')) {
        echo "<div style='background: #ffebee; color: #c62828; padding: 10px; border-radius: 5px; margin: 10px;'>";
        echo "<strong>Error:</strong> " . htmlspecialchars($exception->getMessage());
        echo "</div>";
    }
}

// Set handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
?>