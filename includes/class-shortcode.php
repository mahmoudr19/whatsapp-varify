<?php
/**
 * Shortcode class.
 *
 * Registers and renders the [whatsapp_gateway] shortcode.
 *
 * @package WhatsApp_Gateway
 * @since   1.0.0
 */

namespace WhatsApp_Gateway;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Shortcode
 *
 * Handles the [whatsapp_gateway] shortcode registration and output.
 */
class Shortcode {

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( 'whatsapp_gateway', array( $this, 'render' ) );
	}

	/**
	 * Render the shortcode output.
	 *
	 * Loads the gateway form template if the gateway is enabled.
	 *
	 * @param array|string $atts Shortcode attributes (unused for MVP).
	 * @return string HTML output of the gateway form.
	 */
	public function render( $atts = array() ) {
		$atts = shortcode_atts( array(
			'inline' => 'no',
		), $atts, 'whatsapp_gateway' );

		// Check if gateway is enabled.
		$enabled = get_option( 'wa_gateway_enabled', '1' );

		if ( '1' !== $enabled ) {
			return '';
		}

		// Get settings for the template.
		$destination_number = sanitize_text_field( get_option( 'wa_destination_number', '' ) );
		$default_message    = sanitize_text_field( get_option( 'wa_default_message', '' ) );
		$nonce              = wp_create_nonce( 'wa_gateway_nonce' );
		$is_inline          = 'yes' === $atts['inline'];

		// Buffer output from template.
		ob_start();
		include WA_GATEWAY_PLUGIN_DIR . 'templates/gateway-form.php';
		return ob_get_clean();
	}
}
