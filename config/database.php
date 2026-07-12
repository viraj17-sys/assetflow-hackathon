<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "assetflow_db";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Helper functions
function db() {
    global $conn;
    return $conn;
}

function escape($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}

function prepare($sql) {
    global $conn;
    return mysqli_prepare($conn, $sql);
}

function query($sql) {
    global $conn;
    return mysqli_query($conn, $sql);
}
?>