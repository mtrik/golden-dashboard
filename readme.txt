=== Golden Dashboard ===
Contributors: mtrik
Tags: elementor, wallet, ecommerce, woocommerce, gold
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Elementor widgets for a front-end user wallet dashboard, with cash and gold wallets, WooCommerce top-up, withdrawals, and cashback.

== Description ==

Golden Dashboard extends Elementor with a set of widgets for building a front-end user wallet dashboard. It provides two wallet types: a standard cash wallet and a gold-denominated wallet.

**Features**

* Cash wallet: balance, top-up (via WooCommerce checkout), and withdrawal request widgets
* Gold wallet: gold-denominated balance and purchase widgets
* Wallet transaction history with filtering, pagination, and CSV export
* Cashback rules engine
* Admin panel: transaction management, manual credit/debit, fee reports, withdrawal request review
* Security monitoring: rate limiting, IP blocking, suspicious-activity logging

**Requirements**

* [Elementor](https://wordpress.org/plugins/elementor/) must be installed and active
* WooCommerce is required for the top-up and gold-purchase checkout flow

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/golden-dashboard` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Make sure Elementor (and WooCommerce, if you plan to use top-up/gold purchase) is installed and active.
4. Configure wallet, gold wallet, cashback, and security options under Golden Dashboard → Settings in the admin menu.
5. Add the plugin's widgets to any page from the Elementor editor, under the "گلدن داشبورد" widget category.

== Frequently Asked Questions ==

= Does this plugin require Elementor? =

Yes, all front-end widgets are built as Elementor widgets and require Elementor to be installed and active.

= Does this plugin require WooCommerce? =

WooCommerce is required for the wallet top-up and gold purchase checkout flow. The rest of the plugin (balance display, withdrawals, transaction history) does not require WooCommerce.

= Is this plugin free? =

Yes, Golden Dashboard is free and released under the GPLv2 (or later) license.

== Screenshots ==

1. Cash wallet widget on the front end
2. Gold wallet widget on the front end
3. Admin dashboard overview
4. Withdrawal request management in the admin panel

== Changelog ==

= 1.0.0 =
* Initial public release.

== Upgrade Notice ==

= 1.0.0 =
Initial public release.
