<main class="content">
    <div class="container-fluid p-0">
        <div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
            <div class="container">
                <div class="row justify-content-end">
                    <div class="col-lg-12 text-right">
                        <?php if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin'): ?>
                        <div class="form-group d-inline-block mr-2" style="max-width: 220px; text-align: left;">
                            <select class="form-control" id="companyFilter">
                                <option value="">All Companies</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php $currentRole = $_SESSION['erole'] ?? $_SESSION['role'] ?? ''; ?>
            <?php if (in_array($currentRole, ['super_admin', 'company_admin', 'manager'], true)): ?>
            <div class="container mb-3">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="<?php echo ($currentRole === 'super_admin') ? 'col-md-3' : 'col-md-4'; ?> mb-2">
                                <label for="campaignFilter"><strong>Select Campaign</strong></label>
                                <select class="form-control" id="campaignFilter">
                                    <option value="">All Campaigns</option>
                                    <?php foreach (($campaigns ?? []) as $campaign): ?>
                                        <option value="<?php echo $campaign['id']; ?>"><?php echo htmlspecialchars($campaign['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="<?php echo ($currentRole === 'super_admin') ? 'col-md-3' : 'col-md-4'; ?> mb-2">
                                <label for="dateFilter"><strong>Date Range</strong></label>
                                <select class="form-control" id="dateFilter">
                                    <option value="today" selected>Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="this_week">This Week</option>
                                    <option value="last_week">Last Week</option>
                                    <option value="this_month">This Month</option>
                                    <option value="custom">Custom Date</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2" id="customDateWrap" style="display:none;">
                                <label><strong>Custom Range</strong></label>
                                <div class="d-flex">
                                    <input type="datetime-local" class="form-control mr-2" id="customStart">
                                    <input type="datetime-local" class="form-control" id="customEnd">
                                </div>
                            </div>
                            <div class="<?php echo ($currentRole === 'super_admin') ? 'col-md-3' : 'col-md-4'; ?> mb-2">
                                <label for="stateFilter"><strong>Select State</strong></label>
                                <select class="form-control" id="stateFilter">
                                    <option value="">All States</option>
                                    <option value="READY">READY</option>
                                    <option value="NOT_DIALED">NOT_DIALED</option>
                                    <option value="RETRY">RETRY</option>
                                    <option value="DIAL_FAILED">DIAL_FAILED</option>
                                    <option value="SCHEDULED">SCHEDULED</option>
                                </select>
                            </div>
                            <div class="<?php echo ($currentRole === 'super_admin') ? 'col-md-3' : 'col-md-4'; ?> mb-2">
                                <label for="lastCallFilter"><strong>Select Last Call</strong></label>
                                <select class="form-control" id="lastCallFilter">
                                    <option value="">All Outcomes</option>
                                    <option value="NO_ANSWER">NO_ANSWER</option>
                                    <option value="FAILED">FAILED</option>
                                    <option value="BUSY">BUSY</option>
                                    <option value="CANCELLED">CANCELLED</option>
                                    <option value="UNREACHABLE">UNREACHABLE</option>
                                    <option value="VOICEMAIL">VOICEMAIL</option>
                                    <option value="TRANSFERRED">TRANSFERRED</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2 d-flex align-items-end">
                                <button type="button" class="btn btn-primary w-100" id="moveNotDialedBtn">Move Filtered To READY</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Not Dialed Numbers</h5>
                </div>
                <div class="card-body">
                    <table id="notDialedTable" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <?php if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin'): ?>
                                <th>Company</th>
                                <?php endif; ?>
                                <th>Campaign</th>
                                <th>Phone</th>
                                <th>Name</th>
                                <th>State</th>
                                <th>Last Outcome</th>
                                <th>Attempts</th>
                                <th>Created At</th>
                                <th>Next Call At</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
$(document).ready(function() {
    const isSuperAdmin = <?php echo (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') ? 'true' : 'false'; ?>;
    const currentRole = <?php echo json_encode($currentRole); ?>;
    const canUseAdvancedFilters = ['super_admin', 'company_admin', 'manager'].indexOf(currentRole) !== -1;
    function parseUtcDateTime(value) {
        if (!value || value === '0000-00-00 00:00:00') return null;
        var parts = String(value).trim().replace('T', ' ').replace('Z', '').split(/[- :]/);
        if (parts.length < 5) return null;
        return new Date(Date.UTC(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10), parseInt(parts[3] || '0', 10), parseInt(parts[4] || '0', 10), parseInt(parts[5] || '0', 10)));
    }
    function formatUtcDateTime(value, timezone) {
        var dt = parseUtcDateTime(value);
        if (!dt) return value || '-';
        try {
            return new Intl.DateTimeFormat('en-US', {
                timeZone: timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
                year: 'numeric',
                month: 'short',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            }).format(dt);
        } catch (e) {
            return value;
        }
    }

    function toggleCustomDateFields() {
        if ($('#dateFilter').val() === 'custom') {
            $('#customDateWrap').show();
        } else {
            $('#customDateWrap').hide();
            $('#customStart').val('');
            $('#customEnd').val('');
        }
    }

    function buildFilterPayload() {
        return {
            company_id: $('#companyFilter').val(),
            campaign_id: $('#campaignFilter').val(),
            state_filter: $('#stateFilter').val(),
            last_call_filter: $('#lastCallFilter').val(),
            date_filter: $('#dateFilter').val() || 'today',
            custom_start: $('#customStart').val(),
            custom_end: $('#customEnd').val()
        };
    }

    const columns = [
        { data: 'campaign_name', defaultContent: '-' },
        { data: 'phone_e164', defaultContent: '-' },
        {
            data: null,
            render: function(data, type, row) {
                return ((row.first_name || '') + ' ' + (row.last_name || '')).trim() || '-';
            }
        },
        { data: 'state', defaultContent: '-' },
        { data: 'last_call_status', defaultContent: '-' },
        {
            data: null,
            render: function(data, type, row) {
                var used = row.attempts_used || 0;
                var max = row.max_attempts || 0;
                return used + '/' + max;
            }
        },
        {
            data: 'created_at',
            defaultContent: '-',
            render: function(data, type, row) {
                return type === 'display' ? formatUtcDateTime(data, row.timezone) : data;
            }
        },
        {
            data: 'next_call_at',
            defaultContent: '-',
            render: function(data, type, row) {
                return type === 'display' ? formatUtcDateTime(data, row.timezone) : data;
            }
        }
    ];

    if (isSuperAdmin) {
        columns.unshift({ data: 'company_name', defaultContent: '-' });
    }

    const table = $('#notDialedTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'csvHtml5', title: 'not_dialed_numbers' },
            { extend: 'excelHtml5', title: 'not_dialed_numbers' }
        ],
        ajax: {
            url: 'campaign/get_not_dialed_list',
            type: 'GET',
            data: function(d) {
                Object.assign(d, buildFilterPayload());
            },
            dataSrc: ''
        },
        columns: columns,
        order: [[isSuperAdmin ? 7 : 6, 'asc']]
    });

    $('#companyFilter').on('change', function() {
        if (isSuperAdmin && canUseAdvancedFilters) {
            $.ajax({
                url: 'campaign/get_dialed_filter_options',
                type: 'GET',
                dataType: 'json',
                data: {
                    company_id: $('#companyFilter').val()
                },
                success: function(response) {
                    var options = '<option value="">All Campaigns</option>';
                    $.each(response.campaigns || [], function(index, campaign) {
                        options += '<option value="' + campaign.id + '">' + $('<div>').text(campaign.name || ('Campaign ' + campaign.id)).html() + '</option>';
                    });
                    $('#campaignFilter').html(options);
                }
            });
        }
        table.ajax.reload();
    });

    $('#campaignFilter, #stateFilter, #lastCallFilter, #dateFilter').on('change', function() {
        toggleCustomDateFields();
        if ($('#dateFilter').val() !== 'custom') {
            table.ajax.reload();
        }
    });

    $('#customStart, #customEnd').on('change', function() {
        if ($('#dateFilter').val() === 'custom' && $('#customStart').val() && $('#customEnd').val()) {
            table.ajax.reload();
        }
    });

    $('#moveNotDialedBtn').on('click', function() {
        const payload = buildFilterPayload();
        if (payload.date_filter === 'custom' && (!payload.custom_start || !payload.custom_end)) {
            alert('Please select both custom start and end date.');
            return;
        }

        if (!confirm('Move filtered not dialed numbers with no agent connected to READY? Attempts will reset to 0 only when max attempts was reached.')) {
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).text('Moving...');

        $.ajax({
            url: 'campaign/move_not_dialed_to_ready',
            type: 'POST',
            dataType: 'json',
            data: payload,
            success: function(response) {
                if (response && response.success) {
                    alert((response.updated_count || 0) + ' number(s) moved to READY.');
                    table.ajax.reload(null, false);
                } else {
                    alert('Error: ' + ((response && response.message) || 'Unable to move numbers.'));
                }
            },
            error: function() {
                alert('Failed to move numbers to READY.');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Move Filtered To READY');
            }
        });
    });

    toggleCustomDateFields();
});
</script>
