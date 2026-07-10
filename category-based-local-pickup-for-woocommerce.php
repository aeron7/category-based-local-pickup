<?php
/**
 * Plugin Name:       Category Based Local Pickup for WooCommerce
 * Plugin URI:        https://github.com/aeron7/category-based-local-pickup-for-woocommerce
 * Description:       Show or hide each WooCommerce Local Pickup location based on the product categories in the cart. Perfect when different pickup points only stock certain categories (brands, publishers, suppliers). A cart is offered a pickup point only when its items qualify.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            aeron7
 * Author URI:        https://aeron7.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       category-based-local-pickup-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   10.7
 *
 * @package CategoryBasedLocalPickup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CBLP_VERSION', '1.0.0' );
define( 'CBLP_FILE', __FILE__ );
define( 'CBLP_PATH', plugin_dir_path( __FILE__ ) );
define( 'CBLP_URL', plugin_dir_url( __FILE__ ) );
define( 'CBLP_OPTION_RULES', 'cblp_location_rules' );
define( 'CBLP_OPTION_DEBUG', 'cblp_debug' );

require_once CBLP_PATH . 'includes/class-cblp-locations.php';
require_once CBLP_PATH . 'includes/class-cblp-gating.php';
require_once CBLP_PATH . 'includes/class-cblp-admin.php';

/**
 * Boot the plugin once all plugins are loaded (so WooCommerce is available).
 */
function cblp_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'cblp_missing_wc_notice' );
		return;
	}

	load_plugin_textdomain(
		'category-based-local-pickup-for-woocommerce',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	CBLP_Gating::instance();

	if ( is_admin() ) {
		CBLP_Admin::instance();
	}
}
add_action( 'plugins_loaded', 'cblp_init' );

/**
 * Admin notice shown when WooCommerce is not active.
 */
function cblp_missing_wc_notice() {
	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Category Based Local Pickup for WooCommerce requires WooCommerce to be installed and active.', 'category-based-local-pickup-for-woocommerce' );
	echo '</p></div>';
}

/**
 * Declare High-Performance Order Storage (HPOS) compatibility.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/**
 * Add a Settings link on the plugins list row.
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	$url = admin_url( 'admin.php?page=cblp-settings' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'category-based-local-pickup-for-woocommerce' ) . '</a>' );
	return $links;
} );
