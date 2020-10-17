jQuery(function($){

	var parsley = $('#wp-travel-booking').length > 0 && $('#wp-travel-booking').parsley();
	var isPayFast = function(){
		return parsley.isValid() && $('[name=wp_travel_booking_option]').val() == 'booking_with_payment' && 'payfast' === $('#wp-travel-payment-payfast:checked').val()
	}

	$('#wp-travel-book-now').on('click', function(e){
		if(isPayFast()){
			if ( 'ZAR' !== wp_travel.payment.currency_code ) {
				e.preventDefault();
				alert('PayFast checkout only support South African Rand (ZAR).');
				return;
			}
		}
	});
	// For Partial Payment.
	$('#wp-travel-complete-partial-payment').on('click', function(e){
		var isPayFast = $('[name=wp_travel_booking_option]').val() == 'booking_with_payment' && 'payfast' === $('#wp-travel-payment-payfast:checked').val()
		if(isPayFast){
			if ( 'ZAR' !== wp_travel.payment.currency_code ) {
				e.preventDefault();
				alert('PayFast checkout only support South African Rand (ZAR).');
				return;
			}
		}
	});

	$('#wp-travel-booking').on('change keyup', function(){
		'payfast' === $('#wp-travel-payment-payfast:checked').val() && $('#wp-travel-book-now').show().siblings().hide();
	})
});
