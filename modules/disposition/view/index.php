
<style>
    .disposition-filter-panel {
        background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
        border: 1px solid #d8e7ff;
        border-radius: 10px;
        padding: 16px;
        box-shadow: 0 2px 8px rgba(15, 62, 136, 0.08);
    }

    .disposition-filter-title {
        font-size: 14px;
        letter-spacing: 0.5px;
        color: #144c9e;
        margin-bottom: 6px;
        text-transform: uppercase;
        font-weight: 700;
    }

    .disposition-filter-subtitle {
        font-size: 13px;
        color: #5e6f8d;
        margin: 0;
    }

    .disposition-form-grid .form-group {
        margin-bottom: 1rem;
    }

    .disposition-table-wrap {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    #dispositionTable {
        width: 100% !important;
        min-width: 920px;
    }

    #dispositionTable td,
    #dispositionTable th {
        white-space: nowrap;
        vertical-align: middle;
    }

    #dispositionTable td:last-child {
        min-width: 130px;
    }

    #dispositionTable .btn {
        margin: 2px 2px 2px 0;
    }

    @media (max-width: 767.98px) {
        #dispositionTable {
            min-width: 760px;
            font-size: 12px;
        }

        #dispositionTable .btn {
            font-size: 11px;
            padding: 0.2rem 0.45rem;
        }

        .disposition-filter-panel .btn {
            width: 100%;
        }
    }
</style>

<main class="content">
<div class="container-fluid p-0">
    <div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
        <div class="container">
            <div class="disposition-filter-panel mb-3">
                <div class="row align-items-end">
                    <div class="col-md-8 mb-2 mb-md-0">
                        <div class="disposition-filter-title">Disposition Management</div>
                        <p class="disposition-filter-subtitle">Create and maintain call outcomes used by agents.</p>
                    </div>
                    <div class="col-md-4 text-md-right">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addDispositionModal">Add Disposition</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="disposition-table-wrap">
                    <table id="dispositionTable" class="table table-striped table-bordered" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Action Type</th>
                                <th>Color</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data as $row): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['code']; ?></td>
                                <td><?php echo $row['label']; ?></td>
                                <td><?php echo $row['action_type']; ?></td>
                                <td>
                                    <span class="badge badge-pill" style="background-color: <?php echo $row['color_code'] ?? '#808080'; ?>; color: #fff;">
                                        <?php echo $row['color_code'] ?? '#808080'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-btn" 
                                            data-id="<?php echo $row['id']; ?>" 
                                            data-code="<?php echo $row['code']; ?>" 
                                            data-name="<?php echo $row['label']; ?>" 
                                            data-action="<?php echo $row['action_type']; ?>"
                                            data-color="<?php echo $row['color_code'] ?? '#808080'; ?>">Edit</button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $row['id']; ?>">Delete</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
    </div>
</div>
</main>

<!-- Add Modal -->
<div class="modal fade" id="addDispositionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Disposition</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="addDispositionForm">
                <div class="modal-body">
                    <div class="row disposition-form-grid">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Code</label>
                                <input type="text" class="form-control" name="code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Action Type</label>
                                <select class="form-control" name="action_type" required>
                                    <option value="CLOSE">CLOSE</option>
                                    <option value="CALLBACK">CALLBACK</option>
                                    <option value="RETRY">RETRY</option>
                                    <option value="DROP_VM">DROP_VM</option>
                                    <option value="INVALID">INVALID</option>
                                    <option value="GLOBAL_DNC">GLOBAL_DNC</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Color Code</label>
                                <input type="color" class="form-control" name="color_code" value="#808080">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editDispositionModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Disposition</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editDispositionForm">
                <div class="modal-body">
                    <input type="hidden" name="dataval" id="edit_id">
                    <div class="row disposition-form-grid">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Code</label>
                                <input type="text" class="form-control" name="code" id="edit_code" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Action Type</label>
                                <select class="form-control" name="action_type" id="edit_action" required>
                                    <option value="CLOSE">CLOSE</option>
                                    <option value="CALLBACK">CALLBACK</option>
                                    <option value="RETRY">RETRY</option>
                                    <option value="DROP_VM">DROP_VM</option>
                                    <option value="INVALID">INVALID</option>
                                    <option value="GLOBAL_DNC">GLOBAL_DNC</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Color Code</label>
                                <input type="color" class="form-control" name="color_code" id="edit_color" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
