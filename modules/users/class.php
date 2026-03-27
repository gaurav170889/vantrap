<?php
// Modulename
Class Users{
	function __construct() {
      $this->modal = loadmodal("users");;
    }
	public function index(){
		// Check if this is an API/AJAX request
		$is_api = (isset($_GET['json']) && $_GET['json'] == '1') || 
		          (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest');
		
		$session_role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
		$is_super_admin = ($session_role === 'super_admin');
		// Use null (not 0) when company_id is absent — intval(null)=0 would wrongly trigger WHERE filtering
		$company_id = (isset($_SESSION['company_id']) && intval($_SESSION['company_id']) > 0) ? intval($_SESSION['company_id']) : null;
		
		// Access Control
		if(!isset($_SESSION['erole']) || ($_SESSION['erole'] != 'super_admin' && $_SESSION['erole'] != 'company_admin' && $_SESSION['erole'] != 'manager'))
		{
			if ($is_api) {
				header('Content-Type: application/json');
				echo json_encode(['status' => 'error', 'msg' => 'Unauthorized access']);
				exit;
			}
			echo "<script>window.location.href='".BASE_URL."?route=dashboard/index';</script>";
			exit;
		}

		// If API request, return JSON
		if ($is_api) {
			header('Content-Type: application/json');
			$data = $this->modal->select("users", $company_id, $is_super_admin, $session_role);
			echo json_encode(['status' => 'success', 'data' => $data ?: []]);
			exit;
		}

		// Otherwise render HTML page
		$_SESSION['navurl'] = 'Users';
		include(INCLUDEPATH.'modules/common/header.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');

		if($_SESSION['role']== "uagent")
		{
			include("view/notadmin.php");
		}
		else
		{
		$companies = $is_super_admin ? $this->modal->getCompanies() : [];
		$data = $this->modal->select("users", $company_id, $is_super_admin, $session_role);
		$counter = 1;
		include("view/index.php");
		}
		include('modules/common/footer_1.php');
	}
	
	public function record()
	{
		header('Content-Type: application/json');
		
		$session_role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
		$is_super_admin = ($session_role === 'super_admin');
		$company_id = (isset($_SESSION['company_id']) && intval($_SESSION['company_id']) > 0) ? intval($_SESSION['company_id']) : null;

		// DEBUG: Echo session info
		error_log("=== USERS RECORD METHOD START ===");
		error_log("Session ID: " . session_id());
		error_log("Session erole: " . ($_SESSION['erole'] ?? 'NOT SET'));
		error_log("Session role: " . ($_SESSION['role'] ?? 'NOT SET'));
		error_log("Session company_id RAW: " . ($_SESSION['company_id'] ?? 'NOT SET'));
		error_log("is_super_admin: " . ($is_super_admin ? 'YES' : 'NO'));
		error_log("company_id AFTER VALIDATION: " . ($company_id ?? 'NULL'));

		$select = null;

		if(isset($_POST['keyword']) && !empty(trim($_POST['keyword']))){
			$keyword = $this->modal->htmlvalidation($_POST['keyword']);
			$match_field['email'] = $keyword;
			$match_field['user_email'] = $keyword;
			$select = $this->modal->search("users", $match_field, "OR");
		}
		else
		{
			error_log("Calling modal->select() with: company_id=" . ($company_id ?? 'NULL') . ", is_super_admin=" . ($is_super_admin ? 'YES' : 'NO') . ", role=" . $session_role);
			$select = $this->modal->select('users', $company_id, $is_super_admin, $session_role);
		}

		$result_count = count($select ?: []);
		error_log("Query result count: " . $result_count);
		if ($result_count > 0) {
			error_log("First result: " . json_encode($select[0]));
		}
		error_log("=== USERS RECORD METHOD END ===");

		echo json_encode($select ?: []);
	}
	
	public function getagent()
	{
		$json = array();
		if(isset($_POST['depart']))
		{
			$session_role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
			$is_super_admin = ($session_role === 'super_admin');
			$company_id = $is_super_admin
				? (isset($_POST['company_id']) ? intval($_POST['company_id']) : null)
				: (isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : null);

			//echo "goga";
			$select_grp = $this->modal->agentassoc("agent", $company_id, $is_super_admin);
			//print_r($select_grp);
			if($select_grp)
			{
				$json =  json_encode($select_grp);
				echo $json;
			}
			else
			{
				$json['status'] = 102;
				$json['msg'] = "Data Not Inserted";
				//"Data Not Inserted"
			}
		}
		else
		{
			$json['status'] = 103;
				$json['msg'] = "Error in Request";
		}
	}
	
	public function updateprocess()
	
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'GET'){
				if(isset($_GET['checkid']) && $_GET['checkid'] > 0){

					$update_ch_id = intval($this->modal->htmlvalidation($_GET['checkid']));
					$session_role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
					$is_super_admin = ($session_role === 'super_admin');
					$company_id = isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : 0;

					if(!$is_super_admin && !$this->modal->userExistsInCompany($update_ch_id, $company_id)) {
						$json['status'] = 2;
						$json['msg'] = "Invalid Values Passed";
						echo json_encode($json);
						return;
					}

					$user = $this->modal->getResolvedUserById($update_ch_id);
					if($user)
					{
						$role = strtolower(trim($user['role_name'] ?? ''));
						$loginName = $user['login_name'] ?? '';
						$agentId = intval($user['agent_id'] ?? 0);
						$targetCompanyId = intval($user['company_id'] ?? 0);

						$roleLabel = ($role === 'uagent') ? 'User (Agent)' : (($role === 'manager' || $role === 'company_admin') ? 'Manager' : ucfirst($role));

						$agentDetails = array();
						if($role === 'uagent') {
							$agent = $this->modal->getAgentById($agentId);
							if($agent) {
								$agentDetails[] = ($agent['agent_name'] ?? '-') . " (" . ($agent['agent_ext'] ?? '-') . ")";
							}
						}

						$managerScope = array('mode' => 'all', 'agent_ids' => array());
						$managerAgents = array();
						if($role === 'manager' || $role === 'company_admin') {
							$managerScope = $this->modal->getManagerScopeForUser($update_ch_id);
							if(($managerScope['mode'] ?? 'all') === 'all') {
								$allAgents = $this->modal->agentassoc('agent', $targetCompanyId > 0 ? $targetCompanyId : $company_id, false);
								if($allAgents) {
									foreach($allAgents as $ag) {
										$managerAgents[] = array(
											'id' => intval($ag['agent_id'] ?? 0),
											'label' => ($ag['agent_name'] ?? '-') . " (" . ($ag['agent_ext'] ?? '-') . ")"
										);
									}
								}
								$agentDetails[] = 'All Agents';
							} else {
								$picked = $this->modal->getAgentsByIds($managerScope['agent_ids'] ?? array());
								foreach($picked as $ag) {
									$managerAgents[] = array(
										'id' => intval($ag['agent_id'] ?? 0),
										'label' => ($ag['agent_name'] ?? '-') . " (" . ($ag['agent_ext'] ?? '-') . ")"
									);
									$agentDetails[] = ($ag['agent_name'] ?? '-') . " (" . ($ag['agent_ext'] ?? '-') . ")";
								}
							}

							$available = $this->modal->agentassoc('agent', $targetCompanyId > 0 ? $targetCompanyId : $company_id, false);
							$availableAgents = array();
							if($available) {
								foreach($available as $ag) {
									$availableAgents[] = array(
										'id' => intval($ag['agent_id'] ?? 0),
										'label' => ($ag['agent_name'] ?? '-') . " (" . ($ag['agent_ext'] ?? '-') . ")"
									);
								}
							}
							$json['available_agents'] = $availableAgents;
						}

						$json['status'] = 0;
						$json['name'] = $loginName;
						$json['urole'] = $roleLabel;
						$json['role_code'] = $role;
						$json['uagent'] = implode("\n", $agentDetails);
						$json['manager_scope'] = $managerScope['mode'] ?? 'all';
						$json['manager_agents'] = array_map('intval', $managerScope['agent_ids'] ?? array());
						$json['msg'] = "Success";

					}
					else
					{

						$json['status'] = 1;
						$json['name'] = "NULL";
						$json['password'] = "NULL";
						$json['role'] = "NULL";
						$json['group'] = "NULL";
						$json['msg'] = "Fail";

					}

				}
				else
				{
						$json['status'] = 2;
						$json['name'] = "NULL";
						$json['password'] = "NULL";						
						$json['msg'] = "Invalid Values Passed";
				}
			}
			else
			{
						$json['status'] = 3;
						$json['name'] = "NULL";
						$json['password'] = "NULL";	
						$json['msg'] = "Invalid Method Found";
			}


		echo json_encode($json);
	}
	
	public function updateprocess2()
	{
		$json = array();

			if($_SERVER['REQUEST_METHOD'] == 'POST')
			{
				if(isset($_POST['dataval']))
				{
					$update_id = intval($this->modal->htmlvalidation($_POST['dataval']));
					$pass = isset($_POST['loginpass']) ? trim($_POST['loginpass']) : '';
					$manager_scope = isset($_POST['manager_scope']) ? $this->modal->htmlvalidation($_POST['manager_scope']) : 'all';
					$manager_scope = ($manager_scope === 'selected') ? 'selected' : 'all';
					$manager_agents = isset($_POST['manager_agents']) ? $_POST['manager_agents'] : array();
					if(!is_array($manager_agents)) {
						$manager_agents = array();
					}

					$session_role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
					$is_super_admin = ($session_role === 'super_admin');
					$company_id = isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : 0;

					if(!$is_super_admin && !$this->modal->userExistsInCompany($update_id, $company_id)) {
						$json['status'] = 108;
						$json['msg'] = "Invalid Values Passed";
						echo json_encode($json);
						return;
					}

					$targetUser = $this->modal->getResolvedUserById($update_id);
					if(!$targetUser) {
						$json['status'] = 108;
						$json['msg'] = "Invalid Values Passed";
						echo json_encode($json);
						return;
					}

					$targetRole = strtolower(trim($targetUser['role_name'] ?? ''));
					$passwordHash = null;
					if($pass !== '') {
						if(strlen($pass) < 6) {
							$json['status'] = 110;
							$json['msg'] = "Password must be at least 6 characters long";
							echo json_encode($json);
							return;
						}
						$passwordHash = password_hash($pass, PASSWORD_DEFAULT);
					}

					if($targetRole === 'uagent') {
						$update = $this->modal->updatePasswordAndManagerScope($update_id, $passwordHash, null, null);
						if($update) {
							$json['status'] = 101;
							$json['msg'] = ($pass === '') ? "No password change" : "Password updated";
						} else {
							$json['status'] = 102;
							$json['msg'] = "Data Not Updated";
						}
						echo json_encode($json);
						return;
					}

					if($targetRole === 'manager' || $targetRole === 'company_admin') {
						$allowedManagedAgents = array();
						foreach($manager_agents as $agentIdRaw) {
							$agentId = intval($agentIdRaw);
							if($agentId <= 0) continue;
							if($is_super_admin || $this->modal->isAgentInCompany($agentId, $company_id)) {
								$allowedManagedAgents[] = $agentId;
							}
						}

						if($manager_scope === 'selected' && count($allowedManagedAgents) === 0) {
							$json['status'] = 107;
							$json['msg'] = "Please select at least one supervised agent";
							echo json_encode($json);
							return;
						}

						$update = $this->modal->updatePasswordAndManagerScope($update_id, $passwordHash, $manager_scope, $allowedManagedAgents);
						if($update) {
							$json['status'] = 101;
							$json['msg'] = ($pass === '') ? "Manager access updated" : "Manager access and password updated";
						} else {
							$json['status'] = 102;
							$json['msg'] = "Data Not Updated";
						}
						echo json_encode($json);
						return;
					}

					$json['status'] = 108;
					$json['msg'] = "Unsupported role for update";
					

				}
				else
				{

				$json['status'] = 108;
				$json['msg'] = "Invalid Values Passed";

				}

			}
			else
			{

				$json['status'] = 109;
				$json['msg'] = "Invalid Method Found";

			}
			echo json_encode($json);

	}
	
	public function insprocess()
	{
		$json = array();

		if($_SERVER['REQUEST_METHOD'] == 'POST'){

			if(isset($_POST['loginname']) && isset($_POST['loginpass']) && isset($_POST['role']) ){

				$username = $this->modal->htmlvalidation($_POST['loginname']);
				$pass = $this->modal->htmlvalidation($_POST['loginpass']);
				$urole=$this->modal->htmlvalidation($_POST['role']);
				$session_role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
				$is_super_admin = ($session_role === 'super_admin');
				$company_id = $is_super_admin
					? (isset($_POST['company_id']) ? intval($_POST['company_id']) : 0)
					: (isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : 0);
				if(!$is_super_admin && $company_id <= 0) {
					$json['status'] = 108;
					$json['msg'] = "Company scope missing. Please login again.";
					echo json_encode($json);
					return;
				}

				if($urole === 'admin') {
					$urole = 'manager';
				}

				if((!preg_match('/^[ ]*$/', $username)) && (!preg_match('/^[ ]*$/', $pass)) && (!preg_match('/^[ ]*$/', $urole)) )
				{
					if($this->modal->loginNameExists($username)) {
						$json['status'] = 111;
						$json['msg'] = "Username already exists. Please use a different email/login.";
						echo json_encode($json);
						return;
					}

					if($urole !== "uagent" && $urole !== "manager")
					{
						$json['status'] = 105;
						$json['msg'] = "Invalid role selected";
					}
					else if($urole=="manager")
					{
						if($company_id <= 0) {
							$json['status'] = 108;
							$json['msg'] = "Please select company for manager";
							echo json_encode($json);
							return;
						}
						$manager_scope = isset($_POST['manager_scope']) ? $this->modal->htmlvalidation($_POST['manager_scope']) : 'all';
						$manager_scope = ($manager_scope === 'selected') ? 'selected' : 'all';
						$managed_agents = isset($_POST['manager_agents']) ? $_POST['manager_agents'] : array();
						if(!is_array($managed_agents)) {
							$managed_agents = array();
						}

						$allowedManagedAgents = array();
						foreach($managed_agents as $agentIdRaw) {
							$agentId = intval($agentIdRaw);
							if($agentId <= 0) continue;
							if($is_super_admin || $this->modal->isAgentInCompany($agentId, $company_id)) {
								$allowedManagedAgents[] = $agentId;
							}
						}

						if($manager_scope === 'selected' && count($allowedManagedAgents) === 0) {
							$json['status'] = 107;
							$json['msg'] = "Please select at least one agent for manager scope";
							echo json_encode($json);
							return;
						}

						$insert = $this->modal->insertUserWithSchema(
							$username,
							password_hash($pass,PASSWORD_DEFAULT),
							$urole,
							null,
							$company_id,
							$manager_scope,
							$manager_scope === 'selected' ? $allowedManagedAgents : array()
						);

						if($insert){
							$json['status'] = 101;
							$json['msg'] = "Data Successfully Inserted";
						//
						}
						else{
							$json['status'] = 102;
							$json['msg'] = "Data Not Inserted";
						//"Data Not Inserted"
						}
					}
					else
					{
						$agent = isset($_POST['agent']) ? intval($_POST['agent']) : 0;
						if($agent <= 0){
							$json['status'] = 106;
							$json['msg'] = "Please Select Agent";
							echo json_encode($json);
							return;
						}

						if(!$is_super_admin && !$this->modal->isAgentInCompany($agent, $company_id)) {
							$json['status'] = 106;
							$json['msg'] = "Please Select Agent from your company";
							echo json_encode($json);
							return;
						}

						$agentCompanyId = $this->modal->getAgentCompanyId($agent);
						if($agentCompanyId <= 0) {
							$json['status'] = 106;
							$json['msg'] = "Selected agent not found";
							echo json_encode($json);
							return;
						}

						$agentRow = $this->modal->getAgentById($agent);
						$agent3cxId = isset($agentRow['3cx_id']) ? intval($agentRow['3cx_id']) : 0;
						if($agent3cxId <= 0) {
							$json['status'] = 106;
							$json['msg'] = "Selected agent has no valid 3CX ID. Please sync agent 3CX mapping first.";
							echo json_encode($json);
							return;
						}

						$company_id = $agentCompanyId;

						$insert = $this->modal->insertUserWithSchema(
							$username,
							password_hash($pass,PASSWORD_DEFAULT),
							$urole,
							$agent,
							$agentCompanyId,
							null,
							null
						);

						if($insert){
							$json['status'] = 101;
							$json['msg'] = "Data Successfully Inserted";
						//
						}
						else{
							$json['status'] = 102;
							$json['msg'] = "Data Not Inserted";
						//"Data Not Inserted"
						}
					}

				}
				else{

					if(preg_match('/^[ ]*$/', $username)){

						$json['status'] = 103;
						$json['msg'] = "Please Enter usersname";

					}
					if(preg_match('/^[ ]*$/', $pass)){

						$json['status'] = 104;
						$json['msg'] = "Please Enter password";

					}
					

				}

			}
			else{

				$json['status'] = 108;
				$json['msg'] = "Invalid Values Passed";

			}

		}
		else{

			$json['status'] = 109;
			$json['msg'] = "Invalid Method Found";

		}


		echo json_encode($json);
	}
	
	public function deleteprocess()
	{
		$json = array();

		if($_SERVER['REQUEST_METHOD'] == 'POST')
		{
			$session_role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
			$is_super_admin = ($session_role === 'super_admin');
			$company_id = isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : 0;

			if(isset($_POST['delete_id']) && $_POST['delete_id'] > 0)
			{

				$deleteid = $this->modal->htmlvalidation($_POST['delete_id']);

				if(!$is_super_admin && !$this->modal->userExistsInCompany($deleteid, $company_id)) {
					$json['status'] = 2;
					$json['msg'] = "Invalid Value Passed";
					echo json_encode($json);
					return;
				}

				$condition['id'] = $deleteid;
				$delete_rec = $this->modal->delete("users",$condition);
		
				if($delete_rec)
				{
					$json['status'] = 0;
					$json['msg'] = "SuccessFully Deleted";
				}
				else
				{
					$json['status'] = 1;
					$json['msg'] = "Data Not Deleted";
				}

			}
			else
			{
				$json['status'] = 2;
				$json['msg'] = "Invalid Value Passed";
			}

		}
		else
		{
			$json['status'] = 3;
			$json['msg'] = "Invalid Method Found";
		}

		echo json_encode($json);
	}
	
}
?>