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

// Initialize default failure response
$response = [
    'result' => 'fail',
    'number' => '0',
    'campaignnumber_id' => '0',
    'routeto' => '0',
    'message' => 'Unknown error'
];

// Step 1: Fetch the currently running campaign
$query = "SELECT c.id, c.company_id, c.routeto, c.weekdays, c.starttime, c.stoptime, p.timezone
          FROM campaign c
          LEFT JOIN pbxdetail p ON c.company_id = p.company_id
          WHERE c.status = 'Running' 
          LIMIT 1";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);

    $campaignId = $row['id'];
    $companyId = intval($row['company_id'] ?? 0);
    $routeto = $row['routeto'];
    $startTime = $row['starttime'];
    $endTime = $row['stoptime'];
    $weekdaysJson = $row['weekdays'];
    $timezoneName = normalize_timezone_name($row['timezone'] ?? 'UTC');
    $localTimezone = new DateTimeZone($timezoneName);
    $utcTimezone = new DateTimeZone('UTC');
    $nowLocal = new DateTime('now', $localTimezone);
    $currentDay = $nowLocal->format('l');
    $currentTime = $nowLocal->format('H:i:s');
    $dayStartUtc = clone $nowLocal;
    $dayStartUtc->setTime(0, 0, 0);
    $dayEndUtc = clone $nowLocal;
    $dayEndUtc->setTime(23, 59, 59);
    $dayStartUtc->setTimezone($utcTimezone);
    $dayEndUtc->setTimezone($utcTimezone);

    $allowedDays = json_decode($weekdaysJson, true);

    if (is_array($allowedDays) && in_array($currentDay, $allowedDays)) {
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
            // Step 2: Get one number where all 3 calltry columns are 0
            $numQuery = "SELECT id, number, calltry1, calltry2, calltry3 
                         FROM campaignnumbers 
                         WHERE campaignid = '$campaignId' 
                           AND calltry1 = 0 AND calltry2 = 0 AND calltry3 = 0
                           AND company_id = '$companyId'
                           AND inserttime >= '" . mysqli_real_escape_string($conn, $dayStartUtc->format('Y-m-d H:i:s')) . "'
                           AND inserttime <= '" . mysqli_real_escape_string($conn, $dayEndUtc->format('Y-m-d H:i:s')) . "'
                         LIMIT 1";
            $numResult = mysqli_query($conn, $numQuery);

            if ($numResult && mysqli_num_rows($numResult) > 0) {
                $numRow = mysqli_fetch_assoc($numResult);
                $campaignNumberId = $numRow['id'];

                // Determine which calltry column to update
                $tryUpdate = '';
                if ($numRow['calltry1'] == 0) {
                    $tryUpdate = 'calltry1';
                     $dtUpdate = 'calltry1dt';
                } elseif ($numRow['calltry2'] == 0) {
                    $tryUpdate = 'calltry2';
                     $dtUpdate = 'calltry2dt';
                } elseif ($numRow['calltry3'] == 0) {
                    $tryUpdate = 'calltry3';
                     $dtUpdate = 'calltry3dt';
                }

                // Update the calltry column to 1 if applicable
                if ($tryUpdate != '') {
                    $dttime = gmdate('Y-m-d H:i:s');
                    $updateQuery = "UPDATE campaignnumbers 
                                    SET $tryUpdate = 1 ,$dtUpdate = '$dttime'
                                    WHERE id = '$campaignNumberId'";
                    mysqli_query($conn, $updateQuery);
                }
                
                
                $values = [];
                for ($p = 11; $p <= 22; $p++) {
                    $values[] = "({$campaignId}, {$p})";
                }
                $initSql = "INSERT IGNORE INTO campaign_prefix_usage (campaignid, prefix) VALUES " . implode(',', $values) . ";";
                mysqli_query($conn, $initSql);
            
                // Begin transaction (best effort)
                $txnStarted = mysqli_begin_transaction($conn);
            
                // Pick least-used prefix for this campaign with a lock
                $pickSql = "
                    SELECT prefix
                    FROM campaign_prefix_usage
                    WHERE campaignid = {$campaignId}
                    ORDER BY usage_count ASC, last_used ASC, prefix ASC
                    LIMIT 1
                    FOR UPDATE
                ";
                $pickRes = mysqli_query($conn, $pickSql);
            
                if ($pickRes && mysqli_num_rows($pickRes) > 0) {
                    $pick           = mysqli_fetch_assoc($pickRes);
                    $currentPrefix  = (int)$pick['prefix'];
            
                    // Increment usage for chosen prefix
                    $updSql = "
                        UPDATE campaign_prefix_usage
                        SET usage_count = usage_count + 1, last_used = UTC_TIMESTAMP()
                        WHERE campaignid = {$campaignId} AND prefix = {$currentPrefix}
                        LIMIT 1
                    ";
                    mysqli_query($conn, $updSql);
            
                    if ($txnStarted) {
                        mysqli_commit($conn);
                    }
                } else {
                    // Fallback if pick failed
                    $currentPrefix = 11;
                    if ($txnStarted) {
                        mysqli_rollback($conn);
                    }
                }
            
                // 5) Prefix + original number
                $prefixedNumber = $currentPrefix . (string)$numRow['number'];

                // Success response
                $response = [
                    'result' => 'success',
                   'number'             => (string)$prefixedNumber,
                    'campaignnumber_id' => (string)$numRow['id'],
                    'routeto' => (string)$routeto,
                    'message' => 'Number assigned successfully'
                ];
            } else {
                $response['message'] = 'No campaign number with all call tries 0';
            }
        } else {
            $response['message'] = 'Current time is outside campaign time window';
        }
    } else {
        $response['message'] = 'Today is not in allowed campaign weekdays';
    }
} else {
    $response['message'] = 'No running campaign found';
}

header('Content-Type: application/json');
echo json_encode($response);

?>
