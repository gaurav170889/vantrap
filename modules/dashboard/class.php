<?php
Class Dashboard{
	function __construct() {
      $this->name = loadmodal("dashboard");
    }

	private function resolvePeriod()
	{
		$allowedPeriods = ['today', 'this_week', 'last_week', 'this_month', 'this_year'];
		$period = isset($_GET['period']) ? trim((string)$_GET['period']) : 'today';
		if (!in_array($period, $allowedPeriods, true)) {
			$period = 'today';
		}
		return $period;
	}

	private function getCompanyId()
	{
		$company_id = isset($_SESSION['company_id']) ? intval($_SESSION['company_id']) : null;
		if ($company_id !== null && $company_id <= 0) {
			$company_id = null;
		}
		return $company_id;
	}

	public function index(){
		$_SESSION['navurl'] = 'Dashboard';
		include(INCLUDEPATH.'modules/common/header.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');

		$period = $this->resolvePeriod();
		$periodOptions = [
			'today' => 'Today',
			'this_week' => 'This Week',
			'last_week' => 'Last Week',
			'this_month' => 'This Month',
			'this_year' => 'This Year'
		];

		include("view/index.php");
	}

	public function analytics()
	{
		header('Content-Type: application/json');
		$period = $this->resolvePeriod();
		$company_id = $this->getCompanyId();
		$dashboard = $this->name->getDashboardData($company_id, $period);
		$includeDebug = isset($_GET['debug']) && $_GET['debug'] == '1';

		ob_start();
		include(__DIR__ . '/view/analytics.php');
		$html = ob_get_clean();

		$response = [
			'status' => 101,
			'period' => $period,
			'html' => $html
		];

		if ($includeDebug) {
			$response['debug'] = $this->name->getDebugSnapshot($company_id, $period);
		}

		echo json_encode($response);
	}

	public function goga(){
		echo "This is goga";
	}
}
?>