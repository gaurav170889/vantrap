<?php
// Load environment variables
require_once(__DIR__ . '/../config.php');

$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$dbname = getenv('DB_NAME');

$conn = mysqli_connect($servername, $username, $password, $dbname);
if(!$conn){
   die('Could not Connect My Sql:' . mysqli_connect_error());
}
?>