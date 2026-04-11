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

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Scheduled Calls</h5>
                    <h6 class="card-subtitle text-muted">Pending callbacks/retries set by disposition. Calls attempted by dialer are auto-marked done and removed from this list.</h6>
                </div>
                <div class="card-body">
                    <table id="scheduledCallsTable" class="table table-striped table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <?php if (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin'): ?>
                                <th>Company</th>
                                <?php endif; ?>
                                <th>Campaign</th>
                                <th>Phone</th>
                                <th>Name</th>
                                <th>Agent</th>
                                <?php if (isset($_SESSION['erole']) && in_array($_SESSION['erole'], ['company_admin', 'manager'], true)): ?>
                                <th>Route To</th>
                                <?php endif; ?>
                                <th>Disposition</th>
                                <th>Scheduled For</th>
                                <th>Status</th>
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

<script>
$(document).ready(function() {
    const isSuperAdmin = <?php echo (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') ? 'true' : 'false'; ?>;
    const currentRole = <?php echo json_encode($_SESSION['erole'] ?? $_SESSION['role'] ?? ''); ?>;
    const showFallbackRoute = ['company_admin', 'manager'].indexOf(currentRole) !== -1;
    let scheduledTable;
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
                    tooltipTitle = (last.date || "") + "<br>" + (last.note || "") + "<br>By: " + (last.user || "Unknown");
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
                    tooltipTitle = match ? (match[1] + "<br>" + match[3] + "<br>By: " + match[2]) : lastNote;
                }
            }
        }

        var tooltipAttr = tooltipTitle ? 'data-toggle="tooltip" data-html="true" data-tooltip-content="' + tooltipTitle.replace(/"/g, '&quot;') + '"' : '';
        var iconHtml = tooltipTitle ? '<i class="fas fa-sticky-note text-primary mr-2" style="cursor:pointer; font-size: 1.2em;" ' + tooltipAttr + '></i>' : '<span class="mr-4"></span>';
        var currentDispo = row.last_disposition || row.disposition_label || '';
        var scheduleAt = row.next_call_at || row.scheduled_for || '';

        return '<div class="d-flex align-items-center justify-content-center">' +
            iconHtml +
            '<button class="btn btn-sm btn-info open-dispo" data-id="' + row.campaignnumber_id + '" data-notes="' + safeNotes + '" data-disposition="' + currentDispo + '" data-schedule="' + scheduleAt + '" data-timezone="' + (row.display_timezone || row.timezone || 'UTC') + '">Disposition</button>' +
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
        { data: 'disposition_label', defaultContent: '-' },
        {
            data: 'scheduled_for',
            defaultContent: '-',
            render: function(data, type, row) {
                return type === 'display' ? formatUtcDateTime(data, row.display_timezone || row.timezone) : data;
            }
        },
        { data: 'status', defaultContent: '-' },
        {
            data: null,
            orderable: false,
            render: function(data, type, row) {
                return buildDispositionAction(row);
            }
        }
    ];

    if (showFallbackRoute) {
        columns.splice(4, 0, {
            data: 'fallback_route_to',
            defaultContent: '-',
            render: function(data, type, row) {
                if (row.agent_ext || row.agent_id || row.agent_name) {
                    return '-';
                }
                return data || '-';
            }
        });
    }

    if (isSuperAdmin) {
        columns.unshift({ data: 'company_name', defaultContent: '-' });
    }

    scheduledTable = $('#scheduledCallsTable').DataTable({
        responsive: true,
        ajax: {
            url: 'campaign/get_schedule_calls_list',
            type: 'GET',
            data: function(d) {
                d.company_id = $('#companyFilter').val();
            },
            dataSrc: ''
        },
        columns: columns,
        order: [[(isSuperAdmin ? 6 : 5) + (showFallbackRoute ? 1 : 0), 'asc']]
    });

    $('#companyFilter').on('change', function() {
        scheduledTable.ajax.reload();
    });

    setInterval(function() {
        scheduledTable.ajax.reload(null, false);
    }, 30000);

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

    $('#scheduledCallsTable').on('click', '.open-dispo', function() {
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
                            displayHistory += "[" + (item.date || "") + "] " + (item.user || "Unknown") + ": " + item.note + "\n";
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
                $('#scheduledCallsTable').DataTable().ajax.reload(null, false);
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
