<?php

namespace Barn2\Plugin\WC_Product_Table;

use Barn2\Plugin\WC_Product_Table\Admin\Admin_Controller;
use Barn2\Plugin\WC_Product_Table\Compat\Theme_Compat;
use Barn2\Plugin\WC_Product_Table\Util\Settings;
use Barn2\WPT_Lib\Plugin\Licensed_Plugin;
use Barn2\WPT_Lib\Plugin\Premium_Plugin;
use Barn2\WPT_Lib\Registerable;
use Barn2\WPT_Lib\Service_Container;
use Barn2\WPT_Lib\Service_Provider;
use Barn2\WPT_Lib\Translatable;
use Barn2\WPT_Lib\Util;

/**
 * The main plugin class. Responsible for setting up to core plugin services.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Plugin extends Premium_Plugin implements Licensed_Plugin, Registerable, Translatable, Service_Provider {

	const NAME    = 'WooCommerce Product Table';
	const ITEM_ID = 12913;

	use Service_Container;

	public function __construct( $file, $version = '1.0' ) {
		parent::__construct( [
			'name'               => self::NAME,
			'item_id'            => self::ITEM_ID,
			'version'            => $version,
			'file'               => $file,
			'is_woocommerce'     => true,
			'settings_path'      => 'admin.php?page=wc-settings&tab=products&section=' . Settings::SECTION_SLUG,
			'documentation_path' => 'kb-categories/woocommerce-product-table-kb',
			'legacy_db_prefix'   => 'wcpt'
		] );
	}

	public function register() {
		parent::register();
		add_action( 'plugins_loaded', [ $this, 'maybe_load_plugin' ] );
	}

	public function maybe_load_plugin() {
		// Bail if WooCommerce not installed & active.
		if ( ! Util::is_woocommerce_active() ) {
			return;
		}

		add_action( 'init', [ $this, 'load_textdomain' ], 5 );
		add_action( 'init', [ $this, 'register_services' ] );
		add_action( 'init', [ $this, 'load_template_functions' ] );
		add_action( 'widgets_init', [ $this, 'register_widgets' ] );
	}

	public function get_services() {
		$services          = [];
		$services['admin'] = new Admin_Controller( $this );

		if ( $this->get_license()->is_valid() ) {
			$services['shortcode']        = new Table_Shortcode();
			$services['scripts']          = new Frontend_Scripts( $this->get_version() );
			$services['cart_handler']     = new Cart_Handler();
			$services['ajax_handler']     = new Ajax_Handler();
			$services['template_handler'] = new Template_Handler();
			$services['theme_compat']     = new Theme_Compat();
		}

		return $services;
	}

	public function load_template_functions() {
		require_once $this->get_dir_path() . 'includes/template-functions.php';
		include_once $this->get_dir_path() . 'includes/Compat/woocommerce-compat.php';
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'woocommerce-product-table', false, $this->get_slug() . '/languages' );
	}

	public function register_widgets() {
		if ( ! $this->get_license()->is_valid() ) {
			return;
		}

		$widget_classes = [
			'WC_Product_Table_Widget_Layered_Nav_Filters',
			'WC_Product_Table_Widget_Layered_Nav',
			'WC_Product_Table_Widget_Price_Filter',
			'WC_Product_Table_Widget_Rating_Filter'
		];

		// Register the product table widgets
		array_map( 'register_widget', array_filter( $widget_classes, 'class_exists' ) );
	}

}
