<?php

namespace Barn2\Plugin\WC_Product_Table;

use Barn2\Plugin\WC_Product_Table\Util\Settings;
use Barn2\WPT_Lib\Conditional;
use Barn2\WPT_Lib\Registerable;
use Barn2\WPT_Lib\Service;
use Barn2\WPT_Lib\Util;

/**
 * This class handles adding the product table to the shop, archive, and product search pages.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Template_Handler implements Service, Registerable, Conditional {

	public function is_required() {
		return Util::is_front_end();
	}

	public function register() {
		add_action( 'template_redirect', [ __CLASS__, 'template_shop_override' ] );
	}

	public static function template_shop_override() {
		$misc_setings = Settings::get_setting_misc();
		$override     = false;

		if ( is_shop() ) {
			if ( isset( $_GET['s'] ) ) {
				if ( ! empty( $misc_setings['search_override'] ) ) {
					$override = true;
				}
			} else {
				if ( ! empty( $misc_setings['shop_override'] ) ) {
					$override = true;
				}
			}
		} else if ( is_product_category() ) {
			if ( ! empty( $misc_setings['archive_override'] ) ) {
				$override = true;
			}
		} else if ( is_tax() ) {
			global $wp_query;
			$taxonomy = $wp_query->queried_object->taxonomy;

			if ( ! empty( $misc_setings['attribute_override'] ) && taxonomy_is_product_attribute( $taxonomy ) ) {
				$override = true;
			} else if ( ! empty( $misc_setings[ $taxonomy . '_override' ] ) ) {
				$override = true;
			}
		}

		if ( $override == true ) {
			add_action( 'woocommerce_before_shop_loop', [ __CLASS__, 'disable_default_woocommerce_loop' ] );
			add_action( 'woocommerce_after_shop_loop', [ __CLASS__, 'add_product_table_after_shop_loop' ] );
			remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

			$theme    = wp_get_theme();
			$template = $theme->get( 'Template' );
			$name     = $theme->get( 'Name' );

			if ( $template == 'genesis' || $name == 'Genesis' ) {
				//Replace Genesis loop with product table
				remove_action( 'genesis_loop', 'genesis_do_loop' );
				add_action( 'genesis_loop', [ __CLASS__, 'add_product_table_after_shop_loop' ] );
			} else if ( $name == 'Storefront' ) {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
				remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
				remove_action( 'woocommerce_before_shop_loop', 'storefront_sorting_wrapper', 9 );
				remove_action( 'woocommerce_before_shop_loop', 'storefront_sorting_wrapper_close', 31 );
				remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper', 9 );
				remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper_close', 31 );
			} else if ( $name == 'Avada' ) {
				global $avada_woocommerce;

				if ( ! empty( $avada_woocommerce ) ) {
					remove_action( 'woocommerce_before_shop_loop', [ $avada_woocommerce, 'catalog_ordering' ], 30 );
				}
			} else if ( $name == 'XStore' ) {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
				remove_action( 'woocommerce_before_shop_loop', 'etheme_grid_list_switcher', 35 );
				remove_action( 'woocommerce_before_shop_loop', 'etheme_products_per_page_select', 37 );
			}
		}
	}

	public static function disable_default_woocommerce_loop() {
		$GLOBALS['woocommerce_loop']['total'] = false;
	}

	public static function add_product_table_after_shop_loop() {
		$shortcode = '[product_table]';

		$args = shortcode_parse_atts( str_replace( [ '[product_table', ']' ], '', $shortcode ) );
		$args = ! empty( $args ) && is_array( $args ) ? $args : [];

		if ( is_product_category() ) {
			// Product category archive
			$args['category'] = get_queried_object_id();
		} elseif ( is_product_tag() ) {
			// Product tag archive
			$args['tag'] = get_queried_object_id();
		} elseif ( is_product_taxonomy() ) {
			// Other product taxonomy archive
			$term         = get_queried_object();
			$args['term'] = "{$term->taxonomy}:{$term->term_id}";
		} elseif ( is_post_type_archive( 'product' ) && ( $search_term = get_query_var( 's' ) ) ) {
			// Product search results page
			$args['search_term'] = $search_term;
		}

		// Display the product table
		wc_the_product_table( $args );
	}

}
