<?php
/* Modulename_modal */
Class Agent_modal{
	
	
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

	private function resolveUserLinkValueForAgent($agentId, $companyId)
	{
		$agentId = intval($agentId);
		$companyId = intval($companyId);
		if ($agentId <= 0) {
			return 0;
		}

		if ($this->hasColumn('agent', '3cx_id')) {
			$sql = "SELECT `3cx_id` FROM `agent` WHERE `agent_id` = $agentId";
			if ($companyId > 0 && $this->hasColumn('agent', 'company_id')) {
				$sql .= " AND `company_id` = $companyId";
			}
			$sql .= " LIMIT 1";
			$res = mysqli_query($this->conn, $sql);
			if ($res && mysqli_num_rows($res) > 0) {
				$row = mysqli_fetch_assoc($res);
				$linkValue = intval($row['3cx_id'] ?? 0);
				if ($linkValue > 0) {
					return $linkValue;
				}
			}
		}

		return $agentId;
	}

	public function agentHasPortalLogin($agentId, $companyId)
	{
		$agentId = intval($agentId);
		$companyId = intval($companyId);
		if ($agentId <= 0) {
			return false;
		}

		if ($this->hasColumn('users', 'agentid')) {
			$sql = "SELECT id FROM users WHERE agentid = $agentId";
		} else if ($this->hasColumn('users', 'user_id')) {
			$linkValue = $this->resolveUserLinkValueForAgent($agentId, $companyId);
			if ($linkValue <= 0) {
				return false;
			}
			$sql = "SELECT id FROM users WHERE user_id = $linkValue";
		} else {
			return false;
		}

		if ($companyId > 0 && $this->hasColumn('users', 'company_id')) {
			$sql .= " AND company_id = $companyId";
		}
		$sql .= " LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		return ($res && mysqli_num_rows($res) > 0);
	}

	public function usernameExists($username)
	{
		$username = mysqli_real_escape_string($this->conn, $username);
		$emailCol = $this->hasColumn('users', 'user_email') ? 'user_email' : 'email';
		$sql = "SELECT id FROM users WHERE $emailCol = '$username' LIMIT 1";
		$res = mysqli_query($this->conn, $sql);
		return ($res && mysqli_num_rows($res) > 0);
	}

	public function createPortalLoginForAgent($agentId, $companyId, $username, $passwordHash)
	{
		$agentId = intval($agentId);
		$companyId = intval($companyId);
		if ($agentId <= 0 || $companyId <= 0 || $username === '' || $passwordHash === '') {
			return false;
		}

		$data = [];
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
			$data['user_type'] = 'uagent';
		}
		if ($this->hasColumn('users', 'role')) {
			$data['role'] = 'uagent';
		}
		if ($this->hasColumn('users', 'company_id')) {
			$data['company_id'] = $companyId;
		}
		if ($this->hasColumn('users', 'agentid')) {
			$data['agentid'] = $agentId;
		} else if ($this->hasColumn('users', 'user_id')) {
			$data['user_id'] = $this->resolveUserLinkValueForAgent($agentId, $companyId);
		}

		if (empty($data)) {
			return false;
		}

		return $this->insert('users', $data);
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
		if(mysqli_num_rows($select_assoc_query) > 0){
			if(mysqli_num_rows($select_assoc_query) == 1)
			{
				$select_assoc_fire = mysqli_fetch_assoc($select_assoc_query);
				if($select_assoc_fire){
					return $select_assoc_fire;
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

	public function select($tblname, $company_id = null){
		
		$where = "";
		if($company_id !== null && $this->hasColumn($tblname, 'company_id')) {
			$where = " WHERE company_id = $company_id";
		}
		$select = "SELECT * FROM $tblname" . $where;
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
	
	public function groupassoc($tblname){
		
		$select = "SELECT * FROM $tblname";
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
	
	public function search($tblname,$search_val,$op="AND",$paginationStart,$limit){

		$search = "";
		foreach($search_val as $s_key => $s_value){
			$search = $search."$s_key LIKE '%$s_value%' $op ";
		}
		$search = rtrim($search, "$op ");

		$search = "SELECT * FROM $tblname WHERE $search LIMIT $paginationStart, $limit";
		$search_query = mysqli_query($this->conn, $search);
		if(mysqli_num_rows($search_query) > 0){
			$serch_fetch = mysqli_fetch_all($search_query, MYSQLI_ASSOC);
			//$rows = mysqli_num_rows($search_query);
			//print_r($search);
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
	
	public function getgrpname($tblname,$grpid)
	{
		$sql = "SELECT `grpname` FROM $tblname WHERE id = $grpid";
		$record= mysqli_query($this->conn,$sql);
		if(mysqli_num_rows($record) > 0)
		{
			$select_fetch = mysqli_fetch_all($record, MYSQLI_ASSOC);
			if($select_fetch)
			{
				//print_r($select_fetch);
				$grpname = $select_fetch[0]['grpname'];
				return $grpname;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		
	}
	public function getgrpid($tblname,$grpid)
	{
		$sql = "SELECT `id` FROM $tblname WHERE grpname = '$grpid'";
		$record= mysqli_query($this->conn,$sql);
		//print_r($sql);
		if(mysqli_num_rows($record) > 0)
		{
			$select_fetch = mysqli_fetch_all($record, MYSQLI_ASSOC);
			if($select_fetch)
			{
				//print_r($select_fetch);
				$grpname = $select_fetch[0]['id'];
				return $grpname;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
		
	}
	
	public function recordcount($tblname){		
		$sql = "SELECT count(agent_id) AS id FROM $tblname";
		$record= mysqli_query($this->conn,$sql);
		$getcount = mysqli_fetch_all($record, MYSQLI_ASSOC);
		$allRecrods = $getcount[0]['id'];
		//echo $allRecrods;
		// Calculate total pages
		return $allRecrods;
		
	}
	
}	
?>