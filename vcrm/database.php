<?php
// Load environment variables
require_once(__DIR__ . '/../config.php');

$missing = app_validate_db_env();
if (!empty($missing)) {
   echo "Database configuration is incomplete. Please check .env (missing: " . implode(', ', $missing) . ")";
   exit();
}

$servername = app_env('DB_HOST', 'localhost');
$username = app_env('DB_USER', '');
$password = app_env('DB_PASS', '');
$dbname = app_env('DB_NAME', '');

$conn = mysqli_connect($servername, $username, $password, $dbname);
if(!$conn){
   die('Failed to connect to MySQL. Please verify DB_* values in .env. Error: ' . mysqli_connect_error());
}
?>