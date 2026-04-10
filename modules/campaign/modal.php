<?php
/* Modulename_modal */
Class Campaign_modal{
	
	
	public function __construct()
	{
		$this->conn = ConnectDB();
        $this->ensureDidTables();
        $this->ensureCampaignColumns();
        $this->ensureCampaignStatusAuditTable();
        $this->ensureDispositionHistoryTable();
	}

    private function ensureCampaignColumns()
    {
        $check = mysqli_query($this->conn, "SHOW COLUMNS FROM campaign LIKE 'dg_reception_number'");
        if (!$check || mysqli_num_rows($check) === 0) {
            mysqli_query($this->conn, "ALTER TABLE campaign ADD COLUMN dg_reception_number VARCHAR(32) NULL AFTER dn_number");
        }
    }

    private function ensureDidTables()
    {
        $sql1 = "CREATE TABLE IF NOT EXISTS pbx_dids (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            inbound_rule_id INT NOT NULL,
            did VARCHAR(64) NOT NULL,
            trunk VARCHAR(255) DEFAULT NULL,
            rule_name VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_company_inbound_rule (company_id, inbound_rule_id),
            KEY idx_company_did (company_id, did)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $sql2 = "CREATE TABLE IF NOT EXISTS campaign_outbound_rule (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            campaign_id INT NOT NULL,
            outbound_rule_id INT NOT NULL,
            last_used_map_id INT DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_company_campaign (company_id, campaign_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $sql3 = "CREATE TABLE IF NOT EXISTS campaign_did_map (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            campaign_id INT NOT NULL,
            did_id INT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_campaign_did (campaign_id, did_id),
            KEY idx_company_campaign (company_id, campaign_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        mysqli_query($this->conn, $sql1);
        mysqli_query($this->conn, $sql2);
        mysqli_query($this->conn, $sql3);
    }

    private function ensureCampaignStatusAuditTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS campaign_status_audit (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            campaign_id INT NOT NULL,
            campaign_name VARCHAR(255) DEFAULT NULL,
            previous_status VARCHAR(20) DEFAULT NULL,
            new_status VARCHAR(20) NOT NULL,
            changed_by_user_id INT DEFAULT NULL,
            changed_by_email VARCHAR(255) DEFAULT NULL,
            changed_by_role VARCHAR(50) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            forwarded_ip VARCHAR(255) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            request_uri VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_company_campaign_created (company_id, campaign_id, created_at),
            KEY idx_changed_by_user (changed_by_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        mysqli_query($this->conn, $sql);
    }

    private function ensureDispositionHistoryTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS disposition_history (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            company_id INT NOT NULL,
            campaign_id INT DEFAULT NULL,
            campaignnumber_id INT NOT NULL,
            phone_e164 VARCHAR(32) DEFAULT NULL,
            action_type VARCHAR(50) NOT NULL DEFAULT 'updated',
            previous_disposition VARCHAR(255) DEFAULT NULL,
            new_disposition VARCHAR(255) DEFAULT NULL,
            previous_notes LONGTEXT DEFAULT NULL,
            new_notes LONGTEXT DEFAULT NULL,
            changed_by_user_id INT DEFAULT NULL,
            changed_by_email VARCHAR(255) DEFAULT NULL,
            changed_by_role VARCHAR(50) DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            forwarded_ip VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_contact_created (campaignnumber_id, created_at),
            KEY idx_company_campaign (company_id, campaign_id),
            KEY idx_changed_by (changed_by_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        @mysqli_query($this->conn, $sql);
    }

    public function isOutboundPrefixEnabled($company_id)
    {
        $company_id = intval($company_id);
        if ($company_id <= 0) {
            return false;
        }

        $query = "SELECT outbound_prefix FROM pbxdetail WHERE company_id = $company_id LIMIT 1";
        $result = mysqli_query($this->conn, $query);
        if ($result && ($row = mysqli_fetch_assoc($result))) {
            return isset($row['outbound_prefix']) && $row['outbound_prefix'] === 'Yes';
        }

        return false;
    }

    public function getOutboundPrefixByCompany()
    {
        $data = [];
        $query = "SELECT company_id, outbound_prefix FROM pbxdetail";
        $result = mysqli_query($this->conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $cid = (int)$row['company_id'];
                $data[$cid] = isset($row['outbound_prefix']) && $row['outbound_prefix'] === 'Yes';
            }
        }

        return $data;
    }
	
	public function htmlvalidation($form_data){
		$form_data = trim( stripslashes( htmlspecialchars( $form_data ) ) );
		$form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
		return $form_data;
	}

    private function hasColumn($table, $column)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
        $sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
        $res = mysqli_query($this->conn, $sql);
        return ($res && mysqli_num_rows($res) > 0);
    }

    private function hasTable($table)
    {
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $sql = "SHOW TABLES LIKE '$table'";
        $res = mysqli_query($this->conn, $sql);
        return ($res && mysqli_num_rows($res) > 0);
    }

    private function normalizeTimezone($timezone)
    {
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

    private function getCompanyTimezone($companyId)
    {
        $companyId = intval($companyId);
        if ($companyId <= 0 || !$this->hasColumn('pbxdetail', 'timezone')) {
            return 'UTC';
        }

        $query = mysqli_query($this->conn, "SELECT timezone FROM pbxdetail WHERE company_id = $companyId LIMIT 1");
        if ($query && mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            return $this->normalizeTimezone($row['timezone'] ?? 'UTC');
        }

        return 'UTC';
    }

    private function getUtcRangeForLocalDateFilter($companyId, $filter, $customStart = '', $customEnd = '')
    {
        $timezoneName = $this->getCompanyTimezone($companyId);
        $localTimezone = new DateTimeZone($timezoneName);
        $utcTimezone = new DateTimeZone('UTC');
        $filter = strtolower(trim((string)$filter));

        try {
            if ($filter === 'custom') {
                $customStart = trim((string)$customStart);
                $customEnd = trim((string)$customEnd);
                if ($customStart === '' || $customEnd === '') {
                    return null;
                }

                $startLocal = new DateTime(str_replace('T', ' ', $customStart), $localTimezone);
                $endLocal = new DateTime(str_replace('T', ' ', $customEnd), $localTimezone);
            } elseif ($filter === 'yesterday') {
                $startLocal = new DateTime('yesterday', $localTimezone);
                $endLocal = clone $startLocal;
                $startLocal->setTime(0, 0, 0);
                $endLocal->setTime(23, 59, 59);
            } elseif ($filter === 'this_week') {
                $startLocal = new DateTime('monday this week', $localTimezone);
                $endLocal = new DateTime('sunday this week', $localTimezone);
                $startLocal->setTime(0, 0, 0);
                $endLocal->setTime(23, 59, 59);
            } elseif ($filter === 'last_week') {
                $startLocal = new DateTime('monday last week', $localTimezone);
                $endLocal = new DateTime('sunday last week', $localTimezone);
                $startLocal->setTime(0, 0, 0);
                $endLocal->setTime(23, 59, 59);
            } elseif ($filter === 'this_month') {
                $startLocal = new DateTime('first day of this month', $localTimezone);
                $endLocal = new DateTime('last day of this month', $localTimezone);
                $startLocal->setTime(0, 0, 0);
                $endLocal->setTime(23, 59, 59);
            } elseif ($filter === 'all') {
                return null;
            } else {
                $startLocal = new DateTime('today', $localTimezone);
                $endLocal = clone $startLocal;
                $startLocal->setTime(0, 0, 0);
                $endLocal->setTime(23, 59, 59);
            }

            $startUtc = clone $startLocal;
            $endUtc = clone $endLocal;
            $startUtc->setTimezone($utcTimezone);
            $endUtc->setTimezone($utcTimezone);

            return [
                'start' => $startUtc->format('Y-m-d H:i:s'),
                'end' => $endUtc->format('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    private function resolveRequestIp()
    {
        $remoteAddr = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        $forwardedFor = trim((string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));

        if ($forwardedFor !== '') {
            $parts = array_map('trim', explode(',', $forwardedFor));
            foreach ($parts as $part) {
                if (filter_var($part, FILTER_VALIDATE_IP)) {
                    return [$part, $forwardedFor];
                }
            }
        }

        if (filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return [$remoteAddr, $forwardedFor];
        }

        return ['', $forwardedFor];
    }

    private function logCampaignStatusAudit(array $campaignRow, $previousStatus, $newStatus)
    {
        $campaignId = intval($campaignRow['id'] ?? 0);
        $companyId = intval($campaignRow['company_id'] ?? 0);
        if ($campaignId <= 0 || $companyId <= 0) {
            return;
        }

        $userId = intval($_SESSION['zid'] ?? 0);
        $userEmail = trim((string)($_SESSION['ename'] ?? ''));
        $userRole = trim((string)($_SESSION['erole'] ?? ($_SESSION['role'] ?? '')));
        $campaignName = trim((string)($campaignRow['name'] ?? ''));
        list($ipAddress, $forwardedIp) = $this->resolveRequestIp();
        $userAgent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $requestUri = trim((string)($_SERVER['REQUEST_URI'] ?? ''));

        $campaignName = mysqli_real_escape_string($this->conn, $campaignName);
        $previousStatus = mysqli_real_escape_string($this->conn, (string)$previousStatus);
        $newStatus = mysqli_real_escape_string($this->conn, (string)$newStatus);
        $userEmailSql = $userEmail !== '' ? "'" . mysqli_real_escape_string($this->conn, $userEmail) . "'" : "NULL";
        $userRoleSql = $userRole !== '' ? "'" . mysqli_real_escape_string($this->conn, $userRole) . "'" : "NULL";
        $ipAddressSql = $ipAddress !== '' ? "'" . mysqli_real_escape_string($this->conn, $ipAddress) . "'" : "NULL";
        $forwardedIpSql = $forwardedIp !== '' ? "'" . mysqli_real_escape_string($this->conn, $forwardedIp) . "'" : "NULL";
        $userAgentSql = $userAgent !== '' ? "'" . mysqli_real_escape_string($this->conn, substr($userAgent, 0, 500)) . "'" : "NULL";
        $requestUriSql = $requestUri !== '' ? "'" . mysqli_real_escape_string($this->conn, substr($requestUri, 0, 255)) . "'" : "NULL";
        $userIdSql = $userId > 0 ? $userId : "NULL";

        $sql = "
            INSERT INTO campaign_status_audit (
                company_id,
                campaign_id,
                campaign_name,
                previous_status,
                new_status,
                changed_by_user_id,
                changed_by_email,
                changed_by_role,
                ip_address,
                forwarded_ip,
                user_agent,
                request_uri,
                created_at
            ) VALUES (
                $companyId,
                $campaignId,
                '$campaignName',
                '$previousStatus',
                '$newStatus',
                $userIdSql,
                $userEmailSql,
                $userRoleSql,
                $ipAddressSql,
                $forwardedIpSql,
                $userAgentSql,
                $requestUriSql,
                UTC_TIMESTAMP()
            )
        ";

        @mysqli_query($this->conn, $sql);
    }

    private function resolveSessionAgentId()
    {
        $userId = intval($_SESSION['zid'] ?? 0);
        if ($userId <= 0) {
            return 0;
        }

        $agentByColumn = 0;
        if ($this->hasColumn('users', 'agentid')) {
            $res = mysqli_query($this->conn, "SELECT agentid FROM users WHERE id = $userId LIMIT 1");
            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                $agentByColumn = intval($row['agentid'] ?? 0);
                if ($agentByColumn > 0) {
                    return $agentByColumn;
                }
            }
        }

        $select = [];
        if ($this->hasColumn('users', 'userno')) {
            $select[] = 'userno';
        }
        if ($this->hasColumn('users', 'user_id')) {
            $select[] = 'user_id';
        }
        if ($this->hasColumn('users', 'company_id')) {
            $select[] = 'company_id';
        }
        if (empty($select)) {
            return 0;
        }

        $res = mysqli_query($this->conn, "SELECT " . implode(', ', $select) . " FROM users WHERE id = $userId LIMIT 1");
        if (!$res || mysqli_num_rows($res) === 0) {
            return 0;
        }
        $user = mysqli_fetch_assoc($res);

        $companyId = intval($user['company_id'] ?? ($_SESSION['company_id'] ?? 0));
        $safeCompany = $companyId > 0 ? " AND company_id = $companyId" : '';

        $userNo = trim((string)($user['userno'] ?? ''));
        if ($userNo !== '') {
            $safeUserNo = mysqli_real_escape_string($this->conn, $userNo);
            $aRes = mysqli_query($this->conn, "SELECT agent_id FROM agent WHERE agent_ext = '$safeUserNo' $safeCompany LIMIT 1");
            if ($aRes && mysqli_num_rows($aRes) > 0) {
                $aRow = mysqli_fetch_assoc($aRes);
                $agentId = intval($aRow['agent_id'] ?? 0);
                if ($agentId > 0) {
                    return $agentId;
                }
            }
        }

        $user3cxId = intval($user['user_id'] ?? 0);
        if ($user3cxId > 0) {
            $aRes = mysqli_query($this->conn, "SELECT agent_id FROM agent WHERE `3cx_id` = $user3cxId $safeCompany LIMIT 1");
            if ($aRes && mysqli_num_rows($aRes) > 0) {
                $aRow = mysqli_fetch_assoc($aRes);
                return intval($aRow['agent_id'] ?? 0);
            }
        }

        return 0;
    }

    private function normalizeImportedPhone($rawNumber)
    {
        $rawNumber = trim((string)$rawNumber);
        if ($rawNumber === '') {
            return '';
        }

        $hasLeadingPlus = (strpos($rawNumber, '+') === 0);
        $digitsOnly = preg_replace('/\D+/', '', $rawNumber);
        if ($digitsOnly === '') {
            return '';
        }

        return $hasLeadingPlus ? ('+' . $digitsOnly) : $digitsOnly;
    }

    private function resolveImportedSchedule($schDate, $schTime, $companyTimezone = 'UTC')
    {
        $schDate = trim((string)$schDate);
        $schTime = trim((string)$schTime);
        $companyTimezone = $this->normalizeTimezone($companyTimezone);
        $localTimezone = new DateTimeZone($companyTimezone);
        $utcTimezone = new DateTimeZone('UTC');

        if ($schDate === '' && $schTime === '') {
            return ['state' => 'READY', 'next_call_at_sql' => 'UTC_TIMESTAMP()', 'was_past' => false];
        }

        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $schDate, $matches)) {
            $schDate = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
        }

        if ($schDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $schDate)) {
            return ['state' => 'READY', 'next_call_at_sql' => 'UTC_TIMESTAMP()', 'was_past' => false];
        }

        if ($schTime === '') {
            $schTime = '09:00:00';
        } elseif (preg_match('/^\d{2}:\d{2}$/', $schTime)) {
            $schTime .= ':00';
        }

        try {
            $scheduledLocal = new DateTime($schDate . ' ' . $schTime, $localTimezone);
        } catch (Exception $e) {
            return ['state' => 'READY', 'next_call_at_sql' => 'UTC_TIMESTAMP()', 'was_past' => false];
        }

        $nowLocal = new DateTime('now', $localTimezone);
        if ($scheduledLocal < $nowLocal) {
            return ['state' => 'READY', 'next_call_at_sql' => 'UTC_TIMESTAMP()', 'was_past' => true];
        }

        $scheduledUtc = clone $scheduledLocal;
        $scheduledUtc->setTimezone($utcTimezone);
        $dateTimeValue = $scheduledUtc->format('Y-m-d H:i:s');

        return [
            'state' => 'SCHEDULED',
            'next_call_at_sql' => "'" . mysqli_real_escape_string($this->conn, $dateTimeValue) . "'",
            'was_past' => false
        ];
    }
	
	public function updatestatus($id, $status) 
	{
        $id = intval($id);
        if ($id <= 0) {
            return false;
        }

        $statusText = ($status == '1') ? 'Running' : 'Stop';
        $statusTextSql = mysqli_real_escape_string($this->conn, $statusText);
        $userId = intval($_SESSION['zid'] ?? 0);
        $updatedBySql = $userId > 0 ? ", updated_by = $userId" : '';

        $campaignRes = mysqli_query($this->conn, "SELECT id, company_id, name, status FROM campaign WHERE id = $id LIMIT 1");
        if (!$campaignRes || mysqli_num_rows($campaignRes) === 0) {
            return false;
        }

        $campaignRow = mysqli_fetch_assoc($campaignRes);
        $previousStatus = trim((string)($campaignRow['status'] ?? ''));

        $sql = "UPDATE campaign SET status = '$statusTextSql', statusupdate = UTC_TIMESTAMP() $updatedBySql WHERE id = $id";
        $updated = mysqli_query($this->conn, $sql) ? true : false;

        if ($updated) {
            $this->logCampaignStatusAudit($campaignRow, $previousStatus, $statusText);
        }

        return $updated;
    }
	
	public function getcampaign($company_id = null)
    {
        $query = "
            SELECT c.*, 
                   u1.user_email as created_by_name, 
                   u2.user_email as updated_by_name 
            FROM campaign c
            LEFT JOIN users u1 ON c.created_by = u1.id
            LEFT JOIN users u2 ON c.updated_by = u2.id
            WHERE c.is_deleted = 0
        ";
        
        if ($company_id !== null) {
            $company_id = intval($company_id);
            $query .= " AND c.company_id = $company_id";
        }
        
        $result = mysqli_query($this->conn, $query);
    
        $data = [];
    
        if ($result && mysqli_num_rows($result) > 0) {
            $index = 1;
            while ($row = mysqli_fetch_assoc($result)) {
                // Save real DB ID as campaignid
                $row['campaignid'] = $row['id'];
    
                // Replace 'id' with index
                $row['id'] = $index++;
    
                // Decode and reformat weekdays
                $row['weekdays'] = json_decode($row['weekdays'], true);
                if (is_array($row['weekdays'])) {
                    $row['weekdays'] = implode(', ', $row['weekdays']);
                } else {
                     $row['weekdays'] = '';
                }
    
                $data[] = $row;
            }
        }
    
        return json_encode($data);
    }
	
    public function addCampaignSql($name, $routeto, $returncall, $weekdays, $starttime, $stoptime, $company_id, $created_by, $dialer_mode, $route_type, $concurrent_calls, $webhook_token = null, $dn_number = null, $dg_reception_number = null) 
	{
         if (is_array($weekdays)) {
            $weekdays = json_encode($weekdays);
        }
    
        // Escape and sanitize inputs
        $name       = mysqli_real_escape_string($this->conn, $name);
        $routeto    = $routeto != '' ? mysqli_real_escape_string($this->conn, $routeto) : 0;
        $returncall = ($returncall != '') ? $returncall : 0;
        $weekdays   = $weekdays != '' ? mysqli_real_escape_string($this->conn, $weekdays) : '';
        $starttime  = $starttime  !== '' ? "'" . mysqli_real_escape_string($this->conn, $starttime) . "'" : "NULL";
        $stoptime   = $stoptime   !== '' ? "'" . mysqli_real_escape_string($this->conn, $stoptime) . "'" : "NULL";
        $company_id = intval($company_id);
        $created_by = intval($created_by);
        
        $dialer_mode = $dialer_mode !== '' ? "'" . mysqli_real_escape_string($this->conn, $dialer_mode) . "'" : "'Power Dialer'";
        $route_type  = $route_type  !== '' ? "'" . mysqli_real_escape_string($this->conn, $route_type) . "'" : "'Queue'";
        $concurrent_calls = $concurrent_calls !== '' ? intval($concurrent_calls) : 1;
        $webhook_token = $webhook_token ? "'" . mysqli_real_escape_string($this->conn, $webhook_token) . "'" : "NULL";
        $dn_number = $dn_number ? "'" . mysqli_real_escape_string($this->conn, $dn_number) . "'" : "NULL";
        $dg_reception_number = $dg_reception_number ? "'" . mysqli_real_escape_string($this->conn, $dg_reception_number) . "'" : "NULL";
    
        // Final SQL query
        $query = "
            INSERT INTO campaign (company_id, name, routeto, dn_number, dg_reception_number, returncall, weekdays, starttime, stoptime, created_by, dialer_mode, route_type, concurrent_calls, webhook_token)
            VALUES ($company_id, '$name', '$routeto', $dn_number, $dg_reception_number, $returncall, '$weekdays', $starttime, $stoptime, $created_by, $dialer_mode, $route_type, $concurrent_calls, $webhook_token)
        ";
    
        $insert_fire = mysqli_query($this->conn, $query);
    
        if (!$insert_fire) {
            // Return SQL error for debugging
            return ['success' => false, 'error' => mysqli_error($this->conn)];
        }
    
        return  ['success' => true];
    }


    public function importnumbersql($campaignId, $filePath)
    {
        $insertCount = 0;
        $skippedCount = 0;
        $normalizedCount = 0;
        $invalidCount = 0;
        $pastScheduleAdjusted = 0;
        $campaignId = intval($campaignId);
        
        // Fetch Campaign Info (Max Attempts & Company ID)
        $campQuery = mysqli_query($this->conn, "SELECT company_id, routeto, name, returncall, created_by, updated_by FROM campaign WHERE id = $campaignId");
        
        if (!$campQuery || mysqli_num_rows($campQuery) === 0) {
            return ['success' => false, 'message' => "Campaign or Company not found."];
        }
        
        $campaignData = mysqli_fetch_assoc($campQuery);
        $companyId = $campaignData['company_id'];
        $companyTimezone = $this->getCompanyTimezone($companyId);
        $returnCall = intval($campaignData['returncall']);
        $campaignRouteTo = trim((string)($campaignData['routeto'] ?? ''));
        $campaignName = trim((string)($campaignData['name'] ?? ''));
        $maxAttempts = ($returnCall > 0) ? $returnCall : 3;
        
        $createdBy = $_SESSION['zid'] ?? 0;
        $activeUser = $_SESSION['zid'] ?? 0;

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $headers = fgetcsv($handle); // First line is header
            
            // Normalize headers
            $headers = array_map('trim', $headers);
            $headers = array_map('strtolower', $headers);

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Map headers to data
                $row = [];
                foreach ($headers as $index => $header) {
                    if (isset($data[$index])) {
                        $row[$header] = trim($data[$index]);
                    } else {
                        $row[$header] = '';
                    }
                }

                // Extract fixed fields
                $rawNumber = $row['number'] ?? '';
                $normalizedNumber = $this->normalizeImportedPhone($rawNumber);
                if (trim((string)$rawNumber) !== '' && $normalizedNumber !== trim((string)$rawNumber)) {
                    $normalizedCount++;
                }
                if ($normalizedNumber === '') {
                    $invalidCount++;
                    continue;
                }

                $number = mysqli_real_escape_string($this->conn, $normalizedNumber);
                $fname  = mysqli_real_escape_string($this->conn, $row['fname'] ?? '');
                $lname  = mysqli_real_escape_string($this->conn, $row['lname'] ?? '');
                $type   = mysqli_real_escape_string($this->conn, $row['type'] ?? '');
                $feedback = mysqli_real_escape_string($this->conn, $row['feedback'] ?? '');
                
                // Scheduling Logic
                $schDate = isset($row['scheduled_date']) ? trim($row['scheduled_date']) : '';
                $schTime = isset($row['scheduled_time']) ? trim($row['scheduled_time']) : '';
                $scheduleInfo = $this->resolveImportedSchedule($schDate, $schTime, $companyTimezone);
                $state = $scheduleInfo['state'];
                $nextCallAt = $scheduleInfo['next_call_at_sql'];
                if (!empty($scheduleInfo['was_past'])) {
                    $pastScheduleAdjusted++;
                }

                // Extract Extra Data
                $exdata = [];
                $fixedFields = ['number', 'fname', 'lname', 'type', 'feedback', 'scheduled_date', 'scheduled_time'];
                foreach ($row as $key => $val) {
                    if (!in_array($key, $fixedFields)) {
                        $exdata[$key] = $val;
                    }
                }
                $exdataJson = mysqli_real_escape_string($this->conn, json_encode($exdata));

                if (!empty($number)) {
                    // Check DNC
                    $isDnc = 0;
                    $dncCheck = "SELECT id FROM dialer_dnc WHERE phone_raw='$number' AND company_id='$companyId' LIMIT 1";
                    $dncRes = mysqli_query($this->conn, $dncCheck);
                    if ($dncRes && mysqli_num_rows($dncRes) > 0) {
                        $isDnc = 1;
                        $state = 'DNC';
                        $nextCallAt = "NULL";
                    }
                    
                    // Unique Check: Last 8 digits match for same campaign
                    $last8 = substr($number, -8);
                    $checkQuery = "SELECT id FROM campaignnumbers WHERE campaignid = $campaignId AND RIGHT(phone_e164, 8) = '$last8'";
                    $checkResult = mysqli_query($this->conn, $checkQuery);

                    if (mysqli_num_rows($checkResult) > 0) {
                        // Duplicate FOUND -> Insert to Skipped
                        // skipped table schema might need update? Or just use exdata
                        $skippedQuery = "INSERT INTO campaign_skipped_numbers 
                                        (company_id, campaignid, number, fname, lname, type, feedback, exdata)
                                        VALUES ($companyId, $campaignId, '$number', '$fname', '$lname', '$type', '$feedback', '$exdataJson')";
                        mysqli_query($this->conn, $skippedQuery);
                        $skippedCount++;
                    } else {
                        // Valid -> Insert to Campaign Numbers (New Schema)
                        // Schema: id, company_id, campaignid, phone_e164, phone_raw, first_name, last_name, exdata, state, next_call_at, max_attempts, is_dnc ...
                        $mainQuery = "INSERT INTO campaignnumbers 
                                     (company_id, campaignid, phone_e164, phone_raw, first_name, last_name, exdata, state, next_call_at, max_attempts, is_dnc, created_by, updated_by)
                                     VALUES ($companyId, $campaignId, '$number', '$number', '$fname', '$lname', '$exdataJson', '$state', $nextCallAt, $maxAttempts, $isDnc, $createdBy, $activeUser)";
                        
                        if (mysqli_query($this->conn, $mainQuery)) {
                            $campaignnumberId = intval(mysqli_insert_id($this->conn));
                            if (
                                $campaignnumberId > 0 &&
                                $state === 'SCHEDULED' &&
                                $this->hasColumn('scheduled_calls', 'id')
                            ) {
                                $scheduledFor = trim($nextCallAt, "'");
                                $safeScheduledFor = mysqli_real_escape_string($this->conn, $scheduledFor);
                                $safeTimezone = mysqli_real_escape_string($this->conn, $companyTimezone);
                                $safeQueueDn = mysqli_real_escape_string($this->conn, $campaignRouteTo);
                                $safeDisposition = $feedback !== '' ? "'" . $feedback . "'" : "NULL";
                                $safeCampaignName = mysqli_real_escape_string($this->conn, $campaignName);
                                $metaJson = mysqli_real_escape_string($this->conn, json_encode([
                                    'scheduled_via' => 'csv_import',
                                    'campaign_name' => $campaignName,
                                    'phone_e164' => $normalizedNumber,
                                    'contact_name' => trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''))
                                ]));

                                $scheduledInsert = "INSERT INTO scheduled_calls
                                    (company_id, campaign_id, campaignnumber_id, route_type, queue_dn, agent_id, agent_ext,
                                     scheduled_for, timezone, status, source_module, disposition_label, note_text, meta_json,
                                     created_by, updated_by)
                                    VALUES
                                    ($companyId, $campaignId, $campaignnumberId, 'Queue',
                                     " . ($safeQueueDn !== '' ? "'$safeQueueDn'" : "NULL") . ", NULL, NULL,
                                     '$safeScheduledFor', '$safeTimezone', 'queued', 'csv_import',
                                     $safeDisposition, NULL, '$metaJson',
                                     $createdBy, $activeUser)";

                                @mysqli_query($this->conn, $scheduledInsert);
                            }
                            $insertCount++;
                        }
                    }
                }
            }
            fclose($handle);
        }
        
        $message = "$insertCount numbers imported. $skippedCount duplicates skipped.";
        if ($normalizedCount > 0) {
            $message .= " $normalizedCount number(s) auto-cleaned to valid format.";
        }
        if ($pastScheduleAdjusted > 0) {
            $message .= " $pastScheduleAdjusted past scheduled row(s) were imported as ready contacts.";
        }
        if ($invalidCount > 0) {
            $message .= " $invalidCount invalid/empty number(s) were ignored.";
        }

        return ['success' => true, 'message' => $message];
    }


	public function delete($tblname, $condition, $op='AND'){

		$delete_data = "";

		foreach ($condition as $q_key => $q_value) {
			$delete_data = $delete_data."$q_key='$q_value' $op ";
		}

		$delete_data = rtrim($delete_data,"$op ");		
		$delete = "DELETE FROM $tblname WHERE $delete_data";
		$delete_fire = mysqli_query($this->conn, $delete);
		if($delete_fire){
			return $delete_fire;
		}
		else{
			return false;
		}

	}
	
	public function updatecampaign($id, $data)
    {
        $id = intval($id);
        $fields = [];
    
        foreach ($data as $key => $value) {
            $escaped = mysqli_real_escape_string($this->conn, $value);
            $fields[] = "`$key` = '$escaped'";
        }
    
        $setClause = implode(', ', $fields);
        $query = "UPDATE campaign SET $setClause WHERE id = $id";
    
        return mysqli_query($this->conn, $query);
    }

    public function getCompanies()
    {
        $query = "SELECT id, name FROM companies ORDER BY name ASC";
        $result = mysqli_query($this->conn, $query);
        $companies = [];
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $companies[] = $row;
            }
        }
        return $companies;
    }

public function getSkippedNumbers($company_id = null)
{
    // Fix: Join with campaign/companies to show names
    $sql = "SELECT s.*, c.name as campaign_name, co.name as company_name 
            FROM campaign_skipped_numbers s 
            LEFT JOIN campaign c ON s.campaignid = c.id
            LEFT JOIN companies co ON s.company_id = co.id
            WHERE 1=1";
            
    if ($company_id) {
        $company_id = intval($company_id);
        $sql .= " AND s.company_id = $company_id";
    }
    
    $sql .= " ORDER BY s.id DESC LIMIT 1000"; // Limit to avoid crash on huge datasets
    
    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

public function getImportLogs($company_id = null)
{
    $sql = "SELECT i.*, c.name as campaign_name, co.name as company_name, 
            (SELECT user_email FROM users WHERE users.id = i.import_by) as imported_by_name
            FROM importnum i
            LEFT JOIN campaign c ON i.campaign_id = c.id
            LEFT JOIN companies co ON i.company_id = co.id
            WHERE 1=1";
            
    if ($company_id) {
        $company_id = intval($company_id);
        $sql .= " AND i.company_id = $company_id";
    }
    
    $sql .= " ORDER BY i.import_at DESC";
    
    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

private function buildNotDialedWhere($company_id = null, $campaign_id = 0, $state_filter = '', $last_call_filter = '', $date_filter = 'today', $custom_start = '', $custom_end = '')
{
    $where = "WHERE cn.is_dnc = 0
              AND COALESCE(cn.state, '') NOT IN ('DNC', 'CLOSED', 'DISPO_SUBMITTED')
              AND (
                    (
                        cn.attempts_used > 0
                        AND (
                            COALESCE(cn.last_call_status, '') IN ('NO_ANSWER', 'FAILED', 'BUSY', 'CANCELLED', 'UNREACHABLE', 'VOICEMAIL', 'TRANSFERRED')
                            OR cn.agent_connected IS NULL
                            OR TRIM(COALESCE(cn.agent_connected, '')) = ''
                            OR cn.agent_connected = '0'
                            OR COALESCE(cn.state, '') IN ('READY', 'NOT_DIALED', 'RETRY', 'DIAL_FAILED')
                        )
                    )
                    OR
                    (
                        cn.attempts_used = 0
                    )
              )";

    if ($company_id !== null) {
        $company_id = intval($company_id);
        if ($company_id > 0) {
            $where .= " AND cn.company_id = $company_id";
        }
    }

    $campaign_id = intval($campaign_id);
    if ($campaign_id > 0) {
        $where .= " AND cn.campaignid = $campaign_id";
    }

    $state_filter = trim((string)$state_filter);
    if ($state_filter !== '') {
        $safeState = mysqli_real_escape_string($this->conn, $state_filter);
        $where .= " AND cn.state = '$safeState'";
    }

    $last_call_filter = trim((string)$last_call_filter);
    if ($last_call_filter !== '') {
        $safeLastCall = mysqli_real_escape_string($this->conn, $last_call_filter);
        $where .= " AND COALESCE(cn.last_call_status, '') = '$safeLastCall'";
    }

    $range = $this->getUtcRangeForLocalDateFilter($company_id, $date_filter, $custom_start, $custom_end);
    if ($range) {
        $where .= " AND cn.created_at >= '{$range['start']}' AND cn.created_at <= '{$range['end']}'";
    }

    return $where;
}

public function getNotDialedNumbers($company_id = null, $campaign_id = 0, $state_filter = '', $last_call_filter = '', $date_filter = 'today', $custom_start = '', $custom_end = '')
{
    $where = $this->buildNotDialedWhere($company_id, $campaign_id, $state_filter, $last_call_filter, $date_filter, $custom_start, $custom_end);

    $sql = "SELECT cn.id, cn.company_id, cn.campaignid, cn.phone_e164, cn.first_name, cn.last_name,
                   cn.state, cn.created_at, cn.next_call_at, cn.last_call_status, cn.attempts_used, cn.max_attempts,
                   c.name AS campaign_name,
                   co.name AS company_name,
                   p.timezone
            FROM campaignnumbers cn
            LEFT JOIN campaign c ON c.id = cn.campaignid
            LEFT JOIN companies co ON co.id = cn.company_id
            LEFT JOIN pbxdetail p ON p.company_id = cn.company_id
            $where
            ORDER BY 
                CASE 
                    WHEN cn.attempts_used > 0 THEN 0
                    ELSE 1
                END ASC,
                CASE 
                    WHEN COALESCE(cn.last_call_status, '') IN ('FAILED', 'CANCELLED', 'UNREACHABLE', 'DIAL_FAILED') THEN 0
                    WHEN COALESCE(cn.last_call_status, '') IN ('NO_ANSWER', 'BUSY', 'VOICEMAIL', 'TRANSFERRED') THEN 1
                    ELSE 2
                END ASC,
                COALESCE(cn.next_call_at, cn.created_at) ASC,
                cn.id DESC";

    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

public function moveNotDialedNumbersToReady($company_id = null, $campaign_id = 0, $state_filter = '', $last_call_filter = '', $date_filter = 'today', $custom_start = '', $custom_end = '')
{
    $where = $this->buildNotDialedWhere($company_id, $campaign_id, $state_filter, $last_call_filter, $date_filter, $custom_start, $custom_end);
    $where .= " AND (cn.agent_connected IS NULL OR TRIM(COALESCE(cn.agent_connected, '')) = '' OR cn.agent_connected = '0')";

    $sql = "UPDATE campaignnumbers cn
            SET cn.state = 'READY',
                cn.next_call_at = UTC_TIMESTAMP(),
                cn.locked_at = NULL,
                cn.locked_by = NULL,
                cn.lock_token = NULL,
                cn.attempts_used = CASE
                    WHEN COALESCE(cn.attempts_used, 0) >= COALESCE(cn.max_attempts, 0) AND COALESCE(cn.max_attempts, 0) > 0 THEN 0
                    ELSE cn.attempts_used
                END
            $where";

    $result = mysqli_query($this->conn, $sql);
    if (!$result) {
        return ['success' => false, 'message' => mysqli_error($this->conn)];
    }

    return [
        'success' => true,
        'updated_count' => intval(mysqli_affected_rows($this->conn))
    ];
}

public function getDialedAnsweredAgents($company_id = null)
{
    $where = "WHERE 1=1";
    if ($company_id !== null) {
        $company_id = intval($company_id);
        if ($company_id > 0) {
            $where .= " AND company_id = $company_id";
        }
    }

    $sql = "SELECT agent_id, agent_name, agent_ext
            FROM agent
            $where
            ORDER BY agent_name ASC, agent_ext ASC";

    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

public function getDialedAnsweredCampaigns($company_id = null)
{
    $where = "WHERE is_deleted = 0";
    if ($company_id !== null) {
        $company_id = intval($company_id);
        if ($company_id > 0) {
            $where .= " AND company_id = $company_id";
        }
    }

    $sql = "SELECT id, name
            FROM campaign
            $where
            ORDER BY name ASC";

    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

public function getDialedAnsweredDispositions($company_id = null, $campaign_id = 0, $agent_id = 0)
{
    $where = "WHERE l.call_status = 'ANSWERED' AND TRIM(COALESCE(cn.last_disposition, '')) <> ''";

    if ($company_id !== null) {
        $company_id = intval($company_id);
        if ($company_id > 0) {
            $where .= " AND l.company_id = $company_id";
        }
    }

    $campaign_id = intval($campaign_id);
    if ($campaign_id > 0) {
        $where .= " AND l.campaign_id = $campaign_id";
    }

    $agent_id = intval($agent_id);
    if ($agent_id > 0) {
        $where .= " AND l.agent_id = $agent_id";
    }

    $sql = "SELECT DISTINCT cn.last_disposition
            FROM dialer_call_log l
            LEFT JOIN campaignnumbers cn ON cn.id = l.campaignnumber_id
            $where
            ORDER BY cn.last_disposition ASC";

    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = ['label' => $row['last_disposition']];
        }
    }
    return $data;
}

public function getDialedAnsweredNumbers($company_id = null, $campaign_id = 0, $agent_id = 0, $date_filter = 'today', $custom_start = '', $custom_end = '', $disposition_filter = '')
{
    $where = "WHERE l.call_status = 'ANSWERED'";

    if ($company_id !== null) {
        $company_id = intval($company_id);
        if ($company_id > 0) {
            $where .= " AND l.company_id = $company_id";
        }
    }

    $campaign_id = intval($campaign_id);
    if ($campaign_id > 0) {
        $where .= " AND l.campaign_id = $campaign_id";
    }

    $agent_id = intval($agent_id);
    if ($agent_id > 0) {
        $where .= " AND l.agent_id = $agent_id";
    }

    $disposition_filter = trim((string)$disposition_filter);
    if ($disposition_filter !== '') {
        $safeDisposition = mysqli_real_escape_string($this->conn, $disposition_filter);
        $where .= " AND cn.last_disposition = '$safeDisposition'";
    }

    $range = $this->getUtcRangeForLocalDateFilter($company_id, $date_filter, $custom_start, $custom_end);
    if ($range) {
        $where .= " AND l.started_at >= '{$range['start']}' AND l.started_at <= '{$range['end']}'";
    }

    $sql = "SELECT l.id AS log_id, l.company_id, l.campaign_id, l.campaignnumber_id, l.call_id,
                   l.call_status, l.started_at, l.ended_at,
                   CASE
                       WHEN l.started_at IS NOT NULL AND l.ended_at IS NOT NULL THEN GREATEST(TIMESTAMPDIFF(SECOND, l.started_at, l.ended_at), 0)
                       ELSE l.duration_sec
                   END AS duration_sec,
                   l.agent_id,
                   cn.phone_e164, cn.first_name, cn.last_name, cn.notes, cn.last_disposition, cn.next_call_at,
                   c.name AS campaign_name,
                   co.name AS company_name,
                   p.timezone,
                   a.agent_name,
                   a.agent_ext,
                   dm.color_code
            FROM dialer_call_log l
            LEFT JOIN campaignnumbers cn ON cn.id = l.campaignnumber_id
            LEFT JOIN campaign c ON c.id = l.campaign_id
            LEFT JOIN companies co ON co.id = l.company_id
            LEFT JOIN pbxdetail p ON p.company_id = l.company_id
            LEFT JOIN agent a ON a.agent_id = l.agent_id
            LEFT JOIN dialer_disposition_master dm ON dm.company_id = l.company_id AND dm.label = cn.last_disposition
            $where
            ORDER BY COALESCE(l.ended_at, l.started_at, l.created_at) DESC";

    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

public function getDispositionHistory($campaignnumberId = 0, $company_id = null)
{
    $campaignnumberId = intval($campaignnumberId);
    if ($campaignnumberId <= 0 || !$this->hasTable('disposition_history')) {
        return [];
    }

    $where = "WHERE dh.campaignnumber_id = $campaignnumberId";
    if ($company_id !== null) {
        $company_id = intval($company_id);
        if ($company_id > 0) {
            $where .= " AND dh.company_id = $company_id";
        }
    }

    $where .= $this->buildAgentScopeWhere('cn', true);

    $sql = "SELECT dh.id, dh.company_id, dh.campaign_id, dh.campaignnumber_id, dh.phone_e164,
                   dh.action_type, dh.previous_disposition, dh.new_disposition,
                   dh.previous_notes, dh.new_notes, dh.changed_by_user_id,
                   dh.changed_by_email, dh.changed_by_role, dh.created_at,
                   p.timezone
            FROM disposition_history dh
            LEFT JOIN campaignnumbers cn ON cn.id = dh.campaignnumber_id
            LEFT JOIN pbxdetail p ON p.company_id = dh.company_id
            $where
            ORDER BY dh.created_at DESC, dh.id DESC";

    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

private function syncCompletedScheduledCalls($company_id = null)
{
    $whereCompany = '';
    if ($company_id !== null) {
        $company_id = intval($company_id);
        if ($company_id > 0) {
            $whereCompany = " AND sc.company_id = $company_id";
        }
    }

    $sql = "UPDATE scheduled_calls sc
            INNER JOIN campaignnumbers cn ON cn.id = sc.campaignnumber_id
            SET sc.status = 'done',
                sc.completed_at = COALESCE(cn.last_call_ended_at, cn.last_attempt_at, UTC_TIMESTAMP()),
                sc.updated_at = UTC_TIMESTAMP()
            WHERE sc.status IN ('pending_agent', 'pending', 'queued', 'scheduled')
              AND cn.last_attempt_at IS NOT NULL
              AND cn.last_attempt_at >= sc.scheduled_for
              AND (cn.state <> 'SCHEDULED' OR cn.next_call_at IS NULL OR cn.next_call_at <> sc.scheduled_for)
              $whereCompany";

    mysqli_query($this->conn, $sql);
}

public function getScheduledCalls($company_id = null, $role = '', $sessionAgentId = 0)
{
    if (!$this->hasColumn('scheduled_calls', 'id')) {
        return [];
    }

    $this->syncCompletedScheduledCalls($company_id);

    $where = "WHERE sc.status IN ('pending_agent', 'pending', 'queued', 'scheduled')
              AND LOWER(COALESCE(dm.action_type, '')) IN ('callback', 'retry')";

    if ($company_id !== null) {
        $company_id = intval($company_id);
        if ($company_id > 0) {
            $where .= " AND sc.company_id = $company_id";
        }
    }

    $role = strtolower(trim((string)$role));
    $agentId = intval($sessionAgentId);
    if ($role === 'uagent') {
        if ($agentId <= 0) {
            $agentId = $this->resolveSessionAgentId();
        }
        if ($agentId > 0) {
            $where .= " AND sc.agent_id = $agentId";
        } else {
            $where .= " AND 1=0";
        }
    }

    $sql = "SELECT sc.id, sc.company_id, sc.campaign_id, sc.campaignnumber_id,
                   sc.agent_id, sc.agent_ext, sc.scheduled_for, sc.timezone,
                   sc.status, sc.source_module, sc.disposition_label, sc.note_text,
                   cn.phone_e164, cn.first_name, cn.last_name, cn.notes, cn.last_disposition, cn.next_call_at,
                   c.name AS campaign_name, c.routeto AS fallback_route_to,
                   co.name AS company_name,
                   a.agent_name,
                   COALESCE(sc.timezone, p.timezone) AS display_timezone
            FROM scheduled_calls sc
            LEFT JOIN campaignnumbers cn ON cn.id = sc.campaignnumber_id
            LEFT JOIN campaign c ON c.id = sc.campaign_id
            LEFT JOIN companies co ON co.id = sc.company_id
            LEFT JOIN agent a ON a.agent_id = sc.agent_id
            LEFT JOIN pbxdetail p ON p.company_id = sc.company_id
            LEFT JOIN dialer_disposition_master dm ON dm.company_id = sc.company_id AND dm.label = sc.disposition_label
            $where
            ORDER BY sc.scheduled_for ASC, sc.id DESC";

    $result = mysqli_query($this->conn, $sql);
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

    public function getSessionAgentId()
    {
        return $this->resolveSessionAgentId();
    }

    private function getCurrentUserAgentScope()
    {
        $role = trim((string)($_SESSION['erole'] ?? ($_SESSION['role'] ?? '')));
        $userId = intval($_SESSION['zid'] ?? 0);

        if ($role === 'uagent') {
            $agentId = $this->resolveSessionAgentId();
            return [
                'role' => $role,
                'mode' => 'selected',
                'agent_ids' => $agentId > 0 ? [$agentId] : []
            ];
        }

        if ($role !== 'manager') {
            return [
                'role' => $role,
                'mode' => 'all',
                'agent_ids' => []
            ];
        }

        if ($userId <= 0 || !$this->hasColumn('users', 'manager_agent_mode') || !$this->hasColumn('users', 'managed_agent_ids')) {
            return [
                'role' => $role,
                'mode' => 'all',
                'agent_ids' => []
            ];
        }

        $sql = "SELECT manager_agent_mode, managed_agent_ids FROM users WHERE id = $userId LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        if (!$res || mysqli_num_rows($res) === 0) {
            return [
                'role' => $role,
                'mode' => 'all',
                'agent_ids' => []
            ];
        }

        $row = mysqli_fetch_assoc($res);
        $mode = ($row['manager_agent_mode'] ?? 'all') === 'selected' ? 'selected' : 'all';
        if ($mode !== 'selected') {
            return [
                'role' => $role,
                'mode' => 'all',
                'agent_ids' => []
            ];
        }

        $decoded = json_decode((string)($row['managed_agent_ids'] ?? ''), true);
        $agentIds = [];
        if (is_array($decoded)) {
            foreach ($decoded as $id) {
                $id = intval($id);
                if ($id > 0) {
                    $agentIds[] = $id;
                }
            }
        }

        return [
            'role' => $role,
            'mode' => 'selected',
            'agent_ids' => array_values(array_unique($agentIds))
        ];
    }

    private function buildAgentScopeWhere($alias = 'cn', $includeUnassigned = false)
    {
        $scope = $this->getCurrentUserAgentScope();
        if (($scope['mode'] ?? 'all') !== 'selected') {
            return '';
        }

        $ids = array_map('intval', $scope['agent_ids'] ?? []);
        $ids = array_values(array_filter($ids, function ($id) {
            return $id > 0;
        }));

        if (empty($ids)) {
            return " AND 1 = 0";
        }

        $condition = "$alias.agent_connected IN (" . implode(',', $ids) . ")";
        if ($includeUnassigned) {
            $condition = "($condition OR $alias.agent_connected IS NULL OR $alias.agent_connected = '' OR $alias.agent_connected = '0')";
        }

        return " AND $condition";
    }
    public function checkDuplicateCampaign($name, $company_id)
    {
        $name = mysqli_real_escape_string($this->conn, $name);
        $company_id = intval($company_id);
        
        $query = "SELECT id FROM campaign WHERE name = '$name' AND company_id = $company_id AND is_deleted = 0";
        $result = mysqli_query($this->conn, $query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return true; // Exists
        }
        return false;
    }
    
    public function deleteCampaign($id)
    {
        $id = intval($id);
        $query = "UPDATE campaign SET is_deleted = 1 WHERE id = $id";
        return mysqli_query($this->conn, $query);
    }
    
    public function getCampaignStatus($id)
    {
        $id = intval($id);
        $query = "SELECT status FROM campaign WHERE id = $id";
        $result = mysqli_query($this->conn, $query);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row['status'];
        }
        return false;
    }
    
    public function logImport($campaignId, $originalName, $tempName, $userId)
    {
        $campaignId = intval($campaignId);
        $userId = intval($userId);
        
        // Fetch Company ID
        $companyQuery = mysqli_query($this->conn, "SELECT company_id FROM campaign WHERE id = $campaignId");
        if ($companyQuery && $row = mysqli_fetch_assoc($companyQuery)) {
            $companyId = $row['company_id'];
            
            $originalName = mysqli_real_escape_string($this->conn, $originalName);
            $tempName = mysqli_real_escape_string($this->conn, $tempName);
            
            $query = "INSERT INTO importnum (company_id, campaign_id, importfilename, tempname, import_by) 
                      VALUES ($companyId, $campaignId, '$originalName', '$tempName', $userId)";
            
            mysqli_query($this->conn, $query);
        }
    }
	

	
}	
?>
