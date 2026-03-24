<?php
// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $env = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $env);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

if (!function_exists('app_env')) {
    function app_env($key, $default = null) {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return $default;
        }
        return $value;
    }
}

if (!function_exists('app_validate_db_env')) {
    function app_validate_db_env() {
        $required = array('DB_USER', 'DB_NAME');
        $missing = array();

        foreach ($required as $key) {
            $value = app_env($key, null);
            if ($value === null || $value === '') {
                $missing[] = $key;
            }
        }

        return $missing;
    }
}
?>
