<?php
/**
 * Plugin Name:       Store Maintenance Checklist for WooCommerce
 * Plugin URI:        https://github.com/storecraftedhq/store-maintenance-checklist
 * Description:       Find and fix WooCommerce operational problems with a prioritized, evidence-backed maintenance checklist.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            StoreCrafted
 * Author URI:        https://storecrafted.com
 * Text Domain:       store-maintenance-checklist-for-woocommerce
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * WC requires at least: 10.0
 * WC tested up to: 10.0
 *
 * @package Store_Maintenance_Checklist
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'STMC_VERSION', '1.0.0' );
define( 'STMC_PLUGIN_FILE', __FILE__ );
define( 'STMC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STMC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'STMC_TEXT_DOMAIN', 'store-maintenance-checklist-for-woocommerce' );
define( 'STMC_URL_AUTO_ARCHIVE', 'https://storecrafted.com/product/auto-archive-old-orders-for-woocommerce/' );
define( 'STMC_URL_EXPECTED_DELIVERY', 'https://storecrafted.com/product/expected-delivery-times-for-woocommerce/' );
define( 'STMC_URL_WC_MEMORY_LIMIT_DOCS', 'https://woocommerce.com/document/increasing-the-wordpress-memory-limit/' );

require_once STMC_PLUGIN_DIR . 'includes/class-stmc-autoloader.php';
STMC_Autoloader::register();

register_activation_hook( __FILE__, array( 'STMC_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'STMC_Deactivator', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="error"><p>';
					esc_html_e( 'Store Maintenance Checklist for WooCommerce requires WooCommerce.', 'store-maintenance-checklist-for-woocommerce' );
					echo '</p></div>';
				},
				10,
				0
			);
			return;
		}

		( new STMC_Plugin() )->boot();
	},
	20,
	0
);

add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				STMC_PLUGIN_FILE,
				true
			);
		}
	}
);
