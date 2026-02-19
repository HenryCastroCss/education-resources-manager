/**
 * Education Resources Manager — Admin Scripts
 *
 * @package GlobalAuthenticity\EducationManager
 */

/* global ermAdmin, jQuery */

( function ( $, config ) {
	'use strict';

	/**
	 * Settings form handler.
	 */
	var ErmSettings = {

		$form: null,
		$status: null,
		$submitBtn: null,

		init: function () {
			this.$form      = $( '#erm-settings-form' );
			this.$status    = $( '.erm-save-status' );
			this.$submitBtn = $( '#erm-save-settings' );

			if ( ! this.$form.length ) {
				return;
			}

			this.$form.on( 'submit', this.onSubmit.bind( this ) );
		},

		onSubmit: function ( e ) {
			e.preventDefault();

			var self = this;

			self.$status.removeClass( 'is-error' ).text( '' );
			self.$submitBtn.prop( 'disabled', true );

			var data = {
				action:               'erm_save_settings',
				nonce:                config.nonce,
				resources_per_page:   $( '#resources_per_page' ).val(),
				default_difficulty:   $( '#default_difficulty' ).val(),
				enable_rest_api:      $( '[name="enable_rest_api"]' ).is( ':checked' ) ? 1 : 0,
				enable_download_count: $( '[name="enable_download_count"]' ).is( ':checked' ) ? 1 : 0,
			};

			$.post( config.ajaxUrl, data )
				.done( function ( response ) {
					if ( response.success ) {
						self.$status.text( response.data.message || config.i18n.saved );
					} else {
						self.$status.addClass( 'is-error' ).text(
							( response.data && response.data.message ) || config.i18n.error
						);
					}
				} )
				.fail( function () {
					self.$status.addClass( 'is-error' ).text( config.i18n.error );
				} )
				.always( function () {
					self.$submitBtn.prop( 'disabled', false );

					// Clear status after 4 seconds.
					setTimeout( function () {
						self.$status.text( '' ).removeClass( 'is-error' );
					}, 4000 );
				} );
		},
	};

	/**
	 * Resource meta box enhancements.
	 */
	var ErmMetaBox = {

		init: function () {
			this.initCharCount();
			this.initUrlValidation();
		},

		/**
		 * Add a live character counter to text inputs if needed.
		 */
		initCharCount: function () {
			var $urlField = $( '#erm_resource_url' );
			if ( ! $urlField.length ) {
				return;
			}
			$urlField.on( 'input', function () {
				var len = $( this ).val().length;
				if ( len > 2000 ) {
					$( this ).addClass( 'erm-input-warning' );
				} else {
					$( this ).removeClass( 'erm-input-warning' );
				}
			} );
		},

		/**
		 * Basic URL format feedback.
		 */
		initUrlValidation: function () {
			var $urlField = $( '#erm_resource_url' );
			if ( ! $urlField.length ) {
				return;
			}

			$urlField.on( 'blur', function () {
				var val = $.trim( $( this ).val() );
				if ( val && ! /^https?:\/\//i.test( val ) ) {
					$( this ).closest( 'td' )
						.find( '.erm-url-hint' )
						.remove();
					$( this ).after(
						'<p class="description erm-url-hint" style="color:#b32d2e;">' +
						'URL should begin with https://</p>'
					);
				} else {
					$( this ).closest( 'td' ).find( '.erm-url-hint' ).remove();
				}
			} );
		},
	};

	// Bootstrap on DOM ready.
	$( function () {
		ErmSettings.init();
		ErmMetaBox.init();
	} );

} )( jQuery, window.ermAdmin || {} );
