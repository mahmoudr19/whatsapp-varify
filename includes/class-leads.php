<?php
/**
 * Leads management class.
 *
 * Handles CRUD operations for stored phone leads and the
 * AJAX endpoint for saving leads from the frontend form.
 *
 * @package WhatsApp_Gateway
 * @since   1.1.0
 */

namespace WhatsApp_Gateway;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Leads
 *
 * Manages lead storage and retrieval.
 */
class Leads {

	/**
	 * Get the leads table name.
	 *
	 * @return string Full table name with prefix.
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'wa_gateway_leads';
	}

	/**
	 * Register AJAX hooks for lead saving.
	 *
	 * @return void
	 */
	public function register_ajax() {
		add_action( 'wp_ajax_wa_gateway_save_lead', array( $this, 'ajax_save_lead' ) );
		add_action( 'wp_ajax_nopriv_wa_gateway_save_lead', array( $this, 'ajax_save_lead' ) );
	}

	/**
	 * AJAX handler — Save a validated lead.
	 *
	 * @return void
	 */
	public function ajax_save_lead() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wa_gateway_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
		}

		$phone = isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '';

		// Validate with the Validator class.
		if ( ! Validator::validate( $phone ) ) {
			wp_send_json_error( array( 'message' => 'Invalid phone number.' ), 400 );
		}

		$international = Validator::to_international( $phone );
		$page_url      = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';
		$lead_type     = isset( $_POST['lead_type'] ) ? sanitize_text_field( wp_unslash( $_POST['lead_type'] ) ) : 'whatsapp';

		$result = self::save_lead( $phone, $international, $page_url, $lead_type );

		if ( $result ) {
			wp_send_json_success( array( 'message' => 'Lead saved.' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to save lead.' ), 500 );
		}
	}

	/**
	 * Save a lead to the database.
	 *
	 * @param string $phone_number       Local phone number (e.g. 0501234567).
	 * @param string $phone_international International format (e.g. 966501234567).
	 * @param string $page_url           The page URL where the form was submitted.
	 * @param string $lead_type          The interaction type (whatsapp or call).
	 * @return bool True on success, false on failure.
	 */
	public static function save_lead( $phone_number, $phone_international, $page_url = '', $lead_type = 'whatsapp' ) {
		global $wpdb;

		$ip_address = '';
		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip_address = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$result = $wpdb->insert(
			self::get_table_name(),
			array(
				'phone_number'        => $phone_number,
				'phone_international' => $phone_international,
				'lead_type'           => $lead_type,
				'ip_address'          => $ip_address,
				'page_url'            => $page_url,
				'created_at'          => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $result;
	}

	/**
	 * Get leads with pagination.
	 *
	 * @param int    $per_page Number of leads per page.
	 * @param int    $page     Current page number.
	 * @param string $orderby  Column to order by.
	 * @param string $order    ASC or DESC.
	 * @return array Array of lead objects.
	 */
	public static function get_leads( $per_page = 20, $page = 1, $orderby = 'created_at', $order = 'DESC' ) {
		global $wpdb;

		$table_name = self::get_table_name();

		// Whitelist orderby columns.
		$allowed_orderby = array( 'id', 'phone_number', 'ip_address', 'created_at' );
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'created_at';
		}

		$order  = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
		$offset = ( $page - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name and orderby are sanitized above.
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get total leads count.
	 *
	 * @return int Total number of leads.
	 */
	public static function get_leads_count() {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
	}

	/**
	 * Delete a lead by ID.
	 *
	 * @param int $id Lead ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete_lead( $id ) {
		global $wpdb;

		$result = $wpdb->delete(
			self::get_table_name(),
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);

		return false !== $result;
	}

	/**
	 * Delete multiple leads by IDs.
	 *
	 * @param array $ids Array of lead IDs.
	 * @return int Number of deleted rows.
	 */
	public static function delete_leads( $ids ) {
		global $wpdb;

		$table_name = self::get_table_name();
		$ids        = array_map( 'absint', $ids );
		$ids_string = implode( ',', $ids );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->query( "DELETE FROM {$table_name} WHERE id IN ({$ids_string})" );
	}

	/**
	 * Get all leads for CSV export.
	 *
	 * @return array Array of lead objects.
	 */
	public static function get_all_leads() {
		global $wpdb;

		$table_name = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created_at DESC" );
	}
}
