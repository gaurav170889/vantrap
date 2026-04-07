<style>
.dashboard-shell {
	padding-bottom: 24px;
	background: #f4f6fb;
}
.dashboard-toolbar {
	background: #fff;
	border: 1px solid #e4e8ef;
	border-radius: 12px;
	padding: 18px 20px;
	color: #243447;
	margin-bottom: 20px;
	box-shadow: 0 8px 20px rgba(15, 35, 64, 0.05);
}
.dashboard-toolbar.outbound-view {
	border-left: 4px solid #3973e6;
}
.dashboard-toolbar.rating-view {
	border-left: 4px solid #e07a24;
}
.dashboard-toolbar h3 {
	margin-bottom: 4px;
	font-size: 1.9rem;
}
.dashboard-toolbar .dashboard-description {
	font-size: 0.93rem;
	color: #607086;
}
.dashboard-pill-group {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	justify-content: flex-end;
}
.dashboard-pill {
	appearance: none;
	border: 1px solid #b8c4d4;
	background: #fff;
	color: #58677b;
	border-radius: 999px;
	padding: 6px 12px;
	font-size: 12px;
	line-height: 1.2;
	cursor: pointer;
	transition: all .2s ease;
}
.dashboard-pill:hover,
.dashboard-pill.active {
	background: #3973e6;
	border-color: #3973e6;
	color: #fff;
	box-shadow: 0 8px 16px rgba(57, 115, 230, 0.18);
}
.dashboard-meta-strip {
	margin-top: 14px;
	padding: 10px 12px;
	border: 1px solid #e6eaef;
	border-radius: 8px;
	background: #fafbfd;
	display: flex;
	justify-content: space-between;
	align-items: center;
	gap: 12px;
	font-size: 13px;
	color: #58677b;
}
.dashboard-chip-host {
	display: flex;
	flex-wrap: wrap;
	justify-content: flex-end;
}
.dashboard-section-title {
	font-size: 1.05rem;
	font-weight: 700;
	margin-bottom: 16px;
	color: #233447;
}
.metric-card {
	border: 0;
	border-radius: 6px;
	box-shadow: none;
	height: 100%;
	overflow: hidden;
	position: relative;
	color: #fff;
}
.metric-card:after {
	content: '';
	position: absolute;
	right: -18px;
	top: -18px;
	width: 92px;
	height: 92px;
	border-radius: 50%;
	background: rgba(255, 255, 255, 0.12);
}
.metric-card .card-body {
	position: relative;
	z-index: 1;
	padding: 16px 16px 14px;
}
.metric-header {
	display: flex;
	justify-content: space-between;
	align-items: flex-start;
	gap: 12px;
}
.metric-icon {
	width: 42px;
	height: 42px;
	border-radius: 50%;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 1.2rem;
	background: rgba(255, 255, 255, 0.12);
}
.metric-label {
	font-size: 0.82rem;
	font-weight: 600;
	color: rgba(255, 255, 255, 0.92);
	margin-bottom: 8px;
}
.metric-value {
	font-size: 2rem;
	font-weight: 700;
	color: #fff;
	line-height: 1.1;
}
.metric-note {
	margin-top: 8px;
	font-size: 0.86rem;
	color: rgba(255, 255, 255, 0.9);
}
.metric-theme-ocean { background: linear-gradient(135deg, #2d61d9 0%, #2952c8 100%); }
.metric-theme-success { background: linear-gradient(135deg, #0d9668 0%, #0c8b61 100%); }
.metric-theme-orange { background: linear-gradient(135deg, #d97706 0%, #c96b05 100%); }
.metric-theme-danger { background: linear-gradient(135deg, #e11d21 0%, #cf191f 100%); }
.metric-theme-cyan { background: linear-gradient(135deg, #168aad 0%, #1b7e9c 100%); }
.metric-theme-slate { background: linear-gradient(135deg, #475569 0%, #3f4d63 100%); }
.metric-theme-purple { background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); }
.metric-theme-pink { background: linear-gradient(135deg, #db2777 0%, #be185d 100%); }
.analytics-panel {
	border: 1px solid #e5e9f0;
	border-radius: 6px;
	box-shadow: none;
	margin-bottom: 18px;
	overflow: hidden;
	background: #fff;
}
.analytics-panel .card-body {
	padding: 0;
}
.panel-header-lite {
	padding: 11px 16px;
	background: #f0f2f5;
	border-bottom: 1px solid #e0e5eb;
	font-size: 0.92rem;
	font-weight: 600;
	color: #374252;
}
.panel-body-lite {
	padding: 14px 16px;
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
	background: #f0f2f5;
	color: #374252;
	font-size: 0.78rem;
	text-transform: uppercase;
	letter-spacing: 0.04em;
}
.table td {
	vertical-align: middle;
	color: #58677b;
}
.empty-state {
	border: 1px dashed #c8d1dc;
	border-radius: 8px;
	padding: 18px;
	background: #fbfcfe;
	color: #5b6975;
}
.kpi-chip {
	display: inline-flex;
	align-items: center;
	padding: 5px 9px;
	border-radius: 999px;
	background: #edf3ff;
	color: #2456b7;
	font-size: 0.82rem;
	font-weight: 600;
	margin-left: 6px;
	margin-top: 4px;
}
.dashboard-alert {
	padding: 9px 12px;
	margin-bottom: 18px;
	border-radius: 4px;
	border: 1px solid #ecd694;
	background: #fdf1c8;
	color: #8a6d3b;
	font-size: 13px;
}
.dashboard-alert .muted-copy {
	color: #6b7280;
	font-size: 12px;
}
.dashboard-loading {
	padding: 40px 20px;
	text-align: center;
	color: #5b6975;
	background: #fff;
	border: 1px dashed #cfd7e2;
	border-radius: 8px;
}
@media (max-width: 767px) {
	.dashboard-toolbar {
		padding: 14px;
	}
	.dashboard-pill-group,
	.dashboard-chip-host {
		justify-content: flex-start;
	}
	.dashboard-meta-strip {
		flex-direction: column;
		align-items: flex-start;
	}
	.metric-value {
		font-size: 1.6rem;
	}
}
</style>

<?php $isRatingView = isset($dashboardView) && $dashboardView === 'rating'; ?>

<main class="content dashboard-shell">
	<div class="container-fluid p-0">
		<div class="dashboard-toolbar <?php echo $isRatingView ? 'rating-view' : 'outbound-view'; ?>">
			<div class="row align-items-center">
				<div class="col-lg-7">
					<h3><?php echo htmlspecialchars(isset($dashboardTitle) ? $dashboardTitle : 'Outbound Call Analytics'); ?></h3>
					<div class="dashboard-description"><?php echo htmlspecialchars(isset($dashboardSubtitle) ? $dashboardSubtitle : ''); ?></div>
				</div>
				<div class="col-lg-5 mt-3 mt-lg-0">
					<form id="dashboardFilterForm">
						<label class="d-none" for="dashboardPeriod">Period</label>
						<select name="period" id="dashboardPeriod" class="d-none">
							<?php foreach ($periodOptions as $periodKey => $periodLabel) { ?>
								<option value="<?php echo htmlspecialchars($periodKey); ?>" <?php echo $period === $periodKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($periodLabel); ?></option>
							<?php } ?>
						</select>
						<div class="dashboard-pill-group">
							<?php foreach ($periodOptions as $periodKey => $periodLabel) { ?>
								<button type="button" class="dashboard-pill <?php echo $period === $periodKey ? 'active' : ''; ?>" data-period="<?php echo htmlspecialchars($periodKey); ?>"><?php echo htmlspecialchars($periodLabel); ?></button>
							<?php } ?>
						</div>
					</form>
				</div>
			</div>
			<div class="dashboard-meta-strip">
				<div><strong>Showing analytics for:</strong> <span id="dashboardRangeLabel">Loading selected period...</span></div>
				<div class="dashboard-chip-host" id="dashboardTopChips"></div>
			</div>
		</div>

		<div id="dashboardContent" class="dashboard-loading">Loading <?php echo $isRatingView ? 'rate analytics' : 'outbound analytics'; ?>...</div>
	</div>
</main>

<script>
(function() {
	var dashboardView = <?php echo json_encode(isset($dashboardView) ? $dashboardView : 'outbound'); ?>;
	var loadingLabel = dashboardView === 'rating' ? 'rate analytics' : 'outbound analytics';

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

	function syncPeriodButtons(period) {
		document.querySelectorAll('.dashboard-pill').forEach(function(button) {
			button.classList.toggle('active', button.getAttribute('data-period') === period);
		});
	}

	function updateUrl(period) {
		if (!window.history || !window.history.replaceState) {
			return;
		}
		var url = new URL(window.location.href);
		url.searchParams.set('route', 'dashboard/index');
		url.searchParams.set('period', period);
		url.searchParams.set('view', dashboardView);
		window.history.replaceState({}, '', url.toString());
	}

	function loadDashboard(period) {
		setLoading('Loading ' + loadingLabel + '...');
		fetch('<?php echo BASE_URL; ?>?route=dashboard/analytics&period=' + encodeURIComponent(period) + '&view=' + encodeURIComponent(dashboardView), {
			credentials: 'same-origin'
		})
		.then(function(response) {
			return response.json();
		})
		.then(function(payload) {
			if (!payload || payload.status !== 101) {
				throw new Error('Invalid dashboard response');
			}
			if (payload.view) {
				dashboardView = payload.view;
				loadingLabel = dashboardView === 'rating' ? 'rate analytics' : 'outbound analytics';
			}
			document.getElementById('dashboardContent').innerHTML = payload.html || '<div class="dashboard-loading">No dashboard content returned.</div>';
			var periodSelect = document.getElementById('dashboardPeriod');
			if (periodSelect && payload.period) {
				periodSelect.value = payload.period;
				syncPeriodButtons(payload.period);
			}
			var rangeNode = document.getElementById('dashboard-period-range');
			var chipsNode = document.getElementById('dashboard-period-chips');
			document.getElementById('dashboardRangeLabel').textContent = rangeNode ? rangeNode.textContent : '';
			document.getElementById('dashboardTopChips').innerHTML = chipsNode ? chipsNode.innerHTML : '';
			updateUrl(payload.period || period);
		})
		.catch(function(error) {
			setLoading('Unable to load ' + loadingLabel + '. ' + error.message);
		});
	}

	document.addEventListener('DOMContentLoaded', function() {
		var form = document.getElementById('dashboardFilterForm');
		var periodSelect = document.getElementById('dashboardPeriod');

		document.querySelectorAll('.dashboard-pill').forEach(function(button) {
			button.addEventListener('click', function() {
				var selected = this.getAttribute('data-period');
				if (periodSelect) {
					periodSelect.value = selected;
				}
				syncPeriodButtons(selected);
				loadDashboard(selected);
			});
		});

		if (form) {
			form.addEventListener('submit', function(event) {
				event.preventDefault();
				loadDashboard(periodSelect ? periodSelect.value : 'today');
			});
		}

		syncPeriodButtons(periodSelect ? periodSelect.value : 'today');
		loadDashboard(periodSelect ? periodSelect.value : 'today');
	});
})();
</script>

<?php include('modules/common/footer_1.php'); ?>