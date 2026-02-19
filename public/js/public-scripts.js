/**
 * Education Resources Manager — Public Scripts
 *
 * Handles client-side resource filtering, pagination, loading overlay,
 * and "Ver recurso" view tracking via the ERM REST API.
 *
 * No jQuery dependency — uses native fetch() and DOM APIs.
 *
 * @package GlobalAuthenticity\EducationManager
 */

/* global ermPublic */

( function () {
	'use strict';

	const cfg = window.ermPublic || {};

	// ── Utility ────────────────────────────────────────────────────────────────

	/**
	 * Returns a debounced version of fn that fires after `delay` ms of silence.
	 *
	 * @param {Function} fn    Function to debounce.
	 * @param {number}   delay Milliseconds to wait.
	 * @return {Function}
	 */
	function debounce( fn, delay ) {
		let timer;
		return function ( ...args ) {
			clearTimeout( timer );
			timer = setTimeout( () => fn.apply( this, args ), delay );
		};
	}

	/**
	 * Minimal HTML escaping for values injected into innerHTML.
	 *
	 * @param {*} value Value to escape.
	 * @return {string}
	 */
	function esc( value ) {
		return String( value )
			.replace( /&/g, '&amp;' )
			.replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' )
			.replace( /"/g, '&quot;' )
			.replace( /'/g, '&#039;' );
	}

	// ── ErmGrid class ──────────────────────────────────────────────────────────

	/**
	 * Manages one [education_resources] shortcode instance.
	 * Reads initial config from data-* attributes on the .erm-app element.
	 */
	class ErmGrid {

		/**
		 * @param {HTMLElement} el Root .erm-app element.
		 */
		constructor( el ) {
			this.el = el;

			// DOM refs.
			this.$grid       = el.querySelector( '.erm-grid' );
			this.$pagination = el.querySelector( '.erm-pagination' );
			this.$overlay    = el.querySelector( '.erm-loading-overlay' );
			this.$search     = el.querySelector( '.erm-filter__search' );
			this.$type       = el.querySelector( '.erm-filter__type' );
			this.$difficulty = el.querySelector( '.erm-filter__difficulty' );
			this.$category   = el.querySelector( '.erm-filter__category' );

			// State.
			this.page       = 1;
			this.totalPages = 1;

			// Base query params from shortcode attributes.
			this.baseParams = {
				per_page: parseInt( el.dataset.perPage, 10 ) || 12,
				orderby:  el.dataset.orderby || 'date',
				order:    el.dataset.order   || 'DESC',
			};

			// Pre-apply shortcode-level category / difficulty / featured filters.
			if ( el.dataset.category )   this.baseParams.category         = el.dataset.category;
			if ( el.dataset.difficulty ) this.baseParams.difficulty_level = el.dataset.difficulty;
			if ( el.dataset.featured )   this.baseParams.featured         = el.dataset.featured;

			// Pre-select difficulty select if set via shortcode attr.
			if ( el.dataset.difficulty && this.$difficulty ) {
				this.$difficulty.value = el.dataset.difficulty;
			}

			// Pre-select category select if set via shortcode attr.
			if ( el.dataset.category && this.$category ) {
				this.$category.value = el.dataset.category;
			}

			this._bindEvents();
			this._fetch();
		}

		// ── Event binding ─────────────────────────────────────────────────────

		_bindEvents() {
			// Search: debounced 300 ms.
			if ( this.$search ) {
				this.$search.addEventListener(
					'input',
					debounce( () => {
						this.page = 1;
						this._fetch();
					}, 300 )
				);
			}

			// Selects: immediate.
			[ this.$type, this.$difficulty, this.$category ].forEach( ( $el ) => {
				if ( $el ) {
					$el.addEventListener( 'change', () => {
						this.page = 1;
						this._fetch();
					} );
				}
			} );
		}

		// ── Params builder ────────────────────────────────────────────────────

		/**
		 * Merge base params with current filter UI values.
		 *
		 * @return {Object}
		 */
		_buildParams() {
			const params = { ...this.baseParams, page: this.page };

			const search     = this.$search     ? this.$search.value.trim()     : '';
			const type       = this.$type       ? this.$type.value               : '';
			const difficulty = this.$difficulty ? this.$difficulty.value         : '';
			const category   = this.$category   ? this.$category.value           : '';

			if ( search )     params.search           = search;
			if ( type )       params.resource_type    = type;
			if ( difficulty ) params.difficulty_level = difficulty;
			if ( category )   params.category         = category;

			return params;
		}

		// ── Fetch ─────────────────────────────────────────────────────────────

		async _fetch() {
			this._showLoading();

			const url    = new URL( cfg.restUrl );
			const params = this._buildParams();

			Object.entries( params ).forEach( ( [ k, v ] ) => {
				if ( v !== '' && v !== null && v !== undefined ) {
					url.searchParams.set( k, v );
				}
			} );

			try {
				const response = await fetch( url.toString(), {
					headers: { 'X-WP-Nonce': cfg.nonce },
				} );

				if ( ! response.ok ) {
					throw new Error( `HTTP ${ response.status }` );
				}

				const resources = await response.json();

				this.totalPages = parseInt(
					response.headers.get( 'X-WP-TotalPages' ) || '1',
					10
				);

				this._render( resources );
				this._renderPagination();

			} catch ( err ) {
				this.$grid.setAttribute( 'aria-busy', 'false' );
				this.$grid.innerHTML = `<p class="erm-error">${ esc( cfg.i18n.error ) }</p>`;
				this.$pagination.innerHTML = '';
			} finally {
				this._hideLoading();
			}
		}

		// ── Render cards ──────────────────────────────────────────────────────

		/**
		 * Populate the grid with resource cards.
		 *
		 * @param {Array} resources Array of resource objects from the REST API.
		 */
		_render( resources ) {
			this.$grid.setAttribute( 'aria-busy', 'false' );

			if ( ! resources.length ) {
				this.$grid.innerHTML = `<p class="erm-no-results">${ esc( cfg.i18n.no_items ) }</p>`;
				return;
			}

			this.$grid.innerHTML = resources.map( ( r ) => this._renderCard( r ) ).join( '' );

			// Bind "Ver recurso" click → fire-and-forget view track.
			this.$grid.querySelectorAll( '.erm-card__cta' ).forEach( ( btn ) => {
				btn.addEventListener( 'click', () => {
					this._trackView( parseInt( btn.dataset.ermId, 10 ) );
				} );
			} );
		}

		/**
		 * Build HTML string for a single resource card.
		 *
		 * @param {Object} r Resource data from REST API.
		 * @return {string}
		 */
		_renderCard( r ) {
			const badge = r.is_featured
				? `<span class="erm-card__badge erm-card__badge--featured">${ esc( cfg.i18n.featured ) }</span>`
				: '';

			const thumbnail = r.thumbnail
				? `<img src="${ esc( r.thumbnail ) }" class="erm-card__thumbnail" alt="" loading="lazy">`
				: `<div class="erm-card__thumbnail-placeholder" aria-hidden="true"></div>`;

			let chips = '';
			if ( r.resource_type ) {
				chips += `<span class="erm-card__type erm-card__type--${ esc( r.resource_type ) }">${ esc( r.resource_type ) }</span>`;
			}
			if ( r.difficulty_level ) {
				chips += `<span class="erm-card__difficulty erm-card__difficulty--${ esc( r.difficulty_level ) }">${ esc( r.difficulty_level ) }</span>`;
			}
			if ( r.duration_minutes ) {
				chips += `<span class="erm-card__duration">${ parseInt( r.duration_minutes, 10 ) } min</span>`;
			}

			const ctaHref = ( r.resource_url && r.resource_url.trim() )
				? r.resource_url
				: r.permalink;

			return `
<article class="erm-card${ r.is_featured ? ' erm-card--featured' : '' }">
  <div class="erm-card__media">
    ${ thumbnail }
    ${ badge }
  </div>
  <div class="erm-card__body">
    <h3 class="erm-card__title">
      <a href="${ esc( r.permalink ) }">${ esc( r.title ) }</a>
    </h3>
    ${ r.excerpt ? `<p class="erm-card__excerpt">${ esc( r.excerpt ) }</p>` : '' }
    <div class="erm-card__meta">${ chips }</div>
    <a
      href="${ esc( ctaHref ) }"
      class="erm-card__cta"
      data-erm-id="${ parseInt( r.id, 10 ) }"
      target="_blank"
      rel="noopener noreferrer"
    >${ esc( cfg.i18n.ver_recurso ) }</a>
  </div>
</article>`;
		}

		// ── Pagination ────────────────────────────────────────────────────────

		_renderPagination() {
			if ( this.totalPages <= 1 ) {
				this.$pagination.innerHTML = '';
				return;
			}

			let html = `<nav class="erm-pagination__nav" aria-label="Pagination">`;

			if ( this.page > 1 ) {
				html += `<button class="erm-pagination__btn erm-pagination__btn--prev" data-page="${ this.page - 1 }" aria-label="Previous page">&larr; ${ esc( cfg.i18n.prev ) }</button>`;
			}

			html += `<span class="erm-pagination__info">${ this.page } ${ esc( cfg.i18n.page_of ) } ${ this.totalPages }</span>`;

			if ( this.page < this.totalPages ) {
				html += `<button class="erm-pagination__btn erm-pagination__btn--next" data-page="${ this.page + 1 }" aria-label="Next page">${ esc( cfg.i18n.next ) } &rarr;</button>`;
			}

			html += `</nav>`;
			this.$pagination.innerHTML = html;

			this.$pagination.querySelectorAll( '.erm-pagination__btn' ).forEach( ( btn ) => {
				btn.addEventListener( 'click', () => {
					this.page = parseInt( btn.dataset.page, 10 );
					this._fetch();
					// Scroll back to the top of this grid instance smoothly.
					this.el.scrollIntoView( { behavior: 'smooth', block: 'start' } );
				} );
			} );
		}

		// ── View tracking ─────────────────────────────────────────────────────

		/**
		 * Fire-and-forget POST to /erm/v1/resources/:id/track.
		 *
		 * @param {number} postId WordPress post ID.
		 */
		async _trackView( postId ) {
			if ( ! postId ) return;
			try {
				await fetch( `${ cfg.restUrl }/${ postId }/track`, {
					method:  'POST',
					headers: { 'X-WP-Nonce': cfg.nonce },
				} );
			} catch ( _ ) {
				// Silently swallow — tracking failures must not interrupt the user.
			}
		}

		// ── Loading overlay ───────────────────────────────────────────────────

		_showLoading() {
			if ( this.$overlay ) {
				this.$overlay.removeAttribute( 'hidden' );
				this.$overlay.removeAttribute( 'aria-hidden' );
			}
			if ( this.$grid ) {
				this.$grid.setAttribute( 'aria-busy', 'true' );
			}
		}

		_hideLoading() {
			if ( this.$overlay ) {
				this.$overlay.setAttribute( 'hidden', '' );
				this.$overlay.setAttribute( 'aria-hidden', 'true' );
			}
		}
	}

	// ── Bootstrap ──────────────────────────────────────────────────────────────

	document.addEventListener( 'DOMContentLoaded', () => {
		document.querySelectorAll( '.erm-app' ).forEach( ( el ) => new ErmGrid( el ) );
	} );

} )();
