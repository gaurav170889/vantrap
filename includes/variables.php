<?php
$ip = $_SERVER['HTTP_HOST'] ?? 'localhost';
$protocol = (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
) ? "https" : "http";

// Resolve project root and URL base dynamically so folder name can differ per server.
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: ($_SERVER['DOCUMENT_ROOT'] ?? '');
$documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');

$projectRoot = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$projectRoot = rtrim(str_replace('\\', '/', $projectRoot), '/');

$basePath = '';
if ($documentRoot !== '' && strpos($projectRoot, $documentRoot) === 0) {
    $basePath = substr($projectRoot, strlen($documentRoot));
}
$basePath = trim(str_replace('\\', '/', $basePath), '/');
$basePath = $basePath === '' ? '' : '/' . $basePath;

$baseUrl = "$protocol://$ip$basePath";

define('ROOT_PATH', $projectRoot);
define("MODULEPATH", ROOT_PATH."/modules/");
define("DBHOST", "localhost");
define("NAVURL", "$baseUrl/");
define("INCLUDEPATH", ROOT_PATH."/");
define("HEADURL", "$baseUrl/");
define("LOGOUT", "$baseUrl");
define("BASE_URL", "$baseUrl/");
define("UPLOAD", ROOT_PATH."/asset/importnum/");
define("WEBHOOK_URL", "$baseUrl/api/webhook_rating.php");
define("QUEUE_WEBHOOK_URL", "$baseUrl/api/webhook_queue_status_secure.php");
?>