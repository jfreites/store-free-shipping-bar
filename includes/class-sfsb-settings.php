<?php
/**
 * Settings management.
 *
 * @package Store_Free_Shipping_Bar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings helper.
 */
class SFSB_Settings {

	/**
	 * Option key in wp_options.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'sfsb_settings';

	/**
	 * Return defaults.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'threshold'           => 50,
			'remaining_message'   => __( 'Te faltan %s para envio gratis', 'store-free-shipping-bar' ),
			'complete_message'    => __( 'Ya tienes envio gratis', 'store-free-shipping-bar' ),
			'empty_message'       => __( 'Tu carrito esta vacio. Agrega productos para desbloquear el envio gratis.', 'store-free-shipping-bar' ),
			'display_on_cart'     => 'yes',
			'display_on_minicart' => 'yes',
			'milestones'          => '',
		);
	}

	/**
	 * Return all settings merged with defaults.
	 *
	 * @return array
	 */
	public static function get() {
		$settings = get_option( self::OPTION_KEY, array() );

		return wp_parse_args( is_array( $settings ) ? $settings : array(), self::defaults() );
	}

	/**
	 * Return a single setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get_setting( $key ) {
		$settings = self::get();

		return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
	}

	/**
	 * Parse bonus milestones from textarea.
	 *
	 * Accepted format: amount|label per line.
	 *
	 * @param string $raw Raw textarea value.
	 * @return array
	 */
	public static function parse_milestones( $raw ) {
		$milestones = array();
		$lines      = preg_split( '/\r\n|\r|\n/', (string) $raw );

		foreach ( $lines as $line ) {
			$line = trim( $line );

			if ( '' === $line ) {
				continue;
			}

			$parts = array_map( 'trim', explode( '|', $line, 2 ) );
			$amount = isset( $parts[0] ) ? wc_format_decimal( $parts[0] ) : '';

			if ( '' === $amount ) {
				continue;
			}

			$milestones[] = array(
				'amount' => (float) $amount,
				'label'  => isset( $parts[1] ) && '' !== $parts[1] ? sanitize_text_field( $parts[1] ) : sprintf(
					/* translators: %s: amount milestone. */
					__( 'Meta %s', 'store-free-shipping-bar' ),
					wc_price( (float) $amount )
				),
			);
		}

		usort(
			$milestones,
			static function ( $left, $right ) {
				if ( $left['amount'] === $right['amount'] ) {
					return 0;
				}

				return ( $left['amount'] < $right['amount'] ) ? -1 : 1;
			}
		);

		return $milestones;
	}
}
