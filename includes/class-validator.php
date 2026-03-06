<?php
/**
 * Validator class.
 *
 * Provides server-side validation for Saudi phone numbers
 * and number format transformation.
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
 * Class Validator
 *
 * Saudi phone number validation and formatting.
 */
class Validator {

	/**
	 * Saudi mobile number regex pattern.
	 *
	 * Must start with 05 and be exactly 10 digits.
	 *
	 * @var string
	 */
	const SAUDI_MOBILE_PATTERN = '/^05\d{8}$/';

	/**
	 * Validate a Saudi mobile phone number.
	 *
	 * @param string $phone_number The phone number to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate( $phone_number ) {
		// Remove any whitespace or dashes.
		$phone_number = preg_replace( '/[\s\-]/', '', $phone_number );

		// Check if the number matches the Saudi mobile pattern.
		return (bool) preg_match( self::SAUDI_MOBILE_PATTERN, $phone_number );
	}

	/**
	 * Transform a local Saudi number to international format.
	 *
	 * Converts 0501234567 → 966501234567
	 *
	 * @param string $phone_number The local phone number (starting with 0).
	 * @return string The number in international format (starting with 966).
	 */
	public static function to_international( $phone_number ) {
		// Remove any whitespace or dashes.
		$phone_number = preg_replace( '/[\s\-]/', '', $phone_number );

		// Strip leading zero and prepend country code.
		return '966' . substr( $phone_number, 1 );
	}

	/**
	 * Sanitize a phone number input.
	 *
	 * Strips all non-numeric characters.
	 *
	 * @param string $phone_number Raw phone number input.
	 * @return string Sanitized phone number (digits only).
	 */
	public static function sanitize( $phone_number ) {
		return preg_replace( '/[^0-9]/', '', $phone_number );
	}
}
