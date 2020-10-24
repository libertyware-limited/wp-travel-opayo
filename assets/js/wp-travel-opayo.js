jQuery(function ($) {

	var parsley = $('#wp-travel-booking').length > 0 && $('#wp-travel-booking').parsley();
	var isopayo = function () {
		return parsley.isValid() && $('[name=wp_travel_booking_option]').val() == 'booking_with_payment' && 'opayo' === $('#wp-travel-payment-opayo:checked').val()
	}


	// For Partial Payment.
	$('#wp-travel-complete-partial-payment').on('click', function (e) {
		var isopayo = $('[name=wp_travel_booking_option]').val() == 'booking_with_payment' && 'opayo' === $('#wp-travel-payment-opayo:checked').val()
	});

	$('#wp-travel-booking').on('change keyup', function () {
		'opayo' === $('#wp-travel-payment-opayo:checked').val() && $('#wp-travel-book-now').show().siblings().hide();
	})
});
