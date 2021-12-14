<?php
/**
 * Provides backwards compatibility for sites running older versions of WooCommerce.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_esc_json' ) ) {

	/**
	 * Escape JSON for use on HTML or attribute text nodes.
	 *
	 * @since 3.5.5
	 * @param string $json JSON to escape.
	 * @param bool   $html True if escaping for HTML text node, false for attributes. Determines how quotes are handled.
	 * @return string Escaped JSON.
	 */
	function wc_esc_json( $json, $html = false ) {
		return _wp_specialchars(
			$json,
			$html ? ENT_NOQUOTES : ENT_QUOTES, // Escape quotes in attribute nodes only.
			'UTF-8', // json_encode() outputs UTF-8 (really just ASCII), not the blog's charset.
			true                               // Double escape entities: `&amp;` -> `&amp;amp;`.
		);
	}

}

if ( ! function_exists( 'woocommerce_product_loop' ) ) {

	/**
	 * Should the WooCommerce loop be displayed?
	 *
	 * This will return true if we have posts (products) or if we have subcats to display.
	 *
	 * @since 3.4.0
	 * @return bool
	 */
	function woocommerce_product_loop() {
		return have_posts() || ( function_exists( 'woocommerce_get_loop_display_mode' ) && 'products' !== woocommerce_get_loop_display_mode() );
	}

}