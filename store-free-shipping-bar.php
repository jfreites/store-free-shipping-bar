<?php
/**
 * Plugin Name: Store Free Shipping Bar for WooCommerce
 * Plugin URI:  https://github.com/jfreites/store-free-shipping-bar
 * Description: Adds a reusable free shipping progress bar for WooCommerce carts, mini carts, drawers, and product pages.
 * Version:     1.0.0
 * Author:      Jonathan Freites
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: store-free-shipping-bar
 * Domain Path: /languages
 *
 * @package Store_Free_Shipping_Bar
 */

defined( 'ABSPATH' ) || exit;

define( 'SFSB_VERSION', '1.0.0' );
define( 'SFSB_PLUGIN_FILE', __FILE__ );
define( 'SFSB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'SFSB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once SFSB_PLUGIN_PATH . 'includes/class-sfsb-settings.php';
require_once SFSB_PLUGIN_PATH . 'includes/class-sfsb-progress.php';
require_once SFSB_PLUGIN_PATH . 'includes/class-sfsb-renderer.php';
require_once SFSB_PLUGIN_PATH . 'includes/class-sfsb-plugin.php';

SFSB_Plugin::instance();

if ( ! function_exists( 'wc_free_shipping_bar' ) ) {
	/**
	 * Render the free shipping bar anywhere in PHP templates.
	 *
	 * @param array $args Optional render arguments.
	 * @return void
	 */
	function wc_free_shipping_bar( $args = array() ) {
		if ( ! class_exists( 'SFSB_Plugin' ) ) {
			return;
		}

		echo SFSB_Plugin::instance()->renderer()->render( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
