<?php
require_once "database.php";
global $conn;

function normalize_timezone_name($timezone) {
    $timezone = trim((string)$timezone);
    if ($timezone === '') {
        return 'UTC';
    }
    try {
        new DateTimeZone($timezone);
        return $timezone;
    } catch (Exception $e) {
        return 'UTC';
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$result = isset($_GET['result']) ? mysqli_real_escape_string($conn, $_GET['result']) : '';
$try = isset($_GET['try']) ? mysqli_real_escape_string($conn, $_GET['try']) : ''; // expected: calltry1, calltry2, calltry3

if ($id <= 0) {
    die("Error: Invalid ID provided.");
}
if (empty($result)) {
    die("Error: Result parameter is missing or empty.");
}
if (!in_array($try, ['calltry1', 'calltry2', 'calltry3'])) {
    die("Error: Invalid try parameter.");
}

// --- Build dynamic field name ---
$fieldName = $try . "status";

// Resolve company-local day range and match against UTC-stored inserttime.
$contextSql = "SELECT cn.company_id, p.timezone
               FROM campaignnumbers cn
               LEFT JOIN pbxdetail p ON cn.company_id = p.company_id
               WHERE cn.id = $id
               LIMIT 1";
$contextRes = mysqli_query($conn, $contextSql);
$context = $contextRes ? mysqli_fetch_assoc($contextRes) : null;
$timezoneName = normalize_timezone_name($context['timezone'] ?? 'UTC');
$localTimezone = new DateTimeZone($timezoneName);
$utcTimezone = new DateTimeZone('UTC');
$dayStartUtc = new DateTime('today', $localTimezone);
$dayEndUtc = clone $dayStartUtc;
$dayEndUtc->setTime(23, 59, 59);
$dayStartUtc->setTime(0, 0, 0);
$dayStartUtc->setTimezone($utcTimezone);
$dayEndUtc->setTimezone($utcTimezone);

// --- Build SQL ---
$sql = "UPDATE campaignnumbers 
        SET `$fieldName` = '$result' 
        WHERE id = $id
          AND inserttime >= '" . mysqli_real_escape_string($conn, $dayStartUtc->format('Y-m-d H:i:s')) . "'
          AND inserttime <= '" . mysqli_real_escape_string($conn, $dayEndUtc->format('Y-m-d H:i:s')) . "'";

// --- Execute query ---
if (mysqli_query($conn, $sql)) {
    if (mysqli_affected_rows($conn) > 0) {
        error_log("Successfully updated ID $id with $fieldName = $result");
    } else {
        error_log("No record updated for ID $id (possibly wrong date or same value)");
    }
} else {
    error_log("Query failed: " . mysqli_error($conn));
}

// --- Close connection ---
mysqli_close($conn);

// --- Output success ---
echo "200";

?>
