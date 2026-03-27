<style>
.dashboard-shell {
	padding-bottom: 24px;
}
.dashboard-toolbar {
	background: linear-gradient(135deg, #113a5d 0%, #1f6f8b 55%, #99b898 100%);
	border-radius: 16px;
	padding: 24px;
	color: #fff;
	margin-bottom: 24px;
}
.dashboard-toolbar h3 {
	margin-bottom: 8px;
}
.dashboard-toolbar .dashboard-range {
	opacity: 0.9;
	font-size: 14px;
}
.dashboard-filter-card {
	border: 0;
	border-radius: 16px;
	box-shadow: 0 10px 30px rgba(17, 58, 93, 0.08);
}
.dashboard-filter-card .form-control,
.dashboard-filter-card .btn {
	min-height: 42px;
}
.dashboard-section-title {
	font-size: 1.1rem;
	font-weight: 700;
	margin-bottom: 16px;
	color: #113a5d;
}
.metric-card {
	border: 0;
	border-radius: 16px;
	box-shadow: 0 10px 24px rgba(31, 111, 139, 0.08);
	height: 100%;
}
.metric-label {
	font-size: 0.82rem;
	text-transform: uppercase;
	letter-spacing: 0.04em;
	color: #6c7a89;
	margin-bottom: 10px;
}
.metric-value {
	font-size: 2rem;
	font-weight: 700;
	color: #12343b;
	line-height: 1.1;
}
.metric-note {
	margin-top: 10px;
	font-size: 0.9rem;
	color: #6c7a89;
}
.analytics-panel {
	border: 0;
	border-radius: 16px;
	box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
	margin-bottom: 24px;
}
.analytics-panel .card-body {
	padding: 22px;
}
.distribution-row {
	display: flex;
	align-items: center;
	margin-bottom: 12px;
	gap: 12px;
}
.distribution-row:last-child {
	margin-bottom: 0;
}
.distribution-label {
	width: 72px;
	font-weight: 600;
	color: #113a5d;
}
.distribution-value {
	width: 48px;
	text-align: right;
	font-weight: 600;
	color: #12343b;
}
.progress {
	flex: 1;
	height: 10px;
	border-radius: 999px;
	background: #e9eef2;
}
.progress-bar-score {
	background: linear-gradient(90deg, #ffb703 0%, #fb8500 100%);
}
.table thead th {
	border-top: 0;
	color: #113a5d;
	font-size: 0.8rem;
	text-transform: uppercase;
	letter-spacing: 0.04em;
}
.table td {
	vertical-align: middle;
}
.empty-state {
	border: 1px dashed #b8c4ce;
	border-radius: 16px;
	padding: 20px;
	background: #f8fbfc;
	color: #5b6975;
}
.kpi-chip {
	display: inline-flex;
	align-items: center;
	padding: 6px 10px;
	border-radius: 999px;
	background: #edf6f9;
	color: #125b50;
	font-size: 0.85rem;
	font-weight: 600;
	margin-right: 8px;
	margin-bottom: 8px;
}
.dashboard-loading {
	padding: 40px 20px;
	text-align: center;
	color: #5b6975;
	background: #f8fbfc;
	border: 1px dashed #b8c4ce;
	border-radius: 16px;
}
@media (max-width: 767px) {
	.dashboard-toolbar,
	.dashboard-filter-card .card-body,
	.analytics-panel .card-body {
		padding: 16px;
	}
	.metric-value {
		font-size: 1.5rem;
	}
}
</style>

<main class="content dashboard-shell">
	<div class="container-fluid p-0">
		<div class="dashboard-toolbar">
			<div class="row align-items-center">
				<div class="col-lg-8">
					<h3>Outbound Dialer and Rating Dashboard</h3>
					<div class="dashboard-range" id="dashboardRangeLabel">Loading selected period...</div>
				</div>
				<div class="col-lg-4 mt-3 mt-lg-0" id="dashboardTopChips"></div>
			</div>
		</div>

		<div class="card dashboard-filter-card mb-4">
			<div class="card-body">
				<form id="dashboardFilterForm">
					<div class="row align-items-end">
						<div class="col-md-4">
							<label><b>Period</b></label>
							<select name="period" id="dashboardPeriod" class="form-control">
								<?php foreach ($periodOptions as $periodKey => $periodLabel) { ?>
									<option value="<?php echo htmlspecialchars($periodKey); ?>" <?php echo $period === $periodKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($periodLabel); ?></option>
								<?php } ?>
							</select>
						</div>
						<div class="col-md-4 mt-3 mt-md-0">
							<div class="text-muted">Analytics are loaded by AJAX from PHP/MySQL for the selected period.</div>
						</div>
						<div class="col-md-4 mt-3 mt-md-0 text-md-right">
							<button type="submit" class="btn btn-primary px-4">Apply Filter</button>
						</div>
					</div>
				</form>
			</div>
		</div>

		<div id="dashboardContent" class="dashboard-loading">Loading dashboard analytics...</div>
	</div>
</main>

<script>
(function() {
	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/\"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function setLoading(message) {
		document.getElementById('dashboardContent').innerHTML = '<div class="dashboard-loading">' + escapeHtml(message) + '</div>';
	}

	function updateUrl(period) {
		if (!window.history || !window.history.replaceState) {
			return;
		}
		var url = new URL(window.location.href);
		url.searchParams.set('route', 'dashboard/index');
		url.searchParams.set('period', period);
		window.history.replaceState({}, '', url.toString());
	}

	function loadDashboard(period) {
		setLoading('Loading dashboard analytics...');
		fetch('<?php echo BASE_URL; ?>?route=dashboard/analytics&period=' + encodeURIComponent(period), {
			credentials: 'same-origin'
		})
		.then(function(response) {
			return response.json();
		})
		.then(function(payload) {
			if (!payload || payload.status !== 101) {
				throw new Error('Invalid dashboard response');
			}
			document.getElementById('dashboardContent').innerHTML = payload.html || '<div class="dashboard-loading">No dashboard content returned.</div>';
			var periodSelect = document.getElementById('dashboardPeriod');
			if (periodSelect && payload.period) {
				periodSelect.value = payload.period;
			}
			var rangeNode = document.getElementById('dashboard-period-range');
			var chipsNode = document.getElementById('dashboard-period-chips');
			document.getElementById('dashboardRangeLabel').textContent = rangeNode ? rangeNode.textContent : '';
			document.getElementById('dashboardTopChips').innerHTML = chipsNode ? chipsNode.innerHTML : '';
			updateUrl(payload.period || period);
		})
		.catch(function(error) {
			setLoading('Unable to load dashboard analytics. ' + error.message);
		});
	}

	document.addEventListener('DOMContentLoaded', function() {
		var form = document.getElementById('dashboardFilterForm');
		var periodSelect = document.getElementById('dashboardPeriod');

		form.addEventListener('submit', function(event) {
			event.preventDefault();
			loadDashboard(periodSelect.value);
		});

		loadDashboard(periodSelect.value);
	});
})();
</script>

<?php include('modules/common/footer_1.php'); ?>