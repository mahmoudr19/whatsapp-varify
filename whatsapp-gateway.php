<?php
/**
 * Plugin Name: WhatsApp Number Validation Gateway
 * Plugin URI:  https://example.com/whatsapp-gateway
 * Description: Inserts a validation gateway popup before users are redirected to WhatsApp. Ensures valid Saudi phone numbers for higher lead quality.
 * Version:     1.1.0
 * Author:      Mahmoud Reda
 * Author URI:  https://mahmoudreda.me
 * License:     GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: whatsapp-gateway
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package WhatsApp_Gateway
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin constants.
 */
define( 'WA_GATEWAY_VERSION', '1.1.1' );
define( 'WA_GATEWAY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WA_GATEWAY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WA_GATEWAY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload plugin classes.
 */
require_once WA_GATEWAY_PLUGIN_DIR . 'includes/class-plugin-init.php';
require_once WA_GATEWAY_PLUGIN_DIR . 'includes/class-settings.php';
require_once WA_GATEWAY_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once WA_GATEWAY_PLUGIN_DIR . 'includes/class-validator.php';
require_once WA_GATEWAY_PLUGIN_DIR . 'includes/class-redirect.php';
require_once WA_GATEWAY_PLUGIN_DIR . 'includes/class-leads.php';
require_once WA_GATEWAY_PLUGIN_DIR . 'includes/class-leads-page.php';

/**
 * Initialize the plugin.
 *
 * @return void
 */
function wa_gateway_init() {
	$plugin = new \WhatsApp_Gateway\Plugin_Init();
	$plugin->run();
}
add_action( 'plugins_loaded', 'wa_gateway_init' );

/**
 * Activation hook — set default options and create DB table.
 *
 * @return void
 */
function wa_gateway_activate() {
	wa_gateway_install_db();
}
register_activation_hook( __FILE__, 'wa_gateway_activate' );

/**
 * Install or upgrade the database schema.
 *
 * @return void
 */
function wa_gateway_install_db() {
	global $wpdb;

	// Default options.
	$defaults = array(
		'wa_destination_number'  => '966114797777',
		'wa_default_message'     => '',
		'wa_gateway_enabled'     => '1',
		'wa_redirect_url'        => '',
		'wa_trigger_button_id'   => 'whatsapp-btn',
	);

	foreach ( $defaults as $key => $value ) {
		if ( false === get_option( $key ) ) {
			add_option( $key, $value );
		}
	}

	// Create leads table.
	$table_name      = $wpdb->prefix . 'wa_gateway_leads';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		phone_number VARCHAR(20) NOT NULL,
		phone_international VARCHAR(20) NOT NULL,
		lead_type VARCHAR(20) DEFAULT 'whatsapp',
		ip_address VARCHAR(45) DEFAULT '',
		page_url TEXT DEFAULT '',
		created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY phone_number (phone_number),
		KEY created_at (created_at)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Store DB version.
	update_option( 'wa_gateway_db_version', '1.1' );
}

/**
 * Check if the database needs an upgrade.
 *
 * @return void
 */
function wa_gateway_check_upgrade() {
	$current_version = get_option( 'wa_gateway_db_version', '1.0' );
	if ( version_compare( $current_version, '1.1', '<' ) ) {
		wa_gateway_install_db();
	}
}
add_action( 'plugins_loaded', 'wa_gateway_check_upgrade' );

/**
 * Deactivation hook — clean up if needed.
 *
 * @return void
 */
function wa_gateway_deactivate() {
	// Future cleanup logic can go here.
}
register_deactivation_hook( __FILE__, 'wa_gateway_deactivate' );
