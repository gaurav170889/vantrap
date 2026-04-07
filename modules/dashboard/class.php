<?php
Class Dashboard{
	function __construct() {
      $this->name = loadmodal("dashboard");
    }

	private function resolvePeriod()
	{
		$allowedPeriods = ['today', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year'];
		$period = isset($_GET['period']) ? trim((string)$_GET['period']) : 'today';
		if (!in_array($period, $allowedPeriods, true)) {
			$period = 'today';
		}
		return $period;
	}

	private function resolveView()
	{
		$allowedViews = ['outbound', 'rating'];
		$view = isset($_GET['view']) ? trim((string)$_GET['view']) : 'outbound';
		if (!in_array($view, $allowedViews, true)) {
			$view = 'outbound';
		}
		return $view;
	}

	private function getViewConfig($view)
	{
		$config = [
			'outbound' => [
				'navurl' => 'Dashboard',
				'title' => 'Outbound Call Analytics',
				'subtitle' => 'Shows only outbound dialer activity, agent performance, and call disposition trends.'
			],
			'rating' => [
				'navurl' => 'RateAnalytics',
				'title' => 'Rate Analytics',
				'subtitle' => 'Shows rating coverage, score trends, and agent rating performance for the selected period.'
			]
		];

		return isset($config[$view]) ? $config[$view] : $config['outbound'];
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
		$period = $this->resolvePeriod();
		$dashboardView = $this->resolveView();
		$viewConfig = $this->getViewConfig($dashboardView);

		$_SESSION['navurl'] = $viewConfig['navurl'];
		$dashboardTitle = $viewConfig['title'];
		$dashboardSubtitle = $viewConfig['subtitle'];

		include(INCLUDEPATH.'modules/common/header.php');
		include(INCLUDEPATH.'modules/common/navbar_1.php');

		$periodOptions = [
			'today' => 'Today',
			'this_week' => 'This Week',
			'last_week' => 'Last Week',
			'this_month' => 'This Month',
			'last_month' => 'Last Month',
			'this_year' => 'This Year'
		];

		include("view/index.php");
	}

	public function analytics()
	{
		header('Content-Type: application/json');
		$period = $this->resolvePeriod();
		$dashboardView = $this->resolveView();
		$company_id = $this->getCompanyId();
		$dashboard = $this->name->getDashboardData($company_id, $period);
		$includeDebug = isset($_GET['debug']) && $_GET['debug'] == '1';

		ob_start();
		include(__DIR__ . '/view/analytics.php');
		$html = ob_get_clean();

		$response = [
			'status' => 101,
			'period' => $period,
			'view' => $dashboardView,
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