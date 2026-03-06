<?php
/**
 * Admin settings class.
 *
 * Registers the WhatsApp Gateway settings page under
 * Settings → WhatsApp Gateway using the WordPress Settings API.
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
 * Class Settings
 *
 * Handles the WordPress admin settings page.
 */
class Settings {

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'wa_gateway_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'whatsapp-gateway';

	/**
	 * Register hooks for the admin settings page.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
	}

	/**
	 * Enqueue admin styles for the settings page.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_styles( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wa-gateway-admin-style',
			WA_GATEWAY_PLUGIN_URL . 'assets/css/admin-style.css',
			array(),
			WA_GATEWAY_VERSION
		);
	}

	/**
	 * Add the settings page to the WordPress admin menu.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'WhatsApp Gateway Settings', 'whatsapp-gateway' ),
			__( 'WhatsApp Gateway', 'whatsapp-gateway' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 *
	 * @return void
	 */
	public function register_settings() {

		// --- Section: General Settings ---.
		add_settings_section(
			'wa_gateway_general_section',
			'',
			'__return_empty_string',
			self::PAGE_SLUG
		);

		// Field: Destination Number.
		register_setting( self::OPTION_GROUP, 'wa_destination_number', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'wa_destination_number',
			__( 'WhatsApp Destination Number', 'whatsapp-gateway' ),
			array( $this, 'render_destination_number_field' ),
			self::PAGE_SLUG,
			'wa_gateway_general_section'
		);

		// Field: Default Message.
		register_setting( self::OPTION_GROUP, 'wa_default_message', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'wa_default_message',
			__( 'Default WhatsApp Message', 'whatsapp-gateway' ),
			array( $this, 'render_default_message_field' ),
			self::PAGE_SLUG,
			'wa_gateway_general_section'
		);

		// Field: Enable/Disable Gateway.
		register_setting( self::OPTION_GROUP, 'wa_gateway_enabled', array(
			'type'              => 'string',
			'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
			'default'           => '1',
		) );

		add_settings_field(
			'wa_gateway_enabled',
			__( 'Enable Gateway', 'whatsapp-gateway' ),
			array( $this, 'render_enabled_field' ),
			self::PAGE_SLUG,
			'wa_gateway_general_section'
		);

		// Field: Redirect URL (fallback).
		register_setting( self::OPTION_GROUP, 'wa_redirect_url', array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => '',
		) );

		add_settings_field(
			'wa_redirect_url',
			__( 'Redirect URL', 'whatsapp-gateway' ),
			array( $this, 'render_redirect_url_field' ),
			self::PAGE_SLUG,
			'wa_gateway_general_section'
		);

		// Field: Trigger Button ID.
		register_setting( self::OPTION_GROUP, 'wa_trigger_button_id', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'whatsapp-btn',
		) );

		add_settings_field(
			'wa_trigger_button_id',
			__( 'WhatsApp Trigger ID', 'whatsapp-gateway' ),
			array( $this, 'render_trigger_button_field' ),
			self::PAGE_SLUG,
			'wa_gateway_general_section'
		);

		// Field: Call Destination Number.
		register_setting( self::OPTION_GROUP, 'wa_call_destination_number', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'wa_call_destination_number',
			__( 'Phone Call Destination Number', 'whatsapp-gateway' ),
			array( $this, 'render_call_destination_number_field' ),
			self::PAGE_SLUG,
			'wa_gateway_general_section'
		);

		// Field: Call Trigger Button ID.
		register_setting( self::OPTION_GROUP, 'wa_call_trigger_button_id', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'call-btn',
		) );

		add_settings_field(
			'wa_call_trigger_button_id',
			__( 'Call Trigger ID', 'whatsapp-gateway' ),
			array( $this, 'render_call_trigger_button_field' ),
			self::PAGE_SLUG,
			'wa_gateway_general_section'
		);
	}

	/**
	 * Render the admin settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap wa-gateway-admin-wrap">
			<div class="wa-gateway-admin-header">
				<div class="wa-gateway-admin-logo">
					<svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="#25D366"/>
						<path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a7.96 7.96 0 01-4.11-1.14l-.29-.174-3.01.79.8-2.93-.19-.3A7.963 7.963 0 014 12c0-4.411 3.589-8 8-8s8 3.589 8 8-3.589 8-8 8z" fill="#25D366"/>
					</svg>
				</div>
				<h1><?php esc_html_e( 'WhatsApp Gateway Settings', 'whatsapp-gateway' ); ?></h1>
				<p class="wa-gateway-admin-subtitle"><?php esc_html_e( 'Configure the phone number validation gateway for WhatsApp.', 'whatsapp-gateway' ); ?></p>
			</div>

			<div class="wa-gateway-admin-card">
				<form method="post" action="options.php">
					<?php
					settings_fields( self::OPTION_GROUP );
					do_settings_sections( self::PAGE_SLUG );
					submit_button( __( 'Save Settings', 'whatsapp-gateway' ) );
					?>
				</form>
			</div>

			<div class="wa-gateway-admin-card wa-gateway-admin-info">
				<h3><?php esc_html_e( 'طريقة الاستخدام', 'whatsapp-gateway' ); ?></h3>
				<p><?php esc_html_e( 'أضف زر في أي مكان بالموقع وحدد له ID يطابق القيمة أعلاه:', 'whatsapp-gateway' ); ?></p>
				<code>&lt;button id="whatsapp-btn"&gt;تواصل معنا عبر واتساب&lt;/button&gt;</code>
				<p class="wa-gateway-admin-hint">
					<?php esc_html_e( 'عند الضغط على الزر، سيظهر نموذج التحقق كبوب أب. يمكنك أيضاً استخدام الشورت كود [whatsapp_gateway] كخيار بديل لفتح البوب أب مباشرة، أو إستخدام [whatsapp_gateway inline="yes"] لطباعة النموذج الكارد بدون خلفية لاستخدامه داخل Elementor Popup.', 'whatsapp-gateway' ); ?>
				</p>
				<p class="wa-gateway-admin-hint">
					<?php esc_html_e( 'يدعم أيضاً الربط بالكلاس — أي زر يحمل نفس الكلاس سيفتح البوب أب.', 'whatsapp-gateway' ); ?>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Destination Number field.
	 *
	 * @return void
	 */
	public function render_destination_number_field() {
		$value = get_option( 'wa_destination_number', '' );
		printf(
			'<input type="text" id="wa_destination_number" name="wa_destination_number" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( $value ),
			esc_attr__( 'e.g. 966501234567', 'whatsapp-gateway' )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'The WhatsApp number that users will be directed to (international format without +).', 'whatsapp-gateway' )
		);
	}

	/**
	 * Render the Default Message field.
	 *
	 * @return void
	 */
	public function render_default_message_field() {
		$value = get_option( 'wa_default_message', '' );
		printf(
			'<textarea id="wa_default_message" name="wa_default_message" class="large-text" rows="3" placeholder="%s">%s</textarea>',
			esc_attr__( 'e.g. Hello, I would like to inquire about...', 'whatsapp-gateway' ),
			esc_textarea( $value )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Pre-filled message when users are redirected to WhatsApp (optional).', 'whatsapp-gateway' )
		);
	}

	/**
	 * Render the Enable/Disable Gateway field.
	 *
	 * @return void
	 */
	public function render_enabled_field() {
		$value = get_option( 'wa_gateway_enabled', '1' );
		printf(
			'<label for="wa_gateway_enabled"><input type="checkbox" id="wa_gateway_enabled" name="wa_gateway_enabled" value="1" %s /> %s</label>',
			checked( $value, '1', false ),
			esc_html__( 'Enable the validation gateway', 'whatsapp-gateway' )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'When disabled, the shortcode will not render the form.', 'whatsapp-gateway' )
		);
	}

	/**
	 * Render the Redirect URL field.
	 *
	 * @return void
	 */
	public function render_redirect_url_field() {
		$value = get_option( 'wa_redirect_url', '' );
		printf(
			'<input type="url" id="wa_redirect_url" name="wa_redirect_url" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( $value ),
			esc_attr__( 'e.g. https://example.com/thank-you', 'whatsapp-gateway' )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'Optional fallback redirect URL after WhatsApp redirect (leave empty to use default).', 'whatsapp-gateway' )
		);
	}

	/**
	 * Sanitize checkbox value.
	 *
	 * @param mixed $input Raw input value.
	 * @return string '1' if checked, '0' otherwise.
	 */
	public function sanitize_checkbox( $input ) {
		return ( '1' === $input ) ? '1' : '0';
	}

	/**
	 * Render the Trigger Button ID field.
	 *
	 * @return void
	 */
	public function render_trigger_button_field() {
		$value = get_option( 'wa_trigger_button_id', 'whatsapp-btn' );
		printf(
			'<input type="text" id="wa_trigger_button_id" name="wa_trigger_button_id" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( $value ),
			esc_attr__( 'e.g. whatsapp-btn', 'whatsapp-gateway' )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'الـ ID أو الكلاس الخاص بالزرار الذي يفتح بوب أب الواتساب. يدعم أكثر من ID مفصولة بفاصلة.', 'whatsapp-gateway' )
		);
	}

	/**
	 * Render the Call Destination Number field.
	 *
	 * @return void
	 */
	public function render_call_destination_number_field() {
		$value = get_option( 'wa_call_destination_number', '' );
		printf(
			'<input type="text" id="wa_call_destination_number" name="wa_call_destination_number" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( $value ),
			esc_attr__( 'e.g. +966114797777', 'whatsapp-gateway' )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'The phone number for the call gateway. Include the country code and + sign if preferred.', 'whatsapp-gateway' )
		);
	}

	/**
	 * Render the Call Trigger Button ID field.
	 *
	 * @return void
	 */
	public function render_call_trigger_button_field() {
		$value = get_option( 'wa_call_trigger_button_id', 'call-btn' );
		printf(
			'<input type="text" id="wa_call_trigger_button_id" name="wa_call_trigger_button_id" value="%s" class="regular-text" placeholder="%s" />',
			esc_attr( $value ),
			esc_attr__( 'e.g. call-btn', 'whatsapp-gateway' )
		);
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'الـ ID أو الكلاس الخاص بالزرار الذي يفتح بوب أب الاتصال. يدعم أكثر من ID مفصولة بفاصلة.', 'whatsapp-gateway' )
		);
	}
}
