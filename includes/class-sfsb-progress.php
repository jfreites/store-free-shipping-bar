<?php
/**
 * Progress calculations.
 *
 * @package Store_Free_Shipping_Bar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Progress service.
 */
class SFSB_Progress {

	/**
	 * Return current cart subtotal excluding shipping.
	 *
	 * @return float
	 */
	public function get_cart_subtotal() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return 0.0;
		}

		return (float) WC()->cart->get_displayed_subtotal();
	}

	/**
	 * Build progress payload for a threshold.
	 *
	 * @param float $threshold Threshold amount.
	 * @return array
	 */
	public function get_state( $threshold ) {
		$threshold = max( 0, (float) $threshold );
		$subtotal  = $this->get_cart_subtotal();
		$remaining = max( 0, $threshold - $subtotal );
		$progress  = $threshold > 0 ? min( 100, ( $subtotal / $threshold ) * 100 ) : 0;
		$has_items  = function_exists( 'WC' ) && WC()->cart && WC()->cart->get_cart_contents_count() > 0;

		return array(
			'subtotal'          => $subtotal,
			'threshold'         => $threshold,
			'remaining'         => $remaining,
			'progress'          => (float) round( $progress, 2 ),
			'has_reached_goal'  => $threshold > 0 && $subtotal >= $threshold,
			'has_items'         => $has_items,
			'formattedSubtotal' => wc_price( $subtotal ),
			'formattedThreshold'=> wc_price( $threshold ),
			'formattedRemaining'=> wc_price( $remaining ),
		);
	}
}
