<?php
// Modulename
Class Dashboard{
	function __construct() {
      $this->name = loadmodal("dashboard");;
    }
	public function index(){
		include(INCLUDEPATH.'modules/common/header.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');
		
		$date=date("m/d/Y");
		$company_id = isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : null;
		
		$point_1=$this->name->pointone("rate","1", $company_id);
		$point_3=$this->name->pointthree("rate","3", $company_id);
		$point_5=$this->name->pointfive("rate","5", $company_id);
		$point_all=$this->name->totalcallpoint("rate", $company_id);
		$agentout = $this->name->averagescore("rate", $company_id);
		
		$counter = 1;
		include("view/index.php");
	}
	
	public function goga(){
		echo "This is goga";
	}
}
?>