( function ( $, window, document ) {
	'use strict';

	var config = window.sfsbSettings || {};
	var selector = '.sfsb-free-shipping-bar';

	if ( ! config.ajaxUrl || ! document.querySelector( selector ) ) {
		return;
	}

	function parseNumber( value ) {
		var parsed = parseFloat( value );
		return isNaN( parsed ) ? 0 : parsed;
	}

	function formatMoney( amount ) {
		var decimals = Number( config.decimals || 2 );
		var decimalSeparator = config.decimalSeparator || '.';
		var thousandSeparator = config.thousandSeparator || ',';
		var parts = parseNumber( amount ).toFixed( decimals ).split( '.' );

		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, thousandSeparator );

		var formatted = decimals > 0 ? parts.join( decimalSeparator ) : parts[ 0 ];
		var currencyFormat = config.currencyFormat || '%1$s%2$s';

		return currencyFormat
			.replace( '%1$s', config.currencySymbol || '$' )
			.replace( '%2$s', formatted );
	}

	function getMessage( element, state ) {
		if ( ! state.hasItems ) {
			return element.dataset.messageEmpty || '';
		}

		if ( state.hasReachedGoal ) {
			return element.dataset.messageComplete || '';
		}

		var template = element.dataset.messageRemaining || '';
		return template.replace( '%s', formatMoney( state.remaining ) );
	}

	function buildState( subtotal, threshold, itemCount ) {
		var safeThreshold = Math.max( 0, threshold );
		var remaining = Math.max( 0, safeThreshold - subtotal );
		var progress = safeThreshold > 0 ? Math.min( 100, ( subtotal / safeThreshold ) * 100 ) : 0;

		return {
			hasItems: itemCount > 0,
			hasReachedGoal: safeThreshold > 0 && subtotal >= safeThreshold,
			progress: progress,
			remaining: remaining,
			subtotal: subtotal,
			threshold: safeThreshold,
		};
	}

	function updateMilestones( element, subtotal ) {
		element.querySelectorAll( '.sfsb-free-shipping-bar__milestone' ).forEach( function ( milestone ) {
			var amount = parseNumber( milestone.dataset.amount );
			milestone.classList.toggle( 'is-reached', subtotal >= amount );
		} );
	}

	function updateElement( element, subtotal, itemCount ) {
		var threshold = parseNumber( element.dataset.threshold );
		var state = buildState( subtotal, threshold, itemCount );
		var message = element.querySelector( '.sfsb-free-shipping-bar__message' );
		var fill = element.querySelector( '.sfsb-free-shipping-bar__fill' );
		var subtotalNode = element.querySelector( '.sfsb-free-shipping-bar__subtotal' );
		var goalNode = element.querySelector( '.sfsb-free-shipping-bar__goal' );

		element.classList.toggle( 'is-complete', state.hasReachedGoal );
		element.classList.toggle( 'is-incomplete', ! state.hasReachedGoal );
		element.classList.toggle( 'is-empty', ! state.hasItems );

		if ( message ) {
			message.textContent = getMessage( element, state );
		}

		if ( fill ) {
			fill.style.width = state.progress + '%';
		}

		if ( subtotalNode ) {
			subtotalNode.textContent = 'Subtotal actual: ' + formatMoney( state.subtotal );
		}

		if ( goalNode ) {
			goalNode.textContent = 'Meta: ' + formatMoney( state.threshold );
		}

		updateMilestones( element, subtotal );
	}

	function refreshBars() {
		var body = new window.URLSearchParams();
		body.append( 'action', config.action );
		body.append( 'nonce', config.nonce );

		window.fetch( config.ajaxUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
			},
			body: body.toString(),
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Request failed' );
				}

				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload.success || ! payload.data ) {
					return;
				}

				document.querySelectorAll( selector ).forEach( function ( element ) {
					updateElement( element, parseNumber( payload.data.subtotal ), Number( payload.data.itemCount || 0 ) );
				} );
			} )
			.catch( function () {
				// Silent fail to avoid interrupting cart interactions.
			} );
	}

	$( document.body ).on(
		'added_to_cart removed_from_cart updated_cart_totals wc_fragments_loaded wc_fragments_refreshed updated_wc_div',
		function () {
			refreshBars();
		}
	);

	refreshBars();
} ( jQuery, window, document ) );
