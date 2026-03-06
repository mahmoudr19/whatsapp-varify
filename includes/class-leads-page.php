<?php
/**
 * Leads admin page class.
 *
 * Displays a dashboard for viewing and managing stored leads
 * in the WordPress admin area.
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
 * Class Leads_Page
 *
 * Admin page for viewing and managing leads.
 */
class Leads_Page {

	/**
	 * Page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'wa-gateway-leads';

	/**
	 * Register hooks for the admin leads page.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
	}

	/**
	 * Add the leads page to the admin menu.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'الأرقام المسجلة', 'whatsapp-gateway' ),
			__( 'أرقام واتساب', 'whatsapp-gateway' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' ),
			'dashicons-phone',
			30
		);
	}

	/**
	 * Enqueue admin styles for the leads page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_styles( $hook ) {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wa-gateway-admin-leads',
			WA_GATEWAY_PLUGIN_URL . 'assets/css/admin-leads.css',
			array(),
			WA_GATEWAY_VERSION
		);
	}

	/**
	 * Handle admin actions (delete, bulk delete, export CSV).
	 *
	 * @return void
	 */
	public function handle_actions() {
		// Single delete.
		if ( isset( $_GET['action'] ) && 'delete' === $_GET['action'] && isset( $_GET['lead_id'] ) ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wa_delete_lead_' . absint( $_GET['lead_id'] ) ) ) {
				wp_die( esc_html__( 'Security check failed.', 'whatsapp-gateway' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'whatsapp-gateway' ) );
			}

			Leads::delete_lead( absint( $_GET['lead_id'] ) );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&deleted=1' ) );
			exit;
		}

		// Bulk delete.
		if ( isset( $_POST['wa_bulk_action'] ) && 'delete' === $_POST['wa_bulk_action'] && ! empty( $_POST['lead_ids'] ) ) {
			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'wa_bulk_leads' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'whatsapp-gateway' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'whatsapp-gateway' ) );
			}

			$ids = array_map( 'absint', (array) $_POST['lead_ids'] );
			Leads::delete_leads( $ids );
			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&deleted=' . count( $ids ) ) );
			exit;
		}

		// CSV Export.
		if ( isset( $_GET['wa_export'] ) && '1' === $_GET['wa_export'] && isset( $_GET['page'] ) && self::PAGE_SLUG === $_GET['page'] ) {
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'wa_export_leads' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'whatsapp-gateway' ) );
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'whatsapp-gateway' ) );
			}

			$this->export_csv();
		}
	}

	/**
	 * Export leads as CSV.
	 *
	 * @return void
	 */
	private function export_csv() {
		$leads = Leads::get_all_leads();

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=whatsapp-leads-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );

		// BOM for proper Arabic display in Excel.
		fprintf( $output, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		// Header row.
		fputcsv( $output, array( 'ID', 'التصنيف', 'رقم الجوال', 'الرقم الدولي', 'عنوان IP', 'رابط الصفحة', 'التاريخ' ) );

		foreach ( $leads as $lead ) {
			$type_label = ( 'call' === $lead->lead_type ) ? 'اتصال' : 'واتساب';
			fputcsv( $output, array(
				$lead->id,
				$type_label,
				$lead->phone_number,
				$lead->phone_international,
				$lead->ip_address,
				$lead->page_url,
				$lead->created_at,
			) );
		}

		fclose( $output );
		exit;
	}

	/**
	 * Render the leads admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$per_page    = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$total_leads = Leads::get_leads_count();
		$total_pages = ceil( $total_leads / $per_page );
		$leads       = Leads::get_leads( $per_page, $current_page );

		$export_url = wp_nonce_url(
			admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&wa_export=1' ),
			'wa_export_leads'
		);
		?>
		<div class="wrap wa-leads-wrap">
			<!-- Header -->
			<div class="wa-leads-header">
				<div class="wa-leads-header-info">
					<h1 class="wa-leads-title">
						<span class="dashicons dashicons-phone"></span>
						<?php esc_html_e( 'الأرقام المسجلة', 'whatsapp-gateway' ); ?>
					</h1>
					<p class="wa-leads-subtitle">
						<?php
						printf(
							/* translators: %s: total number of leads */
							esc_html__( 'إجمالي الأرقام: %s', 'whatsapp-gateway' ),
							'<strong>' . esc_html( number_format_i18n( $total_leads ) ) . '</strong>'
						);
						?>
					</p>
				</div>
				<div class="wa-leads-header-actions">
					<?php if ( $total_leads > 0 ) : ?>
						<a href="<?php echo esc_url( $export_url ); ?>" class="wa-leads-btn wa-leads-btn-export">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'تصدير CSV', 'whatsapp-gateway' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( isset( $_GET['deleted'] ) ) : ?>
				<div class="wa-leads-notice wa-leads-notice-success">
					<?php
					$count = absint( $_GET['deleted'] );
					printf(
						/* translators: %d: number of deleted leads */
						esc_html( _n( 'تم حذف %d رقم بنجاح.', 'تم حذف %d أرقام بنجاح.', $count, 'whatsapp-gateway' ) ),
						$count
					);
					?>
				</div>
			<?php endif; ?>

			<?php if ( $total_leads === 0 ) : ?>
				<!-- Empty State -->
				<div class="wa-leads-empty">
					<div class="wa-leads-empty-icon">
						<span class="dashicons dashicons-phone"></span>
					</div>
					<h2><?php esc_html_e( 'لا توجد أرقام مسجلة بعد', 'whatsapp-gateway' ); ?></h2>
					<p><?php esc_html_e( 'ستظهر هنا الأرقام التي يتم إدخالها من خلال نموذج التحقق.', 'whatsapp-gateway' ); ?></p>
				</div>
			<?php else : ?>
				<!-- Leads Table -->
				<form method="post" action="">
					<?php wp_nonce_field( 'wa_bulk_leads' ); ?>

					<div class="wa-leads-card">
						<div class="wa-leads-table-actions">
							<label>
								<input type="checkbox" id="wa-select-all" />
								<?php esc_html_e( 'تحديد الكل', 'whatsapp-gateway' ); ?>
							</label>
							<button type="submit" name="wa_bulk_action" value="delete" class="wa-leads-btn wa-leads-btn-delete" onclick="return confirm('<?php esc_attr_e( 'هل أنت متأكد من حذف الأرقام المحددة؟', 'whatsapp-gateway' ); ?>');">
								<span class="dashicons dashicons-trash"></span>
								<?php esc_html_e( 'حذف المحدد', 'whatsapp-gateway' ); ?>
							</button>
						</div>

						<table class="wa-leads-table">
							<thead>
								<tr>
									<th class="wa-leads-col-check"><input type="checkbox" id="wa-select-all-top" /></th>
									<th><?php esc_html_e( '#', 'whatsapp-gateway' ); ?></th>
									<th><?php esc_html_e( 'التصنيف', 'whatsapp-gateway' ); ?></th>
									<th><?php esc_html_e( 'رقم الجوال', 'whatsapp-gateway' ); ?></th>
									<th><?php esc_html_e( 'الرقم الدولي', 'whatsapp-gateway' ); ?></th>
									<th><?php esc_html_e( 'عنوان IP', 'whatsapp-gateway' ); ?></th>
									<th><?php esc_html_e( 'الصفحة', 'whatsapp-gateway' ); ?></th>
									<th><?php esc_html_e( 'التاريخ', 'whatsapp-gateway' ); ?></th>
									<th><?php esc_html_e( 'إجراءات', 'whatsapp-gateway' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $leads as $index => $lead ) : ?>
									<tr>
										<td class="wa-leads-col-check">
											<input type="checkbox" name="lead_ids[]" value="<?php echo absint( $lead->id ); ?>" class="wa-lead-checkbox" />
										</td>
										<td class="wa-leads-col-id"><?php echo absint( $lead->id ); ?></td>
										<td class="wa-leads-col-type">
											<?php if ( 'call' === $lead->lead_type ) : ?>
												<span class="wa-leads-badge wa-leads-badge-call"><?php esc_html_e( 'اتصال', 'whatsapp-gateway' ); ?></span>
											<?php else : ?>
												<span class="wa-leads-badge wa-leads-badge-whatsapp"><?php esc_html_e( 'واتساب', 'whatsapp-gateway' ); ?></span>
											<?php endif; ?>
										</td>
										<td class="wa-leads-col-phone">
											<strong dir="ltr"><?php echo esc_html( $lead->phone_number ); ?></strong>
										</td>
										<td class="wa-leads-col-international" dir="ltr">
											<?php echo esc_html( $lead->phone_international ); ?>
										</td>
										<td class="wa-leads-col-ip" dir="ltr">
											<?php echo esc_html( $lead->ip_address ); ?>
										</td>
										<td class="wa-leads-col-page">
											<?php if ( ! empty( $lead->page_url ) ) : ?>
												<a href="<?php echo esc_url( $lead->page_url ); ?>" target="_blank" title="<?php echo esc_attr( $lead->page_url ); ?>">
													<?php echo esc_html( wp_parse_url( $lead->page_url, PHP_URL_PATH ) ?: '/' ); ?>
												</a>
											<?php else : ?>
												—
											<?php endif; ?>
										</td>
										<td class="wa-leads-col-date">
											<?php echo esc_html( wp_date( 'Y/m/d - h:i A', strtotime( $lead->created_at ) ) ); ?>
										</td>
										<td class="wa-leads-col-actions">
											<a href="https://wa.me/<?php echo esc_attr( $lead->phone_international ); ?>" target="_blank" class="wa-leads-action-wa" title="<?php esc_attr_e( 'تواصل عبر واتساب', 'whatsapp-gateway' ); ?>">
												<span class="dashicons dashicons-format-chat"></span>
											</a>
											<?php
											$delete_url = wp_nonce_url(
												admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=delete&lead_id=' . absint( $lead->id ) ),
												'wa_delete_lead_' . absint( $lead->id )
											);
											?>
											<a href="<?php echo esc_url( $delete_url ); ?>" class="wa-leads-action-delete" title="<?php esc_attr_e( 'حذف', 'whatsapp-gateway' ); ?>" onclick="return confirm('<?php esc_attr_e( 'هل أنت متأكد من حذف هذا الرقم؟', 'whatsapp-gateway' ); ?>');">
												<span class="dashicons dashicons-trash"></span>
											</a>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>

						<?php if ( $total_pages > 1 ) : ?>
							<div class="wa-leads-pagination">
								<?php
								$base_url = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
								for ( $i = 1; $i <= $total_pages; $i++ ) :
									$active = ( $i === $current_page ) ? ' wa-leads-page-active' : '';
									?>
									<a href="<?php echo esc_url( add_query_arg( 'paged', $i, $base_url ) ); ?>" class="wa-leads-page-link<?php echo esc_attr( $active ); ?>"><?php echo absint( $i ); ?></a>
								<?php endfor; ?>
							</div>
						<?php endif; ?>
					</div>
				</form>
			<?php endif; ?>
		</div>

		<script>
		(function() {
			// Select all checkboxes.
			var selectAllTop = document.getElementById('wa-select-all-top');
			var selectAll    = document.getElementById('wa-select-all');
			var checkboxes   = document.querySelectorAll('.wa-lead-checkbox');

			function toggleAll(checked) {
				checkboxes.forEach(function(cb) { cb.checked = checked; });
				if (selectAllTop) selectAllTop.checked = checked;
				if (selectAll) selectAll.checked = checked;
			}

			if (selectAllTop) selectAllTop.addEventListener('change', function() { toggleAll(this.checked); });
			if (selectAll) selectAll.addEventListener('change', function() { toggleAll(this.checked); });
		})();
		</script>
		<?php
	}
}
