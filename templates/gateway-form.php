<?php
/**
 * Gateway form template — Popup Modal.
 *
 * Renders the phone number validation form inside a modal overlay.
 * Variables available: $destination_number, $default_message, $nonce.
 *
 * @package WhatsApp_Gateway
 * @since   1.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<?php 
$uid = uniqid(); 
$is_inline_mode = isset( $is_inline ) && $is_inline;
?>

<?php if ( ! $is_inline_mode ) : ?>
<!-- WhatsApp Gateway Popup Overlay -->
<div class="wa-gateway-overlay" id="wa-gateway-overlay" role="dialog" aria-modal="true" aria-labelledby="wa-gateway-title">
	<div class="wa-gateway-backdrop" id="wa-gateway-backdrop"></div>
	<div class="wa-gateway-container" id="wa-gateway-container">
<?php else : ?>
<div class="wa-gateway-inline-container wa-gateway-container" style="max-width: 420px; margin: 0 auto; transform: none !important;">
<?php endif; ?>

		<div class="wa-gateway-card">
			<?php if ( ! $is_inline_mode ) : ?>
			<!-- Close Button -->
			<button type="button" class="wa-gateway-close" id="wa-gateway-close" aria-label="إغلاق">
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
			<?php endif; ?>

			<!-- Header -->
			<div class="wa-gateway-header">
				<div class="wa-gateway-icon">
					<!-- WhatsApp Icon -->
					<svg class="wa-icon-whatsapp" width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" fill="#ffffff"/>
						<path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a7.96 7.96 0 01-4.11-1.14l-.29-.174-3.01.79.8-2.93-.19-.3A7.963 7.963 0 014 12c0-4.411 3.589-8 8-8s8 3.589 8 8-3.589 8-8 8z" fill="#ffffff"/>
					</svg>
					<!-- Call Icon -->
					<svg class="wa-icon-call" style="display:none;" width="36" height="36" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
				<h2 class="wa-gateway-title" id="wa-gateway-title"><?php esc_html_e( 'المتابعة إلى واتساب', 'whatsapp-gateway' ); ?></h2>
				<p class="wa-gateway-subtitle"><?php esc_html_e( 'أدخل رقم جوالك للمتابعة', 'whatsapp-gateway' ); ?></p>
			</div>

			<!-- Form -->
			<form id="wa-gateway-form-<?php echo esc_attr( $uid ); ?>" class="wa-gateway-form" novalidate>
				<input type="hidden" name="wa_gateway_nonce" value="<?php echo esc_attr( $nonce ); ?>" />
				<input type="hidden" name="gateway_type" class="wa-gateway-type-input" value="whatsapp" />

				<div class="wa-gateway-input-group" id="wa-gateway-input-group-<?php echo esc_attr( $uid ); ?>">
					<label for="wa-phone-input-<?php echo esc_attr( $uid ); ?>" class="wa-gateway-label">
						<?php esc_html_e( 'رقم الجوال', 'whatsapp-gateway' ); ?>
					</label>
					<div class="wa-gateway-input-wrapper">
						<input
							type="tel"
							id="wa-phone-input-<?php echo esc_attr( $uid ); ?>"
							name="phone_number"
							class="wa-gateway-input"
							placeholder="05XXXXXXXX أدخل رقمك"
							maxlength="10"
							autocomplete="tel"
							inputmode="numeric"
							pattern="[0-9]*"
							aria-describedby="wa-gateway-error-<?php echo esc_attr( $uid ); ?>"
							required
						/>
						<span class="wa-gateway-prefix">
							<span class="wa-gateway-flag">🇸🇦</span>
							<span class="wa-gateway-country-code">+966</span>
						</span>
					</div>
					<div class="wa-gateway-error" id="wa-gateway-error-<?php echo esc_attr( $uid ); ?>" role="alert" aria-live="polite">
						<!-- Error messages injected by JS -->
					</div>
				</div>

				<button type="submit" class="wa-gateway-button wa-gateway-submit">
					<span class="wa-gateway-button-text"><?php esc_html_e( 'المتابعة إلى واتساب', 'whatsapp-gateway' ); ?></span>
					<span class="wa-gateway-button-icon">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
						</svg>
					</span>
				</button>
			</form>

			<!-- Footer -->
			<div class="wa-gateway-footer">
				<p class="wa-gateway-disclaimer">
				<?php
				printf(
					/* translators: %1$s: terms link, %2$s: privacy link */
					esc_html__( 'عند المتابعة، فإنك توافق على %1$s و %2$s', 'whatsapp-gateway' ),
					'<a href="#" class="wa-gateway-link">' . esc_html__( 'شروط الاستخدام', 'whatsapp-gateway' ) . '</a>',
					'<a href="#" class="wa-gateway-link">' . esc_html__( 'شروط الخصوصية', 'whatsapp-gateway' ) . '</a>'
				);
				?>
			</p>
			</div>
		</div>

<?php if ( ! $is_inline_mode ) : ?>
	</div>
</div>
<?php else : ?>
</div>
<?php endif; ?>
