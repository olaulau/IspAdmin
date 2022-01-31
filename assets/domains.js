$(function() {

	// checkboxes
	$("input[type=checkbox][id^=domain_entry]").change(function() {
		if ($(this).is(':checked')) {
			$(this).parents("tr").addClass("table-active");
		}
		else {
			$(this).parents("tr").removeClass("table-active");
		}
		update_selected_count();
	});

	function update_selected_count() {
		var nb = $("input[type=checkbox][id^=domain_entry]:checked").length;
		$("#selected-count").html(nb);
		if (nb > 0) {
			$("#bulk_edit,#edit_name,#edit_data").attr("disabled", false);
		}
		else {
			$("#bulk_edit,#edit_name,#edit_data").attr("disabled", true);
		}
	}


	// global checkbox
	$("input[type=checkbox][id=all_domain_entries]").change(function() {
		var is_checked = $(this).prop("checked");
		var checkboxes = $("input[type=checkbox][id^=domain_entry]");
		checkboxes.each(function() {
			$(this).prop("checked", is_checked);
			$(this).change();
		});
	});


	// init
	update_selected_count();

});
