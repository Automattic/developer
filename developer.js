function a8c_developer_lightbox() {
	(function($){
		function make_colorbox( href, transition ) {
			$.colorbox({
				inline: true,
				href: href,
				title: a8c_developer_i18n.lightbox_title,
				innerWidth: 650,
				maxHeight: '100%',
				transition: transition
			});
		}

		make_colorbox( '#a8c-developer-setup-dialog-step-1', 'none' );

		$('#a8c-developer-setup-dialog-step-1-form').submit( function(e) {
			var form = this;

			$('#a8c-developer-setup-dialog-step-1-submit').val( a8c_developer_i18n.saving );

			if ( 'yes' != a8c_developer_i18n.go_to_step_2 )
				return;

			e.preventDefault();

			$.post( ajaxurl, $(form).serialize() )
				.success( function( result ) {
					// If there was an error with the AJAX save, then do a normal POST
					if ( '-1' == result ) {
						location.href = 'options-general.php?page=' + a8c_developer_i18n.settings_slug + '&a8cdev_errorsaving=1';
						return;
					}

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
	})(jQuery);
}

function a8c_developer_bind_events() {
	(function($){
		$('.a8c-developer-button-install').click( function() {
			var button = this;

			$(button).html( a8c_developer_i18n.installing );

			$.post( ajaxurl, {
				'action': 'a8c_developer_install_plugin',
				'_ajax_nonce': $(button).attr('data-nonce'),
				'plugin_slug': $(button).attr('data-pluginslug')
			} )
				.success( function( result ) {
					if ( '1' === result ) {
						$( button )
							.html( a8c_developer_i18n.installed )
							.nextAll( '.a8c-developer-action-result' )
							.remove();

						$(button).unbind('click').prop('disabled', true);
					} else {
						alert( result );

						$( button )
							.html( a8c_developer_i18n.ERROR )
							.nextAll( '.a8c-developer-action-result' )
							.remove();

						$( button ).after( '<span class="a8c-developer-action-result error">' + result + '</span>' );
					}
				})
				.error( function( response ) {
					$( button )
						.html( a8c_developer_i18n.ERROR )
						.nextAll( '.a8c-developer-action-result' )
						.remove();

					$( button ).after( '<span class="a8c-developer-action-result error">' + response.statusText + ': ' + response.responseText + '</span>' );
				})
			;
		});

		$('.a8c-developer-button-activate').click( function() {
			var button = this;

			$(button).html( a8c_developer_i18n.activating );

			$.post( ajaxurl, {
				'action': 'a8c_developer_activate_plugin',
				'_ajax_nonce': $(button).attr('data-nonce'),
				'path': $(button).attr('data-path')
			} )
				.success( function( result ) {
					if ( '1' === result ) {
						$( button )
							.html( a8c_developer_i18n.activated )
							.nextAll( '.a8c-developer-action-result' )
							.remove();

						$(button).unbind('click').prop('disabled', true);
					} else {
						alert( result );

						$( button )
							.html( a8c_developer_i18n.ERROR )
							.nextAll( '.a8c-developer-action-result' )
							.remove();

						$( button ).after( '<span class="a8c-developer-action-result error">' + result + '</span>' );
					}
				})
				.error( function( response ) {
					$( button )
						.html( a8c_developer_i18n.ERROR )
						.nextAll( '.a8c-developer-action-result' )
						.remove();

					$( button ).after( '<span class="a8c-developer-action-result error">' + response.statusText + ': ' + response.responseText + '</span>' );
				})
			;
		});

		$( '.a8c-developer-button-close' ).on( 'click', function() {
			$.colorbox.close();
		});
	})(jQuery);
}

function a8c_developer_bind_settings_events() {
	(function($){
		$('.a8c-developer-button-install').click( function() {
			var button = this;

			$(button).html( a8c_developer_i18n.installing );

			$.post( ajaxurl, {
				'action': 'a8c_developer_install_plugin',
				'_ajax_nonce': $(button).attr('data-nonce'),
				'plugin_slug': $(button).attr('data-pluginslug')
			} )
				.success( function( result ) {
					if ( '1' == result ) {
						$( button )
							.nextAll( '.a8c-developer-action-result' )
							.remove();

						$( button ).replaceWith( "<span class='a8c-developer-active'>" + a8c_developer_i18n.ACTIVE + "</span>" );
					} else {
						$( button )
							.html( a8c_developer_i18n.ERROR )
							.nextAll( '.a8c-developer-action-result' )
							.remove();

						$( button ).after( '<span class="a8c-developer-action-result error">' + result + '</span>' );
					}
				})
				.error( function( response ) {
					$( button )
						.html( a8c_developer_i18n.ERROR )
						.nextAll( '.a8c-developer-action-result' )
						.remove();

					$( button ).after( '<span class="a8c-developer-action-result error">' + response.statusText + ': ' + response.responseText + '</span>' );
				})
			;

			return false;
		});

		$('.a8c-developer-button-activate').click( function() {
			var button = this;

			$(button).html( a8c_developer_i18n.activating );

			$.post( ajaxurl, {
				'action': 'a8c_developer_activate_plugin',
				'_ajax_nonce': $(button).attr('data-nonce'),
				'path': $(button).attr('data-path')
			} )
				.success( function( result ) {
					if ( '1' == result ) {
						$( button )
							.nextAll( '.a8c-developer-action-result' )
							.remove();

						$( button ).replaceWith( "<span class='a8c-developer-active'>" + a8c_developer_i18n.ACTIVE + "</span>" );
					} else {
						$( button )
							.html( a8c_developer_i18n.ERROR )
							.nextAll( '.a8c-developer-action-result' )
							.remove();

						$( button ).after( '<span class="a8c-developer-action-result error">' + result + '</span>' );
					}
				})
				.error( function( response ) {
					$( button )
						.html( a8c_developer_i18n.ERROR )
						.nextAll( '.a8c-developer-action-result' )
						.remove();

					$( button ).after( '<span class="a8c-developer-action-result error">' + response.statusText + ': ' + response.responseText + '</span>' );
					
				})
			;

			return false;
		});
	})(jQuery);
}