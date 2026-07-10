<?php
/**
 * Reads WooCommerce's native Local Pickup locations.
 *
 * @package CategoryBasedLocalPickup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper for the pickup locations WooCommerce core stores for the
 * block-based "Local pickup" feature (option: pickup_location_pickup_locations).
 */
class CBLP_Locations {

	/**
	 * Return the configured pickup locations, keyed by their WooCommerce index.
	 *
	 * The index is what core uses to build the shipping rate id
	 * ("pickup_location:<index>"), so we key our rules by the same value.
	 *
	 * @return array<int,array{name:string,enabled:bool}>
	 */
	public static function all() {
		$raw = get_option( 'pickup_location_pickup_locations', array() );
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$out = array();
		foreach ( $raw as $index => $loc ) {
			if ( ! is_array( $loc ) ) {
				continue;
			}
			$out[ (int) $index ] = array(
				'name'    => isset( $loc['name'] ) ? (string) $loc['name'] : sprintf( 'Location #%d', (int) $index ),
				'enabled' => ! empty( $loc['enabled'] ),
			);
		}

		return $out;
	}

	/**
	 * Whether WooCommerce's block Local Pickup feature is switched on.
	 *
	 * @return bool
	 */
	public static function is_pickup_enabled() {
		$settings = get_option( 'woocommerce_pickup_location_settings', array() );
		return is_array( $settings ) && isset( $settings['enabled'] ) && 'yes' === $settings['enabled'];
	}
}
