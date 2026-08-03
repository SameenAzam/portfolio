<?php
// Database Configuration
$servername = "localhost"; // Your host IP (e.g., 127.0.0.1)
$username   = "root";      // Your MySQL Username (default 'root')
$password   = "";          // Your MySQL Password (default empty/blank)
$dbname     = "portfolio"; // Your Database name

// Create Connection using MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Check Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set Charset to UTF-8 (Urdu/Hindi/English special characters support ke liye)
$conn->set_charset("utf8mb4");
?>
