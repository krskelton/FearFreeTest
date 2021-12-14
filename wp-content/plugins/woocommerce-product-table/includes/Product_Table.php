<?php

namespace Barn2\Plugin\WC_Product_Table;

use Barn2\Plugin\WC_Product_Table\Data\Data_Factory;
use Barn2\Plugin\WC_Product_Table\Util\Columns_Util;
use Barn2\Plugin\WC_Product_Table\Util\Util;
use Html_Data_Table;
use WC_Shortcodes;

/**
 * Represents a table of WooCommerce products.
 *
 * This class is responsible for creating the table from the specified parameter and returning the complete table as a Html_Data_Table instance.
 *
 * The main functions provided are get_table() and get_data().
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Table {

	public $id;
	public $args;
	public $query;
	public $hooks;
	public $data_table;
	private $columns;
	private $data_factory;
	private $config_builder;
	private $cache;

	private $table_initialised = false;
	private $data_added = false;

	const CONTROL_COLUMN_DATA_SOURCE = 'control';

	public function __construct( $id, array $args = [] ) {
		$this->id = $id;

		// Initialize helpers
		$this->args           = new Table_Args( $args );
		$this->query          = new Table_Query( $this->args );
		$this->columns        = new Table_Columns( $this->args );
		$this->data_factory   = new Data_Factory( $this->args );
		$this->hooks          = new Hook_Manager( $this->args );
		$this->config_builder = new Config_Builder( $this->id, $this->args, $this->columns );
		$this->cache          = new Table_Cache( $this->id, $this->args, $this->query );
		$this->data_table     = new Html_Data_Table();
	}

	/**
	 * Retrieves the data table containing the list of products based on the arguments
	 * supplied on construction.
	 *
	 * The table returned includes the table headings, attributes and data.
	 *
	 * If the output is 'object' the returned object is an Html_Data_Table instance.
	 * If the output is 'html', the table returned will be a string containing the <table> element
	 * at its root, and include the <thead>, <tfoot> and <tbody> elements.
	 * If the output is 'array', the table returned will be an array containing the following keys:
	 * 'thead', 'tbody', 'tfoot' and 'attributes'.
	 * If the output is 'json', the return value will be a JSON-encoded string in the same format
	 * as 'array'.
	 *
	 * @param string $output The output format - object, html, array or json. Default 'object'.
	 * @return object|array|string The product table in the requested format.
	 */
	public function get_table( $output = 'object' ) {

		if ( ! $this->table_initialised ) {
			// Add table to cache.
			$this->cache->add_table();

			// Reset the table
			$this->data_table->reset();

			do_action( 'wc_product_table_before_get_table', $this );

			// Enqueue the scripts for this table.
			Frontend_Scripts::load_table_scripts( $this->args );

			// Add attriutes and table headings.
			$this->add_attributes();
			$this->add_headings();

			// Fetch the data.
			$this->fetch_data();

			do_action( 'wc_product_table_after_get_table', $this );

			$this->table_initialised = true;
		}

		$result = $this->data_table;

		if ( 'html' === $output ) {
			$result = $this->data_table->to_html();

			// Include any 'add to cart' messages above table.
			if ( method_exists( 'WC_Shortcodes', 'shop_messages' ) ) {
				$result = WC_Shortcodes::shop_messages() . $result;
			}
		} elseif ( 'array' === $output ) {
			$result = $this->data_table->to_array();
		} elseif ( 'json' === $output ) {
			$result = $this->data_table->to_json();
		}

		return apply_filters( 'wc_product_table_get_table_output', $result, $output, $this );
	}

	/**
	 * Retrieves the data table containing the list of products based on the specified arguments.
	 *
	 * The table returned includes only the table data itself (i.e. the rows), and doesn't include the header, footer, etc.
	 *
	 * If the output is 'object' the returned object will be an Html_Data_Table instance.
	 * If the output is 'html', the data returned will be a string containing a list of <tr> elements,
	 * one for each product found.
	 * If the output is 'array', the data returned will be an array of rows, one for each product found.
	 * if the output is 'json', the data returned will be a JSON-encoded string in the same format
	 * as 'array'.
	 *
	 * @param string $output The output type (see description).
	 * @return object|array|string The product table data in the requested format.
	 */
	public function get_data( $output = 'object' ) {
		// Fetch the data.
		$this->fetch_data();

		$result = $this->data_table;

		// Build the output.
		if ( 'html' === $output ) {
			$result = $this->data_table->to_html( true );
		} elseif ( 'array' === $output ) {
			$result = $this->data_table->to_array( true );
		} elseif ( 'json' === $output ) {
			$result = $this->data_table->to_json( true );
		}

		return apply_filters( 'wc_product_table_get_data_output', $result, $output, $this );
	}

	/**
	 * Update the table with new arguments specified in $args. Previously posts data is preserved where possible,
	 * to prevent additional DB calls.
	 *
	 * @param array $args The new args. Does not need to be a complete list (will be merged with current properties)
	 */
	public function update( array $args ) {
		$this->table_initialised = false;
		$this->data_added        = false;

		$product_selection_args   = [
			'status',
			'year',
			'month',
			'day',
			'category',
			'tag',
			'term',
			'cf',
			'author',
			'exclude',
			'include',
			'exclude_category',
			'search_term'
		];
		$product_sort_paging_args = [ 'rows_per_page', 'product_limit', 'offset', 'sort_by', 'sort_order' ];
		$user_search_args         = [ 'search_filters', 'user_search_term' ];

		// Work out what changed
		$modified_args  = array_keys( Util::array_diff_assoc( $args, get_object_vars( $this->args ) ) );
		$products_reset = false;

		if ( array_intersect( $modified_args, $product_selection_args ) ) {
			// If any of the post paramaters are updated, reset posts array and totals
			$this->query->set_products( null );
			$this->query->set_total_products( null );
			$this->query->set_total_filtered_products( null );
			$products_reset = true;
		}

		if ( array_intersect( $modified_args, $product_sort_paging_args ) ) {
			// If just the table paramaters are updated, reset posts but not totals
			$this->query->set_products( null );
			$products_reset = true;
		}

		// If our search term or search filters changed from last time, reset products and filtered total, but leave the overall total.
		if ( array_intersect( $modified_args, $user_search_args ) ) {
			$this->query->set_products( null );
			$this->query->set_total_filtered_products( null );
			$products_reset = true;
		}

		// If we have an original search term and a user applied search term, we need to reset the total to avoid conflicts.
		if ( $this->args->search_term && in_array( 'user_search_term', $modified_args ) ) {
			$this->query->set_total_products( null );
		}

		// Don't use cache if lazy loading and query params have been modified (e.g. rows_per_page, sort_by, etc)
		// We don't check offset here as we cache each page of results separately using offset in the cache key.
		if ( $products_reset && $this->args->lazy_load && $this->args->cache ) {
			$args['cache'] = false;
		}

		// Next we update the args - this will update the args object in all helper classes as objects are stored by reference.
		$this->args->set_args( $args );

		do_action( 'wc_product_table_args_updated', $this );
	}

	private function fetch_data() {
		if ( $this->data_added || ! $this->can_fetch_data() ) {
			return;
		}

		// Reset the table data
		$this->data_table->reset_data();

		if ( $data = $this->cache->get_data() ) {
			$this->data_table->set_data( $data );
		} else {
			// No cache found, or caching disabled.
			do_action( 'wc_product_table_before_get_data', $this );

			// Register the data hooks.
			$this->hooks->register();

			// Add all products to the table.
			$this->add_products_to_table( $this->query->get_products() );

			// Reset hooks.
			$this->hooks->reset();

			// Update the cache.
			$this->cache->update_data( $this->data_table->get_data() );
			$this->cache->update_table( true );

			do_action( 'wc_product_table_after_get_data', $this );
		}

		$this->data_added = true;
	}

	private function can_fetch_data() {
		if ( ! $this->args->lazy_load ) {
			return true;
		} else {
			return $this->args->lazy_load && defined( 'DOING_AJAX' ) && DOING_AJAX;
		}
	}

	/**
	 * Add the products (array of post objects) to the table.
	 *
	 * @param array $products An array of WC_Product objects
	 */
	private function add_products_to_table( $products ) {
		// Bail if no products to add
		if ( ! $products ) {
			return;
		}

		// To make sure the post and product globals are reset, we store them here and set it back after our product loop.
		$old_global_post    = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : false;
		$old_global_product = isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : false;

		// Get required columns to walk through
		$cols = $this->columns->get_all_columns();

		// Loop through array of WC_Products and add data for each.
		foreach ( $products as $product ) {

			// If it's a variable product and we're displaying separate variations, process variations and continue to next product.
			if ( 'separate' === $this->args->variations && 'variable' === $product->get_type() ) {
				$this->add_products_to_table( Util::get_available_variations( $product ) );
				continue;
			}

			$this->setup_postdata_for_product( $product );

			$this->data_table->new_row( $this->get_row_attributes( $product ) );

			// Add an empty cell if we're using the control column for responsive child rows
			if ( 'column' === $this->args->responsive_control ) {
				$this->data_table->add_data( '', false, self::CONTROL_COLUMN_DATA_SOURCE );
			}

			// Add the data for this product
			array_walk( $cols, [ $this, 'add_product_data' ], $product );
		}

		// Reset main WP query as we called setup_postdata
		if ( $old_global_post ) {
			$GLOBALS['post'] = $old_global_post;
		}
		if ( $old_global_product ) {
			$GLOBALS['product'] = $old_global_product;
		}

		wp_reset_postdata();
	}

	private function add_product_data( $column, $key, $product ) {
		$data = '';
		$atts = false;

		// Get data object for this column
		if ( $data_obj = $this->data_factory->create( $column, $product ) ) {
			$data = $data_obj->get_data();
			$atts = array_filter(
				[
					'data-sort'   => $data_obj->get_sort_data(),
					'data-filter' => $data_obj->get_filter_data()
				],
				[ self::class, 'array_filter_empty_attribute' ]
			);
		} else {
			// Back compat: data object not found (e.g. custom column) so run filter
			$data = apply_filters( 'wc_product_table_custom_data_' . $column, '', Util::get_post( $product ), $product );
			$atts = apply_filters( 'wc_product_table_custom_data_atts_' . $column, false, $product );
		}

		$this->data_table->add_data( $data, $atts, Columns_Util::get_column_data_source( $column ) );
	}

	private function add_attributes() {
		// Set table attributes.
		$table_class = trim( 'wc-product-table woocommerce ' . ( $this->args->wrap ? '' : 'nowrap ' ) . apply_filters( 'wc_product_table_custom_class', '', $this ) );

		$this->data_table->add_attribute( 'id', $this->id );
		$this->data_table->add_attribute( 'class', $table_class );

		// This is required otherwise tables can expand beyond their container.
		$this->data_table->add_attribute( 'width', '100%' );

		// Add the table config as JSON encoded data.
		$this->data_table->add_attribute( 'data-config', wc_esc_json( self::json_encode_config( $this->config_builder->get_config() ), false ) );
		$this->data_table->add_attribute( 'data-filters', wc_esc_json( wp_json_encode( $this->config_builder->get_filters() ), false ) );

		// Set table ordering during initialisation - default to no ordering (i.e. use post order returned from WP_Query).
		$order_attr = '[]';

		// If column is sortable, set initial sort order for DataTables.
		if ( 'menu_order' !== $this->args->sort_by && $this->columns->is_sortable( $this->args->sort_by ) ) {
			$sort_index = $this->columns->column_index( $this->args->sort_by );

			if ( false !== $sort_index ) {
				// 'sort_order' has to be in double quotes (@see https://datatables.net/manual/options).
				$order_attr = sprintf( '[[%u, "%s"]]', $sort_index, $this->args->sort_order );
			}
		}
		$this->data_table->add_attribute( 'data-order', $order_attr );
	}

	private function add_headings() {
		// Add the control column for responsive layouts if required (the column that contains the + / - icon)
		if ( 'column' === $this->args->responsive_control ) {
			$this->add_heading( '', [ 'data-data' => self::CONTROL_COLUMN_DATA_SOURCE ], self::CONTROL_COLUMN_DATA_SOURCE );
		}

		// Add column headings
		foreach ( $this->columns->get_columns() as $i => $column ) {
			$data_source = Columns_Util::get_column_data_source( $column );

			$column_atts = [
				'class'           => $this->columns->get_column_header_class( $i, $column ),
				'data-name'       => Columns_Util::get_column_name( $column ), // used to easily pick out column in JS, e.g. dataTable.column( 'sku:name' ).
				'data-orderable'  => $this->columns->is_sortable( $column ),
				'data-searchable' => $this->columns->is_searchable( $column ),
				'data-width'      => $this->columns->get_column_width( $i, $column ),
				'data-priority'   => $this->columns->get_column_priority( $i, $column )
			];

			if ( $this->args->lazy_load ) {
				// Data source required only for lazy load - used to identify the column from JSON data.
				$column_atts['data-data'] = $data_source;
			}

			$this->add_heading( $this->columns->get_column_heading( $i, $column ), $column_atts, $data_source );
		}

		// Add hidden columns
		foreach ( $this->columns->get_hidden_columns() as $column ) {
			$data_source = Columns_Util::get_column_data_source( $column );

			$column_atts = [
				'data-name'       => Columns_Util::get_column_name( $column ),
				'data-searchable' => $this->columns->is_searchable( $column ),
				'data-visible'    => $this->args->show_hidden
			];

			if ( $this->args->lazy_load ) {
				// Data source required only for lazy load.
				$column_atts['data-data'] = $data_source;
			}

			$this->add_heading( $column, $column_atts, $data_source );
		}
	}

	private function add_heading( $heading, $attributes, $key ) {
		$this->data_table->add_header( $heading, $attributes, $key );

		if ( $this->args->show_footer ) {
			$this->data_table->add_footer( $heading, false, $key ); // attributes not needed in footer.
		}
	}

	private function get_row_attributes( $product ) {
		$id = $product->get_id();

		$classes = [
			'product', // need this for compatibility with add-to-cart-variation.js
			'product-row',
			'product-' . $id,
			$product->get_stock_status()
		];

		if ( $product->is_on_sale() ) {
			$classes[] = 'sale';
		}
		if ( $product->is_featured() ) {
			$classes[] = 'featured';
		}
		if ( $product->is_downloadable() ) {
			$classes[] = 'downloadable';
		}
		if ( $product->is_virtual() ) {
			$classes[] = 'virtual';
		}
		if ( $product->is_sold_individually() ) {
			$classes[] = 'sold-individually';
		}
		if ( $product->get_type() ) {
			$classes[] = 'product-type-' . $product->get_type();
		}

		$row_attributes = [
			'id'    => 'product-row-' . $id,
			'class' => implode( ' ', apply_filters( 'wc_product_table_row_class', $classes, $product ) )
		];

		return apply_filters( 'wc_product_table_row_attributes', $row_attributes, $product );
	}

	private function setup_postdata_for_product( $product ) {
		$product_post = get_post( $product->get_id() );

		// Set global post object, so that any code referring to 'global $post' (e.g. get_the_content) works correctly.
		$GLOBALS['post'] = $product_post;

		// Setup global post data (id, authordata, etc) and global product.
		setup_postdata( $product_post );
	}

	private static function array_filter_empty_attribute( $value ) {
		return ( '' === $value ) ? false : true;
	}

	private static function json_encode_config( $config ) {
		$json = wp_json_encode( $config );

		// Ensure Javascript functions are defined as a function, not a string.
		$json = preg_replace( '#"(jQuery\.fn.*)"#U', '$1', $json );

		return $json;
	}

}
