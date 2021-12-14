<?php

namespace Barn2\Plugin\WC_Product_Table\Admin;

use Barn2\Plugin\WC_Product_Table\Util\Util;
use Barn2\WPT_Lib\Conditional;
use Barn2\WPT_Lib\Plugin\Admin\Admin_Links;
use Barn2\WPT_Lib\Plugin\Licensed_Plugin;
use Barn2\WPT_Lib\Registerable;
use Barn2\WPT_Lib\Service;
use Barn2\WPT_Lib\Service_Container;
use Barn2\WPT_Lib\Util as Lib_Util;
use Barn2\WPT_Lib\WooCommerce\Admin\Navigation;

/**
 * Handles general admin functions, such as adding links to our settings page in the Plugins menu.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Admin_Controller implements Service, Registerable, Conditional {

	use Service_Container;

	private $plugin;

	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function is_required() {
		return Lib_Util::is_admin();
	}

	public function register() {
		$this->register_services();
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_scripts' ] );
	}

	public function get_services() {
		return [
			'admin_links'   => new Admin_Links( $this->plugin ),
			'settings_page' => new Settings_Page( $this->plugin ),
			'tiny_mce'      => new TinyMCE(),
			'navigation'    => new Navigation( $this->plugin, 'product-table', __( 'Product Table', 'woocommerce-product-table' ) )
		];
	}

	public function register_admin_scripts( $hook_suffix ) {
		if ( 'woocommerce_page_wc-settings' !== $hook_suffix ) {
			return;
		}

		$suffix = Lib_Util::get_script_suffix();

		wp_enqueue_style( 'wcpt-admin', Util::get_asset_url( 'css/admin/wc-product-table-admin.min.css' ), [], $this->plugin->get_version() );
		wp_enqueue_script( 'wcpt-admin', Util::get_asset_url( "js/admin/wc-product-table-admin{$suffix}.js" ), [ 'jquery' ], $this->plugin->get_version(), true );
	}

}
