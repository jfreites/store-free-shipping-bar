<?php
/**
 * Markup rendering.
 *
 * @package Store_Free_Shipping_Bar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renderer.
 */
class SFSB_Renderer {

	/**
	 * Progress service.
	 *
	 * @var SFSB_Progress
	 */
	protected $progress;

	/**
	 * Constructor.
	 *
	 * @param SFSB_Progress $progress Progress service.
	 */
	public function __construct( SFSB_Progress $progress ) {
		$this->progress = $progress;
	}

	/**
	 * Render the bar HTML.
	 *
	 * @param array $args Render arguments.
	 * @return string
	 */
	public function render( $args = array() ) {
		if ( ! function_exists( 'WC' ) ) {
			return '';
		}

		$settings = SFSB_Settings::get();
		$args     = wp_parse_args(
			$args,
			array(
				'context'   => 'default',
				'threshold' => (float) $settings['threshold'],
				'class'     => '',
			)
		);

		$state      = $this->progress->get_state( $args['threshold'] );
		$milestones = SFSB_Settings::parse_milestones( $settings['milestones'] );
		$message    = $this->get_message( $state, $settings );
		$classes    = array(
			'sfsb-free-shipping-bar',
			$state['has_reached_goal'] ? 'is-complete' : 'is-incomplete',
			! $state['has_items'] ? 'is-empty' : '',
			sanitize_html_class( 'sfsb-context-' . $args['context'] ),
		);

		if ( ! empty( $args['class'] ) ) {
			$classes[] = sanitize_html_class( $args['class'] );
		}

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>"
			data-threshold="<?php echo esc_attr( wc_format_decimal( $args['threshold'] ) ); ?>"
			data-context="<?php echo esc_attr( $args['context'] ); ?>"
			data-message-remaining="<?php echo esc_attr( $settings['remaining_message'] ); ?>"
			data-message-complete="<?php echo esc_attr( $settings['complete_message'] ); ?>"
			data-message-empty="<?php echo esc_attr( $settings['empty_message'] ); ?>"
			data-milestones="<?php echo esc_attr( wp_json_encode( $milestones ) ); ?>"
		>
			<div class="sfsb-free-shipping-bar__inner">
				<p class="sfsb-free-shipping-bar__message" aria-live="polite">
					<?php echo esc_html( $message ); ?>
				</p>
				<div class="sfsb-free-shipping-bar__track" aria-hidden="true">
					<span class="sfsb-free-shipping-bar__fill" style="width: <?php echo esc_attr( $state['progress'] ); ?>%;"></span>
				</div>
				<div class="sfsb-free-shipping-bar__meta">
					<span class="sfsb-free-shipping-bar__subtotal">
						<?php
						printf(
							/* translators: %s: current subtotal. */
							esc_html__( 'Subtotal actual: %s', 'store-free-shipping-bar' ),
							wp_strip_all_tags( $state['formattedSubtotal'] )
						);
						?>
					</span>
					<span class="sfsb-free-shipping-bar__goal">
						<?php
						printf(
							/* translators: %s: free shipping goal. */
							esc_html__( 'Meta: %s', 'store-free-shipping-bar' ),
							wp_strip_all_tags( $state['formattedThreshold'] )
						);
						?>
					</span>
				</div>
				<?php if ( ! empty( $milestones ) ) : ?>
					<div class="sfsb-free-shipping-bar__milestones" aria-hidden="true">
						<?php foreach ( $milestones as $milestone ) : ?>
							<?php
							$marker_class = $state['subtotal'] >= $milestone['amount'] ? 'is-reached' : '';
							$left = $state['threshold'] > 0 ? min( 100, ( $milestone['amount'] / $state['threshold'] ) * 100 ) : 0;
							?>
							<span
								class="sfsb-free-shipping-bar__milestone <?php echo esc_attr( $marker_class ); ?>"
								style="left: <?php echo esc_attr( round( $left, 2 ) ); ?>%;"
								title="<?php echo esc_attr( $milestone['label'] ); ?>"
								data-amount="<?php echo esc_attr( wc_format_decimal( $milestone['amount'] ) ); ?>"
							>
								<span class="sfsb-free-shipping-bar__milestone-label"><?php echo esc_html( $milestone['label'] ); ?></span>
							</span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Build the user-facing message.
	 *
	 * @param array $state Progress state.
	 * @param array $settings Plugin settings.
	 * @return string
	 */
	protected function get_message( $state, $settings ) {
		if ( ! $state['has_items'] ) {
			return (string) $settings['empty_message'];
		}

		if ( $state['has_reached_goal'] ) {
			return (string) $settings['complete_message'];
		}

		return sprintf( (string) $settings['remaining_message'], wp_strip_all_tags( $state['formattedRemaining'] ) );
	}
}
