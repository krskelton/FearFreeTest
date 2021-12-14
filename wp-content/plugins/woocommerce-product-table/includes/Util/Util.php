<?php

namespace Barn2\Plugin\WC_Product_Table\Util;

use Barn2\Plugin\WC_Product_Table\Table_Shortcode;
use Barn2\WPT_Lib\Util as Lib_Util;
use WC_Product_Addons_Helper;
use WP_Term;
use const Barn2\Plugin\WC_Product_Table\PLUGIN_FILE;

/**
 * Utility functions for WooCommerce Product Table.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Util {

	private static $attribute_labels = [];
	private static $variation_attributes = [];
	private static $tables_on_page = null;

	// ARRAYS

	/**
	 * Combination of array_pad and array_slice.
	 *
	 * @param array $array Input array
	 * @param int $size The size of the array to return
	 * @param mixed $pad What to pad with
	 * @return array The result
	 */
	public static function array_pad_and_slice( $array, $size, $pad ) {
		if ( ! is_array( $array ) ) {
			$array = [];
		}
		return array_slice( array_pad( $array, $size, $pad ), 0, $size );
	}

	/**
	 * Similar to <code>array_diff_assoc</code>, but does a loose type comparison on array values (== not ===).
	 * Supports multi-dimensional arrays, but doesn't support passing more than two arrays.
	 *
	 * @param array $array1 The main array to compare against
	 * @param array $array2 The array to compare with
	 * @return array All entries in $array1 which are not present in $array2 (including key check)
	 */
	public static function array_diff_assoc( $array1, $array2 ) {
		if ( empty( $array1 ) || ! is_array( $array1 ) ) {
			return [];
		}
		if ( empty( $array2 ) || ! is_array( $array2 ) ) {
			return $array1;
		}

		foreach ( $array1 as $k1 => $v1 ) {
			if ( array_key_exists( $k1, $array2 ) ) {
				$v2 = $array2[ $k1 ];

				if ( $v2 == $v1 ) {
					unset( $array1[ $k1 ] );
				}
			}
		}
		return $array1;
	}

	/**
	 * Similar to <code>wp_list_pluck</code> or <code>array_column</code> but plucks several keys from the source array.
	 *
	 * @param array $list The array of arrays to extract the keys from
	 * @param array|string $keys The list of keys to pluck
	 * @return array An array returned in the same order as $list, but where each item in the array contains just the specified $keys
	 */
	public static function list_pluck_array( $list, $keys = [] ) {
		$result    = [];
		$keys_comp = array_flip( (array) $keys );

		// Return empty array if there are no keys to extract
		if ( ! $keys_comp ) {
			return [];
		}

		foreach ( $list as $key => $item ) {
			if ( ! is_array( $item ) ) {
				// Make sure we have an array to pluck from
				continue;
			}
			$item = array_intersect_key( $item, $keys_comp );

			foreach ( $item as $child_key => $child ) {
				if ( is_array( $child ) ) {
					$item[ $child_key ] = self::list_pluck_array( $child, $keys );
				}
			}

			$result[ $key ] = $item;
		}

		return $result;
	}

	public static function string_list_to_array( $arg ) {
		if ( is_array( $arg ) ) {
			return $arg;
		}
		return array_filter( array_map( 'trim', explode( ',', $arg ) ) );
	}

	// SANITIZING / VALIDATION

	public static function empty_if_false( $var ) {
		if ( false === $var ) {
			return '';
		}
		return $var;
	}

	public static function maybe_parse_bool( $maybe_bool ) {
		if ( is_bool( $maybe_bool ) ) {
			return $maybe_bool;
		} elseif ( 'true' === $maybe_bool || '1' === $maybe_bool ) {
			return true;
		} elseif ( 'false' === $maybe_bool || '' === $maybe_bool ) {
			return false;
		} else {
			return $maybe_bool;
		}
	}

	public static function sanitize_list_arg( $arg, $allow_space = false ) {
		if ( is_string( $arg ) ) {
			// Allows any "word" (letter, digit, underscore), comma, full-stop, colon, hyphen, plus, forward slash, and (optionally) a space.
			$pattern = $allow_space ? '/[^\w,\.\:\-\+\% ]/' : '/[^\w,\.\:\-\+\%]/';
			return preg_replace( $pattern, '', $arg );
		}

		return $arg;
	}

	public static function sanitize_list_arg_allow_space( $arg ) {
		return self::sanitize_list_arg( $arg, true );
	}

	public static function sanitize_numeric_list_arg( $arg ) {
		if ( is_string( $arg ) ) {
			// Allows decimal digit or comma
			return preg_replace( '/[^\d,]/', '', $arg );
		}
		return $arg;
	}

	public static function sanitize_string_or_array_arg( $arg ) {
		return is_array( $arg ) ? filter_var( $arg, FILTER_SANITIZE_STRING, FILTER_REQUIRE_ARRAY ) : filter_var( $arg, FILTER_SANITIZE_STRING );
	}

	public static function sanitize_string_or_bool_arg( $arg ) {
		$maybe_bool = self::maybe_parse_bool( $arg );
		return is_bool( $maybe_bool ) ? $maybe_bool : filter_var( $arg, FILTER_SANITIZE_STRING );
	}

	public static function sanitize_class_name( $class ) {
		return preg_replace( '/[^a-zA-Z0-9-_]/', '', $class );
	}

	public static function set_object_vars( $object, $vars ) {
		if ( ! is_object( $object ) || ! is_array( $vars ) ) {
			return;
		}

		$properties = get_object_vars( $object );

		foreach ( $properties as $name => $value ) {
			$object->$name = isset( $vars[ $name ] ) && ( null !== $vars[ $name ] ) ? $vars[ $name ] : $value;
		}
	}

	// TERMS / TAXONOMIES

	public static function convert_to_term_ids( $terms, $taxonomy ) {
		if ( empty( $terms ) ) {
			return [];
		}
		if ( ! is_array( $terms ) ) {
			$terms = explode( ',', str_replace( '+', ',', $terms ) );
		}
		$result = [];

		foreach ( $terms as $slug ) {
			$_term = false;

			if ( is_numeric( $slug ) ) {
				$_term = get_term_by( 'id', $slug, $taxonomy );
			}
			if ( ! $_term ) {
				$_term = get_term_by( 'slug', $slug, $taxonomy );
			}
			if ( $_term instanceof WP_Term ) {
				$result[] = $_term->term_id;
			}
		}
		return $result;
	}

	public static function get_all_term_children( $term_ids, $taxonomy, $include_parents = false ) {
		$result = $include_parents ? ( $term_ids ? $term_ids : [] ) : [];

		foreach ( $term_ids as $term_id ) {
			$result = array_merge( $result, get_term_children( $term_id, $taxonomy ) );
		}
		// Remove duplicates
		return array_unique( $result );
	}

	public static function get_terms( $args = [] ) {
		global $wp_version;

		// Default to product categories if not set
		if ( empty( $args['taxonomy'] ) ) {
			$args['taxonomy'] = 'product_cat';
		}
		// Arguments for get_terms() changed in WP 4.5
		if ( version_compare( $wp_version, '4.5', '>=' ) ) {
			$terms = get_terms( $args );
		} else {
			$tax = $args['taxonomy'];
			unset( $args['taxonomy'] );
			$terms = get_terms( $tax, $args );
		}

		if ( is_array( $terms ) ) {
			return $terms;
		} else {
			return [];
		}
	}

	// ATTRIBUTES / VARIATIONS

	/**
	 * Pull the attributes from the specified array, which may contain a mix of different data.
	 *
	 * E.g. extract_attributes( array(
	 *    'name' => 'product1',
	 *    'id'   => '123'
	 *    'attribute_pa_size' => 'medium',
	 *    'attribute_pa_color' => 'red'
	 * ) );
	 *
	 * would return an array with the two attributes - attribute_pa_size and attribute_pa_color.
	 *
	 * @param array $array The array to extract from
	 * @return array Just the attributes, or an empty array if there are none.
	 */
	public static function extract_attributes( $array ) {
		return array_intersect_key( $array, array_flip( preg_grep( '/^attribute_/', array_keys( $array ) ) ) );
	}

	public static function get_attribute_name( $attribute_name ) {
		$attribute_taxonomy = wc_attribute_taxonomy_name( str_replace( 'pa_', '', $attribute_name ) );
		return taxonomy_is_product_attribute( $attribute_taxonomy ) ? $attribute_taxonomy : sanitize_title( $attribute_name );
	}

	public static function get_attribute_label( $name, $product = '' ) {
		// Return from label cache if present
		if ( isset( self::$attribute_labels[ $name ] ) ) {
			return self::$attribute_labels[ $name ];
		}
		$label = wc_attribute_label( $name, $product );

		// Cache attribute label to prevent additional DB calls
		if ( taxonomy_is_product_attribute( $name ) ) {
			self::$attribute_labels[ $name ] = $label;
		} else {
			$label = str_replace( [ '-', '_' ], ' ', $label );
		}
		return $label;
	}

	/**
	 * Similar to WC_Product_Variable->get_available_variations() but returns an array of WC_Product_Variation objects rather than arrays.
	 *
	 * @param WC_Product_Variable $product The product to get variations for
	 * @return array An array of WC_Product_Variation objects
	 */
	public static function get_available_variations( $product ) {
		if ( ! $product || 'variable' !== $product->get_type() || ! $product->has_child() ) {
			return [];
		}

		$variations           = array_filter( array_map( 'wc_get_product', $product->get_children() ) );
		$available_variations = [];

		foreach ( $variations as $variation ) {
			// Hide out of stock variations if 'Hide out of stock items from the catalog' is checked
			if ( ! $variation || ! $variation->exists() || ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! $variation->is_in_stock() ) ) {
				continue;
			}

			// Filter 'woocommerce_hide_invisible_variations' to optionally hide invisible variations (disabled variations and variations with empty price).
			if ( apply_filters( 'woocommerce_hide_invisible_variations', true, $product->get_id(), $variation ) && ! $variation->variation_is_visible() ) {
				continue;
			}

			$available_variations[] = $variation;
		}

		return apply_filters( 'wc_product_table_available_variations', $available_variations, $product );
	}

	public static function get_variation_attributes( $product ) {
		$product_id = $product->get_id();

		if ( ! empty( self::$variation_attributes ) && isset( self::$variation_attributes[ $product_id ] ) ) {
			return self::$variation_attributes[ $product_id ];
		}

		// Sanity check, in case non-variable product is passed
		if ( ! method_exists( $product, 'get_variation_attributes' ) ) {
			return [];
		}

		self::$variation_attributes[ $product_id ] = $product->get_variation_attributes();
		return self::$variation_attributes[ $product_id ];
	}

	// CUSTOM FIELDS

	public static function get_acf_field_object( $field, $post_id = false ) {
		$field_obj = false;

		if ( ! $post_id && function_exists( 'acf_get_field' ) ) {
			// If we're not getting field for a specific post, just check field exists (ACF Pro only)
			$field_obj = acf_get_field( $field );
		} elseif ( function_exists( 'get_field_object' ) ) {
			$field_obj = get_field_object( $field, $post_id, [ 'format_value' => false ] );
		}
		if ( $field_obj ) {
			if ( in_array( $field_obj['type'], [ 'date_picker', 'date_time_picker' ] ) && isset( $field_obj['date_format'] ) ) {
				// In ACF v4 and below, date picker fields used jQuery date formats and 'return_format' was called 'date_format'
				$field_obj['return_format'] = self::jquery_to_php_date_format( $field_obj['date_format'] );

				// In ACF v4 and below, display_format used jQuery date format
				if ( isset( $field_obj['display_format'] ) ) {
					$field_obj['display_format'] = self::jquery_to_php_date_format( $field_obj['display_format'] );
				}
			}
			return $field_obj;
		}
		return false;
	}

	// PRODUCTS

	public static function get_product_name( $product ) {
		//@todo: Is this function still needed or does WooCommerce correctly handle variation names?
		if ( ! $product ) {
			return '';
		}
		$name = $product->get_name();

		if ( 'variation' === $product->get_type() ) {
			// Get the name of the parent product
			$parent_name = self::get_parent( $product )->get_name();

			// Name contains attributes if the parent name is different to the variation's name
			$contains_attributes = $name !== $parent_name;

			// Older versions of WC used 'Variation #1234 of...' as the product name, so use parent instead
			if ( stristr( $name, 'Variation' ) ) {
				$name                = $parent_name;
				$contains_attributes = false;
			}

			if ( ! $contains_attributes ) {
				$name   = $product->get_title();
				$labels = [];

				foreach ( $product->get_variation_attributes() as $atttribute_name => $value ) {
					if ( ! $value ) {
						continue;
					}

					$atttribute_name = str_replace( 'attribute_', '', $atttribute_name );

					// If this is a term slug, get the term's nice name
					if ( taxonomy_exists( $atttribute_name ) ) {
						$term = get_term_by( 'slug', $value, $atttribute_name );
						if ( ! is_wp_error( $term ) && ! empty( $term->name ) ) {
							$value = $term->name;
						}
					} else {
						$value = ucwords( str_replace( '-', ' ', $value ) );
					}

					$labels[] = rawurldecode( $value );
				}

				if ( $labels ) {
					$name .= ' - ' . implode( ', ', $labels );
				}
			}
		}

		return $name;
	}

	public static function format_product_link( $product, $link_text = '', $link_class = [] ) {
		$target = $class = '';

		if ( ! $link_text ) {
			$link_text = self::get_product_name( $product );
		}

		if ( ! $product->is_visible() ) {
			return $link_text;
		}

		if ( apply_filters( 'wc_product_table_open_products_in_new_tab', false ) ) {
			$target = ' target="_blank"';
		}

		$classes   = is_string( $link_class ) ? explode( ' ', $link_class ) : $link_class;
		$classes[] = 'single-product-link';

		return sprintf(
			'<a href="%1$s" class="%2$s" data-product_id="%3$u"%4$s>%5$s</a>',
			esc_url( $product->get_permalink() ),
			esc_attr( implode( ' ', $classes ) ),
			$product->get_id(),
			$target,
			$link_text
		);
	}

	public static function format_loop_add_to_cart_link( $link ) {
		if ( apply_filters( 'wc_product_table_open_products_in_new_tab', false ) && ( false === strpos( $link, 'target=' ) ) ) {
			$link = str_replace( '<a ', '<a target="_blank" ', $link );
		}
		return $link;
	}

	public static function get_post( $product ) {
		return get_post( $product->get_id() );
	}

	/**
	 * Return the parent for the given product, if there is one, otherwise it returns the original product.
	 *
	 * @param WC_Product $product The product.
	 * @return WC_Product Either the parent product or the original product.
	 */
	public static function maybe_get_parent( $product ) {
		if ( $product && ( $parent = self::get_parent( $product ) ) ) {
			return $parent;
		}
		return $product;
	}

	/**
	 * Gets the parent product for the given product, or false if there is no parent.
	 *
	 * @param WC_Product $product The product.
	 * @return WC_Product The parent product or false.
	 */
	public static function get_parent( $product ) {
		$parent = false;

		if ( $parent_id = $product->get_parent_id() ) {
			$parent = wc_get_product( $parent_id );
		}

		return $parent ? $parent : false;
	}

	public static function get_product_addons( $product_id ) {
		if ( ! Lib_Util::is_product_addons_active() ) {
			return [];
		}

		if ( ! method_exists( 'WC_Product_Addons_Helper', 'get_product_addons' ) ) {
			return [];
		}

		return WC_Product_Addons_Helper::get_product_addons( $product_id );
	}

	// WIDGETS

	public static function get_layered_nav_params( $lazy_load = false ) {
		$request_params = $lazy_load ? $_POST : $_GET;

		// Get the just the layered nav params (e.g. min_price) from the current request.
		return $request_params ?
			array_intersect_key( $request_params,
				array_flip(
					array_filter(
						array_keys( $request_params ), [ self::class, 'array_filter_layered_nav_params' ] ) ) ) : [];
	}

	public static function array_filter_layered_nav_params( $value ) {
		return in_array( $value, [ 'min_price', 'max_price', 'rating_filter' ] ) ||
			   0 === strpos( $value, 'query_type_' ) || 0 === strpos( $value, 'filter_' );
	}

	// DATES AND TIMES

	/**
	 * Convert a jQuery date format to a PHP one. E.g. 'dd-mm-yy' becomes 'd-m-Y'.
	 * See http://api.jqueryui.com/datepicker/ for jQuery formats.
	 *
	 * @param string $jQueryFormat The jQuery date format
	 * @return string The equivalent PHP date format
	 */
	public static function jquery_to_php_date_format( $jQueryFormat ) {
		$result = $jQueryFormat;

		if ( false === strpos( $result, 'dd' ) ) {
			$result = str_replace( 'd', 'j', $result );
		}
		if ( false === strpos( $result, 'mm' ) ) {
			$result = str_replace( 'm', 'n', $result );
		}
		if ( false === strpos( $result, 'oo' ) ) {
			$result = str_replace( 'o', 'z', $result );
		}

		return str_replace( [ 'dd', 'oo', 'DD', 'mm', 'MM', 'yy' ], [ 'd', 'z', 'l', 'm', 'F', 'Y' ], $result );
	}

	public static function is_european_date_format( $format ) {
		// It's EU format if the day comes first
		return $format && in_array( substr( $format, 0, 1 ), [ 'd', 'j' ] );
	}

	/**
	 * Is the value passed a valid UNIX epoch time (i.e. seconds elapsed since 1st January 1970)?
	 *
	 * Not a perfect implementation as it will return false for valid timestamps representing dates
	 * between 31st October 1966 and 3rd March 1973, but this is needed to prevent valid dates held
	 * in numeric formats (e.g. 20171201) being wrongly interpreted as timestamps.
	 *
	 * @param mixed $value The value to check
	 * @return boolean True if $value is a valid epoch timestamp
	 */
	public static function is_unix_epoch_time( $value ) {
		return is_numeric( $value ) && (int) $value == $value && strlen( (string) absint( $value ) ) > 8;
	}

	/**
	 * Convert a date string to a timestamp. A wrapper around strtotime which accounts for dates already
	 * formatted as a timestamp.
	 *
	 * @param string $date The date to convert to a timestamp.
	 * @return int|boolean The timestamp (number of seconds since the Epoch) for this date, or false on failure.
	 */
	public static function strtotime( $date ) {
		if ( self::is_unix_epoch_time( $date ) ) {
			// Already a UNIX timestamp so return as int.
			return (int) $date;
		}

		return strtotime( $date );
	}

	// SEARCH

	public static function is_valid_search_term( $search_term ) {
		$min_length = max( 1, absint( apply_filters( 'wc_product_table_minimum_search_term_length', 3 ) ) );
		return ! empty( $search_term ) && strlen( $search_term ) >= $min_length;
	}

	// IMAGES

	public static function get_image_size_width( $size ) {
		$width = false;

		if ( is_array( $size ) ) {
			$width = $size[0];
		} elseif ( is_string( $size ) ) {
			$sizes = wp_get_additional_image_sizes();

			if ( isset( $sizes[ $size ]['width'] ) ) {
				$width = $sizes[ $size ]['width'];
			} elseif ( $w = get_option( "{$size}_size_w" ) ) {
				$width = $w;
			}
		}
		return $width;
	}

	// SHORTCODE

	public static function is_table_on_page() {
		return count( self::get_tables_on_page() ) > 0;
	}

	public static function get_tables_on_page() {
		if ( null === self::$tables_on_page ) {
			$table_shortcodes     = self::get_table_shortcodes_in_post_content();
			self::$tables_on_page = is_array( $table_shortcodes ) ? $table_shortcodes : [];
		}
		return self::$tables_on_page;
	}

	private static function get_table_shortcodes_in_post_content() {
		// First, we store the current in_the_loop and current_post values so we can set them back afterwards.
		global $wp_query;
		$in_the_loop  = $wp_query->in_the_loop;
		$current_post = $wp_query->current_post;

		$result = [];

		if ( is_singular() && ! is_attachment() && have_posts() ) {
			// Start an output buffer (discarded below) as some plugins generate output when calling the_post()
			ob_start();

			the_post();

			$matches = [];
			preg_match_all( '#\[' . Table_Shortcode::SHORTCODE . '.*?\]#', get_the_content(), $matches );

			if ( isset( $matches[0] ) ) {
				$result = $matches[0];
			}

			// Rewind posts as we called the_post(), then end output buffer
			rewind_posts();
			ob_end_clean();
		}

		// Set back query properties to previous state as have_posts() and the_post() override them.
		$wp_query->in_the_loop  = $in_the_loop;
		$wp_query->current_post = $current_post;

		return $result;
	}

	// CSS

	public static function get_button_class() {
		return apply_filters( 'wc_product_table_button_class', 'button btn' );
	}

	public static function get_wrapper_class() {
		$template = sanitize_html_class( strtolower( get_template() ) );
		return apply_filters( 'wc_product_table_wrapper_class', 'wc-product-table-wrapper ' . $template );
	}

	// PATHS / URLs

	public static function get_asset_url( $path = '' ) {
		return plugins_url( 'assets/' . ltrim( $path, '/' ), PLUGIN_FILE );
	}

	public static function get_wc_asset_url( $path = '' ) {
		if ( defined( 'WC_PLUGIN_FILE' ) ) {
			return plugins_url( 'assets/' . ltrim( $path, '/' ), WC_PLUGIN_FILE );
		}
		return false;
	}

	public static function get_template_path() {
		return plugin_dir_path( PLUGIN_FILE ) . 'templates/';
	}

	// OTHER

	public static function get_shop_messages() {
		// Print WC notices (e.g. add to cart notifications)
		if ( function_exists( 'wc_print_notices' ) ) {
			ob_start();
			wc_print_notices();
			$messages = ob_get_clean();

			return $messages ? '<div class="woocommerce">' . $messages . '</div>' : '';
		}
	}

	public static function doing_lazy_load() {
		return defined( 'DOING_AJAX' ) && DOING_AJAX && is_string( filter_input( INPUT_POST, 'table_id', FILTER_SANITIZE_STRING ) );
	}

}
