<?php

namespace Barn2\Plugin\WC_Product_Table\Compat;

use Barn2\Plugin\WC_Product_Table\Frontend_Scripts;
use Barn2\Plugin\WC_Product_Table\Table_Args;
use Barn2\Plugin\WC_Product_Table\Util\Util;
use Barn2\WPT_Lib\Conditional;
use Barn2\WPT_Lib\Registerable;
use Barn2\WPT_Lib\Service;
use Barn2\WPT_Lib\Util as Lib_Util;
use function Barn2\Plugin\WC_Product_Table\wpt;

/**
 * Provides functions for compatibility and integration with different themes.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Theme_Compat implements Service, Registerable, Conditional {

	private $theme;

	public function __construct() {
		$this->theme = strtolower( get_template() );
	}

	public function is_required() {
		return Lib_Util::is_front_end();
	}

	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_dequeued_scripts' ], 1000 );

		if ( in_array( $this->theme, [ 'avada', 'enfold', 'flatsome', 'jupiter' ], true ) ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'add_theme_inline_script' ], 50 );
		}

		switch ( $this->theme ) {
			case 'kallyas':
				add_filter( 'add_to_cart_fragments', [ $this, 'kallyas_ensure_valid_add_to_cart_fragments' ], 20 );
				break;
			case 'uncode':
				add_filter( 'add_to_cart_class', [ $this, 'uncode_child_add_to_cart_class' ] );
				break;
			case 'x':
				add_action( 'wp_enqueue_scripts', [ $this, 'x_remove_legacy_mediaelement_styles' ] );
				break;
			case 'woodmart':
				add_action( 'wc_product_table_load_table_scripts', [ $this, 'woodmart_load_quantity_script' ] );
				break;
		}

	}

	public function add_theme_inline_script() {
		$inline_script_file = realpath( wpt()->get_dir_path() . "assets/js/compat/theme/{$this->theme}.js" );

		if ( $inline_script_file ) {
			$inline_script_contents = file_get_contents( $inline_script_file );
			wp_add_inline_script( Frontend_Scripts::SCRIPT_HANDLE, $inline_script_contents );
		}
	}

	public function register_dequeued_scripts() {
		// Some themes take it upon themselves to remove core WC scripts which we rely on, so let's re-register them just in case.
		wp_register_style( 'select2', Util::get_wc_asset_url( 'css/select2.css' ), [], ( defined( 'WC_VERSION' ) ? WC_VERSION : '1.0' ) );
		wp_register_script( 'jquery-blockui', Util::get_wc_asset_url( 'js/jquery-blockui/jquery.blockUI.min.js' ), [ 'jquery' ], '2.70', true );
		wp_register_script( 'selectWoo', Util::get_wc_asset_url( 'js/selectWoo/selectWoo.full.min.js' ), [ 'jquery' ], '1.0.6', true );
	}

	public function kallyas_ensure_valid_add_to_cart_fragments( $fragments ) {
		if ( ! isset( $fragments['zn_added_to_cart'] ) ) {
			$fragments['zn_added_to_cart'] = '';
		}

		return $fragments;
	}

	public function uncode_child_add_to_cart_class( $class ) {
		return $class . ' single_add_to_cart_button';
	}

	public function woodmart_load_quantity_script( Table_Args $args ) {
		if ( $args->quantities && function_exists( 'woodmart_enqueue_js_script' ) ) {
			woodmart_enqueue_js_script( 'woocommerce-quantity' );
		}
	}

	public function x_remove_legacy_mediaelement_styles() {
		wp_dequeue_style( 'wp-mediaelement' );
		wp_deregister_style( 'wp-mediaelement' );
		wp_register_style( 'wp-mediaelement', '/wp-includes/js/mediaelement/wp-mediaelement.min.css' );
	}

}
