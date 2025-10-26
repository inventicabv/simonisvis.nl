jQuery(function ($) {
	
	// Offcanvs Custom
	$('#offcanvas-toggler-custom').on('click', function (event) {
		event.preventDefault();
		$('.offcanvas-init').addClass('offcanvas-active');
	});

});