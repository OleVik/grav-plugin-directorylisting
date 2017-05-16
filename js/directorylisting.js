$(document).ready(function() {
    // Open first level by default
	$('div.directorylist > ul.directorylisting > li.item.directory > input[type="checkbox"]').prop('checked', true);
	$('div.directorylist > ul.directorylisting > ul.directorylisting').show();
    
    // Iterate each Directory List and open folders on check
    $("div.directorylist").each( function( index, element ) {
        $('li.item.directory input[type="checkbox"]', this).each( function( index, element ) {
            $(this).change(function() {
                if ($(this).is(":checked")) {
                    $('li.item.directory').next("ul.directorylisting").show();
                } else {
                    $('li.item.directory').next("ul.directorylisting").hide();
                }
            });
        });
    });

});