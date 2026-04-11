<?php
function dashboardMetricValue($value, $suffix = '') {
	if (is_float($value) || strpos((string)$value, '.') !== false) {
		return number_format((float)$value, 2) . $suffix;
	}
	return number_format((int)$value) . $suffix;
}

function dashboardDuration($seconds) {
	$seconds = (int)round((float)$seconds);
	if ($seconds <= 0) {
		return '0s';
	}
	$minutes = floor($seconds / 60);
	$rem = $seconds % 60;
	if ($minutes <= 0) {
		return $rem . 's';
	}
	return $minutes . 'm ' . $rem . 's';
}

function dashboardClockDuration($seconds) {
	$seconds = max(0, (int)round((float)$seconds));
	$hours = floor($seconds / 3600);
	$minutes = floor(($seconds % 3600) / 60);
	$secs = $seconds % 60;
	return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

function dashboardDisplayDateTime($value, $timezone = 'UTC') {
	$value = trim((string)$value);
	if ($value === '' || $value === '0000-00-00 00:00:00') {
		return '';
	}
	try {
		$dt = new DateTime($value, new DateTimeZone('UTC'));
		$dt->setTimezone(new DateTimeZone($timezone ?: 'UTC'));
		return $dt->format('M d, Y h:i A');
	} catch (Exception $e) {
		return $value;
	}
}

$outbound = $dashboard['outbound'];
$ratings = $dashboard['ratings'];
$dispositions = $dashboard['dispositions'];
$dialerAgents = $dashboard['dialer_agents'] ?? [];
$ratingAgents = $dashboard['rating_agents'] ?? [];
$isRatingView = isset($dashboardView) && $dashboardView === 'rating';

$activeDialerAgents = 0;
foreach ($dialerAgents as $agentRow) {
	if (intval($agentRow['attempts'] ?? 0) > 0 || intval($agentRow['answered_attempts'] ?? 0) > 0) {
		$activeDialerAgents++;
	}
}

$activeRatingAgents = 0;
foreach ($ratingAgents as $agentRow) {
	if (intval($agentRow['rated_calls'] ?? 0) > 0) {
		$activeRatingAgents++;
	}
}

$statusTotal = count($outbound['status_breakdown'] ?? []);
$dashboardTimezone = $dashboard['period']['timezone'] ?? 'UTC';
$latestOutboundText = dashboardDisplayDateTime($outbound['latest_recorded_at'] ?? '', $dashboardTimezone);
$latestRatingText = dashboardDisplayDateTime($ratings['latest_recorded_at'] ?? '', $dashboardTimezone);
?>

<div id="dashboard-period-range" style="display:none;"><?php echo htmlspecialchars($dashboard['period']['label']); ?> (<?php echo htmlspecialchars($dashboard['period']['display']); ?>)</div>
<div id="dashboard-period-chips" style="display:none;">
	<?php if ($isRatingView) { ?>
		<div class="kpi-chip">Rated calls <?php echo dashboardMetricValue($ratings['metrics']['rated_calls']); ?></div>
		<div class="kpi-chip">Coverage <?php echo dashboardMetricValue($ratings['metrics']['rating_coverage'], '%'); ?></div>
		<div class="kpi-chip">Avg score <?php echo dashboardMetricValue($ratings['metrics']['avg_score_per_call']); ?></div>
	<?php } else { ?>
		<div class="kpi-chip">Answer rate <?php echo dashboardMetricValue($outbound['metrics']['answer_rate'], '%'); ?></div>
		<div class="kpi-chip">Connected <?php echo dashboardMetricValue($outbound['metrics']['answered_attempts']); ?></div>
		<div class="kpi-chip">Avg talk <?php echo htmlspecialchars(dashboardClockDuration($outbound['metrics']['avg_duration_sec'])); ?></div>
	<?php } ?>
</div>

<?php if ($isRatingView) { ?>
	<?php if (!$ratings['has_data']) { ?>
	<div class="dashboard-alert">No rating records were found for the selected period.<?php if ($latestRatingText !== '') { ?> <span class="muted-copy">Latest recorded rating: <?php echo htmlspecialchars($latestRatingText); ?></span><?php } ?></div>
	<?php } ?>

	<div class="dashboard-section-title">Rate Analytics Snapshot</div>
	<div class="row">
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-purple"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Rated Calls</div>
						<div class="metric-value"><?php echo dashboardMetricValue($ratings['metrics']['rated_calls']); ?></div>
					</div>
					<div class="metric-icon">⭐</div>
				</div>
				<div class="metric-note">Calls that produced rating responses.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-ocean"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Unique Rated Numbers</div>
						<div class="metric-value"><?php echo dashboardMetricValue($ratings['metrics']['unique_numbers']); ?></div>
					</div>
					<div class="metric-icon">📱</div>
				</div>
				<div class="metric-note">Distinct callers with saved ratings.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-success"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Rating Coverage</div>
						<div class="metric-value"><?php echo dashboardMetricValue($ratings['metrics']['rating_coverage'], '%'); ?></div>
					</div>
					<div class="metric-icon">✅</div>
				</div>
				<div class="metric-note">Answered outbound calls that were rated.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-orange"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Avg Score / Call</div>
						<div class="metric-value"><?php echo dashboardMetricValue($ratings['metrics']['avg_score_per_call']); ?></div>
					</div>
					<div class="metric-icon">📊</div>
				</div>
				<div class="metric-note">Average score across rated calls.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-cyan"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Avg Score / Answer</div>
						<div class="metric-value"><?php echo dashboardMetricValue($ratings['metrics']['avg_score_per_answer']); ?></div>
					</div>
					<div class="metric-icon">🧮</div>
				</div>
				<div class="metric-note">Average across all rating answers.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-pink"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Active Rating Agents</div>
						<div class="metric-value"><?php echo dashboardMetricValue($activeRatingAgents); ?></div>
					</div>
					<div class="metric-icon">👥</div>
				</div>
				<div class="metric-note">Agents with recorded rating activity.</div>
			</div></div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-6">
			<div class="card analytics-panel">
				<div class="card-body">
					<div class="panel-header-lite">Rating Score Distribution</div>
					<div class="panel-body-lite">
						<?php if ($ratings['available'] && $ratings['has_data'] && !empty($ratings['score_distribution'])) {
							$distributionMax = max($ratings['score_distribution']);
							if ($distributionMax <= 0) { $distributionMax = 1; }
							foreach ($ratings['score_distribution'] as $score => $total) {
								$width = round(($total / $distributionMax) * 100, 2);
						?>
						<div class="distribution-row">
							<div class="distribution-label">Score <?php echo $score; ?></div>
							<div class="progress"><div class="progress-bar progress-bar-score" role="progressbar" style="width: <?php echo $width; ?>%"></div></div>
							<div class="distribution-value"><?php echo dashboardMetricValue($total); ?></div>
						</div>
						<?php }
						} else { ?>
						<div class="empty-state">Score distribution will show once rating responses are saved in `ratings_json`.</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>

		<div class="col-lg-6">
			<div class="card analytics-panel">
				<div class="card-body">
					<div class="panel-header-lite">Rating Agent Snapshot</div>
					<div class="panel-body-lite">
						<?php if (!empty($ratingAgents)) { ?>
						<div class="table-responsive">
							<table class="table table-striped table-bordered mb-0">
								<thead>
									<tr>
										<th>Agent</th>
										<th class="text-right">Rated Calls</th>
										<th class="text-right">Avg Rating</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($ratingAgents as $agentRow) { ?>
									<tr>
										<td><?php echo htmlspecialchars($agentRow['label']); ?></td>
										<td class="text-right"><?php echo dashboardMetricValue($agentRow['rated_calls']); ?></td>
										<td class="text-right"><?php echo dashboardMetricValue($agentRow['avg_rating']); ?></td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<?php } else { ?>
						<div class="empty-state">No rating agent data for the selected period.</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } else { ?>
	<?php if (!$outbound['has_data']) { ?>
	<div class="dashboard-alert">No outbound calls were found for the selected period.<?php if ($latestOutboundText !== '') { ?> <span class="muted-copy">Latest recorded outbound call: <?php echo htmlspecialchars($latestOutboundText); ?></span><?php } ?></div>
	<?php } ?>

	<div class="dashboard-section-title">Outbound Dialer Dashboard</div>
	<div class="row">
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-ocean"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Total Calls</div>
						<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['attempts']); ?></div>
					</div>
					<div class="metric-icon">📞</div>
				</div>
				<div class="metric-note">Selected period outbound attempts.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-success"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Unique Numbers Dialed</div>
						<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['unique_numbers']); ?></div>
					</div>
					<div class="metric-icon">🎯</div>
				</div>
				<div class="metric-note">Distinct leads reached in this period.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-orange"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Connected to Agents</div>
						<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['answered_attempts']); ?></div>
					</div>
					<div class="metric-icon">🤝</div>
				</div>
				<div class="metric-note">Calls that reached an agent conversation.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-danger"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Active Agents</div>
						<div class="metric-value"><?php echo dashboardMetricValue($activeDialerAgents); ?></div>
					</div>
					<div class="metric-icon">👥</div>
				</div>
				<div class="metric-note">Agents with connected outbound calls.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-cyan"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Avg Talk Time</div>
						<div class="metric-value"><?php echo htmlspecialchars(dashboardClockDuration($outbound['metrics']['avg_duration_sec'])); ?></div>
					</div>
					<div class="metric-icon">⏱️</div>
				</div>
				<div class="metric-note">Average duration of connected calls.</div>
			</div></div>
		</div>
		<div class="col-md-6 col-xl-4 mb-3">
			<div class="card metric-card metric-theme-slate"><div class="card-body">
				<div class="metric-header">
					<div>
						<div class="metric-label">Statuses / Dispositions</div>
						<div class="metric-value"><?php echo dashboardMetricValue($statusTotal); ?> / <?php echo dashboardMetricValue($dispositions['metrics']['unique_dispositions']); ?></div>
					</div>
					<div class="metric-icon">📊</div>
				</div>
				<div class="metric-note">Tracked call states and logged outcomes.</div>
			</div></div>
		</div>
	</div>

	<div class="row">
		<div class="col-lg-5">
			<div class="card analytics-panel">
				<div class="card-body">
					<div class="panel-header-lite">Call Status Breakdown</div>
					<div class="panel-body-lite">
						<?php if (!empty($outbound['status_breakdown'])) { ?>
						<table class="table table-sm mb-0">
							<thead>
								<tr>
									<th>Status</th>
									<th class="text-right">Attempts</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($outbound['status_breakdown'] as $statusRow) { ?>
								<tr>
									<td><?php echo htmlspecialchars($statusRow['status_label']); ?></td>
									<td class="text-right"><?php echo dashboardMetricValue($statusRow['total']); ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
						<?php } else { ?>
						<div class="empty-state">No outbound call activity was found for this filter.</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>

		<div class="col-lg-7">
			<div class="card analytics-panel">
				<div class="card-body">
					<div class="panel-header-lite">Top Disposition Activity</div>
					<div class="panel-body-lite">
						<?php if ($dispositions['available'] && $dispositions['has_data']) { ?>
						<table class="table table-sm mb-0">
							<thead>
								<tr>
									<th>Disposition</th>
									<th class="text-right">Count</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($dispositions['top_items'] as $dispRow) { ?>
								<tr>
									<td><?php echo htmlspecialchars($dispRow['disposition']); ?></td>
									<td class="text-right"><?php echo dashboardMetricValue($dispRow['total']); ?></td>
								</tr>
								<?php } ?>
							</tbody>
						</table>
						<?php } else { ?>
						<div class="empty-state">No campaign or disposition activity found for this filter.</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-12">
			<div class="card analytics-panel">
				<div class="card-body">
					<div class="panel-header-lite">Agent Pickup Analytics</div>
					<div class="panel-body-lite p-0">
						<?php if (!empty($dialerAgents)) { ?>
						<div class="table-responsive">
							<table class="table table-striped table-bordered mb-0">
								<thead>
									<tr>
										<th style="width:60px">#</th>
										<th>Agent</th>
										<th class="text-right">Attempts</th>
										<th class="text-right">Connected Calls</th>
										<th class="text-right">Not Answered</th>
										<th class="text-right">Avg Talk Time</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($dialerAgents as $index => $agentRow) { ?>
									<tr>
										<td><?php echo intval($index + 1); ?></td>
										<td><?php echo htmlspecialchars($agentRow['label']); ?></td>
										<td class="text-right"><?php echo dashboardMetricValue($agentRow['attempts']); ?></td>
										<td class="text-right"><?php echo dashboardMetricValue($agentRow['answered_attempts']); ?></td>
										<td class="text-right"><?php echo dashboardMetricValue($agentRow['not_answered_attempts']); ?></td>
										<td class="text-right"><?php echo htmlspecialchars(dashboardClockDuration($agentRow['avg_duration_sec'])); ?></td>
									</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<?php } else { ?>
						<div class="panel-body-lite"><div class="empty-state">No agent pickup data found for this date range.</div></div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php } ?>
