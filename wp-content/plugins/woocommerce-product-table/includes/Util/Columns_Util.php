<?php
namespace Barn2\Plugin\WC_Product_Table\Util;

/**
 * Utility functions for the product table columns.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Columns_Util {

	/**
	 * @var array Global column defaults.
	 */
	private static $column_defaults = false;

	/**
	 * Get the default column headings and responsive priorities.
	 *
	 * @return array The column defaults
	 */
	public static function column_defaults() {

		if ( empty( self::$column_defaults ) ) {
			// Priority values are used to determine visiblity at small screen sizes (1 = highest priority).
			self::$column_defaults = apply_filters( 'wc_product_table_column_defaults', [
				'id'                => [ 'heading' => __( 'ID', 'woocommerce-product-table' ), 'priority' => 8 ],
				'sku'               => [ 'heading' => __( 'SKU', 'woocommerce-product-table' ), 'priority' => 6 ],
				'name'              => [ 'heading' => __( 'Name', 'woocommerce-product-table' ), 'priority' => 1 ],
				'description'       => [ 'heading' => __( 'Description', 'woocommerce-product-table' ), 'priority' => 12 ],
				'short-description' => [ 'heading' => __( 'Summary', 'woocommerce-product-table' ), 'priority' => 11 ],
				'date'              => [ 'heading' => __( 'Date', 'woocommerce-product-table' ), 'priority' => 14 ],
				'categories'        => [ 'heading' => __( 'Categories', 'woocommerce-product-table' ), 'priority' => 9 ],
				'tags'              => [ 'heading' => __( 'Tags', 'woocommerce-product-table' ), 'priority' => 10 ],
				'image'             => [ 'heading' => __( 'Image', 'woocommerce-product-table' ), 'priority' => 4 ],
				'stock'             => [ 'heading' => __( 'Stock', 'woocommerce-product-table' ), 'priority' => 7 ],
				'reviews'           => [ 'heading' => __( 'Reviews', 'woocommerce-product-table' ), 'priority' => 13 ],
				'weight'            => [ 'heading' => __( 'Weight', 'woocommerce-product-table' ), 'priority' => 15 ],
				'dimensions'        => [ 'heading' => __( 'Dimensions', 'woocommerce-product-table' ), 'priority' => 16 ],
				'price'             => [ 'heading' => __( 'Price', 'woocommerce-product-table' ), 'priority' => 3 ],
				'buy'               => [ 'heading' => __( 'Buy', 'woocommerce-product-table' ), 'priority' => 2 ],
				'button'            => [ 'heading' => __( 'Details', 'woocommerce-product-table' ), 'priority' => 5 ]
				] );
		}

		return self::$column_defaults;
	}

	public static function check_blank_heading( $heading ) {
		return 'blank' === $heading ? '' : $heading;
	}

	public static function get_column_taxonomy( $column ) {
		if ( 'categories' === $column ) {
			return 'product_cat';
		} elseif ( 'tags' === $column ) {
			return 'product_tag';
		} elseif ( $att = self::get_product_attribute( $column ) ) {
			if ( taxonomy_is_product_attribute( $att ) ) {
				return $att;
			}
		} elseif ( $tax = self::get_custom_taxonomy( $column ) ) {
			return $tax;
		}
		return false;
	}

	public static function is_custom_field( $column ) {
		return $column && 'cf:' === substr( $column, 0, 3 ) && strlen( $column ) > 3;
	}

	public static function get_custom_field( $column ) {
		if ( self::is_custom_field( $column ) ) {
			return substr( $column, 3 );
		}
		return false;
	}

	public static function is_custom_taxonomy( $column ) {
		$is_tax = $column && 'tax:' === substr( $column, 0, 4 ) && strlen( $column ) > 4;
		return $is_tax && taxonomy_exists( substr( $column, 4 ) );
	}

	public static function get_custom_taxonomy( $column ) {
		if ( self::is_custom_taxonomy( $column ) ) {
			return substr( $column, 4 );
		}
		return false;
	}

	public static function is_hidden_filter_column( $column ) {
		return $column && 'hf:' === substr( $column, 0, 3 ) && strlen( $column ) > 3;
	}

	public static function get_hidden_filter_column( $column ) {
		if ( self::is_hidden_filter_column( $column ) ) {
			return substr( $column, 3 );
		}
		return false;
	}

	public static function is_product_attribute( $column ) {
		return $column && 'att:' === substr( $column, 0, 4 );
	}

	public static function get_product_attribute( $column ) {
		if ( self::is_product_attribute( $column ) ) {
			return substr( $column, 4 );
		}
		return false;
	}

	public static function unprefix_column( $column ) {
		if ( false !== ( $str = strstr( $column, ':' ) ) ) {
			$column = substr( $str, 1 );
		}
		return $column;
	}

	public static function get_column_class( $column ) {
		$column_class_suffix = self::unprefix_column( $column );

		// Certain classes are reserved for use by DataTables Responsive, so we need to strip these to prevent conflicts.
		$column_class_suffix = trim( str_replace( [ 'mobile', 'tablet', 'desktop' ], '', $column_class_suffix ), '_- ' );

		return $column_class_suffix ? Util::sanitize_class_name( 'col-' . $column_class_suffix ) : '';
	}

	public static function get_column_data_source( $column ) {
		// '.' not allowed in data source
		return str_replace( '.', '', $column );
	}

	public static function get_column_name( $column ) {
		// ':' not allowed in column name as not compatible with DataTables API.
		return str_replace( ':', '_', $column );
	}

}
