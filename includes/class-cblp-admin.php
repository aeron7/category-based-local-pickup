<?php
/**
 * Admin settings page: map each pickup location to allowed product categories.
 *
 * @package CategoryBasedLocalPickup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and saves the plugin's settings screen under the WooCommerce menu.
 */
class CBLP_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var CBLP_Admin|null
	 */
	private static $instance = null;

	const NONCE = 'cblp_save_settings';

	/**
	 * Get the singleton.
	 *
	 * @return CBLP_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register admin hooks.
	 */
	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Add the submenu page under WooCommerce.
	 */
	public function add_menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Category Based Local Pickup', 'category-based-local-pickup-for-woocommerce' ),
			__( 'Category Pickup', 'category-based-local-pickup-for-woocommerce' ),
			'manage_woocommerce',
			'cblp-settings',
			array( $this, 'render' )
		);
	}

	/**
	 * Load WooCommerce's enhanced select on our screen only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue( $hook ) {
		if ( 'woocommerce_page_cblp-settings' !== $hook ) {
			return;
		}
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );
		wp_add_inline_script(
			'selectWoo',
			'jQuery(function($){$(".cblp-cats").selectWoo({width:"100%"});});'
		);
	}

	/**
	 * Persist the submitted rules.
	 */
	public function maybe_save() {
		if ( ! isset( $_POST['cblp_submit'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'category-based-local-pickup-for-woocommerce' ) );
		}
		check_admin_referer( self::NONCE );

		$rules    = array();
		$submitted = isset( $_POST['cblp'] ) && is_array( $_POST['cblp'] ) ? wp_unslash( $_POST['cblp'] ) : array();

		foreach ( $submitted as $index => $row ) {
			$index      = (int) $index;
			$categories = isset( $row['categories'] ) ? array_values( array_unique( array_map( 'intval', (array) $row['categories'] ) ) ) : array();
			$mode       = ( isset( $row['mode'] ) && 'any' === $row['mode'] ) ? 'any' : 'all';

			if ( empty( $categories ) ) {
				continue; // No categories chosen = no restriction for this location.
			}
			$rules[ $index ] = array(
				'categories' => $categories,
				'mode'       => $mode,
			);
		}

		update_option( CBLP_OPTION_RULES, $rules );
		update_option( CBLP_OPTION_DEBUG, isset( $_POST['cblp_debug'] ) ? 'yes' : 'no' );

		add_settings_error( 'cblp', 'cblp_saved', __( 'Pickup category rules saved.', 'category-based-local-pickup-for-woocommerce' ), 'updated' );
		set_transient( 'cblp_saved_notice', 1, 30 );
	}

	/**
	 * Output the settings screen.
	 */
	public function render() {
		$locations = CBLP_Locations::all();
		$rules     = get_option( CBLP_OPTION_RULES, array() );
		$debug     = 'yes' === get_option( CBLP_OPTION_DEBUG, 'no' );
		$cats      = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Category Based Local Pickup', 'category-based-local-pickup-for-woocommerce' ); ?></h1>

			<?php if ( get_transient( 'cblp_saved_notice' ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pickup category rules saved.', 'category-based-local-pickup-for-woocommerce' ); ?></p></div>
				<?php delete_transient( 'cblp_saved_notice' ); ?>
			<?php endif; ?>

			<?php if ( ! CBLP_Locations::is_pickup_enabled() ) : ?>
				<div class="notice notice-warning"><p>
					<?php
					printf(
						/* translators: %s: link to WooCommerce shipping settings */
						wp_kses_post( __( 'WooCommerce <strong>Local Pickup</strong> is currently disabled. Enable it under <a href="%s">WooCommerce → Settings → Shipping → Local pickup</a> and add at least one location.', 'category-based-local-pickup-for-woocommerce' ) ),
						esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=pickup_location' ) )
					);
					?>
				</p></div>
			<?php endif; ?>

			<p class="description">
				<?php esc_html_e( 'For each pickup location, choose which product categories it can fulfil. A customer is offered a pickup location only when their cart items match its rule. Leave a location blank to always show it.', 'category-based-local-pickup-for-woocommerce' ); ?>
			</p>

			<form method="post">
				<?php wp_nonce_field( self::NONCE ); ?>

				<?php if ( empty( $locations ) ) : ?>
					<p><em><?php esc_html_e( 'No Local Pickup locations found yet. Add them in WooCommerce → Settings → Shipping → Local pickup first, then reload this page.', 'category-based-local-pickup-for-woocommerce' ); ?></em></p>
				<?php else : ?>
					<table class="widefat striped" style="max-width:900px;margin-top:12px;">
						<thead>
							<tr>
								<th style="width:26%"><?php esc_html_e( 'Pickup location', 'category-based-local-pickup-for-woocommerce' ); ?></th>
								<th><?php esc_html_e( 'Allowed product categories', 'category-based-local-pickup-for-woocommerce' ); ?></th>
								<th style="width:22%"><?php esc_html_e( 'Cart must match', 'category-based-local-pickup-for-woocommerce' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( $locations as $index => $loc ) : ?>
							<?php
							$selected = isset( $rules[ $index ]['categories'] ) ? array_map( 'intval', (array) $rules[ $index ]['categories'] ) : array();
							$mode     = isset( $rules[ $index ]['mode'] ) ? $rules[ $index ]['mode'] : 'all';
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $loc['name'] ); ?></strong>
									<?php if ( ! $loc['enabled'] ) : ?>
										<br><span style="color:#a00;">(<?php esc_html_e( 'disabled in WooCommerce', 'category-based-local-pickup-for-woocommerce' ); ?>)</span>
									<?php endif; ?>
								</td>
								<td>
									<select name="cblp[<?php echo esc_attr( $index ); ?>][categories][]" class="cblp-cats" multiple="multiple" data-placeholder="<?php esc_attr_e( 'All categories (no restriction)', 'category-based-local-pickup-for-woocommerce' ); ?>" style="min-width:320px;">
										<?php
										if ( ! is_wp_error( $cats ) ) {
											foreach ( $cats as $cat ) {
												printf(
													'<option value="%d" %s>%s (%d)</option>',
													(int) $cat->term_id,
													selected( in_array( (int) $cat->term_id, $selected, true ), true, false ),
													esc_html( $cat->name ),
													(int) $cat->count
												);
											}
										}
										?>
									</select>
								</td>
								<td>
									<label><input type="radio" name="cblp[<?php echo esc_attr( $index ); ?>][mode]" value="all" <?php checked( 'any' !== $mode ); ?>> <?php esc_html_e( 'ALL items match', 'category-based-local-pickup-for-woocommerce' ); ?></label><br>
									<label><input type="radio" name="cblp[<?php echo esc_attr( $index ); ?>][mode]" value="any" <?php checked( 'any' === $mode ); ?>> <?php esc_html_e( 'ANY item matches', 'category-based-local-pickup-for-woocommerce' ); ?></label>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<p style="margin-top:16px;">
					<label>
						<input type="checkbox" name="cblp_debug" value="1" <?php checked( $debug ); ?>>
						<?php esc_html_e( 'Enable debug logging', 'category-based-local-pickup-for-woocommerce' ); ?>
					</label>
					<span class="description"><?php esc_html_e( '(logs each decision to WooCommerce → Status → Logs, source “category-based-local-pickup”)', 'category-based-local-pickup-for-woocommerce' ); ?></span>
				</p>

				<p class="submit">
					<button type="submit" name="cblp_submit" value="1" class="button button-primary"><?php esc_html_e( 'Save changes', 'category-based-local-pickup-for-woocommerce' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}
}
