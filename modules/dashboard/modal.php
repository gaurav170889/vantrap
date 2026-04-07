<?php
Class Dashboard_modal{
	public function __construct()
	{
		$this->conn = ConnectDB();
	}

	public function htmlvalidation($form_data){
		$form_data = trim(stripslashes(htmlspecialchars($form_data)));
		$form_data = mysqli_real_escape_string($this->conn, trim(strip_tags($form_data)));
		return $form_data;
	}

	private function hasTable($table)
	{
		$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
		$sql = "SHOW TABLES LIKE '$table'";
		$res = mysqli_query($this->conn, $sql);
		return ($res && mysqli_num_rows($res) > 0);
	}

	private function hasColumn($table, $column)
	{
		$table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
		$column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
		$sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
		$res = mysqli_query($this->conn, $sql);
		return ($res && mysqli_num_rows($res) > 0);
	}

	private function fetchAssoc($sql)
	{
		$res = mysqli_query($this->conn, $sql);
		if (!$res) {
			error_log('Dashboard SQL error: ' . mysqli_error($this->conn) . ' | Query: ' . $sql);
			return [];
		}
		if ($res && mysqli_num_rows($res) > 0) {
			return mysqli_fetch_assoc($res);
		}
		return [];
	}

	private function fetchAll($sql)
	{
		$res = mysqli_query($this->conn, $sql);
		if (!$res) {
			error_log('Dashboard SQL error: ' . mysqli_error($this->conn) . ' | Query: ' . $sql);
			return [];
		}
		if ($res && mysqli_num_rows($res) > 0) {
			return mysqli_fetch_all($res, MYSQLI_ASSOC);
		}
		return [];
	}

	private function buildCompanyWhere($table, $company_id, $alias = '')
	{
		$company_id = intval($company_id);
		if ($company_id <= 0 || !$this->hasColumn($table, 'company_id')) {
			return '';
		}
		$prefix = $alias !== '' ? rtrim($alias, '.') . '.' : '';
		return " AND {$prefix}company_id = $company_id";
	}

	private function getDateRange($period)
	{
		$today = new DateTime('today');
		$start = clone $today;
		$end = clone $today;
		$label = 'Today';

		switch ($period) {
			case 'this_week':
				$start = new DateTime('monday this week');
				$end = new DateTime('sunday this week');
				$label = 'This Week';
				break;
			case 'last_week':
				$start = new DateTime('monday last week');
				$end = new DateTime('sunday last week');
				$label = 'Last Week';
				break;
			case 'this_month':
				$start = new DateTime('first day of this month');
				$end = new DateTime('last day of this month');
				$label = 'This Month';
				break;
			case 'last_month':
				$start = new DateTime('first day of last month');
				$end = new DateTime('last day of last month');
				$label = 'Last Month';
				break;
			case 'this_year':
				$start = new DateTime(date('Y-01-01'));
				$end = new DateTime(date('Y-12-31'));
				$label = 'This Year';
				break;
			default:
				$period = 'today';
				break;
		}

		return [
			'key' => $period,
			'label' => $label,
			'start' => $start->format('Y-m-d'),
			'end' => $end->format('Y-m-d'),
			'display' => $start->format('d M Y') . ' - ' . $end->format('d M Y')
		];
	}

	private function getAgentDirectory($company_id)
	{
		$directory = [
			'by_id' => [],
			'by_ext' => []
		];

		if (!$this->hasTable('agent')) {
			return $directory;
		}

		$sql = "SELECT agent_id, agent_name, agent_ext FROM agent WHERE 1=1";
		$sql .= $this->buildCompanyWhere('agent', $company_id);
		if ($this->hasColumn('agent', 'is_archived')) {
			$sql .= " AND is_archived = 0";
		}

		foreach ($this->fetchAll($sql) as $row) {
			$agentId = intval($row['agent_id'] ?? 0);
			$agentExt = trim((string)($row['agent_ext'] ?? ''));
			$agentName = trim((string)($row['agent_name'] ?? ''));
			$label = trim($agentName . ($agentExt !== '' ? ' (' . $agentExt . ')' : ''));
			$payload = [
				'agent_id' => $agentId,
				'agent_ext' => $agentExt,
				'agent_name' => $agentName,
				'label' => $label !== '' ? $label : ($agentExt !== '' ? $agentExt : 'Unknown Agent')
			];

			if ($agentId > 0) {
				$directory['by_id'][(string)$agentId] = $payload;
			}
			if ($agentExt !== '') {
				$directory['by_ext'][$agentExt] = $payload;
			}
		}

		return $directory;
	}

	private function extractNumericRatings($value)
	{
		$scores = [];
		if (is_array($value)) {
			foreach ($value as $item) {
				$scores = array_merge($scores, $this->extractNumericRatings($item));
			}
		} else if (is_numeric($value)) {
			$scores[] = floatval($value);
		}
		return $scores;
	}

	private function buildAnsweredCondition($table = 'dialer_call_log')
	{
		$statusCondition = $this->hasColumn($table, 'call_status') ? "UPPER(COALESCE(call_status, '')) = 'ANSWERED'" : "0 = 1";
		$agentCondition = $this->hasColumn($table, 'agent_id') ? "TRIM(COALESCE(agent_id, '')) <> ''" : "0 = 1";
		return "($statusCondition OR $agentCondition)";
	}

	private function normalizeMetricRow($row)
	{
		return [
			'attempts' => intval($row['attempts'] ?? 0),
			'unique_numbers' => intval($row['unique_numbers'] ?? 0),
			'answered_attempts' => intval($row['answered_attempts'] ?? 0),
			'answered_numbers' => intval($row['answered_numbers'] ?? 0),
			'avg_duration_sec' => floatval($row['avg_duration_sec'] ?? 0)
		];
	}

	private function buildDateExpression($table, $primaryColumn, $fallbackColumn = null)
	{
		$hasPrimary = $this->hasColumn($table, $primaryColumn);
		$hasFallback = $fallbackColumn !== null ? $this->hasColumn($table, $fallbackColumn) : false;
		$primaryExpr = "NULLIF($primaryColumn, '0000-00-00 00:00:00')";
		$fallbackExpr = $fallbackColumn !== null ? "NULLIF($fallbackColumn, '0000-00-00 00:00:00')" : null;

		if ($hasPrimary && $hasFallback) {
			return "COALESCE($primaryExpr, $fallbackExpr)";
		}
		if ($hasPrimary) {
			return $primaryExpr;
		}
		if ($hasFallback) {
			return $fallbackExpr;
		}
		return $primaryColumn;
	}

	public function getOutboundAnalytics($company_id, $range)
	{
		$result = [
			'available' => false,
			'has_data' => false,
			'latest_recorded_at' => '',
			'metrics' => [
				'attempts' => 0,
				'unique_numbers' => 0,
				'answered_attempts' => 0,
				'answered_numbers' => 0,
				'not_answered_attempts' => 0,
				'answer_rate' => 0,
				'attempts_per_number' => 0,
				'avg_duration_sec' => 0
			],
			'status_breakdown' => [],
			'agent_stats' => []
		];

		if (!$this->hasTable('dialer_call_log')) {
			return $result;
		}

		$result['available'] = true;
		$dateExpr = $this->buildDateExpression('dialer_call_log', 'started_at', 'created_at');
		$dateWhere = "$dateExpr >= '{$range['start']} 00:00:00' AND $dateExpr <= '{$range['end']} 23:59:59'";
		$companyWhere = $this->buildCompanyWhere('dialer_call_log', $company_id);
		$latestRow = $this->fetchAssoc("SELECT MAX($dateExpr) AS latest_recorded_at FROM dialer_call_log WHERE 1=1$companyWhere");
		$result['latest_recorded_at'] = trim((string)($latestRow['latest_recorded_at'] ?? ''));
		$uniqueExpr = $this->hasColumn('dialer_call_log', 'campaignnumber_id') ?
			"CAST(campaignnumber_id AS CHAR)" :
			($this->hasColumn('dialer_call_log', 'caller_id') ? "caller_id" : "call_id");
		$answeredCondition = $this->buildAnsweredCondition('dialer_call_log');
		$durationExpr = $this->hasColumn('dialer_call_log', 'duration_sec') ? "AVG(CASE WHEN duration_sec IS NOT NULL AND duration_sec > 0 THEN duration_sec END)" : "NULL";
		$statusLabelExpr = $this->hasColumn('dialer_call_log', 'call_status') ? "COALESCE(NULLIF(call_status, ''), 'UNKNOWN')" : "'UNKNOWN'";
		$agentKeyExpr = $this->hasColumn('dialer_call_log', 'agent_id') ? "TRIM(COALESCE(agent_id, ''))" : "''";

		$sql = "SELECT COUNT(*) AS attempts,
			COUNT(DISTINCT $uniqueExpr) AS unique_numbers,
			SUM(CASE WHEN $answeredCondition THEN 1 ELSE 0 END) AS answered_attempts,
			COUNT(DISTINCT CASE WHEN $answeredCondition THEN $uniqueExpr END) AS answered_numbers,
			$durationExpr AS avg_duration_sec
			FROM dialer_call_log
			WHERE $dateWhere$companyWhere";
		$row = $this->normalizeMetricRow($this->fetchAssoc($sql));
		$row['not_answered_attempts'] = max(0, $row['attempts'] - $row['answered_attempts']);
		$row['answer_rate'] = $row['attempts'] > 0 ? round(($row['answered_attempts'] * 100) / $row['attempts'], 2) : 0;
		$row['attempts_per_number'] = $row['unique_numbers'] > 0 ? round($row['attempts'] / $row['unique_numbers'], 2) : 0;
		$result['metrics'] = $row;
		$result['has_data'] = $row['attempts'] > 0;

		$statusSql = "SELECT $statusLabelExpr AS status_label, COUNT(*) AS total
			FROM dialer_call_log
			WHERE $dateWhere$companyWhere
			GROUP BY status_label
			ORDER BY total DESC
			LIMIT 8";
		$result['status_breakdown'] = $this->fetchAll($statusSql);

		$agentSql = "SELECT $agentKeyExpr AS agent_key,
			COUNT(*) AS attempts,
			SUM(CASE WHEN $answeredCondition THEN 1 ELSE 0 END) AS answered_attempts,
			$durationExpr AS avg_duration_sec
			FROM dialer_call_log
			WHERE $dateWhere$companyWhere AND $agentKeyExpr <> ''
			GROUP BY agent_key";
		foreach ($this->fetchAll($agentSql) as $rowAgent) {
			$key = trim((string)$rowAgent['agent_key']);
			if ($key === '') {
				continue;
			}
			$result['agent_stats'][$key] = [
				'attempts' => intval($rowAgent['attempts'] ?? 0),
				'answered_attempts' => intval($rowAgent['answered_attempts'] ?? 0),
				'not_answered_attempts' => max(0, intval($rowAgent['attempts'] ?? 0) - intval($rowAgent['answered_attempts'] ?? 0)),
				'avg_duration_sec' => floatval($rowAgent['avg_duration_sec'] ?? 0)
			];
		}

		return $result;
	}

	public function getRatingAnalytics($company_id, $range, $outbound)
	{
		$result = [
			'available' => false,
			'has_data' => false,
			'latest_recorded_at' => '',
			'metrics' => [
				'rated_calls' => 0,
				'unique_numbers' => 0,
				'total_points' => 0,
				'total_answers' => 0,
				'avg_score_per_answer' => 0,
				'avg_score_per_call' => 0,
				'rating_coverage' => 0
			],
			'score_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
			'sentiment_breakdown' => [],
			'agent_stats' => []
		];

		if (!$this->hasTable('rate')) {
			return $result;
		}

		$result['available'] = true;
		if (!$this->hasColumn('rate', 'ratings_json')) {
			return $result;
		}
		$dateExpr = $this->buildDateExpression('rate', 'created_at', 'start_date');
		$dateWhere = "$dateExpr >= '{$range['start']} 00:00:00' AND $dateExpr <= '{$range['end']} 23:59:59'";
		$companyWhere = $this->buildCompanyWhere('rate', $company_id);
		$latestRow = $this->fetchAssoc("SELECT MAX($dateExpr) AS latest_recorded_at FROM rate WHERE 1=1$companyWhere");
		$result['latest_recorded_at'] = trim((string)($latestRow['latest_recorded_at'] ?? ''));
		$agentIdSelect = $this->hasColumn('rate', 'agentid') ? 'agentid' : 'NULL AS agentid';
		$agentNoSelect = $this->hasColumn('rate', 'agentno') ? 'agentno' : "'' AS agentno";
		$callerNoSelect = $this->hasColumn('rate', 'callerno') ? 'callerno' : "'' AS callerno";
		$sentimentSelect = $this->hasColumn('rate', 'sentiment') ? 'sentiment' : "'' AS sentiment";
		$rows = $this->fetchAll("SELECT $agentIdSelect, $agentNoSelect, $callerNoSelect, ratings_json, $sentimentSelect FROM rate WHERE $dateWhere$companyWhere");

		$sentiments = [];
		$uniqueNumbers = [];
		foreach ($rows as $row) {
			$ratings = json_decode((string)($row['ratings_json'] ?? ''), true);
			$scores = $this->extractNumericRatings($ratings);
			if (empty($scores)) {
				continue;
			}

			$result['has_data'] = true;
			$result['metrics']['rated_calls']++;
			$callTotal = array_sum($scores);
			$result['metrics']['total_points'] += $callTotal;
			$result['metrics']['total_answers'] += count($scores);

			$callerno = trim((string)($row['callerno'] ?? ''));
			if ($callerno !== '') {
				$uniqueNumbers[$callerno] = true;
			}

			foreach ($scores as $score) {
				$rounded = intval(round($score));
				if (isset($result['score_distribution'][$rounded])) {
					$result['score_distribution'][$rounded]++;
				}
			}

			$sentiment = trim((string)($row['sentiment'] ?? ''));
			if ($sentiment !== '') {
				if (!isset($sentiments[$sentiment])) {
					$sentiments[$sentiment] = 0;
				}
				$sentiments[$sentiment]++;
			}

			$agentKey = '';
			$agentId = intval($row['agentid'] ?? 0);
			$agentNo = trim((string)($row['agentno'] ?? ''));
			if ($agentId > 0) {
				$agentKey = 'id:' . $agentId;
			} else if ($agentNo !== '') {
				$agentKey = 'ext:' . $agentNo;
			}

			if ($agentKey !== '') {
				if (!isset($result['agent_stats'][$agentKey])) {
					$result['agent_stats'][$agentKey] = [
						'rated_calls' => 0,
						'total_points' => 0,
						'total_answers' => 0
					];
				}
				$result['agent_stats'][$agentKey]['rated_calls']++;
				$result['agent_stats'][$agentKey]['total_points'] += $callTotal;
				$result['agent_stats'][$agentKey]['total_answers'] += count($scores);
			}
		}

		$result['metrics']['unique_numbers'] = count($uniqueNumbers);
		$result['metrics']['avg_score_per_answer'] = $result['metrics']['total_answers'] > 0 ? round($result['metrics']['total_points'] / $result['metrics']['total_answers'], 2) : 0;
		$result['metrics']['avg_score_per_call'] = $result['metrics']['rated_calls'] > 0 ? round($result['metrics']['total_points'] / $result['metrics']['rated_calls'], 2) : 0;
		$answeredAttempts = intval($outbound['metrics']['answered_attempts'] ?? 0);
		$result['metrics']['rating_coverage'] = $answeredAttempts > 0 ? round(($result['metrics']['rated_calls'] * 100) / $answeredAttempts, 2) : 0;
		arsort($sentiments);
		$result['sentiment_breakdown'] = $sentiments;

		return $result;
	}

	public function getDispositionAnalytics($company_id, $range, $outbound)
	{
		$result = [
			'available' => false,
			'has_data' => false,
			'source' => '',
			'metrics' => [
				'with_disposition' => 0,
				'unique_dispositions' => 0,
				'disposition_coverage' => 0
			],
			'top_items' => []
		];

		$answeredAttempts = intval($outbound['metrics']['answered_attempts'] ?? 0);

		if ($this->hasTable('dialer_call_log') && $this->hasColumn('dialer_call_log', 'disposition')) {
			$result['available'] = true;
			$dateExpr = $this->buildDateExpression('dialer_call_log', 'started_at', 'created_at');
			$dateWhere = "$dateExpr >= '{$range['start']} 00:00:00' AND $dateExpr <= '{$range['end']} 23:59:59'";
			$companyWhere = $this->buildCompanyWhere('dialer_call_log', $company_id);
			$filledWhere = "$dateWhere$companyWhere AND TRIM(COALESCE(disposition, '')) <> ''";

			$summary = $this->fetchAssoc("SELECT COUNT(*) AS with_disposition, COUNT(DISTINCT disposition) AS unique_dispositions FROM dialer_call_log WHERE $filledWhere");
			$result['metrics']['with_disposition'] = intval($summary['with_disposition'] ?? 0);
			$result['metrics']['unique_dispositions'] = intval($summary['unique_dispositions'] ?? 0);
			$result['source'] = 'dialer_call_log';

			$sql = "SELECT disposition, COUNT(*) AS total
				FROM dialer_call_log
				WHERE $filledWhere
				GROUP BY disposition
				ORDER BY total DESC
				LIMIT 10";
			$result['top_items'] = $this->fetchAll($sql);
		}

		// Fallback for setups where disposition is stored on campaignnumbers, not dialer_call_log.
		if ($result['metrics']['with_disposition'] <= 0 && $this->hasTable('campaignnumbers') && $this->hasColumn('campaignnumbers', 'last_disposition')) {
			$result['available'] = true;
			$dateCandidates = [];
			foreach (['last_call_started_at', 'last_attempt_at', 'updated_at', 'created_at'] as $candidate) {
				if ($this->hasColumn('campaignnumbers', $candidate)) {
					$dateCandidates[] = "NULLIF($candidate, '0000-00-00 00:00:00')";
				}
			}
			$dateExpr = !empty($dateCandidates) ? 'COALESCE(' . implode(',', $dateCandidates) . ')' : 'NULL';
			$dateWhere = "$dateExpr >= '{$range['start']} 00:00:00' AND $dateExpr <= '{$range['end']} 23:59:59'";
			$companyWhere = $this->buildCompanyWhere('campaignnumbers', $company_id);
			$filledWhere = "$dateWhere$companyWhere AND TRIM(COALESCE(last_disposition, '')) <> ''";

			$summary = $this->fetchAssoc("SELECT COUNT(*) AS with_disposition, COUNT(DISTINCT last_disposition) AS unique_dispositions FROM campaignnumbers WHERE $filledWhere");
			$result['metrics']['with_disposition'] = intval($summary['with_disposition'] ?? 0);
			$result['metrics']['unique_dispositions'] = intval($summary['unique_dispositions'] ?? 0);
			$result['source'] = 'campaignnumbers';

			$sql = "SELECT last_disposition AS disposition, COUNT(*) AS total
				FROM campaignnumbers
				WHERE $filledWhere
				GROUP BY last_disposition
				ORDER BY total DESC
				LIMIT 10";
			$result['top_items'] = $this->fetchAll($sql);
		}

		$result['metrics']['disposition_coverage'] = $answeredAttempts > 0 ? round(($result['metrics']['with_disposition'] * 100) / $answeredAttempts, 2) : 0;
		$result['has_data'] = $result['metrics']['with_disposition'] > 0;

		return $result;
	}

	public function getDialerAgentAnalytics($directory, $outbound)
	{
		$agents = [];

		foreach ($outbound['agent_stats'] as $agentId => $stats) {
			$key = 'id:' . $agentId;
			$directoryInfo = $directory['by_id'][(string)$agentId] ?? [
				'label' => 'Agent ' . $agentId,
				'agent_name' => 'Agent ' . $agentId,
				'agent_ext' => ''
			];
			$agents[$key] = [
				'label' => $directoryInfo['label'],
				'agent_name' => $directoryInfo['agent_name'],
				'agent_ext' => $directoryInfo['agent_ext'],
				'attempts' => intval($stats['attempts'] ?? 0),
				'answered_attempts' => intval($stats['answered_attempts'] ?? 0),
				'not_answered_attempts' => intval($stats['not_answered_attempts'] ?? 0),
				'avg_duration_sec' => floatval($stats['avg_duration_sec'] ?? 0),
				'rated_calls' => 0,
				'avg_rating' => 0
			];
		}

		usort($agents, function($left, $right) {
			if ($left['answered_attempts'] === $right['answered_attempts']) {
				return strcmp($left['label'], $right['label']);
			}
			return $right['answered_attempts'] <=> $left['answered_attempts'];
		});

		return array_slice($agents, 0, 12);
	}

	public function getRatingAgentAnalytics($directory, $ratings)
	{
		$agents = [];

		foreach ($ratings['agent_stats'] as $ratingKey => $stats) {
			$directoryInfo = ['label' => $ratingKey, 'agent_name' => $ratingKey, 'agent_ext' => ''];
			if (strpos($ratingKey, 'id:') === 0) {
				$agentId = substr($ratingKey, 3);
				if (isset($directory['by_id'][$agentId])) {
					$directoryInfo = $directory['by_id'][$agentId];
				}
			} else if (strpos($ratingKey, 'ext:') === 0) {
				$agentExt = substr($ratingKey, 4);
				if (isset($directory['by_ext'][$agentExt])) {
					$directoryInfo = $directory['by_ext'][$agentExt];
				}
			}

			if (!isset($agents[$ratingKey])) {
				$agents[$ratingKey] = [
					'label' => $directoryInfo['label'],
					'agent_name' => $directoryInfo['agent_name'],
					'agent_ext' => $directoryInfo['agent_ext'],
					'rated_calls' => 0,
					'avg_rating' => 0
				];
			}

			$agents[$ratingKey]['rated_calls'] = intval($stats['rated_calls'] ?? 0);
			$agents[$ratingKey]['avg_rating'] = intval($stats['total_answers'] ?? 0) > 0 ? round(floatval($stats['total_points'] ?? 0) / floatval($stats['total_answers'] ?? 0), 2) : 0;
		}

		usort($agents, function($left, $right) {
			if ($left['rated_calls'] === $right['rated_calls']) {
				if ($left['avg_rating'] === $right['avg_rating']) {
					return strcmp($left['label'], $right['label']);
				}
				return $right['avg_rating'] <=> $left['avg_rating'];
			}
			return $right['rated_calls'] <=> $left['rated_calls'];
		});

		return array_slice($agents, 0, 12);
	}

	public function getDashboardData($company_id, $period)
	{
		$range = $this->getDateRange($period);
		$directory = $this->getAgentDirectory($company_id);
		$outbound = $this->getOutboundAnalytics($company_id, $range);
		$ratings = $this->getRatingAnalytics($company_id, $range, $outbound);
		$dispositions = $this->getDispositionAnalytics($company_id, $range, $outbound);
		$dialerAgents = $this->getDialerAgentAnalytics($directory, $outbound);
		$ratingAgents = $this->getRatingAgentAnalytics($directory, $ratings);

		return [
			'period' => $range,
			'outbound' => $outbound,
			'ratings' => $ratings,
			'dispositions' => $dispositions,
			'dialer_agents' => $dialerAgents,
			'rating_agents' => $ratingAgents
		];
	}

	public function getDebugSnapshot($company_id, $period)
	{
		$range = $this->getDateRange($period);
		$dialerDateExpr = $this->buildDateExpression('dialer_call_log', 'started_at', 'created_at');
		$rateDateExpr = $this->buildDateExpression('rate', 'created_at', 'start_date');

		$dialerCompanyWhere = $this->buildCompanyWhere('dialer_call_log', $company_id);
		$rateCompanyWhere = $this->buildCompanyWhere('rate', $company_id);

		$debug = [
			'company_id' => $company_id,
			'period' => $range,
			'dialer' => [
				'table_exists' => $this->hasTable('dialer_call_log'),
				'date_expr' => $dialerDateExpr,
				'company_filter' => $dialerCompanyWhere
			],
			'rate' => [
				'table_exists' => $this->hasTable('rate'),
				'date_expr' => $rateDateExpr,
				'company_filter' => $rateCompanyWhere
			]
		];

		if ($debug['dialer']['table_exists']) {
			$dateWhere = "$dialerDateExpr >= '{$range['start']} 00:00:00' AND $dialerDateExpr <= '{$range['end']} 23:59:59'";
			$debug['dialer']['summary'] = $this->fetchAssoc(
				"SELECT COUNT(*) AS total_rows,
				MIN($dialerDateExpr) AS min_period_date,
				MAX($dialerDateExpr) AS max_period_date
				FROM dialer_call_log
				WHERE 1=1$dialerCompanyWhere AND $dateWhere"
			);
			$debug['dialer']['all_rows'] = $this->fetchAssoc(
				"SELECT COUNT(*) AS total_rows,
				MIN($dialerDateExpr) AS min_any_date,
				MAX($dialerDateExpr) AS max_any_date
				FROM dialer_call_log
				WHERE 1=1$dialerCompanyWhere"
			);
			$debug['dialer']['sample'] = $this->fetchAll(
				"SELECT id, company_id, call_id, call_status, agent_id, started_at, created_at
				FROM dialer_call_log
				WHERE 1=1$dialerCompanyWhere
				ORDER BY id DESC
				LIMIT 5"
			);
		}

		if ($debug['rate']['table_exists']) {
			$dateWhere = "$rateDateExpr >= '{$range['start']} 00:00:00' AND $rateDateExpr <= '{$range['end']} 23:59:59'";
			$debug['rate']['summary'] = $this->fetchAssoc(
				"SELECT COUNT(*) AS total_rows,
				MIN($rateDateExpr) AS min_period_date,
				MAX($rateDateExpr) AS max_period_date
				FROM rate
				WHERE 1=1$rateCompanyWhere AND $dateWhere"
			);
			$debug['rate']['all_rows'] = $this->fetchAssoc(
				"SELECT COUNT(*) AS total_rows,
				MIN($rateDateExpr) AS min_any_date,
				MAX($rateDateExpr) AS max_any_date
				FROM rate
				WHERE 1=1$rateCompanyWhere"
			);
		}

		return $debug;
	}
}
?>