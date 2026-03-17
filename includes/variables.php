<?php
$ip = $_SERVER['HTTP_HOST'];
$protocol = (
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
) ? "https" : "http";

define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
define("MODULEPATH", ROOT_PATH."/vantrap/modules/");
define("DBHOST", "localhost");
define("NAVURL","$protocol://$ip/vantrap/");
define("INCLUDEPATH", ROOT_PATH."/vantrap/");
define("HEADURL","$protocol://$ip/vantrap/");
define("LOGOUT","$protocol://$ip/vantrap");
define("BASE_URL", "$protocol://$ip/vantrap/");
define("UPLOAD", ROOT_PATH."/vantrap/asset/importnum/");
define("WEBHOOK_URL", "$protocol://$ip/vantrap/api/webhook_rating.php");
define("QUEUE_WEBHOOK_URL", "$protocol://$ip/vantrap/api/webhook_queue_status_secure.php");
?>