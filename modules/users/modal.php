<?php
/* Modulename_modal */
Class Users_modal{
	
	
	public function __construct()
	{
		$this->conn = ConnectDB();
		$this->ensureUsersColumns();
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

	private function ensureUsersColumns()
	{
		$missingSql = [];
		if (!$this->hasColumn('users', 'manager_agent_mode')) {
			$missingSql[] = "ALTER TABLE users ADD manager_agent_mode VARCHAR(20) NULL";
		}
		if (!$this->hasColumn('users', 'managed_agent_ids')) {
			$missingSql[] = "ALTER TABLE users ADD managed_agent_ids TEXT NULL";
		}
		foreach ($missingSql as $sql) {
			@mysqli_query($this->conn, $sql);
		}
	}

	public function loginNameExists($loginName, $excludeUserId = 0)
	{
		$loginName = trim((string)$loginName);
		if ($loginName === '') {
			return false;
		}

		$loginEsc = mysqli_real_escape_string($this->conn, $loginName);
		$whereParts = array();
		if ($this->hasColumn('users', 'user_email')) {
			$whereParts[] = "LOWER(user_email) = LOWER('$loginEsc')";
		}
		if ($this->hasColumn('users', 'email')) {
			$whereParts[] = "LOWER(email) = LOWER('$loginEsc')";
		}

		if (empty($whereParts)) {
			return false;
		}

		$sql = "SELECT id FROM users WHERE (" . implode(' OR ', $whereParts) . ")";
		$excludeUserId = intval($excludeUserId);
		if ($excludeUserId > 0) {
			$sql .= " AND id != $excludeUserId";
		}
		$sql .= " LIMIT 1";

		$res = mysqli_query($this->conn, $sql);
		return ($res && mysqli_num_rows($res) > 0);
	}

	public function getUsersList($company_id = null, $is_super_admin = false, $current_user_role = '')
	{
		$hasUserEmail = $this->hasColumn('users', 'user_email');
		$hasEmail = $this->hasColumn('users', 'email');
		$hasUserType = $this->hasColumn('users', 'user_type');
		$hasRole = $this->hasColumn('users', 'role');
		$hasAgentId = $this->hasColumn('users', 'agentid');
		$hasCompanyId = $this->hasColumn('users', 'company_id');

		if ($hasUserEmail && $hasEmail) {
			$emailExpr = "COALESCE(NULLIF(u.user_email, ''), u.email)";
		} elseif ($hasUserEmail) {
			$emailExpr = "u.user_email";
		} elseif ($hasEmail) {
			$emailExpr = "u.email";
		} else {
			$emailExpr = "''";
		}

		if ($hasUserType && $hasRole) {
			$roleExpr = "COALESCE(NULLIF(u.user_type, ''), u.role)";
		} elseif ($hasUserType) {
			$roleExpr = "u.user_type";
		} elseif ($hasRole) {
			$roleExpr = "u.role";
		} else {
			$roleExpr = "''";
		}

		// Build FROM clause with conditional JOIN
		$select = "SELECT u.id, $emailExpr AS email, $roleExpr AS role";
		
		if ($hasCompanyId) {
			if ($hasAgentId) {
				// If both company_id and agentid exist, use COALESCE
				$select .= ", COALESCE(NULLIF(u.company_id, 0), a.company_id) AS resolved_company_id";
			} else {
				// Only company_id exists
				$select .= ", u.company_id AS resolved_company_id";
			}
		} else {
			// No company_id in users table, use agent table
			$select .= ", a.company_id AS resolved_company_id";
		}

		$select .= " FROM users u";
		
		// Only add JOIN if agentid column exists
		if ($hasAgentId) {
			$select .= " LEFT JOIN agent a ON a.agent_id = u.agentid";
		}

		if (!$is_super_admin && $company_id !== null && intval($company_id) > 0) {
			$cid = intval($company_id);
			$select .= " WHERE ";
			
			if ($hasCompanyId && $hasAgentId) {
				// Both columns exist: check company_id or fallback to agent's company_id
				$select .= "(u.company_id = $cid OR (COALESCE(u.company_id,0) = 0 AND a.company_id = $cid))";
			} elseif ($hasCompanyId) {
				// Only company_id exists
				$select .= "u.company_id = $cid";
			} else {
				// Only agent table has company_id
				$select .= "a.company_id = $cid";
			}
			
			// Build role filter based on current user type:
			// super_admin: exclude super_admin
			// company_admin: exclude super_admin and company_admin (see only manager and uagent)
			// manager: exclude super_admin, company_admin, and manager (see only uagent)
			// uagent: see nothing (shouldn't reach here, filtered at access control level)
			
			$excludedRoles = array('super_admin');
			if ($current_user_role !== 'super_admin') {
				$excludedRoles[] = 'company_admin'; // non-super users don't see admins
				if ($current_user_role === 'manager') {
					$excludedRoles[] = 'manager'; // managers only see uagents
				}
			}
			
			$rolesIn = "'" . implode("','", $excludedRoles) . "'";
			$select .= " AND ($roleExpr) NOT IN ($rolesIn)";
		}
		
		$select .= " ORDER BY u.id DESC";

		// DEBUG: Log the query
		error_log("=== getUsersList() DEBUG ===");
		error_log("Columns - hasAgentId: " . ($hasAgentId ? 'YES' : 'NO') . ", hasCompanyId: " . ($hasCompanyId ? 'YES' : 'NO'));
		error_log("is_super_admin: " . ($is_super_admin ? 'YES' : 'NO'));
		error_log("current_user_role: " . ($current_user_role ?? 'NULL'));
		error_log("company_id param: " . ($company_id ?? 'NULL'));
		error_log("Full SQL Query:\n" . $select);

		$select_fire = mysqli_query($this->conn, $select);
		if (!$select_fire) {
			error_log("MySQL Error: " . mysqli_error($this->conn));
			error_log("=== END getUsersList() DEBUG ===");
			return false;
		}

		$row_count = mysqli_num_rows($select_fire);
		error_log("Query returned " . $row_count . " rows");

		if ($row_count > 0) {
			$select_fetch = mysqli_fetch_all($select_fire, MYSQLI_ASSOC);
			error_log("Fetched " . count($select_fetch) . " records");
			error_log("First record: " . json_encode($select_fetch[0]));
			error_log("=== END getUsersList() DEBUG ===");
			return $select_fetch ?: false;
		}
		error_log("Query returned 0 rows");
		error_log("=== END getUsersList() DEBUG ===");
		return false;
	}

	public function getCompanies()
	{
		$data = [];
		$sql = "SELECT id, name FROM companies ORDER BY name ASC";
		$res = mysqli_query($this->conn, $sql);
		if ($res) {
			while ($row = mysqli_fetch_assoc($res)) {
				$data[] = [
					'id' => intval($row['id']),
					'name' => $row['name']
				];
			}
		}
		return $data;
	}
	
	public function agentassoc($tblname, $company_id = null, $is_super_admin = false){
		$select = "SELECT * FROM $tblname";
		if (!$is_super_admin && $company_id !== null) {
			$company_id = intval($company_id);
			$select .= " WHERE company_id = $company_id";
		}
		$select .= " ORDER BY agent_name ASC";
		$select_fire = mysqli_query($this->conn,$select);
		if(mysqli_num_rows($select_fire) > 0){
			$select_fetch = mysqli_fetch_all($select_fire, MYSQLI_ASSOC);
			if($select_fetch){
				//print_r($select);
				return $select_fetch;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}

	public function isAgentInCompany($agent_id, $company_id)
	{
		$agent_id = intval($agent_id);
		$company_id = intval($company_id);
		if ($agent_id <= 0 || $company_id <= 0) {
			return false;
		}

		$sql = "SELECT agent_id FROM agent WHERE agent_id = $agent_id AND company_id = $company_id LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		return ($res && mysqli_num_rows($res) > 0);
	}

	public function getAgentCompanyId($agent_id)
	{
		$agent_id = intval($agent_id);
		if ($agent_id <= 0) {
			return 0;
		}
		$sql = "SELECT company_id FROM agent WHERE agent_id = $agent_id LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		if ($res && mysqli_num_rows($res) > 0) {
			$row = mysqli_fetch_assoc($res);
			return intval($row['company_id'] ?? 0);
		}
		return 0;
	}

	public function userExistsInCompany($user_id, $company_id)
	{
		$user_id = intval($user_id);
		$company_id = intval($company_id);
		if ($user_id <= 0 || $company_id <= 0) {
			return false;
		}
		if (!$this->hasColumn('users', 'company_id')) {
			return true;
		}

		$sql = "SELECT id FROM users WHERE id = $user_id AND company_id = $company_id LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		return ($res && mysqli_num_rows($res) > 0);
	}
	
	public function insert($tblname, $filed_data){

		$query_data = "";

		foreach ($filed_data as $q_key => $q_value) {
			$query_data = $query_data."$q_key='$q_value',";
		}
		$query_data = rtrim($query_data,",");

		$query = "INSERT INTO $tblname SET $query_data";
		$insert_fire = mysqli_query($this->conn, $query);
		
		if($insert_fire){
			return $query; 
			//$insert_fire;
		}
		else{
			return false;
		}

	}

	public function select_assoc($tblname, $condition, $op='AND'){

		$field_op = "";
		foreach ($condition as $q_key => $q_value) {
			$field_op = $field_op."$q_key='$q_value' $op ";
		}
		$field_op = rtrim($field_op,"$op ");

		$select_assoc = "SELECT * FROM $tblname WHERE $field_op";
		$select_assoc_query = mysqli_query($this->conn, $select_assoc);
		if($select_assoc_query && mysqli_num_rows($select_assoc_query) > 0){
			if(mysqli_num_rows($select_assoc_query) == 1)
			{
				$select_assoc_fire = mysqli_fetch_assoc($select_assoc_query);
				if($select_assoc_fire){
					if(isset($select_assoc_fire['agentid']) && !empty($select_assoc_fire['agentid']) AND $select_assoc_fire!= NULL)
					{	
					$select_assoc_fire['agentid'] = $this->selectagentno($select_assoc_fire['agentid']);
					return $select_assoc_fire;
					}
					else
					{
						return $select_assoc_fire;
					}
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}
		else{	
			return false;
		}

	}

	public function getResolvedUserById($user_id)
	{
		$user_id = intval($user_id);
		if ($user_id <= 0) {
			return false;
		}

		$emailExpr = "''";
		if ($this->hasColumn('users', 'user_email') && $this->hasColumn('users', 'email')) {
			$emailExpr = "COALESCE(NULLIF(user_email,''), email)";
		} elseif ($this->hasColumn('users', 'user_email')) {
			$emailExpr = "user_email";
		} elseif ($this->hasColumn('users', 'email')) {
			$emailExpr = "email";
		}

		$roleExpr = "''";
		if ($this->hasColumn('users', 'user_type') && $this->hasColumn('users', 'role')) {
			$roleExpr = "COALESCE(NULLIF(user_type,''), role)";
		} elseif ($this->hasColumn('users', 'user_type')) {
			$roleExpr = "user_type";
		} elseif ($this->hasColumn('users', 'role')) {
			$roleExpr = "role";
		}

		$agentExpr = $this->hasColumn('users', 'agentid') ? "agentid" : "NULL";
		$companyExpr = $this->hasColumn('users', 'company_id') ? "company_id" : "NULL";

		$sql = "SELECT id, $emailExpr AS login_name, $roleExpr AS role_name, $agentExpr AS agent_id, $companyExpr AS company_id
			FROM users WHERE id = $user_id LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		if (!$res || mysqli_num_rows($res) === 0) {
			return false;
		}

		$row = mysqli_fetch_assoc($res);
		$row['agent_id'] = intval($row['agent_id'] ?? 0);
		$row['company_id'] = intval($row['company_id'] ?? 0);
		return $row;
	}

	public function getAgentById($agent_id)
	{
		$agent_id = intval($agent_id);
		if ($agent_id <= 0) {
			return null;
		}
		$sql = "SELECT agent_id, agent_name, agent_ext, company_id, `3cx_id` FROM agent WHERE agent_id = $agent_id LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		if (!$res || mysqli_num_rows($res) === 0) {
			return null;
		}
		return mysqli_fetch_assoc($res);
	}

	public function getAgentsByIds($agent_ids = array())
	{
		$ids = array_values(array_filter(array_map('intval', (array)$agent_ids), function($v){ return $v > 0; }));
		if (empty($ids)) {
			return array();
		}
		$sql = "SELECT agent_id, agent_name, agent_ext, company_id FROM agent WHERE agent_id IN (" . implode(',', $ids) . ") ORDER BY agent_name ASC";
		$res = mysqli_query($this->conn, $sql);
		if (!$res) {
			return array();
		}
		return mysqli_fetch_all($res, MYSQLI_ASSOC) ?: array();
	}

	public function updatePasswordAndManagerScope($userId, $passwordHash = null, $managerScope = null, $managedAgentIds = null)
	{
		$userId = intval($userId);
		if ($userId <= 0) {
			return false;
		}

		$field_val = array();
		if (!empty($passwordHash)) {
			if ($this->hasColumn('users', 'password')) {
				$field_val['password'] = $passwordHash;
			}
			if ($this->hasColumn('users', 'password_hash')) {
				$field_val['password_hash'] = $passwordHash;
			}
		}

		if ($managerScope !== null && $this->hasColumn('users', 'manager_agent_mode')) {
			$field_val['manager_agent_mode'] = ($managerScope === 'selected') ? 'selected' : 'all';
		}

		if ($managerScope !== null && $this->hasColumn('users', 'managed_agent_ids')) {
			if ($managerScope === 'selected') {
				$cleanIds = array_values(array_unique(array_filter(array_map('intval', (array)$managedAgentIds), function($v){ return $v > 0; })));
				$field_val['managed_agent_ids'] = json_encode($cleanIds);
			} else {
				$field_val['managed_agent_ids'] = null;
			}
		}

		if (empty($field_val)) {
			return true;
		}

		$condition = array('id' => $userId);
		return $this->update('users', $field_val, $condition);
	}
	
	public function selectagentno($agentid)
	{
		$sql= "SELECT `agent_ext` FROM `agent` WHERE `agent_id`= $agentid";
		$query = mysqli_query($this->conn, $sql);
		$select = mysqli_fetch_array($query,MYSQLI_NUM);
		$agentext=$select[0];
		return $agentext;
	}

	public function select($tblname, $company_id = null, $is_super_admin = false, $current_user_role = ''){
		if ($tblname === 'users') {
			return $this->getUsersList($company_id, $is_super_admin, $current_user_role);
		}

		$select = "SELECT * FROM $tblname";
		$select_fire = mysqli_query($this->conn,$select);
		if(mysqli_num_rows($select_fire) > 0){
			$select_fetch = mysqli_fetch_all($select_fire, MYSQLI_ASSOC);
			if($select_fetch){
				return $select_fetch;
			}
			else{
				return false;
			}
		}
		else{
			return false;
		}
	}

	public function insertUserWithSchema($username, $passwordHash, $role, $agentId = null, $companyId = null, $managerScope = null, $managedAgentIds = null)
	{
		$data = array();
		$role = strtolower(trim((string)$role));
		$isManagerRole = ($role === 'manager' || $role === 'company_admin');
		$isAgentRole = ($role === 'uagent');

		if ($this->hasColumn('users', 'user_email')) {
			$data['user_email'] = $username;
		}
		if ($this->hasColumn('users', 'email')) {
			$data['email'] = $username;
		}

		if ($this->hasColumn('users', 'password_hash')) {
			$data['password_hash'] = $passwordHash;
		}
		if ($this->hasColumn('users', 'password')) {
			$data['password'] = $passwordHash;
		}

		if ($this->hasColumn('users', 'user_type')) {
			$data['user_type'] = $role;
		}
		if ($this->hasColumn('users', 'role')) {
			$data['role'] = $role;
		}

		if ($agentId !== null && $this->hasColumn('users', 'agentid')) {
			$data['agentid'] = intval($agentId);
		}

		// For uagent, map telephony identity from agent table when available.
		if ($isAgentRole && $agentId !== null) {
			$agentRow = $this->getAgentById($agentId);
			if ($agentRow) {
				if ($this->hasColumn('users', 'userno')) {
					$data['userno'] = $agentRow['agent_ext'] ?? null;
				}
				if ($this->hasColumn('users', 'user_id')) {
					$cxId = isset($agentRow['3cx_id']) ? intval($agentRow['3cx_id']) : 0;
					$data['user_id'] = ($cxId > 0) ? $cxId : null;
				}
			}
		}

		if ($companyId !== null && $this->hasColumn('users', 'company_id')) {
			$data['company_id'] = intval($companyId);
		}

		if ($isManagerRole && $this->hasColumn('users', 'manager_agent_mode')) {
			$data['manager_agent_mode'] = ($managerScope === 'selected') ? 'selected' : 'all';
		}

		if ($this->hasColumn('users', 'managed_agent_ids')) {
			if ($isManagerRole && is_array($managedAgentIds) && !empty($managedAgentIds)) {
				$cleanIds = array_values(array_unique(array_map('intval', $managedAgentIds)));
				$cleanIds = array_filter($cleanIds, function($v){ return $v > 0; });
				$data['managed_agent_ids'] = json_encode($cleanIds);
			} else {
				// uagent should not store manager scope payload
				$data['managed_agent_ids'] = null;
			}
		}

		if (empty($data)) {
			return false;
		}

		return $this->insert('users', $data);
	}

	public function updateUserCredentialsWithSchema($userId, $username, $passwordHash)
	{
		$userId = intval($userId);
		if ($userId <= 0) {
			return false;
		}

		$field_val = [];
		if ($this->hasColumn('users', 'email')) {
			$field_val['email'] = $username;
		}
		if ($this->hasColumn('users', 'user_email')) {
			$field_val['user_email'] = $username;
		}
		if ($this->hasColumn('users', 'password')) {
			$field_val['password'] = $passwordHash;
		}
		if ($this->hasColumn('users', 'password_hash')) {
			$field_val['password_hash'] = $passwordHash;
		}

		if (empty($field_val)) {
			return false;
		}

		$condition = ['id' => $userId];
		return $this->update('users', $field_val, $condition);
	}

	public function getManagerScopeForUser($userId)
	{
		$userId = intval($userId);
		if ($userId <= 0) {
			return ['mode' => 'all', 'agent_ids' => []];
		}

		$modeCol = $this->hasColumn('users', 'manager_agent_mode') ? 'manager_agent_mode' : "'all' AS manager_agent_mode";
		$idsCol = $this->hasColumn('users', 'managed_agent_ids') ? 'managed_agent_ids' : "NULL AS managed_agent_ids";
		$sql = "SELECT $modeCol, $idsCol FROM users WHERE id = $userId LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		if (!$res || mysqli_num_rows($res) === 0) {
			return ['mode' => 'all', 'agent_ids' => []];
		}

		$row = mysqli_fetch_assoc($res);
		$mode = (($row['manager_agent_mode'] ?? '') === 'selected') ? 'selected' : 'all';
		$ids = [];
		$raw = $row['managed_agent_ids'] ?? '';
		if (!empty($raw)) {
			$decoded = json_decode($raw, true);
			if (is_array($decoded)) {
				$ids = array_values(array_filter(array_map('intval', $decoded), function($v){ return $v > 0; }));
			}
		}

		return ['mode' => $mode, 'agent_ids' => $ids];
	}

	public function update($tblname, $field_data, $condition, $op='AND'){

		$field_row = "";
		foreach ($field_data as $q_key => $q_value) {
			$field_row = $field_row."$q_key='$q_value',";
		}
		$field_row = rtrim($field_row,",");

		$field_op = "";

		foreach ($condition as $q_key => $q_value) {
			$field_op = $field_op."$q_key='$q_value' $op ";
		}
		$field_op = rtrim($field_op,"$op ");

		$update = "UPDATE $tblname SET $field_row WHERE $field_op";
		$update_fire = mysqli_query($this->conn, $update);
		if($update_fire){
			return $update_fire;
		}
		else{
			return false;
		}

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
	
	public function search($tblname,$search_val,$op="AND"){

		$search = "";
		foreach($search_val as $s_key => $s_value){
			$search = $search."$s_key LIKE '%$s_value%' $op ";
		}
		$search = rtrim($search, "$op ");

		$search = "SELECT * FROM $tblname WHERE $search";
		$search_query = mysqli_query($this->conn, $search);
		if(mysqli_num_rows($search_query) > 0){
			$serch_fetch = mysqli_fetch_all($search_query, MYSQLI_ASSOC);
			return $serch_fetch;
		}
		else{
			return false;
		}

	}
	
	public function checkkeyword()
	{
		if(isset($_POST['keyword']) && !empty(trim($_POST['keyword'])))
		{

			$keyword = $this->htmlvalidation($_POST['keyword']);
	
			$match_field['agent_ext'] = $keyword;
			$match_field['agent_name'] = $keyword;
			$select = $this->search("agent", $match_field, "OR");

		}
		else
			{

				$select = $this->select("agent");

			}
	}
	
}	
?>