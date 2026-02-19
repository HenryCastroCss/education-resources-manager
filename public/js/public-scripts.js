/**
 * Education Resources Manager — Public Scripts
 *
 * Handles client-side download tracking and dynamic resource loading
 * for the [education_resources] shortcode.
 *
 * @package GlobalAuthenticity\EducationManager
 */

/* global ermPublic, jQuery */

( function ( $, config ) {
	'use strict';

	/**
	 * Track a resource download by posting to the REST API.
	 *
	 * @param {number} postId  WordPress post ID.
	 * @param {string} restUrl Base URL for the resources endpoint.
	 * @param {string} nonce   WP REST nonce.
	 */
	function trackDownload( postId, restUrl, nonce ) {
		if ( ! postId || ! restUrl ) {
			return;
		}

		$.ajax( {
			url:     restUrl + '/' + postId + '/download',
			method:  'POST',
			headers: { 'X-WP-Nonce': nonce },
		} );
		// Fire-and-forget: no UI update needed.
	}

	/**
	 * Bind click events on resource links with a data-erm-id attribute.
	 */
	function bindDownloadTracking() {
		$( document ).on( 'click', '[data-erm-id]', function () {
			var postId = $( this ).data( 'erm-id' );
			trackDownload( postId, config.restUrl, config.nonce );
		} );
	}

	/**
	 * Dynamic resource loader — used when JS-rendered grids are needed.
	 * Reads data attributes from a .erm-dynamic-grid container and
	 * fetches resources from the REST API to populate it.
	 */
	var ErmDynamicGrid = {

		containers: [],

		init: function () {
			this.containers = $( '.erm-dynamic-grid' ).toArray();
			if ( ! this.containers.length ) {
				return;
			}
			this.containers.forEach( this.loadGrid.bind( this ) );
		},

		loadGrid: function ( el ) {
			var $el       = $( el );
			var params    = {
				per_page:         $el.data( 'per-page' )   || 12,
				page:             $el.data( 'page' )        || 1,
				resource_type:    $el.data( 'type' )        || '',
				difficulty_level: $el.data( 'difficulty' )  || '',
				category:         $el.data( 'category' )    || '',
				orderby:          $el.data( 'orderby' )     || 'created_at',
				order:            $el.data( 'order' )       || 'DESC',
			};

			// Remove empty params.
			Object.keys( params ).forEach( function ( k ) {
				if ( params[ k ] === '' ) {
					delete params[ k ];
				}
			} );

			$el.html( '<p class="erm-loading">' + config.i18n.loading + '</p>' );

			$.getJSON( config.restUrl, params )
				.done( function ( resources ) {
					if ( ! resources.length ) {
						$el.html( '<p class="erm-no-results">' + config.i18n.no_items + '</p>' );
						return;
					}
					var html = '';
					resources.forEach( function ( r ) {
						html += ErmDynamicGrid.renderCard( r );
					} );
					$el.html( '<div class="erm-resources-grid">' + html + '</div>' );
				} )
				.fail( function () {
					$el.html( '<p class="erm-error">' + config.i18n.error + '</p>' );
				} );
		},

		/**
		 * Build a resource card from a REST response item.
		 *
		 * @param {Object} r Resource data.
		 * @return {string} HTML string.
		 */
		renderCard: function ( r ) {
			var badge = r.is_featured
				? '<span class="erm-card__badge erm-card__badge--featured">Featured</span>'
				: '';

			var thumbnail = r.thumbnail
				? '<img src="' + ErmDynamicGrid.escHtml( r.thumbnail ) + '" class="erm-card__thumbnail" alt="" loading="lazy">'
				: '';

			var meta = '';
			if ( r.resource_type ) {
				meta += '<span class="erm-card__type erm-card__type--' + ErmDynamicGrid.escHtml( r.resource_type ) + '">'
					+ ErmDynamicGrid.escHtml( r.resource_type ) + '</span>';
			}
			if ( r.difficulty_level ) {
				meta += '<span class="erm-card__difficulty erm-card__difficulty--' + ErmDynamicGrid.escHtml( r.difficulty_level ) + '">'
					+ ErmDynamicGrid.escHtml( r.difficulty_level ) + '</span>';
			}
			if ( r.duration_minutes ) {
				meta += '<span class="erm-card__duration">' + parseInt( r.duration_minutes, 10 ) + ' min</span>';
			}

			return '<article class="erm-card' + ( r.is_featured ? ' erm-card--featured' : '' ) + '">'
				+ '<div class="erm-card__media">' + thumbnail + badge + '</div>'
				+ '<div class="erm-card__body">'
				+ '<h3 class="erm-card__title">'
				+ '<a href="' + ErmDynamicGrid.escHtml( r.permalink ) + '" data-erm-id="' + parseInt( r.id, 10 ) + '">'
				+ ErmDynamicGrid.escHtml( r.title ) + '</a></h3>'
				+ ( r.excerpt ? '<p class="erm-card__excerpt">' + ErmDynamicGrid.escHtml( r.excerpt ) + '</p>' : '' )
				+ '<div class="erm-card__meta">' + meta + '</div>'
				+ '</div></article>';
		},

		/**
		 * Minimal HTML escaping for dynamic content.
		 *
		 * @param {*} str Value to escape.
		 * @return {string}
		 */
		escHtml: function ( str ) {
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' )
				.replace( /'/g, '&#039;' );
		},
	};

	// Bootstrap.
	$( function () {
		bindDownloadTracking();
		ErmDynamicGrid.init();
	} );

} )( jQuery, window.ermPublic || {} );
