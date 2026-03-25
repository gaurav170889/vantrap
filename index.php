<?php 
// --- Error logging: works on all hosts including Hostinger shared ---
$_LOG_FILE = __DIR__ . '/php-error.log';
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
@ini_set('error_log', $_LOG_FILE);

// Custom handler: uses file_put_contents so it always works even if ini_set is blocked
set_error_handler(function($errno, $errstr, $errfile, $errline) use ($_LOG_FILE) {
    $msg = date('[Y-m-d H:i:s]') . " PHP Error [$errno]: $errstr in $errfile on line $errline" . PHP_EOL;
    file_put_contents($_LOG_FILE, $msg, FILE_APPEND | LOCK_EX);
    return false; // let PHP also handle it normally
});

register_shutdown_function(function() use ($_LOG_FILE) {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $msg = date('[Y-m-d H:i:s]') . " PHP Fatal [{$error['type']}]: {$error['message']} in {$error['file']} on line {$error['line']}" . PHP_EOL;
        file_put_contents($_LOG_FILE, $msg, FILE_APPEND | LOCK_EX);
    }
});
session_start();
date_default_timezone_set('Asia/Bangkok');

include("includes/variables.php");
include("includes/functions.php");
if(isset($_GET['type']))
{
	session_destroy();
	header("Location: ".LOGOUT);
}
require("modules/checkprivilege.php");

include("modules/login.php");


	


if(!isset($_SESSION['zid'])){
		$class = ucwords('login');
		if(class_exists($class))	{
			$myclass = new $class();
			$myclass->index() ; 
		}
	exit();
}

CheckPrivilege();

//mysqli_close();
?>
