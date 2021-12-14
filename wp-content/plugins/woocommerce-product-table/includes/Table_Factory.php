<?php

namespace Barn2\Plugin\WC_Product_Table;

/**
 * A Product_Table factory.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Table_Factory {

	private static $tables = [];
	private static $current_id = 1;

	/**
	 * Create a new table based on the supplied args.
	 *
	 * @param array $args The args to use for the table.
	 * @return Product_Table The product table object.
	 */
	public static function create( array $args = [] ) {
		// Merge in the default args, so our table ID reflects the full list of args including user settings.
		$args = wp_parse_args( $args, Table_Args::get_user_defaults() );
		$id   = self::generate_id( $args );

		$table               = new Product_Table( $id, $args );
		self::$tables[ $id ] = $table;

		return $table;
	}

	/**
	 * Fetch an existing table by ID.
	 *
	 * @param string $id The product table ID.
	 * @return Product_Table The product table object.
	 */
	public static function fetch( $id ) {
		if ( empty( $id ) ) {
			return false;
		}

		$table = false;

		if ( isset( self::$tables[ $id ] ) ) {
			$table = self::$tables[ $id ];
		} elseif ( $table = Table_Cache::get_table( $id ) ) {
			self::$tables[ $id ] = $table;
		}

		return $table;
	}

	private static function generate_id( array $args ) {
		$id = 'wcpt_' . substr( md5( serialize( $args ) ), 0, 16 ) . '_' . self::$current_id;
		self::$current_id++;

		return $id;
	}

}
