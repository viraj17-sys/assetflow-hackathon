<?php
// config/database.php
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
    $result = mysqli_query($conn, $sql);
    if (!$result && mysqli_errno($conn) != 0) {
        error_log("Query error: " . mysqli_error($conn) . " in query: " . $sql);
    }
    return $result;
}

function fetchOne($sql) {
    $result = query($sql);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

function fetchAll($sql) {
    $result = query($sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}
?>