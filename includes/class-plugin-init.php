<?php
/**
 * Plugin initialization class.
 *
 * Bootstraps all plugin components: settings, leads, popup modal,
 * and site-wide asset loading.
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
 * Class Plugin_Init
 *
 * Main plugin bootstrapper.
 */
class Plugin_Init {

	/**
	 * Settings instance.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Shortcode instance.
	 *
	 * @var Shortcode
	 */
	private $shortcode;

	/**
	 * Leads instance.
	 *
	 * @var Leads
	 */
	private $leads;

	/**
	 * Leads Page instance.
	 *
	 * @var Leads_Page
	 */
	private $leads_page;

	/**
	 * Initialize the plugin components.
	 *
	 * @return void
	 */
	public function run() {
		$this->settings   = new Settings();
		$this->shortcode  = new Shortcode();
		$this->leads      = new Leads();
		$this->leads_page = new Leads_Page();

		// Admin.
		if ( is_admin() ) {
			$this->settings->register();
			$this->leads_page->register();
		}

		// Register shortcode (still available as secondary option).
		$this->shortcode->register();

		// Enqueue frontend assets site-wide (popup can appear on any page).
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Inject popup HTML into footer.
		add_action( 'wp_footer', array( $this, 'render_popup_in_footer' ) );

		// Register AJAX handler for saving leads.
		$this->leads->register_ajax();

		// Add settings link on Plugins page.
		add_filter( 'plugin_action_links_' . WA_GATEWAY_PLUGIN_BASENAME, array( $this, 'add_settings_link' ) );
	}

	/**
	 * Enqueue frontend CSS and JS site-wide (popup can be triggered from any page).
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// Check if gateway is enabled.
		if ( '1' !== get_option( 'wa_gateway_enabled', '1' ) ) {
			return;
		}

		// CSS.
		wp_enqueue_style(
			'wa-gateway-style',
			WA_GATEWAY_PLUGIN_URL . 'assets/css/style.css',
			array(),
			WA_GATEWAY_VERSION
		);

		// Note: 'Portada' font should be loaded by the theme or site.

		// JS.
		wp_enqueue_script(
			'wa-gateway-validation',
			WA_GATEWAY_PLUGIN_URL . 'assets/js/validation.js',
			array(),
			WA_GATEWAY_VERSION,
			true
		);

		// Pass settings to JS.
		wp_localize_script(
			'wa-gateway-validation',
			'waGatewaySettings',
			array(
				'destinationNumber' => sanitize_text_field( get_option( 'wa_destination_number', '' ) ),
				'callDestination'   => sanitize_text_field( get_option( 'wa_call_destination_number', '' ) ),
				'defaultMessage'    => sanitize_text_field( get_option( 'wa_default_message', '' ) ),
				'gatewayEnabled'    => get_option( 'wa_gateway_enabled', '1' ),
				'redirectUrl'       => esc_url( get_option( 'wa_redirect_url', '' ) ),
				'triggerButtonId'   => sanitize_text_field( get_option( 'wa_trigger_button_id', 'whatsapp-btn' ) ),
				'callTriggerId'     => sanitize_text_field( get_option( 'wa_call_trigger_button_id', 'call-btn' ) ),
				'nonce'             => wp_create_nonce( 'wa_gateway_nonce' ),
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Render the popup modal HTML in the footer.
	 *
	 * @return void
	 */
	public function render_popup_in_footer() {
		// Check if gateway is enabled.
		if ( '1' !== get_option( 'wa_gateway_enabled', '1' ) ) {
			return;
		}

		$destination_number = sanitize_text_field( get_option( 'wa_destination_number', '' ) );
		$default_message    = sanitize_text_field( get_option( 'wa_default_message', '' ) );
		$nonce              = wp_create_nonce( 'wa_gateway_nonce' );

		include WA_GATEWAY_PLUGIN_DIR . 'templates/gateway-form.php';
	}

	/**
	 * Add a "Settings" link on the Plugins page.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array Modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=whatsapp-gateway' ) ),
			esc_html__( 'Settings', 'whatsapp-gateway' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}
}
