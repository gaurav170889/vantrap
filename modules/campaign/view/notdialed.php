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
                    <h5 class="card-title">Not Dialed Numbers</h5>
                    <h6 class="card-subtitle text-muted">Shows only contacts that are at least 1 day old with no dial attempt, or calls where the dial failed / never connected properly.</h6>
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
        { data: 'created_at', defaultContent: '-' },
        { data: 'next_call_at', defaultContent: '-' }
    ];

    if (isSuperAdmin) {
        columns.unshift({ data: 'company_name', defaultContent: '-' });
    }

    const table = $('#notDialedTable').DataTable({
        responsive: true,
        ajax: {
            url: 'campaign/get_not_dialed_list',
            type: 'GET',
            data: function(d) {
                d.company_id = $('#companyFilter').val();
            },
            dataSrc: ''
        },
        columns: columns,
        order: [[isSuperAdmin ? 6 : 5, 'asc']]
    });

    $('#companyFilter').on('change', function() {
        table.ajax.reload();
    });
});
</script>
