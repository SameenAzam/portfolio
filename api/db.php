<?php
// Database Configuration
$servername = "localhost"; // Ya aapka host IP (e.g., 127.0.0.1)
$username   = "root";      // Aapka MySQL Username (XAMPP/WAMP me default 'root' hota hai)
$password   = "";          // Aapka MySQL Password (XAMPP me default empty/blank hota hai)
$dbname     = "portfolio"; // Apne Database ka naam yahan likhein

// Create Connection using MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Check Connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Set Charset to UTF-8 (Urdu/Hindi/English special characters support ke liye)
$conn->set_charset("utf8mb4");
?>