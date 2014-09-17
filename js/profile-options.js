/**
 * AJAX handlers for destroying single or multiple sessions on profile.php
 */

( function( $ ) {

	$('.session-destroy').on('click',function(e){

		var data = {
			action      : 'wpsm_destroy_session',
			_ajax_nonce : wpsm.nonce_single,
			user_id     : wpsm.user_id,
			hash        : $(this).closest('tr').data('hash')
		};

		$.post( ajaxurl, data, function( response ) {

			// @TODO remove the relevant table row

		}, 'json' );

		e.preventDefault();

	});

	$('.session-destroy-other,.session-destroy-all').on('click',function(e){

		var data = {
			action      : 'wpsm_destroy_sessions',
			_ajax_nonce : wpsm.nonce_multiple,
			user_id     : wpsm.user_id,
			hash        : $(this).data('hash')
		};

		if ( $(this).data('hash') ) {
			data.hash = $(this).data('hash');
		}

		$.post( ajaxurl, data, function( response ) {

			// @TODO remove the relevant table

		}, 'json' );

		e.preventDefault();

	});

} )( jQuery );