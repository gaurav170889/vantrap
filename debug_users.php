<?php
session_start();

// Display PHP error log location
$error_log = ini_get('error_log');
if (!$error_log || $error_log === 'syslog') {
    // If not configured, it goes to MAMP's default log
    $error_log = 'C:\\MAMP\\logs\\php_error.log (or check MAMP > Preferences > Ports)';
}

echo "<h2>Debug Information</h2>";
echo "<pre>";
echo "=== PHP Error Log Location ===\n";
echo $error_log . "\n\n";

echo "=== Session Data ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session erole: " . ($_SESSION['erole'] ?? 'NOT SET') . "\n";
echo "Session role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "Session company_id: " . ($_SESSION['company_id'] ?? 'NOT SET') . "\n";
echo "Session zid: " . ($_SESSION['zid'] ?? 'NOT SET') . "\n";
echo "Session ename: " . ($_SESSION['ename'] ?? 'NOT SET') . "\n";

echo "\n=== How to Debug ===\n";
echo "1. Check your PHP error log at the location above\n";
echo "2. Refresh the Users page in the browser\n";
echo "3. Look for entries with 'USERS RECORD DEBUG' and 'getUsersList()'\n";
echo "4. These logs will show session values and the SQL query being run\n";
echo "\n";
echo "5. Also check browser Network tab (F12):\n";
echo "   - Look for POST to 'users/record'\n";
echo "   - Response tab should show JSON data or empty array\n";
echo "</pre>";
?>
