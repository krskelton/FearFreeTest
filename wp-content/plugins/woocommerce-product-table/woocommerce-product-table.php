<?php
/**
 * The main plugin file for WooCommerce Product Table.
 *
 * This file is included during the WordPress bootstrap process if the plugin is active.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 *
 * @wordpress-plugin
 * Plugin Name:     WooCommerce Product Table
 * Plugin URI:      https://barn2.com/wordpress-plugins/woocommerce-product-table/
 * Description:     Display and purchase WooCommerce products from a searchable and sortable table. Filter by anything.
 * Version:         2.9.1
 * Author:          Barn2 Plugins
 * Author URI:      https://barn2.com
 * Text Domain:     woocommerce-product-table
 * Domain Path:     /languages
 *
 * WC requires at least: 3.7.0
 * WC tested up to: 5.8
 *
 * Copyright:       Barn2 Media Ltd
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Barn2\Plugin\WC_Product_Table;

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const PLUGIN_FILE    = __FILE__;
const PLUGIN_VERSION = '2.9.1';

// Include autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Helper function to access the shared plugin instance.
 *
 * @return Plugin The plugin instance.
 */
function wpt() {
	return Plugin_Factory::create( PLUGIN_FILE, PLUGIN_VERSION );
}

/**
 * Helper function to access the shared plugin instance.
 *
 * @return Plugin The plugin instance.
 * @deprecated 2.6.2 Replaced by Barn2\Plugin\WC_Product_Table->wpt().
 */
function wc_product_table() {
	_deprecated_function( __FUNCTION__, '2.6.2', 'Barn2\\Plugin\\WC_Product_Table->wpt()' );

	return wpt();
}

// Load the plugin.
wpt()->register();

