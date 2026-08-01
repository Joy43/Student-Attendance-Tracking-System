<?php
// Retrieve environment variables
$url = getenv('DB_URL'); 
$host_env = getenv('DB_HOST');

// If the user accidentally pasted the entire URL into DB_HOST, let's parse it for them!
if ($host_env && strpos($host_env, 'mysql://') === 0) {
    $url = $host_env;
}

if ($url) {
    $dbparts = parse_url($url);
    $servername = $dbparts['host'];
    $username = $dbparts['user'];
    $password = $dbparts['pass'];
    $dbname = ltrim($dbparts['path'], '/');
    $port = $dbparts['port'] ?? 3306;
} else {
    $servername = $host_env ?: "127.0.0.1";
    $username   = getenv('DB_USER') ?: "root";       
    $password   = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "";          
    $dbname     = getenv('DB_NAME') ?: "attapp_db";
    $port       = getenv('DB_PORT') ?: 3306;
}

$conn = mysqli_init();

// If we are connecting to a remote cloud server like Aiven, force SSL mode.
if ($servername !== "127.0.0.1" && $servername !== "db" && $servername !== "localhost") {
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    $conn->real_connect($servername, $username, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);
} else {
    $conn->real_connect($servername, $username, $password, $dbname, $port);
}

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>
