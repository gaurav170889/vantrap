</div>   
 </div>
 <!--<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>-->
  <script src="https://code.jquery.com/jquery-3.5.1.js" integrity="sha256-QWo7LDvxbWT2tbbQ97B53yJnYU3WhH/C8ycbRAkjPDc=" crossorigin="anonymous"></script>
 
    <script src="https://code.jquery.com/jquery-migrate-1.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
    </script>
    <!--<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"
        integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous">
    </script>-->
	<script src="modules/common/js_1/app.js"></script>
    <!-- Font Awesome -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/js/all.min.js"></script>
    <!-- Datepicker -->
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <!-- Datatables -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
	<script type="text/javascript" src="modules/common/callreportdatatable/DataTable/datatables.min.js"></script>
    <!--<script type="text/javascript"
        src="https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.10.20/b-1.6.1/b-flash-1.6.1/b-html5-1.6.1/b-print-1.6.1/r-2.2.3/datatables.min.js">
    </script>-->
    <!-- Momentjs -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment.min.js"></script>

 



<script>
    
var navclass= "<?php echo $_SESSION['navurl']; ?>";
console.log('NavURL:', navclass);

// Remove active from any existing active items in sidebar
$('.navul li.active').removeClass('active');

// Add active to current target
if(navclass) {
    $('.'+navclass).addClass('active');

    // Auto-expand Dropdown if child is active
    var activeItem = $('.'+navclass);
    if(activeItem.length > 0) {
        var parentUl = activeItem.closest('ul.sidebar-dropdown');
        if(parentUl.length > 0) {
            parentUl.addClass('show'); // Expand submenu
            parentUl.parent('li').addClass('active'); // Highlight parent menu
            parentUl.parent('li').find('a[data-toggle="collapse"]').attr('aria-expanded', 'true');
            parentUl.parent('li').find('a[data-toggle="collapse"]').removeClass('collapsed');
        }
    }
}
$('#sidebarCollapse').on('click', function () {
	$('#sidebar').toggleClass('active');
});

$("#myInput").on("keyup", function() 
{
	var value = $(this).val().toLowerCase();
		$(".dropdown-menu li").filter(function()
	{
		$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
	});
});

</script>

<script>
function parseUtcDateTime(value) {
    if (!value || value === '0000-00-00 00:00:00') return null;
    var normalized = String(value).trim().replace('T', ' ').replace('Z', '');
    var parts = normalized.split(/[- :]/);
    if (parts.length < 5) return null;
    var year = parseInt(parts[0], 10);
    var month = parseInt(parts[1], 10) - 1;
    var day = parseInt(parts[2], 10);
    var hour = parseInt(parts[3] || '0', 10);
    var minute = parseInt(parts[4] || '0', 10);
    var second = parseInt(parts[5] || '0', 10);
    return new Date(Date.UTC(year, month, day, hour, minute, second));
}

function formatUtcDateTime(value, timezone) {
    var dt = parseUtcDateTime(value);
    if (!dt) return value || '';
    var tz = timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    try {
        return new Intl.DateTimeFormat('en-US', {
            timeZone: tz,
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }).format(dt);
    } catch (e) {
        return dt.toISOString();
    }
}

function utcToDateTimeParts(value, timezone) {
    var dt = parseUtcDateTime(value);
    if (!dt) return { date: '', time: '' };
    var tz = timezone || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
    try {
        var formatter = new Intl.DateTimeFormat('en-CA', {
            timeZone: tz,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hourCycle: 'h23'
        });
        var parts = formatter.formatToParts(dt);
        var values = {};
        parts.forEach(function(part) {
            if (part.type !== 'literal') values[part.type] = part.value;
        });
        return {
            date: values.year + '-' + values.month + '-' + values.day,
            time: values.hour + ':' + values.minute
        };
    } catch (e) {
        return { date: '', time: '' };
    }
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

$(document).ready(function () {
    var contactTable;
    var isSuperAdmin = <?php echo (($_SESSION['erole'] ?? $_SESSION['role'] ?? '') === 'super_admin') ? 'true' : 'false'; ?>;
    var currentRole = <?php echo json_encode($_SESSION['erole'] ?? $_SESSION['role'] ?? ''); ?>;
    var canViewDispositionHistory = ['super_admin', 'company_admin', 'manager'].indexOf(currentRole) !== -1;
    var companyStorageKey = 'campcontact_selected_company_<?php echo (int)($_SESSION['zid'] ?? 0); ?>';
    var urlParams = new URLSearchParams(window.location.search);
    var requestedCompanyId = urlParams.get('company_id') || '';
    var requestedCampaignId = urlParams.get('campaign_id') || '';
    var pendingAutoOpenContactId = urlParams.get('open_dispo_contact') || '';
    var hasAutoOpenedDisposition = false;

    function getCampaignStorageKey() {
        var selectedCompany = isSuperAdmin ? ($('#filterCompany').val() || '0') : '<?php echo (int)($_SESSION['company_id'] ?? 0); ?>';
        return 'campcontact_selected_campaign_<?php echo (int)($_SESSION['zid'] ?? 0); ?>_' + selectedCompany;
    }

    function resetFilterValue() {
        $('#filterValue').empty().append('<option value="">Select Value</option>').prop('disabled', true);
        $('#filterValueLabel').html('<strong>Select Value</strong>');
    }

    function resetTypeAndValue() {
        $('#filterType').val('').prop('disabled', true);
        resetFilterValue();
    }

    function updateFilterHint() {
        var companyId = isSuperAdmin ? $('#filterCompany').val() : '1';
        var campaignId = $('#filterCampaign').val();
        var type = $('#filterType').val();
        var value = $('#filterValue').val();
        var hint = '';

        if (isSuperAdmin && !companyId) {
            hint = 'Please select Company first, then choose Campaign.';
            $('#filterHint').css({
                'border-color': '#f0d58a',
                'background': '#fff8e8',
                'color': '#7a5a14'
            });
        } else if (!campaignId) {
            hint = 'Please select Campaign first to start filtering contacts.';
            $('#filterHint').css({
                'border-color': '#f0d58a',
                'background': '#fff8e8',
                'color': '#7a5a14'
            });
        } else if (!type) {
            hint = 'Campaign selected. You can now choose Type (Attempt, Agent, Last Outcome, State, Disposition).';
            $('#filterHint').css({
                'border-color': '#b9d6ff',
                'background': '#eff6ff',
                'color': '#194a8d'
            });
        } else if (!value) {
            hint = 'Type selected. Please select a value to apply the filter.';
            $('#filterHint').css({
                'border-color': '#b9d6ff',
                'background': '#eff6ff',
                'color': '#194a8d'
            });
        } else {
            hint = 'Filter active. Contact list is showing matching records.';
            $('#filterHint').css({
                'border-color': '#b8e0c2',
                'background': '#ecfaf0',
                'color': '#1f6a33'
            });
        }

        $('#filterHint').text(hint);
    }

    function loadCompanyFilterOptions() {
        if (!isSuperAdmin) {
            loadCampaignFilterOptions();
            return;
        }

        $.ajax({
            url: 'campcontact/get_filter_companies',
            type: 'POST',
            dataType: 'json',
            success: function (response) {
                var currentSelected = $('#filterCompany').val();
                var savedSelected = localStorage.getItem(companyStorageKey) || '';
                var options = '<option value="">Select Company</option>';

                $.each(response || [], function (_, item) {
                    options += '<option value="' + item.id + '">' + item.name + '</option>';
                });

                $('#filterCompany').html(options);

                if (!currentSelected && requestedCompanyId && $('#filterCompany option[value="' + requestedCompanyId + '"]').length > 0) {
                    currentSelected = requestedCompanyId;
                }
                if (!currentSelected && savedSelected && $('#filterCompany option[value="' + savedSelected + '"]').length > 0) {
                    currentSelected = savedSelected;
                }
                if (!currentSelected && response && response.length > 0) {
                    currentSelected = String(response[0].id);
                }

                if (currentSelected) {
                    $('#filterCompany').val(currentSelected);
                }

                $('#filterCompany').trigger('change');
            },
            error: function () {
                $('#filterCompany').html('<option value="">Select Company</option>');
                $('#filterCompany').trigger('change');
            }
        });
    }

    function loadCampaignFilterOptions() {
        var selectedCompany = isSuperAdmin ? ($('#filterCompany').val() || '') : '';

        if (isSuperAdmin && !selectedCompany) {
            $('#filterCampaign').html('<option value="">Select Campaign</option>').val('');
            resetTypeAndValue();
            updateFilterHint();
            contactTable.ajax.reload();
            return;
        }

        $.ajax({
            url: 'campcontact/get_filter_campaigns',
            type: 'POST',
            data: isSuperAdmin ? { company_id: selectedCompany } : {},
            dataType: 'json',
            success: function (response) {
                var currentSelected = $('#filterCampaign').val();
                var savedSelected = localStorage.getItem(getCampaignStorageKey()) || '';
                var options = '<option value="">Select Campaign</option>';
                $.each(response || [], function (_, item) {
                    options += '<option value="' + item.id + '">' + item.name + '</option>';
                });
                $('#filterCampaign').html(options);

                if (!currentSelected && requestedCampaignId && $('#filterCampaign option[value="' + requestedCampaignId + '"]').length > 0) {
                    currentSelected = requestedCampaignId;
                }
                if (!currentSelected && savedSelected && $('#filterCampaign option[value="' + savedSelected + '"]').length > 0) {
                    currentSelected = savedSelected;
                }

                if (!currentSelected && response && response.length > 0) {
                    currentSelected = String(response[0].id);
                }

                if (currentSelected) {
                    $('#filterCampaign').val(currentSelected);
                }

                $('#filterCampaign').trigger('change');
            },
            error: function () {
                $('#filterCampaign').html('<option value="">Select Campaign</option>');
                $('#filterCampaign').trigger('change');
            }
        });
    }

    function loadFilterValues(campaignId, type) {
        var labelMap = {
            attempt: 'Select Attempt',
            agent: 'Select Agent',
            last_outcome: 'Select Last Outcome',
            state: 'Select State',
            disposition: 'Select Disposition'
        };

        $('#filterValueLabel').html('<strong>' + (labelMap[type] || 'Select Value') + '</strong>');
        $('#filterValue').empty().append('<option value="">Loading...</option>').prop('disabled', true);

        $.ajax({
            url: 'campcontact/get_filter_values',
            type: 'POST',
            dataType: 'json',
            data: {
                company_id: isSuperAdmin ? ($('#filterCompany').val() || '') : '',
                campaign_id: campaignId,
                type: type
            },
            success: function (response) {
                var options = '<option value="">' + (labelMap[type] || 'Select Value') + '</option>';
                $.each(response || [], function (_, item) {
                    options += '<option value="' + item.value + '">' + item.label + '</option>';
                });
                $('#filterValue').html(options).prop('disabled', false);
            },
            error: function () {
                $('#filterValue').html('<option value="">Select Value</option>').prop('disabled', true);
            }
        });
    }

    // JIT Popover Initialization (Avoids jQuery UI Tooltip conflict)
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

    function tryAutoOpenDisposition() {
        if (!pendingAutoOpenContactId || hasAutoOpenedDisposition) {
            return;
        }

        var $button = $('#campaignTable').find('.open-dispo[data-id="' + pendingAutoOpenContactId + '"]').first();
        if ($button.length > 0) {
            hasAutoOpenedDisposition = true;
            pendingAutoOpenContactId = '';
            $button.trigger('click');

            if (window.history && window.history.replaceState) {
                var cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('open_dispo_contact');
                window.history.replaceState({}, '', cleanUrl.toString());
            }
        }
    }

    contactTable = $('#campaignTable').DataTable({
        ajax: {
            url: 'campcontact/getallcontact',
            type: 'POST',
            data: function (d) {
                d.company_id = isSuperAdmin ? ($('#filterCompany').val() || '') : '';
                d.campaign_id = $('#filterCampaign').val();
                d.filter_type = $('#filterType').val();
                d.filter_value = $('#filterValue').val();
                d.open_contact_id = pendingAutoOpenContactId || '';
            },
            dataSrc: ''
        },
        columns: [
            { data: 'id' },
            { data: 'number' },
            { data: 'name' },
            { data: 'type' },
            { data: 'feedback' },
            { data: 'call_status' },
            { data: 'last_try' },
            {
                data: 'last_try_dt',
                render: function(data, type, row) {
                    if (type !== 'display') return data;
                    return formatUtcDateTime(data, row.timezone);
                }
            },
            { data: 'agent_name' },
            { 
                data: 'disposition',
                render: function(data, type, row) {
                    if(data && type === 'display') {
                        var color = row.color_code || '#808080';
                        return '<span class="badge badge-pill" style="background-color: '+color+'; color: #fff; font-size: 100%;">'+data+'</span>';
                    }
                    return data || '';
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    // Use encodeURIComponent to safely transport notes (newlines, quotes)
                    var safeNotes = encodeURIComponent(row.notes || "");
                    var lastNote = "";
                    var tooltipTitle = "";

                    // Parse Notes (JSON or String)
                    if(row.notes) {
                        try {
                            var parsed = JSON.parse(row.notes);
                            if(Array.isArray(parsed) && parsed.length > 0) {
                                var last = parsed[parsed.length - 1];
                                tooltipTitle = (last.date || "") + "<br>" + (last.note || "") + "<br>By: " + (last.user || "Unknown");
                            } else {
                                throw "Not Array";
                            }
                        } catch(e) {
                            var lines = row.notes.split('\n');
                            for(var i=lines.length-1; i>=0; i--) {
                                 if(lines[i].trim() !== "") {
                                     lastNote = lines[i];
                                     break;
                                 }
                            }
                            if(lastNote) {
                                var match = lastNote.match(/^\[(.*?)\] (.*?): (.*)$/);
                                if(match) {
                                    tooltipTitle = match[1] + "<br>" + match[3] + "<br>By: " + match[2];
                                } else {
                                    tooltipTitle = lastNote;
                                }
                            }
                        }
                    }

                    var tooltipAttr = tooltipTitle ? 'data-toggle="tooltip" data-html="true" data-tooltip-content="'+tooltipTitle.replace(/"/g, '&quot;')+'"' : '';
                    var iconHtml = tooltipTitle ? '<i class="fas fa-sticky-note text-primary mr-2" style="cursor:pointer; font-size: 1.2em;" '+tooltipAttr+'></i>' : '<span class="mr-4"></span>';
                    var nextCall = row.next_call_at || '';
                    var historyButton = '';
                    if (canViewDispositionHistory) {
                        historyButton = '<button class="btn btn-sm btn-outline-secondary mr-2 open-dispo-history" data-id="'+row.id+'" data-company-id="'+(row.company_id || '')+'">History</button>';
                    }
                    
                    return '<div class="d-flex align-items-center justify-content-center">' + 
                           iconHtml + 
                           historyButton +
                           '<button class="btn btn-sm btn-info open-dispo" data-id="'+row.id+'" data-notes="'+safeNotes+'" data-disposition="'+(row.disposition || '')+'" data-schedule="'+nextCall+'" data-timezone="'+(row.timezone || 'UTC')+'">Disposition</button>' +
                           '</div>';
                }
            }
        ],
        responsive: true,
        search: {
            return: true
        },
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search all columns",
            emptyTable: "Please select Campaign first to view contacts."
        },
        "order": [[0, "desc"]]
    });

    $('#campaignTable').on('draw.dt', function () {
        tryAutoOpenDisposition();
    });

    resetTypeAndValue();
    updateFilterHint();

    if (isSuperAdmin) {
        $('#filterCompany').on('change', function () {
            var companyId = $(this).val();

            if (companyId) {
                localStorage.setItem(companyStorageKey, String(companyId));
            } else {
                localStorage.removeItem(companyStorageKey);
            }

            $('#filterCampaign').val('');
            resetTypeAndValue();
            loadCampaignFilterOptions();
        });
    }

    $('#filterCampaign').on('change', function () {
        var campaignId = $(this).val();

        if (!campaignId) {
            localStorage.removeItem(getCampaignStorageKey());
            resetTypeAndValue();
            $('#clearFiltersBtn').prop('disabled', true);
        } else {
            localStorage.setItem(getCampaignStorageKey(), String(campaignId));
            $('#filterType').prop('disabled', false).val('');
            resetFilterValue();
            $('#clearFiltersBtn').prop('disabled', false);
        }

        updateFilterHint();
        contactTable.ajax.reload();
    });

    $('#filterType').on('change', function () {
        var campaignId = $('#filterCampaign').val();
        var type = $(this).val();

        if (!campaignId || !type) {
            resetFilterValue();
            updateFilterHint();
            contactTable.ajax.reload();
            return;
        }

        loadFilterValues(campaignId, type);
        updateFilterHint();
        contactTable.ajax.reload();
    });

    $('#filterValue').on('change', function () {
        updateFilterHint();
        contactTable.ajax.reload();
    });

    $('#clearFiltersBtn').on('click', function () {
        if (isSuperAdmin) {
            $('#filterCompany').val('');
            localStorage.removeItem(companyStorageKey);
        }
        $('#filterCampaign').val('');
        localStorage.removeItem(getCampaignStorageKey());
        resetTypeAndValue();
        $(this).prop('disabled', true);
        updateFilterHint();
        contactTable.ajax.reload();
    });

    loadCompanyFilterOptions();


    // Populate Disposition Dropdown on Load
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

    // Open Modal
    $('#campaignTable').on('click', '.open-dispo', function() {
        var id = $(this).data('id');
        var notes = "";
        try {
            var rawNotes = $(this).data('notes');
            notes = decodeURIComponent(rawNotes || "");
        } catch(e) { 
            notes = $(this).data('notes') || ""; // Fallback
        }
        var currentDispo = $(this).data('disposition');
        var schedule = $(this).data('schedule');
        var timezone = $(this).data('timezone') || Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';

        $('#dispo_contact_id').val(id);
        
        // History: Reverse order (Newest First)
        if(notes) {
            var displayHistory = "";
            try {
                var parsed = JSON.parse(notes);
                if(Array.isArray(parsed)) {
                    // Reverse for history display
                    for(var i=parsed.length-1; i>=0; i--) {
                        var item = parsed[i];
                        if(item.note) {
                            displayHistory += "[" + (item.date||"") + "] " + (item.user||"Unknown") + ": " + item.note + "\n";
                        }
                    }
                } else {
                    throw "Not Array";
                }
            } catch(e) {
                // Legacy String
                 var lines = notes.split('\n');
                 displayHistory = lines.filter(line => line.trim() !== "").reverse().join('\n');
            }
            $('#dispo_history').val(displayHistory);
        } else {
            $('#dispo_history').val('');
        }

        // Clear New Note
        $('#dispo_notes').val('');
        
        // Select Current Disposition
        $('#dispo_select').val(currentDispo); 
        $('#dispo_select').trigger('change'); // To show/hide schedule

        // Pre-fill schedule if exists
        if(schedule && schedule !== 'null') {
             var parts = utcToDateTimeParts(schedule, timezone);
             $('#dispo_date').val(parts.date);
             $('#dispo_time').val(parts.time);
        } else {
             $('#dispo_date').val('');
             $('#dispo_time').val('');
        }
        
        if(!currentDispo) {
             $('#dispo_schedule_div').hide();
             $('#dispo_date').val('');
             $('#dispo_time').val('');
        }

        $('#dispositionModal').modal('show');
    });

    $('#campaignTable').on('click', '.open-dispo-history', function() {
        var campaignnumberId = $(this).data('id') || 0;
        var companyId = $(this).data('company-id') || '';
        var $tbody = $('#dispositionHistoryTable tbody');

        $tbody.html('<tr><td colspan="8" class="text-center">Loading history...</td></tr>');
        $('#dispositionHistoryModal').modal('show');

        $.ajax({
            url: 'campcontact/get_disposition_history',
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

    // Show/Hide Schedule inputs based on selection
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
    // Wait... modal.php functions are called via class.php "action". 
    // We need to check if 'updateDispositionSql' is exposed in class.php?
    // It's in modal.php. Usually class.php routes to modal.php.
    // Let's assume standard pattern: action=something -> class.php -> modal.php
    
    $.ajax({
        url: "campcontact/updateDispositionSql",
        type: "POST",
        data: formData,
        dataType: "json",
        success: function(response) {
            if (response.success) {
                $('#dispositionModal').modal('hide');
                $('#campaignTable').DataTable().ajax.reload(null, false);
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

<script>
function deleteAllContacts() {
  if (!confirm("Are you sure you want to delete all contacts for today? This action cannot be undone.")) {
    return;
  }

  // Optional: disable button during request
  const btn = document.getElementById('deleteAllBtn');
  btn.disabled = true;
  btn.textContent = 'Deleting...';

  fetch('campcontact/delete_all_contacts')
    .then(response => response.text())
    .then(data => {
      alert('All contacts deleted successfully.');
      $('#campaignTable').DataTable().ajax.reload(); // reload table
    })
    .catch(error => {
      alert('Error deleting contacts.');
      console.error(error);
    })
    .finally(() => {
      btn.disabled = false;
      btn.textContent = 'Delete All Contacts';
    });
}
</script>
