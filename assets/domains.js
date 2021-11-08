$(function () {
	
	// checkboxes
	$("input[type=checkbox][id^=domain_entry]").change( function () {
		if($(this).is(':checked')) {
			$(this).parents("tr").addClass("table-active");
		}
		else {
			$(this).parents("tr").removeClass("table-active");
		}
		count_selected();
	});
	
	
	// global checkbox
	$("input[type=checkbox][id=all_domain_entries]").change( function () {
		var checked = $(this).prop("checked");
		checkboxes = $("input[type=checkbox][id^=domain_entry]");
		checkboxes.each(function() {
			$(this).prop("checked", checked);
			$(this).change();
		});
	});
	
	
	function count_selected () {
		nb = $("input[type=checkbox][id^=domain_entry]:checked").length;
		$("#selected-count").html(nb);
	}
	
});
