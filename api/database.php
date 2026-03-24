<?php
// Load environment variables
require_once(__DIR__ . '/../config.php');

$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$database = getenv('DB_NAME');

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
  exit();
}
?>
