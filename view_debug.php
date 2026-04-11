<?php
session_start();

// PHP error log location
$error_log_path = ini_get('error_log');
if (!$error_log_path || $error_log_path === 'syslog') {
    // Try common MAMP locations
    $possible_paths = [
        'C:\\MAMP\\logs\\php_error.log',
        'C:\\MAMP\\logs\\error.log',
        sys_get_temp_dir() . '\\php_error.log',
    ];
    
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            $error_log_path = $path;
            break;
        }
    }
    
    if (!$error_log_path) {
        $error_log_path = 'Check MAMP > Preferences > Ports for PHP error log location';
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Users Query</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: #f9f9f9; }
        pre { background: #f4f4f4; padding: 10px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 10px; }
        .warning { background: #fff3cd; padding: 10px; border-left: 4px solid #ff9800; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🔍 Debug Users Query</h1>

<div class="box">
    <h2>Session Data</h2>
    <pre><?php
    echo "Session ID: " . session_id() . "\n";
    echo "Session erole: " . ($_SESSION['erole'] ?? 'NOT SET') . "\n";
    echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
    echo "Session company_id: " . ($_SESSION['company_id'] ?? 'NOT SET') . "\n";
    echo "Session zid: " . ($_SESSION['zid'] ?? 'NOT SET') . "\n";
    echo "Session ename: " . ($_SESSION['ename'] ?? 'NOT SET') . "\n";
    ?></pre>
    <?php if (empty($_SESSION['erole'])): ?>
        <div class="warning">⚠️ Session is empty! You may not be logged in. <a href="<?php echo BASE_URL; ?>?route=login/index">Click here to login</a></div>
    <?php endif; ?>
</div>

<div class="box">
    <h2>PHP Error Log Location</h2>
    <pre><?php echo $error_log_path; ?></pre>
    
    <?php if (file_exists($error_log_path)): ?>
        <h3>Last 50 lines of error log:</h3>
        <pre><?php
        $lines = file($error_log_path);
        $last_lines = array_slice($lines, -50);
        echo htmlspecialchars(implode('', $last_lines));
        ?></pre>
        
        <p>
            <strong>Look for these debug lines after refreshing Users page:</strong><br>
            - <code>=== USERS RECORD METHOD START ===</code><br>
            - <code>getUsersList() DEBUG</code><br>
            - <code>Full SQL Query:</code><br>
            - <code>Query returned X rows</code>
        </p>
    <?php else: ?>
        <div class="warning">⚠️ Error log file not found at: <?php echo $error_log_path; ?></div>
        <p>Check MAMP > Preferences for the correct PHP error log location</p>
    <?php endif; ?>
</div>

<div class="box">
    <h2>Next Steps</h2>
    <ol>
        <li><strong>Logout</strong> and login again as manager (company_id 2)</li>
        <li><strong>Refresh this page</strong> to see updated session data</li>
        <li><strong>Go to Users module</strong> at <a href="<?php echo BASE_URL; ?>?route=users/index">http://localhost/vantrap/users/</a></li>
        <li><strong>Come back here</strong> to see the error log with debug output</li>
        <li><strong>Check the SQL query</strong> and row count in the error log</li>
    </ol>
</div>

</body>
</html>
