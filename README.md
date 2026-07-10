# Category Based Local Pickup for WooCommerce

A free, open-source plugin by **[aeron7.com](https://aeron7.com)**.

Show or hide each WooCommerce **Local Pickup** location based on the **product categories** in the cart.

WooCommerce's built-in Local Pickup shows every pickup location to every shopper. Real stores often have pickup points that only stock certain categories — one warehouse per brand, publisher, supplier or department. This plugin maps each pickup location to a set of product categories and only offers a location when the cart qualifies.

## How it works

For each native WooCommerce Local Pickup location you pick the allowed product categories and a match mode:

- **ALL items match** (default) — every product in the cart must belong to one of the location's allowed categories. A mixed cart with any non-matching product won't see that pickup point.
- **ANY item matches** — the location shows if at least one cart item qualifies.

Leave a location's categories empty to always show it. Category matching is **hierarchical** (a parent category covers its children). It filters WooCommerce shipping rates server-side, so it works with **both the Checkout block and the classic checkout**.

## Installation

1. Install and activate WooCommerce.
2. Enable **Local Pickup** under *WooCommerce → Settings → Shipping → Local pickup* and add your locations.
3. Copy this folder to `wp-content/plugins/` and activate **Category Based Local Pickup for WooCommerce**.
4. Go to **WooCommerce → Category Pickup** and set the allowed categories per location.

## Debug logging

Tick **Enable debug logging** on the settings screen to record each decision (cart categories, which locations were kept/hidden and why) to *WooCommerce → Status → Logs* under the source `category-based-local-pickup`.

## Requirements

- WordPress 6.2+
- WooCommerce 7.0+ (tested to 10.7)
- PHP 7.4+
- HPOS-compatible

## Development

Plain PHP, no build step.

```
category-based-local-pickup.php   Bootstrap, constants, HPOS declaration
includes/class-cblp-locations.php                 Reads native pickup locations
includes/class-cblp-gating.php                     woocommerce_package_rates filter + logging
includes/class-cblp-admin.php                      Settings screen (WooCommerce → Category Pickup)
```

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
