<?php
/**
 * Redirect class.
 *
 * Builds the WhatsApp redirect URL from the validated
 * and formatted phone number.
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
 * Class Redirect
 *
 * Constructs the wa.me redirect URL.
 */
class Redirect {

	/**
	 * WhatsApp API base URL.
	 *
	 * @var string
	 */
	const WA_BASE_URL = 'https://wa.me/';

	/**
	 * Build the WhatsApp redirect URL.
	 *
	 * @param string $international_number Phone number in international format (e.g. 966501234567).
	 * @param string $message              Optional pre-filled message.
	 * @return string The full WhatsApp redirect URL.
	 */
	public static function build_url( $international_number, $message = '' ) {
		$url = self::WA_BASE_URL . rawurlencode( $international_number );

		if ( ! empty( $message ) ) {
			$url = add_query_arg( 'text', rawurlencode( $message ), $url );
		}

		return esc_url( $url );
	}

	/**
	 * Build the redirect URL using admin settings.
	 *
	 * Uses the destination number and default message from plugin settings.
	 * If a destination number is configured, it takes precedence over the
	 * user-entered number.
	 *
	 * @param string $user_international_number The user's phone number in international format.
	 * @return string The WhatsApp redirect URL.
	 */
	public static function build_url_from_settings( $user_international_number ) {
		$destination = sanitize_text_field( get_option( 'wa_destination_number', '' ) );
		$message     = sanitize_text_field( get_option( 'wa_default_message', '' ) );

		// Use the destination number from settings if available, otherwise use user's number.
		$number = ! empty( $destination ) ? $destination : $user_international_number;

		return self::build_url( $number, $message );
	}
}
