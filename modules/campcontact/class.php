<?php
// Modulename
Class Campcontact{
	
	//private $pages;
	//public $select;
	//public $totalPages;
	public function __construct() {
      $this->modal = loadmodal("campcontact");
    }
	public function index(){
        $_SESSION['navurl'] = 'Campcontact';
	    //echo "abcd";
	   // exit();
		include(INCLUDEPATH.'modules/common/campaignheader.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');	
		//echo "abcd";
	//	exit();
		if($_SESSION['role']== "uagent")
		{
			//$qagent = $this->getagent();
			
			include("view/notadmin.php");
		}
		else
		{
		//$page = (isset($_GET['page']) && is_numeric($_GET['page']) ) ? $_GET['page'] : 1;
		//$data = $this->modal->select("agent");
		//$group = $this->modal->groupassoc("agentgroup");
		//print_r($group);
	//	$counter = 1;
		include("view/index.php");
		//$this->record();
		}
		
		include('modules/common/campcontactfooter.php');
		 
	}		
	//public function record($keywords,$pages)
	/*public function record()
	{
		
		include("view/record.php");
	}*/
	
	
    public function getallcontact() 
    {
        $role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
        $company_id = ($role === 'super_admin')
            ? ((isset($_POST['company_id']) && trim((string)$_POST['company_id']) !== '') ? intval($_POST['company_id']) : null)
            : ($_SESSION['company_id'] ?? 0);
        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $filter_type = isset($_POST['filter_type']) ? trim($_POST['filter_type']) : '';
        $filter_value = isset($_POST['filter_value']) ? trim($_POST['filter_value']) : '';
        $open_contact_id = isset($_POST['open_contact_id']) ? intval($_POST['open_contact_id']) : 0;

        $data = $this->modal->getallcontact($company_id, $campaign_id, $filter_type, $filter_value, $open_contact_id);
        header('Content-Type: application/json');
        echo $data ;
        
    }

    public function get_filter_companies()
    {
        $role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
        if ($role !== 'super_admin') {
            header('Content-Type: application/json');
            echo json_encode([]);
            return;
        }

        $data = $this->modal->getFilterCompanies();
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function get_filter_campaigns()
    {
        $role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
        $company_id = ($role === 'super_admin')
            ? (isset($_POST['company_id']) ? intval($_POST['company_id']) : 0)
            : ($_SESSION['company_id'] ?? 0);
        $data = $this->modal->getFilterCampaigns($company_id);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function get_filter_values()
    {
        $role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
        $company_id = ($role === 'super_admin')
            ? (isset($_POST['company_id']) ? intval($_POST['company_id']) : 0)
            : ($_SESSION['company_id'] ?? 0);
        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $type = isset($_POST['type']) ? trim($_POST['type']) : '';

        $data = $this->modal->getFilterValues($company_id, $campaign_id, $type);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
	
	public function addcampaign()
	{
	   
	    $name        = $_POST['name'] ?? '';
        $routeto     = $_POST['routeto'] ?? '';
        $returncall  = $_POST['returncall'] ?? '';
        $weekdays    = $_POST['weekdays'] ?? [];
        $starttime   = $_POST['starttime'] ?? '';
        $stoptime    = $_POST['stoptime'] ?? '';
    
        $result = $this->modal->addCampaignSql($name, $routeto, $returncall, $weekdays, $starttime, $stoptime);
    
        header('Content-Type: application/json');
        echo json_encode($result);
	}
	
	public function import_numbers()
    {
        if (!isset($_FILES['csvFile']) || $_FILES['csvFile']['error'] !== 0) {
            echo json_encode(['success' => false, 'message' => 'File upload failed.']);
            return;
        }
    
        $fileInfo = $_FILES['csvFile'];
        $campaignId = isset($_POST['campaignid']) ? intval($_POST['campaignid']) : 0;
    
        // Validate CSV extension
        $ext = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            echo json_encode(['success' => false, 'message' => 'Only CSV files are allowed.']);
            return;
        }
    
        // Generate temporary filename
        $tempName = 'campaign_' . time() . '_' . rand(1000, 9999) . '.csv';
        $targetPath = UPLOAD . $tempName;
    
        // Move uploaded file
        if (!move_uploaded_file($fileInfo['tmp_name'], $targetPath)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
            return;
        }
    
        // Call model function to import
        $result = $this->modal->importnumbersql($campaignId, $targetPath);
    
        echo json_encode($result);
    }
	
	public function delete_all_contacts()
	{
	    $data = $this->modal->deletecontacts();
	}
    
    public function updateDispositionSql()
    {
        $id = $_POST['contact_id'] ?? 0;
        $disposition = $_POST['disposition'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $date = $_POST['callback_date'] ?? '';
        $time = $_POST['callback_time'] ?? '';

        $result = $this->modal->updateDispositionSql($id, $disposition, $notes, $date, $time);
        echo json_encode($result);
    }

    public function get_disposition_history()
    {
        $role = $_SESSION['erole'] ?? $_SESSION['role'] ?? '';
        if (!in_array($role, ['super_admin', 'company_admin', 'manager'], true)) {
            echo json_encode([]);
            return;
        }

        $company_id = null;
        if ($role === 'super_admin') {
            $company_id = isset($_GET['company_id']) && $_GET['company_id'] !== '' ? intval($_GET['company_id']) : null;
        } elseif (isset($_SESSION['company_id'])) {
            $company_id = intval($_SESSION['company_id']);
        }

        $campaignnumberId = isset($_GET['campaignnumber_id']) ? intval($_GET['campaignnumber_id']) : 0;
        $data = $this->modal->getDispositionHistory($campaignnumberId, $company_id);
        echo json_encode($data);
    }
		
}
?>
