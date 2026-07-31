<?php
$servername = getenv('DB_HOST') ?: "127.0.0.1";   // use 127.0.0.1 instead of localhost
$username   = getenv('DB_USER') ?: "root";       
$password   = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "";          
$dbname     = getenv('DB_NAME') ?: "attapp_db";
$port       = getenv('DB_PORT') ?: 3307;          
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
