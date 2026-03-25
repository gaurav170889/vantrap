
	<!--<div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
		<div class="container">
			
			<div  class="row justify-content-center">
				<div class="col-lg-12">
				<button type="button" class="btn btn-lg btn-primary" id="add_agent" data-toggle="modal" data-target="#exampleModalCenter" >Add Agent</button>	
				</div>
			</div>
		</div>
	</div>-->
	
	
	
		
	<!-- End Update Design Modal -->
		
	<!-- Delete Design Modal -->
		



<!-- Disposition Modal -->
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
              <!-- Populated via AJAX/JS -->
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

<style>
  .contact-filter-panel {
    background: linear-gradient(135deg, #f8fbff 0%, #eef5ff 100%);
    border: 1px solid #d8e7ff;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(15, 62, 136, 0.08);
  }
  .contact-filter-title {
    font-size: 14px;
    letter-spacing: 0.5px;
    color: #144c9e;
    margin-bottom: 10px;
    text-transform: uppercase;
    font-weight: 700;
  }
  .contact-filter-hint {
    border-radius: 8px;
    border: 1px solid #f0d58a;
    background: #fff8e8;
    color: #7a5a14;
    padding: 10px 12px;
    font-size: 13px;
    margin-top: 12px;
  }
</style>


<main class="content">
	<div class="container-fluid p-0">
		<div class="container-fluid" style="margin-top:30px;margin-bottom:20px;">
			<div class="container">
        <div class="contact-filter-panel mb-3">
          <div class="contact-filter-title">Campaign Contact Filters</div>
          <div class="row" id="contactFilterRow">
            <?php $isSuperAdmin = (($_SESSION['erole'] ?? $_SESSION['role'] ?? '') === 'super_admin'); ?>
            <?php if ($isSuperAdmin): ?>
            <div class="col-md-3 mb-2" id="superAdminCompanyWrap">
              <label for="filterCompany"><strong>Select Company</strong></label>
              <select id="filterCompany" class="form-control">
                <option value="">Select Company</option>
              </select>
            </div>
            <?php endif; ?>
            <div class="<?php echo $isSuperAdmin ? 'col-md-3' : 'col-md-4'; ?> mb-2">
              <label for="filterCampaign"><strong>Select Campaign</strong></label>
              <select id="filterCampaign" class="form-control">
                <option value="">Select Campaign</option>
              </select>
            </div>
            <div class="<?php echo $isSuperAdmin ? 'col-md-2' : 'col-md-3'; ?> mb-2">
              <label for="filterType"><strong>Select Type</strong></label>
              <select id="filterType" class="form-control" disabled>
                <option value="">Select Type</option>
                <option value="attempt">Attempt</option>
                <option value="agent">Agent</option>
                <option value="last_outcome">Last Outcome</option>
                <option value="state">State</option>
                <option value="disposition">Disposition</option>
              </select>
            </div>
            <div class="<?php echo $isSuperAdmin ? 'col-md-2' : 'col-md-3'; ?> mb-2">
              <label for="filterValue" id="filterValueLabel"><strong>Select Value</strong></label>
              <select id="filterValue" class="form-control" disabled>
                <option value="">Select Value</option>
              </select>
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
              <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary w-100" disabled>Clear Filters</button>
            </div>
          </div>
          <div id="filterHint" class="contact-filter-hint">
            Please select Campaign first to start filtering contacts.
          </div>
        </div>
			</div>
		</div>
		<div class="d-flex justify-content-end mb-2">
          <button id="deleteAllBtn" class="btn btn-danger btn-sm" onclick="deleteAllContacts()">
            Delete All Contacts
          </button>
        </div>
			<table id="campaignTable" class="table table-striped table-bordered" style="width:100%">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Number</th>
                  <th>Name</th>
                  <th>Type</th>
                  <th>Last Outcome</th>
                  <th>State</th>
                  <th>Attempts</th>
                  <th>Last Attempt</th>
                  <th>Agent</th>
                  <th>Disposition</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
	</div>
</main>

