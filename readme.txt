=== Category Based Local Pickup for WooCommerce ===
Contributors: dexteraeron
Tags: woocommerce, local pickup, shipping, product category, checkout
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Show or hide each WooCommerce Local Pickup location based on the product categories in the cart. A free, open-source plugin by aeron7.com.

== Description ==

A free, open-source plugin by [aeron7.com](https://aeron7.com).

WooCommerce's built-in **Local Pickup** shows every pickup location to every shopper. But real stores often have pickup points that only stock certain categories — one warehouse per brand, publisher, supplier or department.

**Category Based Local Pickup** lets you map each Local Pickup location to a set of product categories. A pickup location is then only offered when the cart's items qualify:

* **ALL items match** (default) — every product in the cart must belong to one of the location's allowed categories. Mixed carts that include a non-matching product simply won't see that pickup point.
* **ANY item matches** — the location shows if at least one cart item qualifies.

Leave a location's categories blank to always show it (no restriction).

Works with both the **block-based Checkout** and the classic shortcode checkout, because it filters WooCommerce shipping rates directly. Category matching is hierarchical — assigning a parent category covers its child categories too.

= Features =

* Per-location allowed-category rules, configured from a simple admin screen.
* "ALL items" or "ANY item" matching mode per location.
* Hierarchical categories (parent covers children).
* Optional debug logging to WooCommerce → Status → Logs.
* No settings bloat, no tracking, HPOS-compatible.

= Example =

A bookshop has two pickup points: a main warehouse that stocks every imprint, and a small store that only stocks one imprint. Assign every imprint category to the warehouse and just the one category to the store. Customers see the small store only when their whole cart is that imprint.

== Installation ==

1. Ensure WooCommerce is installed and active.
2. Enable **Local Pickup** under WooCommerce → Settings → Shipping → Local pickup, and add your pickup locations.
3. Install and activate this plugin.
4. Go to **WooCommerce → Category Pickup** and choose the allowed product categories for each pickup location.

== Frequently Asked Questions ==

= Does it work with the WooCommerce Checkout block? =
Yes. It filters shipping rates server-side, so both the Checkout block and the classic checkout are covered.

= What happens to a mixed cart? =
In the default "ALL items match" mode, a pickup location is hidden if any cart item is outside its allowed categories — so mixed carts fall back to shipping.

= Does it create its own pickup locations? =
No. It uses WooCommerce's native Local Pickup locations; it only decides when each one is shown.

== Changelog ==

= 1.0.0 =
* Initial release.
