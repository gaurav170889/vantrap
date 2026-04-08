<?php
/* Modulename_modal */
Class Campcontact_modal{
	
	
	public function __construct()
	{
		$this->conn = ConnectDB();
		
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

    private function resolveScheduledDateTime($callbackDate, $callbackTime)
    {
        $callbackDate = trim((string)$callbackDate);
        $callbackTime = trim((string)$callbackTime);

        if ($callbackDate !== '' && $callbackTime !== '') {
            $timestamp = strtotime($callbackDate . ' ' . $callbackTime);
            if ($timestamp !== false) {
                return date('Y-m-d H:i:s', $timestamp);
            }
        }

        return date('Y-m-d H:i:s', strtotime('+1 hour'));
    }

    private function cancelPendingScheduledCalls($companyId, $campaignnumberId, $updatedBy = 0)
    {
        if (!$this->hasTable('scheduled_calls')) {
            return true;
        }

        $companyId = intval($companyId);
        $campaignnumberId = intval($campaignnumberId);
        $updatedExpr = intval($updatedBy) > 0 ? intval($updatedBy) : 'NULL';

        $sql = "UPDATE scheduled_calls
                SET status = 'cancelled',
                    cancelled_at = NOW(),
                    updated_by = $updatedExpr
                WHERE company_id = $companyId
                  AND campaignnumber_id = $campaignnumberId
                  AND status IN ('pending_agent', 'pending', 'queued', 'scheduled')";

        return mysqli_query($this->conn, $sql) !== false;
    }

    private function insertScheduledCall($contactRow, $disposition, $notes, $actionType, $scheduledFor, $updatedBy = 0)
    {
        if (!$this->hasTable('scheduled_calls')) {
            return ['success' => false, 'error' => 'scheduled_calls table not found'];
        }

        $companyId = intval($contactRow['company_id'] ?? 0);
        $campaignId = intval($contactRow['campaignid'] ?? 0);
        $campaignnumberId = intval($contactRow['id'] ?? 0);
        $agentId = intval($contactRow['agent_connected'] ?? 0);
        $agentExt = '';

        if ($agentId > 0 && $this->hasTable('agent')) {
            $agentQuery = mysqli_query($this->conn, "SELECT agent_ext FROM agent WHERE agent_id = $agentId LIMIT 1");
            if ($agentQuery && mysqli_num_rows($agentQuery) > 0) {
                $agentRow = mysqli_fetch_assoc($agentQuery);
                $agentExt = trim((string)($agentRow['agent_ext'] ?? ''));
            }
        }

        $timezone = trim((string)($_SESSION['timezone'] ?? date_default_timezone_get()));
        $contactName = trim(((string)($contactRow['first_name'] ?? '')) . ' ' . ((string)($contactRow['last_name'] ?? '')));
        $meta = [
            'action_type' => strtoupper((string)$actionType),
            'scheduled_via' => 'disposition_modal',
            'phone_e164' => (string)($contactRow['phone_e164'] ?? ''),
            'contact_name' => $contactName
        ];

        $safeDisposition = mysqli_real_escape_string($this->conn, $disposition);
        $safeScheduledFor = mysqli_real_escape_string($this->conn, $scheduledFor);
        $safeTimezone = mysqli_real_escape_string($this->conn, $timezone);
        $safeNotes = mysqli_real_escape_string($this->conn, $notes);
        $safeMeta = mysqli_real_escape_string($this->conn, json_encode($meta));
        $safeAgentExt = mysqli_real_escape_string($this->conn, $agentExt);

        $agentIdSql = $agentId > 0 ? "'$agentId'" : "NULL";
        $agentExtSql = $agentExt !== '' ? "'$safeAgentExt'" : "NULL";
        $timezoneSql = $timezone !== '' ? "'$safeTimezone'" : "NULL";
        $notesSql = trim($notes) !== '' ? "'$safeNotes'" : "NULL";
        $metaSql = $safeMeta !== '' ? "'$safeMeta'" : "NULL";
        $updatedBySql = intval($updatedBy) > 0 ? intval($updatedBy) : 'NULL';

        $query = "INSERT INTO scheduled_calls SET
                    company_id = '$companyId',
                    campaign_id = '$campaignId',
                    campaignnumber_id = '$campaignnumberId',
                    route_type = 'Agent',
                    queue_dn = NULL,
                    agent_id = $agentIdSql,
                    agent_ext = $agentExtSql,
                    scheduled_for = '$safeScheduledFor',
                    timezone = $timezoneSql,
                    status = 'pending_agent',
                    source_module = 'campcontact',
                    disposition_label = '$safeDisposition',
                    note_text = $notesSql,
                    meta_json = $metaSql,
                    created_by = $updatedBySql,
                    updated_by = $updatedBySql";

        if (!mysqli_query($this->conn, $query)) {
            return ['success' => false, 'error' => mysqli_error($this->conn)];
        }

        return ['success' => true];
    }

    private function getCurrentUserAgentScope()
    {
        $role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
        $userId = isset($_SESSION['zid']) ? intval($_SESSION['zid']) : 0;

        if ($userId <= 0) {
            return ['mode' => 'all', 'agent_ids' => []];
        }

        if ($role === 'uagent') {
            $sql = "SELECT agentid FROM users WHERE id = $userId LIMIT 1";
            $res = mysqli_query($this->conn, $sql);
            if ($res && mysqli_num_rows($res) > 0) {
                $row = mysqli_fetch_assoc($res);
                $agentId = intval($row['agentid'] ?? 0);
                if ($agentId > 0) {
                    return ['mode' => 'single', 'agent_ids' => [$agentId]];
                }
            }
            return ['mode' => 'none', 'agent_ids' => []];
        }

        if ($role === 'company_admin' || $role === 'manager') {
            if (!$this->hasColumn('users', 'manager_agent_mode') || !$this->hasColumn('users', 'managed_agent_ids')) {
                return ['mode' => 'all', 'agent_ids' => []];
            }

            $sql = "SELECT manager_agent_mode, managed_agent_ids FROM users WHERE id = $userId LIMIT 1";
            $res = mysqli_query($this->conn, $sql);
            if (!$res || mysqli_num_rows($res) === 0) {
                return ['mode' => 'all', 'agent_ids' => []];
            }

            $row = mysqli_fetch_assoc($res);
            $mode = ($row['manager_agent_mode'] ?? 'all') === 'selected' ? 'selected' : 'all';
            if ($mode !== 'selected') {
                return ['mode' => 'all', 'agent_ids' => []];
            }

            $ids = [];
            $decoded = json_decode((string)($row['managed_agent_ids'] ?? ''), true);
            if (is_array($decoded)) {
                foreach ($decoded as $id) {
                    $id = intval($id);
                    if ($id > 0) {
                        $ids[] = $id;
                    }
                }
            }
            $ids = array_values(array_unique($ids));
            if (empty($ids)) {
                return ['mode' => 'none', 'agent_ids' => []];
            }
            return ['mode' => 'selected', 'agent_ids' => $ids];
        }

        return ['mode' => 'all', 'agent_ids' => []];
    }

    private function buildAgentScopeWhere($alias = 'c', $includeUnassigned = false)
    {
        $scope = $this->getCurrentUserAgentScope();
        $alias = trim($alias) === '' ? '' : trim($alias) . '.';

        if ($scope['mode'] === 'none') {
            return " AND 1=0";
        }

        if (($scope['mode'] === 'single' || $scope['mode'] === 'selected') && !empty($scope['agent_ids'])) {
            $ids = array_map('intval', $scope['agent_ids']);
            $ids = array_filter($ids, function($v){ return $v > 0; });
            if (empty($ids)) {
                return " AND 1=0";
            }

            $condition = "{$alias}agent_connected IN (" . implode(',', $ids) . ")";
            if ($includeUnassigned && $scope['mode'] === 'selected') {
                $condition = "($condition OR {$alias}agent_connected IS NULL OR TRIM({$alias}agent_connected) = '' OR {$alias}agent_connected = '0')";
            }

            return " AND " . $condition;
        }

        return "";
    }
	
	public function deletecontacts()
	{
	    $sql = "DELETE FROM campaignnumbers WHERE DATE(iserttime) = CURDATE()";
        if (mysqli_query($this->conn, $sql)) {
            echo "success";
        } else {
            http_response_code(500);
            echo "Failed to delete contacts.";
        }
	}

    public function getFilterCompanies()
    {
        $query = "SELECT id, name FROM companies ORDER BY name ASC";
        $result = mysqli_query($this->conn, $query);

        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name']
                ];
            }
        }

        return $data;
    }

    public function getFilterCampaigns($company_id = null)
    {
        $where = "WHERE is_deleted = 0";
        if ($company_id !== null) {
            $company_id = intval($company_id);
            $where .= " AND company_id = $company_id";
        }

        $query = "SELECT id, name FROM campaign $where ORDER BY name ASC";
        $result = mysqli_query($this->conn, $query);

        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = [
                    'id' => intval($row['id']),
                    'name' => $row['name']
                ];
            }
        }

        return $data;
    }

    public function getFilterValues($company_id, $campaign_id, $type)
    {
        $company_id = ($company_id === null) ? null : intval($company_id);
        $campaign_id = intval($campaign_id);
        $type = strtolower(trim($type));

        if ($campaign_id <= 0) {
            return [];
        }

        $campaignCompanyWhere = ($company_id !== null) ? " AND company_id = $company_id" : "";
        $contactCompanyWhere = ($company_id !== null) ? " AND c.company_id = $company_id" : "";
        $contactCompanyWherePlain = ($company_id !== null) ? " AND company_id = $company_id" : "";

        if ($type === 'attempt') {
            $attemptQuery = "SELECT returncall FROM campaign WHERE id = $campaign_id $campaignCompanyWhere LIMIT 1";
            $attemptRes = mysqli_query($this->conn, $attemptQuery);
            $maxAttempts = 3;
            if ($attemptRes && mysqli_num_rows($attemptRes) > 0) {
                $row = mysqli_fetch_assoc($attemptRes);
                $maxAttempts = intval($row['returncall']);
            }
            if ($maxAttempts <= 0) {
                $maxAttempts = 3;
            }

            $values = [];
            for ($i = 1; $i <= $maxAttempts; $i++) {
                $values[] = [
                    'value' => (string)$i,
                    'label' => (string)$i
                ];
            }
            return $values;
        }

        $values = [];
        if ($type === 'agent') {
                        $agentScopeWhere = $this->buildAgentScopeWhere('c');
            $query = "SELECT DISTINCT c.agent_connected, a.agent_name
                      FROM campaignnumbers c
                      LEFT JOIN agent a ON c.agent_connected = a.agent_id
                                            WHERE c.campaignid = $campaign_id
                                                $contactCompanyWhere
                                                $agentScopeWhere
                        AND c.agent_connected IS NOT NULL
                        AND c.agent_connected <> ''
                      ORDER BY a.agent_name ASC";
            $res = mysqli_query($this->conn, $query);
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $agentId = (string)$row['agent_connected'];
                    $agentLabel = trim((string)$row['agent_name']);
                    $values[] = [
                        'value' => $agentId,
                        'label' => $agentLabel !== '' ? $agentLabel : $agentId
                    ];
                }
            }
            return $values;
        }

        $columnMap = [
            'last_outcome' => 'last_call_status',
            'state' => 'state',
            'disposition' => 'last_disposition'
        ];

        if (!isset($columnMap[$type])) {
            return [];
        }

        $col = $columnMap[$type];
        $query = "SELECT DISTINCT $col AS val
                  FROM campaignnumbers
                                    WHERE campaignid = $campaign_id
                                        $contactCompanyWherePlain
                    AND $col IS NOT NULL
                    AND TRIM($col) <> ''
                  ORDER BY val ASC";
        $res = mysqli_query($this->conn, $query);

        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $val = (string)$row['val'];
                $values[] = [
                    'value' => $val,
                    'label' => $val
                ];
            }
        }

        return $values;
    }
	
    public function getallcontact($company_id = null, $campaign_id = 0, $filter_type = '', $filter_value = '', $open_contact_id = 0) {
         $where = "WHERE 1=1";
         if($company_id !== null) {
             $where .= " AND c.company_id = $company_id";
         }

         $campaign_id = intval($campaign_id);
         if ($campaign_id > 0) {
             $where .= " AND c.campaignid = $campaign_id";
         } else {
             return json_encode([]);
         }
         
         $where .= $this->buildAgentScopeWhere('c', true);

         $filter_type = strtolower(trim((string)$filter_type));
         if ($filter_type !== '' && $filter_value !== '') {
             $safeFilterValue = mysqli_real_escape_string($this->conn, (string)$filter_value);

             if ($filter_type === 'attempt') {
                 $where .= " AND c.attempts_used = " . intval($filter_value);
             } elseif ($filter_type === 'agent') {
                 $where .= " AND c.agent_connected = '$safeFilterValue'";
             } elseif ($filter_type === 'last_outcome') {
                 $where .= " AND c.last_call_status = '$safeFilterValue'";
             } elseif ($filter_type === 'state') {
                 $where .= " AND c.state = '$safeFilterValue'";
             } elseif ($filter_type === 'disposition') {
                 $where .= " AND c.last_disposition = '$safeFilterValue'";
             }
         }

         $open_contact_id = intval($open_contact_id);
         if ($open_contact_id > 0) {
             $where .= " AND (
                            c.id = $open_contact_id
                            OR DATE(c.created_at) = CURDATE()
                            OR (
                                c.next_call_at IS NOT NULL
                                AND DATE(c.next_call_at) = CURDATE()
                            )
                          )";
         } else {
             $where .= " AND (
                            DATE(c.created_at) = CURDATE()
                            OR (
                                c.next_call_at IS NOT NULL
                                AND DATE(c.next_call_at) = CURDATE()
                            )
                          )";
         }

         // New Schema Query
         $query = "SELECT c.id, c.phone_e164, c.first_name, c.last_name, 
                     c.state, c.attempts_used, c.max_attempts,
                     c.last_call_status, c.last_call_started_at,
                     c.agent_connected, c.notes, c.last_disposition,
                     c.next_call_at,
					 a.agent_name, d.color_code
              FROM campaignnumbers c
              LEFT JOIN agent a ON c.agent_connected = a.agent_id
              LEFT JOIN dialer_disposition_master d ON c.last_disposition = d.label AND c.company_id = d.company_id
			  $where
              ORDER BY 
                  CASE 
                      WHEN c.id = $open_contact_id AND $open_contact_id > 0 THEN 0
                      WHEN c.next_call_at IS NOT NULL AND DATE(c.next_call_at) = CURDATE() THEN 1
                      WHEN DATE(c.created_at) = CURDATE() THEN 2
                      ELSE 3
                  END,
                  COALESCE(c.next_call_at, c.created_at) ASC,
                  c.id DESC
              LIMIT 2000";

        $result = mysqli_query($this->conn, $query);

        if (!$result) {
            return json_encode(['error' => mysqli_error($this->conn)]);
        }
    
        $response = [];
    
        while ($row = mysqli_fetch_assoc($result)) {
            // Merge Name
            $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
        
            $response[] = [
                'id'          => $row['id'],
                'number'      => $row['phone_e164'],
                'name'        => $fullName,
                'type'        => 'Lead',
                'feedback'    => $row['last_call_status'],
                'call_status' => $row['state'],
                'last_try'    => $row['attempts_used'] . '/' . $row['max_attempts'],
                'attempts_used' => $row['attempts_used'],
                'last_try_dt' => $row['last_call_started_at'],
                'last_try_dt' => $row['last_call_started_at'],
                'agent_name'  => $row['agent_name'] ?? '',
                'disposition' => $row['last_disposition'],
                'color_code'  => $row['color_code'] ?? '#808080',
                'notes'       => $row['notes'],
                'next_call_at'=> $row['next_call_at']
            ];
        }

        $encoded = json_encode($response);
        if ($encoded === false) {
             return json_encode(['error' => 'JSON Encode Error: ' . json_last_error_msg()]);
        }
        return $encoded;
    }

	
	public function getcampaign()
	{
	    
	     $query = "SELECT * FROM campaign";
        $result = mysqli_query($this->conn, $query);
    
        $data = [];
    
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                // Decode weekdays from JSON
                $row['weekdays'] = json_decode($row['weekdays'], true);
                $row['weekdays'] = is_array($row['weekdays']) ? implode(', ', $row['weekdays']) : '';
                $data[] = $row;
            }
        }
    
        //header('Content-Type: application/json');
        return json_encode($data);
	}
	
	public function addCampaignSql($name, $routeto, $returncall, $weekdays, $starttime, $stoptime) 
	{
         if (is_array($weekdays)) {
            $weekdays = json_encode($weekdays);
        }
    
        // Escape and sanitize inputs
        $name       = $name       !== '' ? "'" . mysqli_real_escape_string($this->conn, $name) . "'" : "NULL";
        $routeto    = $routeto    !== '' ? intval($routeto) : "NULL";
        $returncall = $returncall !== '' ? intval($returncall) : "NULL";
        $weekdays   = $weekdays   !== '' ? "'" . mysqli_real_escape_string($this->conn, $weekdays) . "'" : "NULL";
        $starttime  = $starttime  !== '' ? "'" . mysqli_real_escape_string($this->conn, $starttime) . "'" : "NULL";
        $stoptime   = $stoptime   !== '' ? "'" . mysqli_real_escape_string($this->conn, $stoptime) . "'" : "NULL";
    
        // Final SQL query
        $query = "
            INSERT INTO campaign (name, routeto, returncall, weekdays, starttime, stoptime)
            VALUES ($name, $routeto, $returncall, $weekdays, $starttime, $stoptime)
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
        
        // 1. Fetch Campaign Info (Max Attempts & Company ID)
        $campQuery = "SELECT company_id, returncall FROM campaign WHERE id='$campaignId' LIMIT 1";
        $campRes = mysqli_query($this->conn, $campQuery);
        $maxAttempts = 3;
        $companyId = 0;
        
        if($campRes && mysqli_num_rows($campRes) > 0){
             $crow = mysqli_fetch_assoc($campRes);
             $maxAttempts = intval($crow['returncall']);
             $companyId = intval($crow['company_id']);
        }
        
        if($companyId == 0 && isset($_SESSION['company_id'])) {
            $companyId = $_SESSION['company_id'];
        }

        if (($handle = fopen($filePath, "r")) !== FALSE) {
            fgetcsv($handle); // Skip header

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Formatting based on new schema
                // 0: Number (phone_e164 - NO NORMALIZATION as requested)
                // 1: First Name
                // 2: Last Name
                // 3: ExData/Type?
                
                $phone = mysqli_real_escape_string($this->conn, trim($data[0]));
                if(empty($phone)) continue;

                $fname = isset($data[1]) ? mysqli_real_escape_string($this->conn, trim($data[1])) : "";
                $lname = isset($data[2]) ? mysqli_real_escape_string($this->conn, trim($data[2])) : "";
                // $type = isset($data[3]) ? ... (Ignored for now or put in exdata?)
                
                // DNC Check
                $isDnc = 0;
                $state = 'READY';
                $nextCallAt = "NOW()";
                
                // Check if in DNC
                $dncCheck = "SELECT id FROM dialer_dnc WHERE phone_raw='$phone' AND company_id='$companyId' LIMIT 1";
                $dncRes = mysqli_query($this->conn, $dncCheck);
                if($dncRes && mysqli_num_rows($dncRes) > 0){
                    $isDnc = 1;
                    $state = 'DNC';
                    $nextCallAt = "NULL";
                }

                $query = "INSERT INTO campaignnumbers 
                          (company_id, campaignid, phone_e164, phone_raw, first_name, last_name, state, max_attempts, is_dnc, next_call_at)
                          VALUES 
                          ('$companyId', '$campaignId', '$phone', '$phone', '$fname', '$lname', '$state', '$maxAttempts', '$isDnc', $nextCallAt)
                          ON DUPLICATE KEY UPDATE updated_at=NOW()"; 

                if (mysqli_query($this->conn, $query)) {
                    $insertCount++;
                }
            }
            fclose($handle);
        }
    
        return ['success' => true, 'message' => "$insertCount numbers imported."];
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
	

	
	public function updateDispositionSql($id, $disposition, $notes, $callbackDate, $callbackTime) {
        $id = intval($id);
        $rawDisposition = trim((string)$disposition);
        $rawNotes = trim((string)$notes);

        if ($id <= 0 || $rawDisposition === '') {
            return ['success' => false, 'error' => 'Invalid disposition request'];
        }

        $safeDisposition = mysqli_real_escape_string($this->conn, $rawDisposition);
        $contactQuery = mysqli_query($this->conn, "SELECT id, notes, company_id, campaignid, agent_connected, phone_e164, first_name, last_name FROM campaignnumbers WHERE id='$id' LIMIT 1");
        if (!$contactQuery || mysqli_num_rows($contactQuery) === 0) {
            return ['success' => false, 'error' => 'Contact not found'];
        }
        $cnRow = mysqli_fetch_assoc($contactQuery);

        $actionType = 'close';
        $state = 'DISPO_SUBMITTED';
        $scheduledFor = null;
        $nextCallAt = 'NULL';

        $dispQuery = mysqli_query($this->conn, "SELECT code, action_type FROM dialer_disposition_master WHERE label='$safeDisposition' AND company_id = '" . intval($cnRow['company_id'] ?? 0) . "' LIMIT 1");
        if ($dispQuery && mysqli_num_rows($dispQuery) > 0) {
            $dRow = mysqli_fetch_assoc($dispQuery);
            $actionType = strtolower(trim((string)($dRow['action_type'] ?? '')));
        }

        if ($actionType === 'callback' || $actionType === 'retry') {
            $state = 'SCHEDULED';
            $scheduledFor = $this->resolveScheduledDateTime($callbackDate, $callbackTime);
            $nextCallAt = "'" . mysqli_real_escape_string($this->conn, $scheduledFor) . "'";
        } else if ($actionType === 'global_dnc' || $actionType === 'dnc') {
            $state = 'DNC';
        } else if ($actionType === 'close' || $actionType === 'closed') {
            $state = 'CLOSED';
        }

        $userId = isset($_SESSION['zid']) ? intval($_SESSION['zid']) : 0;
        $userName = 'Unknown';
        if ($userId > 0) {
            $uQ = mysqli_query($this->conn, "SELECT user_email FROM users WHERE id='$userId' LIMIT 1");
            if ($uQ && mysqli_num_rows($uQ) > 0) {
                $uRow = mysqli_fetch_assoc($uQ);
                $userName = $uRow['user_email'] ?? 'Unknown';
            }
        }

        $notesUpdate = '';
        if ($rawNotes !== '') {
            $timestamp = date('Y-m-d H:i');
            $existingNotes = [];
            $decoded = json_decode((string)($cnRow['notes'] ?? ''), true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $existingNotes = $decoded;
            } else if (!empty($cnRow['notes'])) {
                $existingNotes[] = [
                    'date' => '',
                    'user' => 'Legacy',
                    'note' => $cnRow['notes']
                ];
            }

            $existingNotes[] = [
                'date' => $timestamp,
                'user' => $userName,
                'note' => $rawNotes
            ];

            $jsonString = json_encode($existingNotes);
            if ($jsonString === false) {
                $jsonString = '[]';
            }
            $jsonNotes = mysqli_real_escape_string($this->conn, $jsonString);
            $notesUpdate = ", notes = '$jsonNotes'";
        }

        mysqli_begin_transaction($this->conn);

        try {
            $updateSql = "UPDATE campaignnumbers 
                          SET last_disposition='$safeDisposition', 
                              state='$state', 
                              next_call_at=$nextCallAt 
                              $notesUpdate,
                              last_call_ended_at=NOW()
                          WHERE id='$id'";

            if (!mysqli_query($this->conn, $updateSql)) {
                throw new Exception(mysqli_error($this->conn));
            }

            $companyId = intval($cnRow['company_id'] ?? 0);
            $campaignId = intval($cnRow['campaignid'] ?? 0);
            $logNotes = mysqli_real_escape_string($this->conn, $rawNotes);

            $logQ = "INSERT INTO dialer_call_log SET
                     company_id = '$companyId',
                     campaign_id = '$campaignId',
                     campaignnumber_id = '$id',
                     call_status = 'MANUAL_DISPO',
                     disposition = '$safeDisposition',
                     notes = '$logNotes',
                     started_at = NOW()";
            if (!mysqli_query($this->conn, $logQ)) {
                error_log('Dial disposition log insert failed: ' . mysqli_error($this->conn));
            }

            if (!$this->cancelPendingScheduledCalls($companyId, $id, $userId)) {
                throw new Exception(mysqli_error($this->conn));
            }

            if ($actionType === 'callback' || $actionType === 'retry') {
                $scheduleResult = $this->insertScheduledCall($cnRow, $rawDisposition, $rawNotes, $actionType, $scheduledFor, $userId);
                if (!$scheduleResult['success']) {
                    throw new Exception($scheduleResult['error'] ?? 'Unable to create scheduled call');
                }
            }

            mysqli_commit($this->conn);
            return ['success' => true, 'scheduled_for' => $scheduledFor, 'action_type' => strtoupper($actionType)];
        } catch (Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

	
}	
?>
