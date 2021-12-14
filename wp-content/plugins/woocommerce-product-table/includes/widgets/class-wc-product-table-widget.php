<?php

use Barn2\Plugin\WC_Product_Table\Util\Util;

if ( class_exists( 'WC_Widget' ) ) {

	/**
	 * Abstract widget class extended by the Product Table widgets.
	 *
	 * @package   Barn2\woocommerce-product-table
	 * @author    Barn2 Plugins <support@barn2.com>
	 * @license   GPL-3.0
	 * @copyright Barn2 Media Ltd
	 */
	abstract class WC_Product_Table_Widget extends WC_Widget {

		public function __construct() {
			parent::__construct();

			add_filter( 'body_class', [ __CLASS__, 'body_class' ], 99 );
		}

		public static function body_class( $classes ) {
			// Add .woocommerce to body class if product table used on page, so filter widgets pick up correct styles in certain themes (Genesis, Total, etc).
			if ( ! in_array( 'woocommerce', $classes ) && Util::is_table_on_page() ) {
				$classes[] = 'woocommerce';
			}

			return $classes;
		}

		/**
		 * @deprecated 2.8 Replaced by Barn2\Plugin\WC_Product_Table\Util\Util::is_table_on_page
		 */
		public static function is_table_on_page() {
			_deprecated_function( __METHOD__, '2.8', 'Barn2\\Plugin\\WC_Product_Table\\Util\\Util::is_table_on_page' );
			return Util::is_table_on_page();
		}

		public static function unescape_commas( $link ) {
			return str_replace( '%2C', ',', $link );
		}

		protected static function get_main_tax_query() {
			global $wp_the_query;
			return isset( $wp_the_query->tax_query, $wp_the_query->tax_query->queries ) ? $wp_the_query->tax_query->queries : [];
		}

		protected static function get_main_meta_query() {
			global $wp_the_query;
			return isset( $wp_the_query->query_vars['meta_query'] ) ? $wp_the_query->query_vars['meta_query'] : [];
		}

		/**
		 * Return the currently viewed taxonomy name.
		 * @return string
		 */
		protected function get_current_taxonomy() {
			return is_tax() ? get_queried_object()->taxonomy : '';
		}

		/**
		 * Return the currently viewed term ID.
		 * @return int
		 */
		protected function get_current_term_id() {
			return absint( is_tax() ? get_queried_object()->term_id : 0 );
		}

		/**
		 * Return the currently viewed term slug.
		 * @return int
		 */
		protected function get_current_term_slug() {
			return absint( is_tax() ? get_queried_object()->slug : 0 );
		}

	}

} // if WC_Widget exists
