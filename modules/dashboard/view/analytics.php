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

$outbound = $dashboard['outbound'];
$ratings = $dashboard['ratings'];
$dispositions = $dashboard['dispositions'];
$dialerAgents = $dashboard['dialer_agents'] ?? [];
$ratingAgents = $dashboard['rating_agents'] ?? [];
?>

<div id="dashboard-period-range" style="display:none;"><?php echo htmlspecialchars($dashboard['period']['label']); ?> | <?php echo htmlspecialchars($dashboard['period']['display']); ?></div>
<div id="dashboard-period-chips" style="display:none;">
	<div class="kpi-chip">Answer rate <?php echo dashboardMetricValue($outbound['metrics']['answer_rate'], '%'); ?></div>
	<div class="kpi-chip">Rating coverage <?php echo dashboardMetricValue($ratings['metrics']['rating_coverage'], '%'); ?></div>
</div>

<div class="dashboard-section-title">Outbound Dialer Dashboard</div>
<?php if ($outbound['available'] && $outbound['has_data']) { ?>
<div class="row">
	<div class="col-md-6 col-xl-3 mb-3">
		<div class="card metric-card"><div class="card-body">
			<div class="metric-label">Total Attempts</div>
			<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['attempts']); ?></div>
			<div class="metric-note">Every attempt is counted, even if the same number was retried.</div>
		</div></div>
	</div>
	<div class="col-md-6 col-xl-3 mb-3">
		<div class="card metric-card"><div class="card-body">
			<div class="metric-label">Unique Numbers</div>
			<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['unique_numbers']); ?></div>
			<div class="metric-note">Distinct leads reached by the dialer in this period.</div>
		</div></div>
	</div>
	<div class="col-md-6 col-xl-3 mb-3">
		<div class="card metric-card"><div class="card-body">
			<div class="metric-label">Received By Agent</div>
			<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['answered_attempts']); ?></div>
			<div class="metric-note">Attempts that reached an agent or were marked answered.</div>
		</div></div>
	</div>
	<div class="col-md-6 col-xl-3 mb-3">
		<div class="card metric-card"><div class="card-body">
			<div class="metric-label">Not Answered</div>
			<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['not_answered_attempts']); ?></div>
			<div class="metric-note">Attempts not received by an agent.</div>
		</div></div>
	</div>
	<div class="col-md-6 col-xl-4 mb-3">
		<div class="card metric-card"><div class="card-body">
			<div class="metric-label">Answer Rate</div>
			<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['answer_rate'], '%'); ?></div>
			<div class="metric-note">Answered attempts divided by all attempts.</div>
		</div></div>
	</div>
	<div class="col-md-6 col-xl-4 mb-3">
		<div class="card metric-card"><div class="card-body">
			<div class="metric-label">Attempts Per Number</div>
			<div class="metric-value"><?php echo dashboardMetricValue($outbound['metrics']['attempts_per_number']); ?></div>
			<div class="metric-note">Useful for spotting over-dialing or retry pressure.</div>
		</div></div>
	</div>
	<div class="col-md-12 col-xl-4 mb-3">
		<div class="card metric-card"><div class="card-body">
			<div class="metric-label">Average Talk Time</div>
			<div class="metric-value"><?php echo htmlspecialchars(dashboardDuration($outbound['metrics']['avg_duration_sec'])); ?></div>
			<div class="metric-note">Average non-zero duration from dialer call logs.</div>
		</div></div>
	</div>
</div>
<?php } else { ?>
<div class="empty-state mb-4">No outbound dialer data found for the selected period.</div>
<?php } ?>

<div class="row">
	<div class="col-lg-6">
		<div class="card analytics-panel">
			<div class="card-body">
				<div class="dashboard-section-title mb-3">Call Status Mix</div>
				<?php if (!empty($outbound['status_breakdown'])) { ?>
				<table class="table table-sm">
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
				<div class="text-muted">Status distribution will appear once dialer_call_log has rows.</div>
				<?php } ?>
			</div>
		</div>
	</div>

	<div class="col-lg-6">
		<div class="card analytics-panel">
			<div class="card-body">
				<div class="dashboard-section-title mb-3">Rating Dashboard</div>
				<?php if ($ratings['available'] && $ratings['has_data']) { ?>
				<div class="row">
					<div class="col-6 mb-3">
						<div class="metric-label">Rated Calls</div>
						<div class="metric-value" style="font-size:1.5rem"><?php echo dashboardMetricValue($ratings['metrics']['rated_calls']); ?></div>
					</div>
					<div class="col-6 mb-3">
						<div class="metric-label">Unique Rated Numbers</div>
						<div class="metric-value" style="font-size:1.5rem"><?php echo dashboardMetricValue($ratings['metrics']['unique_numbers']); ?></div>
					</div>
					<div class="col-6 mb-3">
						<div class="metric-label">Avg Score Per Answer</div>
						<div class="metric-value" style="font-size:1.5rem"><?php echo dashboardMetricValue($ratings['metrics']['avg_score_per_answer']); ?></div>
					</div>
					<div class="col-6 mb-3">
						<div class="metric-label">Avg Score Per Call</div>
						<div class="metric-value" style="font-size:1.5rem"><?php echo dashboardMetricValue($ratings['metrics']['avg_score_per_call']); ?></div>
					</div>
				</div>
				<div class="metric-note">Rating coverage: <?php echo dashboardMetricValue($ratings['metrics']['rating_coverage'], '%'); ?> of answered attempts produced a rating record.</div>
				<?php } else { ?>
				<div class="empty-state mb-0">No rating data found for the selected period.</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-lg-6">
		<div class="card analytics-panel">
			<div class="card-body">
				<div class="dashboard-section-title mb-3">Rating Score Distribution</div>
				<?php if ($ratings['available'] && $ratings['has_data']) {
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
				<div class="text-muted">Score distribution will show once ratings_json contains responses.</div>
				<?php } ?>
			</div>
		</div>
	</div>

	<div class="col-lg-6">
		<div class="card analytics-panel">
			<div class="card-body">
				<div class="dashboard-section-title mb-3">Disposition Analytics</div>
				<?php if ($dispositions['available'] && $dispositions['has_data']) { ?>
				<div class="metric-note mb-2">Source: <?php echo htmlspecialchars($dispositions['source'] ?? ''); ?></div>
				<div class="row mb-3">
					<div class="col-6">
						<div class="metric-label">Calls With Disposition</div>
						<div class="metric-value" style="font-size:1.5rem"><?php echo dashboardMetricValue($dispositions['metrics']['with_disposition']); ?></div>
					</div>
					<div class="col-6">
						<div class="metric-label">Disposition Coverage</div>
						<div class="metric-value" style="font-size:1.5rem"><?php echo dashboardMetricValue($dispositions['metrics']['disposition_coverage'], '%'); ?></div>
					</div>
				</div>
				<div class="metric-note mb-3">Unique dispositions used: <?php echo dashboardMetricValue($dispositions['metrics']['unique_dispositions']); ?></div>
				<table class="table table-sm">
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
				<div class="empty-state mb-0">No disposition analytics available for the selected period.</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-lg-6">
		<div class="card analytics-panel">
			<div class="card-body">
				<div class="dashboard-section-title mb-3">Dialer Agent Snapshot</div>
				<?php if (!empty($dialerAgents)) { ?>
				<div class="table-responsive">
					<table class="table table-striped table-bordered mb-0">
						<thead>
							<tr>
								<th>Agent</th>
								<th class="text-right">Attempts</th>
								<th class="text-right">Answered</th>
								<th class="text-right">Not Answered</th>
								<th class="text-right">Avg Talk Time</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($dialerAgents as $agentRow) { ?>
							<tr>
								<td><?php echo htmlspecialchars($agentRow['label']); ?></td>
								<td class="text-right"><?php echo dashboardMetricValue($agentRow['attempts']); ?></td>
								<td class="text-right"><?php echo dashboardMetricValue($agentRow['answered_attempts']); ?></td>
								<td class="text-right"><?php echo dashboardMetricValue($agentRow['not_answered_attempts']); ?></td>
								<td class="text-right"><?php echo htmlspecialchars(dashboardDuration($agentRow['avg_duration_sec'])); ?></td>
							</tr>
							<?php } ?>
						</tbody>
					</table>
				</div>
				<?php } else { ?>
				<div class="empty-state mb-0">No dialer agent performance data for the selected period.</div>
				<?php } ?>
			</div>
		</div>
	</div>

	<div class="col-lg-6">
		<div class="card analytics-panel">
			<div class="card-body">
				<div class="dashboard-section-title mb-3">Rating Agent Snapshot</div>
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
				<div class="empty-state mb-0">No rating agent data for the selected period.</div>
				<?php } ?>
			</div>
		</div>
	</div>
</div>