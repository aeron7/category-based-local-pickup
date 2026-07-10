<?php
/**
 * Front-end gating: hides pickup rates whose category rule the cart fails.
 *
 * @package CategoryBasedLocalPickup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filters WooCommerce shipping rates so a Local Pickup location only appears
 * when the cart's product categories satisfy that location's rule.
 */
class CBLP_Gating {

	/**
	 * Singleton instance.
	 *
	 * @var CBLP_Gating|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton.
	 *
	 * @return CBLP_Gating
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Hook up the rate filter.
	 */
	private function __construct() {
		add_filter( 'woocommerce_package_rates', array( $this, 'filter_rates' ), 20, 2 );
	}

	/**
	 * Remove pickup rates that the current package (cart) is not eligible for.
	 *
	 * @param array $rates   Shipping rates keyed by rate id.
	 * @param array $package The shipping package being rated.
	 * @return array
	 */
	public function filter_rates( $rates, $package ) {
		$rules = get_option( CBLP_OPTION_RULES, array() );
		if ( empty( $rules ) || ! is_array( $rules ) ) {
			return $rates;
		}

		$item_terms = $this->collect_item_category_terms( $package );

		$this->log(
			'Evaluating pickup rules',
			array(
				'cart_item_categories' => $item_terms,
				'pickup_rates_present' => array_values(
					array_filter(
						array_keys( $rates ),
						function ( $k ) use ( $rates ) {
							return $this->is_pickup_rate( $rates[ $k ] );
						}
					)
				),
			)
		);

		foreach ( $rates as $rate_key => $rate ) {
			if ( ! $this->is_pickup_rate( $rate ) ) {
				continue;
			}

			$index = $this->rate_location_index( $rate );
			if ( ! isset( $rules[ $index ] ) ) {
				continue; // No rule for this location: leave it visible.
			}

			$allowed = isset( $rules[ $index ]['categories'] ) ? array_map( 'intval', (array) $rules[ $index ]['categories'] ) : array();
			if ( empty( $allowed ) ) {
				continue; // Rule with no categories: no restriction.
			}

			$mode      = ( isset( $rules[ $index ]['mode'] ) && 'any' === $rules[ $index ]['mode'] ) ? 'any' : 'all';
			$qualifies = $this->cart_qualifies( $item_terms, $allowed, $mode );

			$this->log(
				$qualifies ? 'KEEP pickup location' : 'HIDE pickup location',
				array(
					'location_index' => $index,
					'location_label' => $rate->get_label(),
					'allowed_terms'  => $allowed,
					'mode'           => $mode,
					'qualifies'      => $qualifies,
				)
			);

			if ( ! $qualifies ) {
				unset( $rates[ $rate_key ] );
			}
		}

		return $rates;
	}

	/**
	 * Is this a Local Pickup rate (block "pickup_location" or classic "local_pickup")?
	 *
	 * @param WC_Shipping_Rate $rate Rate object.
	 * @return bool
	 */
	private function is_pickup_rate( $rate ) {
		$method = $rate->get_method_id();
		return 'pickup_location' === $method || 'local_pickup' === $method;
	}

	/**
	 * Extract the location index from a rate id like "pickup_location:2".
	 *
	 * @param WC_Shipping_Rate $rate Rate object.
	 * @return int
	 */
	private function rate_location_index( $rate ) {
		$parts = explode( ':', $rate->get_id() );
		return isset( $parts[1] ) && is_numeric( $parts[1] ) ? (int) $parts[1] : -1;
	}

	/**
	 * Category term ids (incl. ancestors) for every cart line, one array per item.
	 *
	 * @param array $package Shipping package.
	 * @return array<int,array<int>>
	 */
	private function collect_item_category_terms( $package ) {
		$items = array();
		if ( empty( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
			return $items;
		}
		foreach ( $package['contents'] as $item ) {
			$product_id = ! empty( $item['product_id'] ) ? (int) $item['product_id'] : 0;
			$terms      = $product_id ? wc_get_product_cat_ids( $product_id ) : array();
			$items[]    = array_map( 'intval', (array) $terms );
		}
		return $items;
	}

	/**
	 * Does the cart satisfy the rule?
	 *
	 * - "all": every cart item shares at least one category with $allowed.
	 * - "any": at least one cart item shares a category with $allowed.
	 *
	 * @param array<int,array<int>> $item_terms Per-item category term ids.
	 * @param array<int>            $allowed    Allowed term ids for the location.
	 * @param string                $mode       'all' or 'any'.
	 * @return bool
	 */
	private function cart_qualifies( $item_terms, $allowed, $mode ) {
		if ( empty( $item_terms ) ) {
			return false;
		}

		if ( 'any' === $mode ) {
			foreach ( $item_terms as $terms ) {
				if ( array_intersect( $terms, $allowed ) ) {
					return true;
				}
			}
			return false;
		}

		// Default "all": a single non-matching item disqualifies the whole cart.
		foreach ( $item_terms as $terms ) {
			if ( ! array_intersect( $terms, $allowed ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Write a debug line to the WooCommerce logger when debug logging is on.
	 * View under WooCommerce → Status → Logs (source: category-based-local-pickup).
	 *
	 * @param string $message Message.
	 * @param array  $context Structured context.
	 */
	private function log( $message, $context = array() ) {
		if ( 'yes' !== get_option( CBLP_OPTION_DEBUG, 'no' ) || ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		wc_get_logger()->info(
			$message . ' ' . wp_json_encode( $context ),
			array( 'source' => 'category-based-local-pickup' )
		);
	}
}
