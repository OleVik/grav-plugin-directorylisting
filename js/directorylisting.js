$(document).ready(function() {
	$('div.directorylist > ul.directorylisting > li.item.directory > input[type="checkbox"]').prop('checked', true);
	$('div.directorylist > ul.directorylisting > ul.directorylisting').show();
	
	$('div.directorylist li.item.directory input[type="checkbox"]').change(function() {
		if ($(this).is(":checked")) {
			$(this).parent().next("ul.directorylisting").show();
		} else {
			$(this).parent().next("ul.directorylisting").hide();
		}
	});
});