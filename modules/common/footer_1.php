	</div>
</div>

	<!--<script src="js/vendor.js"></script>-->
	<script src="modules/common/js_1/app.js"></script>
	<!--<script src="https://code.jquery.com/jquery-3.3.1.min.js" type="text/javascript"></script>-->

<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

	
<script type="text/javascript">
$(document).ready(function (){
	
		var navclass= "<?php echo $_SESSION['navurl']; ?>";
		var check = $('.navul li.active').attr('class') ? $('.navul li.active').attr('class').split(' ')[2] : null;
		
		// Remove previous active
		if(check!==null && check!=undefined)
		{
			$('.'+check).removeClass('active');
		}

        // Add new active
		if(navclass) {
            var activeItem = $('.'+navclass);
            activeItem.addClass('active');

            // Handle Nested Menu Expansion
            var parentDropdown = activeItem.closest('.sidebar-dropdown');
            if(parentDropdown.length > 0) {
                parentDropdown.addClass('show'); // Expand ul
                var parentLi = parentDropdown.closest('.sidebar-item');
                parentLi.addClass('active'); // Activate parent li
                parentLi.find('[data-toggle="collapse"]').removeClass('collapsed'); // Rotate arrow if needed
                parentLi.find('[data-toggle="collapse"]').attr('aria-expanded', 'true');
            }
        }
	/*$('#tbl_rec').load('agent/record');	
	$('#search').keyup(function (){
		var search_data = $(this).val();
		$('#tbl_rec').load('agent/record', {keyword:search_data});
	});*/
	/*function getresult(url) {
		var page = url;
		$.ajax({
			url:'agent/record',
			type: "GET",
			data:  {page:page},
			//beforeSend: function(){$("#overlay").show();},
			success: function(data){
			$("#pagination-result").html(data);
			setInterval(function() {$("#overlay").hide(); },500);
			},
			error: function() 
			{} 	        
	   });
	}*/
	
	// show all agent 
	
	
	
	$('#sidebarCollapse').on('click', function () 
	{
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

	$(document).on("click", "#add_user", function(){
		$('#manager_scope').val('all');
		$('#manager_agent_search').val('');
		$('#managerAgentsWrap').hide();
		$('#managerScopeWrap').hide();
		$('#roleagent').hide();
		$('#msg_7').text('');
		
		var groupname = "grpname";
		$.ajax({
   		type:'POST',
		url:'users/getagent',
                data: {depart:groupname, company_id: $('#user_company_id').val()},
                dataType: 'json',
                success:function(response){
                $("#agent").empty();
				$("#agent").append("<option value='' selected>Choose...</option>");
				$("#manager_agents").empty();

				if (Array.isArray(response)) {
					for (var i = 0; i < response.length; i++) {
						var id = response[i]['agent_id'];
						var agentname = response[i]['agent_name'];
						var agentext= response[i]['agent_ext'];
						var optionLabel = agentname + " (" + agentext + ")";
						$("#agent").append("<option value='"+id+"'>"+optionLabel+"</option>");
						$("#manager_agents").append("<option value='"+id+"'>"+optionLabel+"</option>");
					}
				}
               }
           });		
	
	});

	$('#user_company_id').on('change', function() {
		if ($('#exampleModalCenter').hasClass('show')) {
			$('#add_user').trigger('click');
		}
	});

	$("#role").change(function(){
		$(this).find("option:selected").each(function(){
			var optionValue = $(this).attr("value");
			if(optionValue == "uagent") {
				$("#roleagent").show();
				$("#managerScopeWrap").hide();
				$("#managerAgentsWrap").hide();
			} else if(optionValue == "manager") {
				$("#roleagent").hide();
				$("#managerScopeWrap").show();
				if($("#manager_scope").val() === "selected") {
					$("#managerAgentsWrap").show();
				} else {
					$("#managerAgentsWrap").hide();
				}
			} else {
				$("#roleagent").hide();
				$("#managerScopeWrap").hide();
				$("#managerAgentsWrap").hide();
			}
		});
	}).change();

	$("#manager_scope").on("change", function(){
		if($(this).val() === "selected") {
			$("#managerAgentsWrap").show();
		} else {
			$("#managerAgentsWrap").hide();
			$("#manager_agents option").prop("selected", false);
			$("#manager_agent_search").val("");
			$("#manager_agents option").show();
		}
	});

	$("#manager_agent_search").on("keyup", function(){
		var needle = String($(this).val() || "").toLowerCase();
		$("#manager_agents option").each(function(){
			var txt = String($(this).text() || "").toLowerCase();
			$(this).toggle(txt.indexOf(needle) > -1);
		});
	});

	$("#upd_manager_scope").on("change", function(){
		if($(this).val() === "selected") {
			$("#upd_manager_agents_wrap").show();
		} else {
			$("#upd_manager_agents_wrap").hide();
			$("#upd_manager_agents option").prop("selected", false);
			$("#upd_manager_agent_search").val("");
			$("#upd_manager_agents option").show();
		}
	});

	$("#upd_manager_agent_search").on("keyup", function(){
		var needle = String($(this).val() || "").toLowerCase();
		$("#upd_manager_agents option").each(function(){
			var txt = String($(this).text() || "").toLowerCase();
			$(this).toggle(txt.indexOf(needle) > -1);
		});
	});

	
	$('#user_ins_rec').on("submit", function(e){
		e.preventDefault();
		$.ajax({

			type:'POST',
			url:'users/insprocess',
			data:$(this).serialize(),
			success:function(vardata){
				$('.error-msg').text('');

				var json = JSON.parse(vardata);

				if(json.status == 101){
					console.log(json.msg);
					//$('#tbl_rec').load('agent/record');
					$('#user_ins_rec').trigger('reset');
					$('#role').trigger('change');
					$('#manager_scope').val('all').trigger('change');
					$('#close_click').trigger('click');
					location.reload();
				}
				else if(json.status == 102){
					$('#er_msg').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 103){
					$('#msg_1').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 104){
					$('#msg_2').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 105){
					$('#msg_3').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 106){
					$('#msg_4').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 107){
					$('#msg_6').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 108){
					$('#msg_7').text(json.msg);
					console.log(json.msg);
				}
				else if(json.status == 111){
					$('#msg_1').css('color', '#dc3545').text(json.msg);
					console.log(json.msg);
				}
				else{
					console.log(json.msg);
				}

			}

		});

	});

	$(document).on("click", "button.editdata", function(){
		$('.error-msg').text('');
		$('#sc_msg').text('');
		$('#upd_2').val('');
		$('#upd_manager_agent_search').val('');
		$('#upd_manager_agents').empty();
		$('#upd_manager_scope_wrap').hide();
		$('#upd_manager_agents_wrap').hide();
		$('#upd_agent_info_wrap').hide();

		var check_id = $(this).data('dataid');
		$.getJSON("users/updateprocess", {checkid : check_id}, function(json){
			if(json.status == 0){
				$('#upd_1').val(json.name || '');
				$('#upd_role_label').val(json.urole || '');
				$('#upd_6').val(check_id);
				$('#upd_agent_info').val(json.uagent || '');

				if (json.role_code === 'uagent') {
					$('#upd_agent_info_wrap').show();
				}

				if (json.role_code === 'manager' || json.role_code === 'company_admin') {
					$('#upd_manager_scope_wrap').show();
					$('#upd_manager_scope').val(json.manager_scope || 'all');
					if (Array.isArray(json.available_agents)) {
						for (var i = 0; i < json.available_agents.length; i++) {
							var ag = json.available_agents[i];
							var isSelected = Array.isArray(json.manager_agents) && json.manager_agents.indexOf(parseInt(ag.id, 10)) !== -1;
							$('#upd_manager_agents').append('<option value="' + ag.id + '" ' + (isSelected ? 'selected' : '') + '>' + ag.label + '</option>');
						}
					}

					if ((json.manager_scope || 'all') === 'selected') {
						$('#upd_manager_agents_wrap').show();
					}
				}
			}
			else{
				console.log(json.msg);
			}
		});
	});

	$('#temp_update').on("submit", function(e){
		e.preventDefault();
		$('.error-msg').text('');
		$('#sc_msg').text('');

		$.ajax({
			type:'POST',
			url:'users/updateprocess2',
			data:$(this).serialize(),
			success:function(vardata){
				var json = JSON.parse(vardata);

				if(json.status == 101){
					$('#sc_msg').text(json.msg);
					$('#temp_update').trigger('reset');
					$('#up_cancle').trigger('click');
					location.reload();
				}
				else if(json.status == 102){
					$('#umsg_5').text(json.msg);
				}
				else if(json.status == 107){
					$('#umsg_6').text(json.msg);
				}
				else if(json.status == 110){
					$('#umsg_2').text(json.msg);
				}
				else{
					$('#umsg_5').text(json.msg || 'Update failed');
				}
			}
		});
	});

	
	$(document).on("click", "button.viewdata", function(){
		
		var check_id = $(this).data('dataid');
		$.getJSON("users/updateprocess", {checkid : check_id}, function(json){
			if(json.status == 0){
				$('#vpd_1').val(json.urole);
				$('#vpd_2').val(json.uagent);							
				$('#vpd_3').val(json.name);		
			}
			else{
				console.log(json.msg);
			}
		});
	});

	var selectedDeleteId = 0;
	$(document).on("click", "button.deletedata", function(){
		selectedDeleteId = parseInt($(this).data('dataid'), 10) || 0;
	});

	$(document).on("click", "#deleterec", function(){
		if(!selectedDeleteId){
			console.log('Invalid delete id');
			return;
		}

		$.ajax({
			type:'POST',
			url:'users/deleteprocess',
			data:{delete_id : selectedDeleteId},
			success:function(vardata){
				var json = JSON.parse(vardata);
				if(json.status == 0){
					$('#de_cancle').trigger('click');
					location.reload();
				}
				else{
					console.log(json.msg || 'Delete failed');
				}
			}
		});
	});
			
	function logout(){
	if (confirm('Are you sure you want to logout?')){
		window.location = "<?php echo LOGOUT; ?>?type=logout";
		return true;
		}else{
			return false;
			}
	}

});
</script>
<script src="modules/common/js/jquery.dataTables.min.js"></script>
<script src="modules/common/js/dataTables.bootstrap4.min.js"></script>
<script src="modules/common/js/dataTables.buttons.min.js"></script>

<script>
$(document).ready(function() {
	// Load users data via AJAX when page loads
	var userBody = $('#exampleuser tbody');
	if (userBody.length && userBody.children().length === 0) {
		$.ajax({
			type: 'POST',
			url: 'users/record',
			dataType: 'json',
			success: function(data) {
				userBody.empty();
				if (data && data.length > 0) {
					var counter = 1;
					$.each(data, function(index, user) {
						var role = user.role;
						var displayRole = 'User (Agent)';
						if (role === 'company_admin' || role === 'manager') {
							displayRole = 'Manager';
						}
						var row = '<tr>' +
							'<td style="text-align:center;vertical-align:center">' + counter++ + '</td>' +
							'<td style="text-align:center;vertical-align:center">' + user.email + '</td>' +
							'<td style="text-align:center;vertical-align:center">' + displayRole + '</td>' +
							'<td style="text-align:center;vertical-align:center">' +
								'<button type="button" class="btn btn-warning agtuserview viewdata" data-dataid="' + user.id + '" data-toggle="modal" data-target="#viewModalCenter">View</button> ' +
								'<button type="button" class="btn btn-info agtgrp editdata" data-dataid="' + user.id + '" data-toggle="modal" data-target="#updateModalCenter">Update</button> ' +
								'<button type="button" class="btn btn-danger deletedata" data-dataid="' + user.id + '" data-toggle="modal" data-target="#deleteModalCenter">Delete</button>' +
							'</td>' +
							'</tr>';
						userBody.append(row);
					});
				}
				// Reinitialize DataTables after AJAX load
				if ($.fn.DataTable.isDataTable('#exampleuser')) {
					$('#exampleuser').DataTable().destroy();
				}
				$('#exampleuser').DataTable({
					"bSort": true,
					"aaSorting": [],
					"bSearchable": true,
					"bFilter": true,
					"pageLength": 10,
					"responsive": true
				});
			},
			error: function() {
				console.log('Error loading users data');
			}
		});
	}

	// Initialize DataTables for users table if it exists and already has data
	var usersTable = $('#exampleuser');
	if (usersTable.length && usersTable.find('tbody tr').length > 0) {
		usersTable.DataTable({
			"bSort": true,
			"aaSorting": [],
			"bSearchable": true,
			"bFilter": true,
			"pageLength": 10,
			"responsive": true
		});
	}

	// Initialize DataTables for agent table if it exists
	var agentTable = $('#exampleagent');
	if (agentTable.length) {
		agentTable.DataTable({
			"bSort": true,
			"aaSorting": [],
			"bSearchable": true,
			"bFilter": true,
			"pageLength": 10,
			"responsive": true
		});
	}

	// Generic DataTable for all tables with class 'table'
	$('table.table').not('#exampleuser, #exampleagent').DataTable({
		"bSort": true,
		"aaSorting": [],
		"bSearchable": true,
		"bFilter": true,
		"pageLength": 10,
		"responsive": true
	}).destroy();
});
</script>

</body>

</html>
