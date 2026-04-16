<?php
/**
 * Main plugin bootstrap.
 *
 * @package Store_Free_Shipping_Bar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 */
class SFSB_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var SFSB_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Progress service.
	 *
	 * @var SFSB_Progress
	 */
	protected $progress;

	/**
	 * Renderer.
	 *
	 * @var SFSB_Renderer
	 */
	protected $renderer;

	/**
	 * Bootstrap singleton.
	 *
	 * @return SFSB_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->progress = new SFSB_Progress();
		$this->renderer = new SFSB_Renderer( $this->progress );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_boot_woocommerce' ), 20 );
		register_activation_hook( SFSB_PLUGIN_FILE, array( $this, 'activate' ) );
	}

	/**
	 * Return renderer instance.
	 *
	 * @return SFSB_Renderer
	 */
	public function renderer() {
		return $this->renderer;
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'store-free-shipping-bar', false, dirname( plugin_basename( SFSB_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Add default settings on activation.
	 *
	 * @return void
	 */
	public function activate() {
		if ( false === get_option( SFSB_Settings::OPTION_KEY, false ) ) {
			add_option( SFSB_Settings::OPTION_KEY, SFSB_Settings::defaults() );
		}
	}

	/**
	 * Boot WooCommerce-specific features.
	 *
	 * @return void
	 */
	public function maybe_boot_woocommerce() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_shortcode( 'free_shipping_bar', array( $this, 'shortcode' ) );
		add_action( 'woocommerce_before_cart', array( $this, 'render_before_cart' ), 5 );
		add_action( 'woocommerce_before_mini_cart', array( $this, 'render_before_mini_cart' ), 5 );
		add_action( 'wp_ajax_sfsb_get_progress', array( $this, 'ajax_get_progress' ) );
		add_action( 'wp_ajax_nopriv_sfsb_get_progress', array( $this, 'ajax_get_progress' ) );
	}

	/**
	 * Notice if WooCommerce is missing.
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-warning">
			<p><?php esc_html_e( 'Store Free Shipping Bar for WooCommerce requiere que WooCommerce este activo.', 'store-free-shipping-bar' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Enqueue front-end assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'sfsb-free-shipping-bar',
			SFSB_PLUGIN_URL . 'assets/css/free-shipping-bar.css',
			array(),
			SFSB_VERSION
		);

		wp_enqueue_script(
			'sfsb-free-shipping-bar',
			SFSB_PLUGIN_URL . 'assets/js/free-shipping-bar.js',
			array( 'jquery' ),
			SFSB_VERSION,
			true
		);

		wp_localize_script(
			'sfsb-free-shipping-bar',
			'sfsbSettings',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'action'          => 'sfsb_get_progress',
				'nonce'           => wp_create_nonce( 'sfsb_progress_nonce' ),
				'currencySymbol'  => get_woocommerce_currency_symbol(),
				'currencyFormat'  => html_entity_decode( get_woocommerce_price_format(), ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'decimalSeparator'=> wc_get_price_decimal_separator(),
				'thousandSeparator'=> wc_get_price_thousand_separator(),
				'decimals'        => wc_get_price_decimals(),
			)
		);
	}

	/**
	 * Render on cart page hook.
	 *
	 * @return void
	 */
	public function render_before_cart() {
		if ( 'yes' !== SFSB_Settings::get_setting( 'display_on_cart' ) ) {
			return;
		}

		echo $this->renderer->render( array( 'context' => 'cart' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render on mini cart hook.
	 *
	 * @return void
	 */
	public function render_before_mini_cart() {
		if ( 'yes' !== SFSB_Settings::get_setting( 'display_on_minicart' ) ) {
			return;
		}

		echo $this->renderer->render( array( 'context' => 'mini-cart' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'context'   => 'shortcode',
				'threshold' => SFSB_Settings::get_setting( 'threshold' ),
				'class'     => '',
			),
			$atts,
			'free_shipping_bar'
		);

		return $this->renderer->render( $atts );
	}

	/**
	 * AJAX progress state.
	 *
	 * @return void
	 */
	public function ajax_get_progress() {
		check_ajax_referer( 'sfsb_progress_nonce', 'nonce' );

		$subtotal   = $this->progress->get_cart_subtotal();
		$item_count = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

		wp_send_json_success(
			array(
				'subtotal'   => $subtotal,
				'itemCount'  => $item_count,
			)
		);
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Free Shipping Bar', 'store-free-shipping-bar' ),
			__( 'Free Shipping Bar', 'store-free-shipping-bar' ),
			'manage_woocommerce',
			'sfsb-free-shipping-bar',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'sfsb_settings_group',
			SFSB_Settings::OPTION_KEY,
			array( $this, 'sanitize_settings' )
		);

		add_settings_section(
			'sfsb_general_section',
			__( 'General Settings', 'store-free-shipping-bar' ),
			'__return_false',
			'sfsb-free-shipping-bar'
		);

		$fields = array(
			'threshold'           => __( 'Monto minimo para envio gratis', 'store-free-shipping-bar' ),
			'remaining_message'   => __( 'Mensaje de progreso', 'store-free-shipping-bar' ),
			'complete_message'    => __( 'Mensaje de exito', 'store-free-shipping-bar' ),
			'empty_message'       => __( 'Mensaje con carrito vacio', 'store-free-shipping-bar' ),
			'display_on_cart'     => __( 'Mostrar en carrito', 'store-free-shipping-bar' ),
			'display_on_minicart' => __( 'Mostrar en mini cart', 'store-free-shipping-bar' ),
			'milestones'          => __( 'Umbrales extra (bonus)', 'store-free-shipping-bar' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				$key,
				$label,
				array( $this, 'render_setting_field' ),
				'sfsb-free-shipping-bar',
				'sfsb_general_section',
				array( 'key' => $key )
			);
		}
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$defaults = SFSB_Settings::defaults();
		$output   = array();

		$output['threshold']           = isset( $input['threshold'] ) ? (float) wc_format_decimal( $input['threshold'] ) : $defaults['threshold'];
		$output['remaining_message']   = isset( $input['remaining_message'] ) ? sanitize_text_field( $input['remaining_message'] ) : $defaults['remaining_message'];
		$output['complete_message']    = isset( $input['complete_message'] ) ? sanitize_text_field( $input['complete_message'] ) : $defaults['complete_message'];
		$output['empty_message']       = isset( $input['empty_message'] ) ? sanitize_text_field( $input['empty_message'] ) : $defaults['empty_message'];
		$output['display_on_cart']     = ! empty( $input['display_on_cart'] ) ? 'yes' : 'no';
		$output['display_on_minicart'] = ! empty( $input['display_on_minicart'] ) ? 'yes' : 'no';
		$output['milestones']          = isset( $input['milestones'] ) ? sanitize_textarea_field( $input['milestones'] ) : '';

		return $output;
	}

	/**
	 * Render individual settings field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public function render_setting_field( $args ) {
		$key      = $args['key'];
		$settings = SFSB_Settings::get();
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : '';

		switch ( $key ) {
			case 'threshold':
				?>
				<input
					type="number"
					min="0"
					step="0.01"
					class="regular-text"
					name="<?php echo esc_attr( SFSB_Settings::OPTION_KEY . '[' . $key . ']' ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
				/>
				<p class="description"><?php esc_html_e( 'Ejemplo: 50. El subtotal del carrito se compara contra este monto.', 'store-free-shipping-bar' ); ?></p>
				<?php
				break;

			case 'display_on_cart':
			case 'display_on_minicart':
				?>
				<label>
					<input
						type="checkbox"
						name="<?php echo esc_attr( SFSB_Settings::OPTION_KEY . '[' . $key . ']' ); ?>"
						value="yes"
						<?php checked( 'yes', $value ); ?>
					/>
					<?php esc_html_e( 'Activado', 'store-free-shipping-bar' ); ?>
				</label>
				<?php
				break;

			case 'milestones':
				?>
				<textarea
					name="<?php echo esc_attr( SFSB_Settings::OPTION_KEY . '[' . $key . ']' ); ?>"
					rows="5"
					class="large-text"
				><?php echo esc_textarea( $value ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Opcional. Una meta por linea con formato monto|etiqueta. Ejemplo: 80|Regalo gratis', 'store-free-shipping-bar' ); ?></p>
				<?php
				break;

			default:
				?>
				<input
					type="text"
					class="regular-text"
					name="<?php echo esc_attr( SFSB_Settings::OPTION_KEY . '[' . $key . ']' ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
				/>
				<?php
				if ( 'remaining_message' === $key ) {
					?>
					<p class="description"><?php esc_html_e( 'Usa %s para insertar el monto restante. Ejemplo: Te faltan %s para envio gratis', 'store-free-shipping-bar' ); ?></p>
					<?php
				}
				break;
		}
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free Shipping Bar', 'store-free-shipping-bar' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'sfsb_settings_group' ); ?>
				<?php do_settings_sections( 'sfsb-free-shipping-bar' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
