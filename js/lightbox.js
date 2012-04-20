jQuery(document).ready(function($){
	function make_colorbox( href, transition ) {
		$.colorbox({
			inline: true,
			href: href,
			title: a8c_developer_i18n.lightbox_title,
			innerWidth: 500,
			maxHeight: '100%',
			transition: transition
		});
	}

	make_colorbox( '#a8c-developer-setup-dialog-step-1', 'none' );

	$('#a8c-developer-setup-dialog-step-1-form').submit( function(element) {
		var form = element;

		$('#a8c-developer-setup-dialog-step-1-submit').val( a8c_developer_i18n.saving );

		if ( 'yes' == a8c_developer_i18n.go_to_step_2 )
			return;

		$.post( ajaxurl, $(form).serialize() )
			.success( function( result ) {
				// If there was an error with the AJAX save, then do a normal POST
				if ( '-1' == result ) {
					return;
				}

				form.preventDefault();

				// AJAX says no step 2 needed, so head to the settings page
				if ( 'redirect' == result ) {
					location.href = 'options-general.php?page=' + a8c_developer_i18n.settings_slug + '&updated=1';
					return;
				}

				// Display the AJAX reponse
				$('#a8c-developer-setup-dialog-step-2').html( result );
				make_colorbox( '#a8c-developer-setup-dialog-step-2' );
			})
		;
	});

	$('.a8c_developer_button_install').on( 'click', function() {
		var button = this;

		$(button).html( a8c_developer_i18n.installing );

		$.post( ajaxurl, {
			'action': 'a8c_developer_install_plugin',
			'_ajax_nonce': $(button).attr('data-nonce'),
			'plugin_slug': $(button).attr('data-pluginslug')
		} )
			.success( function( result ) {
				if ( '1' == result ) {
					$(button).html( a8c_developer_i18n.installed );
					$(button).unbind('click').prop('disabled', true);
				} else {
					$(button).html( a8c_developer_i18n.error );
				}
			})
			.error( function() {
				$(button).html( a8c_developer_i18n.error );
			})
		;
	});

	$('.a8c_developer_button_activate').on( 'click', function() {
		var button = this;

		$(button).html( a8c_developer_i18n.activating );

		$.post( ajaxurl, {
			'action': 'a8c_developer_activate_plugin',
			'_ajax_nonce': $(button).attr('data-nonce'),
			'path': $(button).attr('data-path')
		} )
			.success( function( result ) {
				if ( '1' == result ) {
					$(button).html( a8c_developer_i18n.activated );
					$(button).unbind('click').prop('disabled', true);
				} else {
					$(button).html( a8c_developer_i18n.error );
				}
			})
			.error( function() {
				$(button).html( a8c_developer_i18n.error );
			})
		;
	});
});