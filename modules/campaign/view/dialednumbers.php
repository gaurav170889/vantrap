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
                                <th>Status</th>
                                <th>Started At</th>
                                <th>Ended At</th>
                                <th>Duration (sec)</th>
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

<script>
$(document).ready(function() {
    const isSuperAdmin = <?php echo (isset($_SESSION['erole']) && $_SESSION['erole'] == 'super_admin') ? 'true' : 'false'; ?>;

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
        { data: 'call_status', defaultContent: '-' },
        { data: 'started_at', defaultContent: '-' },
        { data: 'ended_at', defaultContent: '-' },
        { data: 'duration_sec', defaultContent: '-' },
        {
            data: null,
            orderable: false,
            render: function(data, type, row) {
                if (!row.campaignnumber_id) {
                    return '-';
                }

                var query = [];
                if (row.company_id) query.push('company_id=' + encodeURIComponent(row.company_id));
                if (row.campaign_id) query.push('campaign_id=' + encodeURIComponent(row.campaign_id));
                query.push('open_dispo_contact=' + encodeURIComponent(row.campaignnumber_id));

                return '<a class="btn btn-sm btn-outline-primary" href="<?php echo NAVURL; ?>campcontact/?' + query.join('&') + '">Add Disposition</a>';
            }
        }
    ];

    if (isSuperAdmin) {
        columns.unshift({ data: 'company_name', defaultContent: '-' });
    }

    const table = $('#dialedAnsweredTable').DataTable({
        responsive: true,
        ajax: {
            url: 'campaign/get_dialed_numbers_list',
            type: 'GET',
            data: function(d) {
                d.company_id = $('#companyFilter').val();
            },
            dataSrc: ''
        },
        columns: columns,
        order: [[isSuperAdmin ? 7 : 6, 'desc']]
    });

    $('#companyFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
