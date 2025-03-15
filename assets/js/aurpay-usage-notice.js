( function ( $ ) {
	var connectionBanner = $( '.aurpay-usage-notice' ),
		connectionBannerDismiss = $( '.aurpay-usage-notice__dismiss' );

	// Dismiss the connection banner via AJAX
	connectionBannerDismiss.on( 'click', function () {
		$( connectionBanner ).hide()
	} );

	var ap_step1_number =$( '.ap_step_pmp_1' ),
	ap_step2_number =$( '.ap_step_pmp_2' );

	ap_step1_number.on('click', function () {
		ap_skip = setTimeout(function () {
			window.location.href = ap_step2_number.attr("href")
		}, 6000);
	} );

} )( jQuery );
