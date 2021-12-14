<?php

namespace Barn2\Plugin\WC_Product_Table\Util;

use Barn2\WPT_Lib\Util as Lib_Util;

/**
 * Utility functions for the product table settings.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Settings {

	/**
	 * Option names for our plugin settings (i.e. the option keys used in wp_options).
	 */
	const OPTION_TABLE_STYLING  = 'wcpt_table_styling';
	const OPTION_TABLE_DEFAULTS = 'wcpt_shortcode_defaults';
	const OPTION_MISC           = 'wcpt_misc_settings';

	/**
	 * The section name within the main WooCommerce Settings.
	 */
	const SECTION_SLUG = 'product-table';

	public static function get_setting_table_styling() {
		return self::get_setting( self::OPTION_TABLE_STYLING, [ 'use_theme' => 'theme' ] );
	}

	public static function get_setting_table_defaults() {
		return self::get_setting( self::OPTION_TABLE_DEFAULTS, [] );
	}

	public static function get_setting_misc() {
		$defaults = [
			'cache_expiry'         => 6,
			'add_selected_text'    => self::add_selected_to_cart_default_text(),
			'quick_view_links'     => false,
			'addons_layout'        => 'block',
			'addons_option_layout' => 'inline',
			'shop_override'        => false,
			'archive_override'     => false
		];

		// Back-compat: add_selected_text used to be stored in the table defaults.
		$table_defaults = self::get_setting_table_defaults();

		if ( ! empty( $table_defaults['add_selected_text'] ) ) {
			$defaults['add_selected_text'] = $table_defaults['add_selected_text'];
		}

		return self::get_setting( self::OPTION_MISC, $defaults );
	}

	public static function to_woocommerce_settings( $settings ) {
		if ( empty( $settings ) ) {
			return $settings;
		}

		foreach ( $settings as $key => $value ) {
			if ( is_bool( $value ) ) {
				$settings[ $key ] = $value ? 'yes' : 'no';
			}
		}

		return $settings;
	}

	public static function add_selected_to_cart_default_text() {
		return __( 'Add to cart', 'woocommerce-product-table' );
	}

	public static function open_links_in_quick_view_pro() {
		if ( Lib_Util::is_quick_view_pro_active() ) {
			$misc_settings = self::get_setting_misc();

			return ! empty( $misc_settings['quick_view_links'] );
		}

		return false;
	}

	private static function get_setting( $option_name, $default = [] ) {
		$option_value = get_option( $option_name, $default );

		if ( is_array( $option_value ) ) {
			// Merge with defaults.
			if ( is_array( $default ) ) {
				$option_value = wp_parse_args( $option_value, $default );
			}

			// Convert 'yes'/'no' options to booleans.
			$option_value = array_map( [ self::class, 'yes_no_to_boolean' ], $option_value );
		}

		return $option_value;
	}

	private static function yes_no_to_boolean( $val ) {
		if ( 'yes' === $val ) {
			return true;
		} elseif ( 'no' === $val ) {
			return false;
		}

		return $val;
	}

}
