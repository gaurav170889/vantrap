<main class="content">
<style>
  .dialed-filter-panel {
    background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
    border: 1px solid #d8e7ff;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(15, 62, 136, 0.08);
  }
  .dialed-filter-title {
    font-size: 14px;
    letter-spacing: 0.5px;
    color: #144c9e;
    margin-bottom: 10px;
    text-transform: uppercase;
    font-weight: 700;
  }
  .dialed-filter-hint {
    border-radius: 8px;
    border: 1px solid #f0d58a;
    background: #fff8e8;
    color: #7a5a14;
    padding: 10px 12px;
    font-size: 13px;
    margin-top: 12px;
  }
  .dialed-disposition-pill {
    display: inline-block;
    min-width: 96px;
    padding: 4px 10px;
    border-radius: 999px;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.4;
    text-align: center;
  }
</style>

    <div class="container-fluid p-0">
        <div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
            <div class="container">
                <?php if (!empty($showDialedFilters)): ?>
                <div class="dialed-filter-panel mb-3">
                    <div class="dialed-filter-title">Dialed Number Filters</div>
                    <div class="row" id="dialedFilterRow">
                        <?php $dialedRole = ($_SESSION['erole'] ?? $_SESSION['role'] ?? ''); ?>
                        <?php if ($dialedRole === 'uagent'): ?>
                        <div class="col-md-3 mb-2">
                            <label for="dateFilter"><strong>Date Range</strong></label>
                            <select class="form-control" id="dateFilter">
                                <option value="today" selected>Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="this_week">This Week</option>
                                <option value="last_week">Last Week</option>
                                <option value="custom">Custom Date</option>
                            </select>
                        </div>
                        <div id="customDateWrap" class="col-md-5 mb-2" style="display:none;">
                            <div class="row">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <label for="customStart"><strong>Start</strong></label>
                                    <input type="datetime-local" class="form-control" id="customStart">
                                </div>
                                <div class="col-md-6">
                                    <label for="customEnd"><strong>End</strong></label>
                                    <input type="datetime-local" class="form-control" id="customEnd">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="dispositionFilter"><strong>Select Disposition</strong></label>
                            <select class="form-control" id="dispositionFilter">
                                <option value="">All Dispositions</option>
                                <?php foreach ($dispositions as $disposition): ?>
                                    <option value="<?php echo htmlspecialchars($disposition['label'] ?? ''); ?>"><?php echo htmlspecialchars($disposition['label'] ?? ''); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <?php if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin'): ?>
                        <div class="col-md-3 mb-2">
                            <label for="companyFilter"><strong>Select Company</strong></label>
                            <select class="form-control" id="companyFilter">
                                <option value="">Select Company</option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-3 mb-2">
                            <label for="campaignFilter"><strong>Select Campaign</strong></label>
                            <select class="form-control" id="campaignFilter">
                                <option value="">Select Campaign</option>
                                <?php foreach ($campaigns as $campaign): ?>
                                    <option value="<?php echo intval($campaign['id']); ?>"><?php echo htmlspecialchars($campaign['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label for="dateFilter"><strong>Date Range</strong></label>
                            <select class="form-control" id="dateFilter">
                                <option value="today" selected>Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="this_week">This Week</option>
                                <option value="last_week">Last Week</option>
                                <option value="custom">Custom Date</option>
                            </select>
                        </div>
                        <div id="customDateWrap" class="col-md-4 mb-2" style="display:none;">
                            <div class="row">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <label for="customStart"><strong>Start</strong></label>
                                    <input type="datetime-local" class="form-control" id="customStart">
                                </div>
                                <div class="col-md-6">
                                    <label for="customEnd"><strong>End</strong></label>
                                    <input type="datetime-local" class="form-control" id="customEnd">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="agentFilter"><strong>Select Agent</strong></label>
                            <select class="form-control" id="agentFilter">
                                <option value="">All Agents</option>
                                <?php foreach ($agents as $agent): ?>
                                    <?php
                                        $agentName = trim((string)($agent['agent_name'] ?? ''));
                                        $agentExt = trim((string)($agent['agent_ext'] ?? ''));
                                        $agentLabel = trim($agentName . ($agentExt !== '' ? ' (' . $agentExt . ')' : ''));
                                        if ($agentLabel === '') {
                                            $agentLabel = 'Agent ' . ($agent['agent_id'] ?? '');
                                        }
                                    ?>
                                    <option value="<?php echo intval($agent['agent_id']); ?>"><?php echo htmlspecialchars($agentLabel); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div id="dialedFilterHint" class="dialed-filter-hint">
                        <?php if ($dialedRole === 'uagent'): ?>
                        Use filters to review your answered outbound calls by date range and disposition.
                        <?php else: ?>
                        Use filters to review answered outbound calls by company, campaign, date range, and agent.
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Dialed Numbers (Answered by Agent)</h5>
                    <h6 class="card-subtitle text-muted">Outbound calls dialed by system where call status is answered.</h6>
                </div>
                <div class="card-body">
                    <table id="dialedAnsweredTable" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <?php if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin'): ?>
                                <th>Company</th>
                                <?php endif; ?>
                                <th>Campaign</th>
                                <th>Phone</th>
                                <th>Name</th>
                                <th>Agent</th>
                                <th>Disposition</th>
                                <th>Status</th>
                                <th>Started At</th>
                                <th>Ended At</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>

<div class="modal fade" id="dispositionModal" tabindex="-1" role="dialog" aria-labelledby="dispositionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dispositionModalLabel">Update Call Disposition</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="dispositionForm">
                    <input type="hidden" id="dispo_contact_id" name="contact_id">
                    <div class="form-group">
                        <label for="dispo_select">Disposition</label>
                        <select class="form-control" id="dispo_select" name="disposition" required>
                            <option value="">Select Disposition...</option>
                        </select>
                    </div>
                    <div class="form-group" id="dispo_schedule_div" style="display:none;">
                        <label>Schedule Callback / Retry</label>
                        <div class="row">
                            <div class="col-md-6">
                                <input type="date" class="form-control" id="dispo_date" name="callback_date">
                            </div>
                            <div class="col-md-6">
                                <input type="time" class="form-control" id="dispo_time" name="callback_time">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>History</label>
                        <textarea class="form-control" id="dispo_history" rows="3" readonly style="font-size: 0.85em; background-color: #f8f9fa;"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="dispo_notes">Notes / Reason</label>
                        <textarea class="form-control" id="dispo_notes" name="notes" rows="3" placeholder="Enter reason or notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitDisposition()">Save changes</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="dispositionHistoryModal" tabindex="-1" role="dialog" aria-labelledby="dispositionHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dispositionHistoryModalLabel">Disposition History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered mb-0" id="dispositionHistoryTable">
                        <thead>
                            <tr>
                                <th>Changed At</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Previous Disposition</th>
                                <th>New Disposition</th>
                                <th>Previous Notes</th>
                                <th>New Notes</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    const isSuperAdmin = <?php echo (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') ? 'true' : 'false'; ?>;
    const currentRole = <?php echo json_encode($_SESSION['erole'] ?? $_SESSION['role'] ?? ''); ?>;
    const showDialedFilters = <?php echo !empty($showDialedFilters) ? 'true' : 'false'; ?>;
    const canViewDispositionHistory = ['super_admin', 'company_admin', 'manager'].indexOf(currentRole) !== -1;
    let dialedTable;
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
    function utcToDateTimeParts(value, timezone) {
        var dt = parseUtcDateTime(value);
        if (!dt) return { date: '', time: '' };
        try {
            var parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hourCycle: 'h23'
            }).formatToParts(dt);
            var values = {};
            parts.forEach(function(part) { if (part.type !== 'literal') values[part.type] = part.value; });
            return { date: values.year + '-' + values.month + '-' + values.day, time: values.hour + ':' + values.minute };
        } catch (e) {
            return { date: '', time: '' };
        }
    }

    function formatDuration(seconds) {
        var total = parseInt(seconds, 10);
        if (!Number.isFinite(total) || total < 0) return '-';
        var hours = Math.floor(total / 3600);
        var minutes = Math.floor((total % 3600) / 60);
        var secs = total % 60;
        return String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    }

    function formatHistoryTimestamp(value, timezone) {
        if (!value) return '';
        var formatted = formatUtcDateTime(value, timezone);
        return formatted && formatted !== '-' ? formatted : value;
    }

    function formatTextBlock(value) {
        var safe = $('<div>').text(value || '-').html();
        return '<div style="max-width:220px; white-space:pre-wrap; word-break:break-word;">' + safe + '</div>';
    }

    function normalizeHistoryNoteValue(value) {
        if (!value) return '-';
        if (typeof value !== 'string') return value;

        try {
            var parsed = JSON.parse(value);
            if (Array.isArray(parsed)) {
                var notes = parsed.map(function(item) {
                    return item && item.note ? String(item.note).trim() : '';
                }).filter(function(note) {
                    return note !== '';
                });
                return notes.length ? notes.join('\n') : '-';
            }
        } catch (e) {
        }

        return value;
    }

    function buildDispositionAction(row) {
        if (!row.campaignnumber_id) {
            return '-';
        }

        var safeNotes = encodeURIComponent(row.notes || "");
        var lastNote = "";
        var tooltipTitle = "";

        if (row.notes) {
            try {
                var parsed = JSON.parse(row.notes);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    var last = parsed[parsed.length - 1];
                    tooltipTitle = formatHistoryTimestamp(last.date || "", row.timezone) + "<br>" + (last.note || "") + "<br>By: " + (last.user || "Unknown");
                } else {
                    throw "Not Array";
                }
            } catch (e) {
                var lines = String(row.notes).split('\n');
                for (var i = lines.length - 1; i >= 0; i--) {
                    if (lines[i].trim() !== "") {
                        lastNote = lines[i];
                        break;
                    }
                }
                if (lastNote) {
                    var match = lastNote.match(/^\[(.*?)\] (.*?): (.*)$/);
                    tooltipTitle = match ? (formatHistoryTimestamp(match[1], row.timezone) + "<br>" + match[3] + "<br>By: " + match[2]) : lastNote;
                }
            }
        }

        var tooltipAttr = tooltipTitle ? 'data-toggle="tooltip" data-html="true" data-tooltip-content="' + tooltipTitle.replace(/"/g, '&quot;') + '"' : '';
        var iconHtml = tooltipTitle ? '<i class="fas fa-sticky-note text-primary mr-2" style="cursor:pointer; font-size: 1.2em;" ' + tooltipAttr + '></i>' : '<span class="mr-4"></span>';
        var currentDispo = row.last_disposition || '';
        var scheduleAt = row.next_call_at || '';

        var historyButton = '';
        if (canViewDispositionHistory) {
            historyButton = '<button class="btn btn-sm btn-outline-secondary mr-2 open-dispo-history" data-id="' + row.campaignnumber_id + '" data-company-id="' + (row.company_id || '') + '">History</button>';
        }

        return '<div class="d-flex align-items-center justify-content-center">' +
            iconHtml +
            historyButton +
            '<button class="btn btn-sm btn-info open-dispo" data-id="' + row.campaignnumber_id + '" data-notes="' + safeNotes + '" data-disposition="' + currentDispo + '" data-schedule="' + scheduleAt + '" data-timezone="' + (row.timezone || 'UTC') + '">Disposition</button>' +
            '</div>';
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
        {
            data: null,
            render: function(data, type, row) {
                const ext = row.agent_ext ? ' (' + row.agent_ext + ')' : '';
                return (row.agent_name || row.agent_id || '-') + ext;
            }
        },
        {
            data: 'last_disposition',
            defaultContent: '-',
            render: function(data, type, row) {
                if (!data) return '-';
                if (type !== 'display') return data;
                var bg = row.color_code || '#808080';
                return '<span class="dialed-disposition-pill" style="background:' + bg + ';">' + $('<div>').text(data).html() + '</span>';
            }
        },
        { data: 'call_status', defaultContent: '-' },
        {
            data: 'started_at',
            defaultContent: '-',
            render: function(data, type, row) {
                return type === 'display' ? formatUtcDateTime(data, row.timezone) : data;
            }
        },
        {
            data: 'ended_at',
            defaultContent: '-',
            render: function(data, type, row) {
                return type === 'display' ? formatUtcDateTime(data, row.timezone) : data;
            }
        },
        {
            data: 'duration_sec',
            defaultContent: '-',
            render: function(data, type) {
                return type === 'display' ? formatDuration(data) : data;
            }
        },
        {
            data: null,
            orderable: false,
            render: function(data, type, row) {
                return buildDispositionAction(row);
            }
        }
    ];

    if (isSuperAdmin) {
        columns.unshift({ data: 'company_name', defaultContent: '-' });
    }

    dialedTable = $('#dialedAnsweredTable').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'csvHtml5',
                title: 'dialed_numbers',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            },
            {
                extend: 'excelHtml5',
                title: 'dialed_numbers',
                exportOptions: {
                    columns: ':not(:last-child)'
                }
            }
        ],
        ajax: {
            url: 'campaign/get_dialed_numbers_list',
            type: 'GET',
            data: function(d) {
                d.company_id = $('#companyFilter').val();
                if (showDialedFilters) {
                    d.campaign_id = $('#campaignFilter').val();
                    d.agent_id = $('#agentFilter').val();
                    d.date_filter = $('#dateFilter').val();
                    d.custom_start = $('#customStart').val();
                    d.custom_end = $('#customEnd').val();
                    d.disposition_filter = $('#dispositionFilter').val();
                }
            },
            dataSrc: ''
        },
        columns: columns,
        order: [[isSuperAdmin ? 7 : 6, 'desc']]
    });

    function buildAgentOption(agent) {
        var name = String(agent.agent_name || '').trim();
        var ext = String(agent.agent_ext || '').trim();
        var label = (name + (ext ? ' (' + ext + ')' : '')).trim() || ('Agent ' + agent.agent_id);
        return '<option value="' + agent.agent_id + '">' + $('<div>').text(label).html() + '</option>';
    }

    function buildCampaignOption(campaign) {
        return '<option value="' + campaign.id + '">' + $('<div>').text(campaign.name || ('Campaign ' + campaign.id)).html() + '</option>';
    }

    function loadDialedFilterOptions() {
        if (!showDialedFilters) return;

        $.ajax({
            url: 'campaign/get_dialed_filter_options',
            type: 'GET',
            dataType: 'json',
            data: {
                company_id: $('#companyFilter').val()
            },
            success: function(response) {
                var campaignOptions = '<option value="">Select Campaign</option>';
                $.each(response.campaigns || [], function(index, campaign) {
                    campaignOptions += buildCampaignOption(campaign);
                });
                $('#campaignFilter').html(campaignOptions);

                var agentOptions = '<option value="">All Agents</option>';
                $.each(response.agents || [], function(index, agent) {
                    agentOptions += buildAgentOption(agent);
                });
                $('#agentFilter').html(agentOptions);

                var dispositionOptions = '<option value="">All Dispositions</option>';
                $.each(response.dispositions || [], function(index, item) {
                    var label = item.label || '';
                    var safeLabel = $('<div>').text(label).html();
                    dispositionOptions += '<option value="' + safeLabel + '">' + safeLabel + '</option>';
                });
                $('#dispositionFilter').html(dispositionOptions);

                dialedTable.ajax.reload();
            }
        });
    }

    $('#companyFilter').on('change', function() {
        loadDialedFilterOptions();
    });

    function toggleCustomDateFields() {
        if ($('#dateFilter').val() === 'custom') {
            $('#customDateWrap').show();
        } else {
            $('#customDateWrap').hide();
            $('#customStart').val('');
            $('#customEnd').val('');
        }
    }

    $('#campaignFilter, #agentFilter, #dispositionFilter').on('change', function() {
        dialedTable.ajax.reload();
    });

    $('#dateFilter').on('change', function() {
        toggleCustomDateFields();
        if ($('#dateFilter').val() !== 'custom') {
            dialedTable.ajax.reload();
        }
    });

    $('#customStart, #customEnd').on('change', function() {
        if ($('#dateFilter').val() === 'custom' && $('#customStart').val() && $('#customEnd').val()) {
            dialedTable.ajax.reload();
        }
    });

    toggleCustomDateFields();

    $('body').on('mouseenter', '[data-toggle="tooltip"]', function() {
        if (!$(this).data('bs.popover')) {
            $(this).popover({
                html: true,
                container: 'body',
                trigger: 'hover',
                placement: 'top',
                content: function() {
                    return $(this).data('tooltip-content');
                }
            }).popover('show');
        }
    });

    $.ajax({
        url: "disposition/getdisposition",
        type: "POST",
        data: { action: "getdisposition" },
        dataType: "json",
        success: function(response) {
            var options = '<option value="">Select Disposition...</option>';
            $.each(response, function(index, item) {
                options += '<option value="' + item.label + '" data-type="' + item.action_type + '">' + item.label + '</option>';
            });
            $('#dispo_select').html(options);
        }
    });

    $('#dialedAnsweredTable').on('click', '.open-dispo', function() {
        var notes = "";
        try {
            notes = decodeURIComponent($(this).data('notes') || "");
        } catch (e) {
            notes = $(this).data('notes') || "";
        }

        var currentDispo = $(this).data('disposition') || '';
        var schedule = $(this).data('schedule') || '';
        var timezone = $(this).data('timezone') || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
        $('#dispo_contact_id').val($(this).data('id'));

        if (notes) {
            var displayHistory = "";
            try {
                var parsed = JSON.parse(notes);
                if (Array.isArray(parsed)) {
                    for (var i = parsed.length - 1; i >= 0; i--) {
                        var item = parsed[i];
                        if (item.note) {
                            displayHistory += "[" + formatHistoryTimestamp(item.date || "", timezone) + "] " + (item.user || "Unknown") + ": " + item.note + "\n";
                        }
                    }
                } else {
                    throw "Not Array";
                }
            } catch (e) {
                displayHistory = notes.split('\n').filter(function(line) {
                    return line.trim() !== "";
                }).reverse().join('\n');
            }
            $('#dispo_history').val(displayHistory);
        } else {
            $('#dispo_history').val('');
        }

        $('#dispo_notes').val('');
        $('#dispo_select').val(currentDispo).trigger('change');

        if (schedule && schedule !== 'null') {
            var parts = utcToDateTimeParts(schedule, timezone);
            $('#dispo_date').val(parts.date);
            $('#dispo_time').val(parts.time);
        } else {
            $('#dispo_date').val('');
            $('#dispo_time').val('');
        }

        if (!currentDispo) {
            $('#dispo_schedule_div').hide();
            $('#dispo_date').val('');
            $('#dispo_time').val('');
        }

        $('#dispositionModal').modal('show');
    });

    $('#dialedAnsweredTable').on('click', '.open-dispo-history', function() {
        var campaignnumberId = $(this).data('id') || 0;
        var companyId = $(this).data('company-id') || '';
        var $tbody = $('#dispositionHistoryTable tbody');

        $tbody.html('<tr><td colspan="8" class="text-center">Loading history...</td></tr>');
        $('#dispositionHistoryModal').modal('show');

        $.ajax({
            url: 'campaign/get_disposition_history',
            type: 'GET',
            dataType: 'json',
            data: {
                campaignnumber_id: campaignnumberId,
                company_id: companyId
            },
            success: function(response) {
                if (!Array.isArray(response) || response.length === 0) {
                    $tbody.html('<tr><td colspan="8" class="text-center">No disposition history found.</td></tr>');
                    return;
                }

                var rows = '';
                $.each(response, function(index, item) {
                    rows += '<tr>' +
                        '<td>' + $('<div>').text(formatHistoryTimestamp(item.created_at || '', item.timezone || 'UTC')).html() + '</td>' +
                        '<td>' + $('<div>').text(item.changed_by_email || '-').html() + '</td>' +
                        '<td>' + $('<div>').text(item.changed_by_role || '-').html() + '</td>' +
                        '<td>' + $('<div>').text(item.action_type || '-').html() + '</td>' +
                        '<td>' + formatTextBlock(item.previous_disposition || '-') + '</td>' +
                        '<td>' + formatTextBlock(item.new_disposition || '-') + '</td>' +
                        '<td>' + formatTextBlock(normalizeHistoryNoteValue(item.previous_notes)) + '</td>' +
                        '<td>' + formatTextBlock(normalizeHistoryNoteValue(item.new_notes)) + '</td>' +
                    '</tr>';
                });
                $tbody.html(rows);
            },
            error: function() {
                $tbody.html('<tr><td colspan="8" class="text-center text-danger">Failed to load disposition history.</td></tr>');
            }
        });
    });

    $('#dispo_select').change(function() {
        var selected = $(this).find(':selected');
        var type = String(selected.data('type') || "").toLowerCase();
        if (type === 'callback' || type === 'retry') {
            $('#dispo_schedule_div').show();
        } else {
            $('#dispo_schedule_div').hide();
        }
    });
});

function submitDisposition() {
    var formData = $('#dispositionForm').serialize();
    formData += '&action=updateDispositionSql';

    $.ajax({
        url: "campcontact/updateDispositionSql",
        type: "POST",
        data: formData,
        dataType: "json",
        success: function(response) {
            if (response.success) {
                $('#dispositionModal').modal('hide');
                $('#dialedAnsweredTable').DataTable().ajax.reload(null, false);
                alert("Disposition Updated!");
            } else {
                alert("Error: " + (response.error || "Unknown error"));
            }
        },
        error: function() {
            alert("Failed to update disposition (AJAX Error).");
        }
    });
}
</script>
