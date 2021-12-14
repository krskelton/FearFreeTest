<?php

namespace Barn2\Plugin\WC_Product_Table;

use Barn2\Plugin\WC_Product_Table\Util\Columns_Util;
use Barn2\WPT_Lib\Util as Lib_Util;
use WP_Query;

/**
 * Responsible for managing the product table query, retrieving the list of products (as an array of WP_Post objects), and finding the product totals.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Table_Query {

	public $args;

	private $products                = null;
	private $total_products          = null;
	private $total_filtered_products = null;

	/**
	 * Table_Query constructor.
	 *
	 * @param Table_Args $args
	 */
	public function __construct( Table_Args $args ) {
		$this->args = $args;
	}

	/**
	 * Get the list of products for this table query.
	 *
	 * @return array|null An array of WC_Product objects.
	 */
	public function get_products() {
		if ( is_array( $this->products ) ) {
			return $this->products;
		}

		// Build query args and retrieve the products for our table.
		$query = $this->run_product_query( $this->build_product_query() );

		// Convert posts to products and store the results.
		$products = ! empty( $query->posts ) ? array_filter( array_map( 'wc_get_product', $query->posts ) ) : [];
		$this->set_products( $products );

		return $this->products;
	}

	public function set_products( $products ) {
		if ( is_object( $products ) && isset( $products['products'] ) ) {
			// Support for wc_get_products function
			$products = $products['products'];
		} elseif ( ! is_array( $products ) ) {
			$products = null;
		}
		$this->products = $products;
	}

	public function get_total_products() {
		if ( is_numeric( $this->total_products ) ) {
			return $this->total_products;
		}

		$total = 0;

		if ( $this->args->search_term && $this->args->user_search_term ) {
			// If we have search term 'on load' and a user applied search, we set the total to match the filtered total to avoid a mismatch.
			$total = $this->get_total_filtered_products();
		} elseif ( -1 === $this->args->rows_per_page && is_array( $this->products ) ) {
			// If showing all products on a single page, the total is the count of products array.
			$total = count( $this->products );
		} else {
			$total_query = $this->run_product_query( $this->build_product_totals_query() );
			$total       = $total_query->post_count;
		}

		$this->total_products = $this->check_within_product_limit( $total );

		return $this->total_products;
	}

	public function set_total_products( $total_products ) {
		$this->total_products = $total_products;
	}

	public function get_total_filtered_products() {
		if ( is_numeric( $this->total_filtered_products ) ) {
			// If we've already calculated the filtered total.
			return $this->total_filtered_products;
		}

		$filtered_total = 0;

		if ( is_array( $this->products ) ) {
			// If we already have products, then this must be the filtered list, so return count of this array.
			$filtered_total = count( $this->products );
		} else {
			// Otherwise we need to calculate total by running a new query.
			$filtered_total_args  = $this->add_user_search_args( $this->build_product_totals_query() );
			$filtered_total_query = $this->run_product_query( $filtered_total_args );

			$filtered_total = $filtered_total_query->post_count;
		}

		$this->total_filtered_products = $this->check_within_product_limit( $filtered_total );

		return $this->total_filtered_products;
	}

	public function set_total_filtered_products( $total_filtered_products ) {
		$this->total_filtered_products = $total_filtered_products;
	}

	private function build_base_product_query() {
		$query_args = [
			'post_type'        => 'product',
			'post_status'      => $this->args->status,
			'tax_query'        => $this->build_tax_query(),
			'meta_query'       => $this->build_meta_query(),
			'year'             => $this->args->year,
			'monthnum'         => $this->args->month,
			'day'              => $this->args->day,
			'no_found_rows'    => true,
			'suppress_filters' => false // Ensure WC post filters run on this query
		];

		if ( $this->args->include ) {
			$query_args['post__in']            = $this->args->include;
			$query_args['ignore_sticky_posts'] = true;
		} elseif ( $this->args->exclude ) {
			$query_args['post__not_in'] = $this->args->exclude;
		}

		if ( $this->args->search_term ) {
			$query_args['s'] = $this->args->search_term;
		}

		if ( $this->args->user_products ) {
			$query_args['post__in'] = $this->get_user_products();
		}

		$query_args = $this->append_ordering_args( $query_args );

		return $query_args;
	}

	private function build_product_query() {
		$query_args = $this->add_user_search_args( $this->build_base_product_query() );

		if ( $this->args->lazy_load ) {
			$query_args['posts_per_page'] = $this->check_within_product_limit( $this->args->rows_per_page );
			$query_args['offset']         = $this->args->offset;
		} else {
			$query_args['posts_per_page'] = $this->args->product_limit;
		}

		return apply_filters( 'wc_product_table_query_args', $query_args, $this );
	}

	private function build_product_totals_query() {
		$query_args                   = $this->build_base_product_query();
		$query_args['offset']         = 0;
		$query_args['posts_per_page'] = -1;
		$query_args['fields']         = 'ids';

		return apply_filters( 'wc_product_table_query_args', $query_args, $this );
	}

	private function build_tax_query() {
		$tax_query = [];

		if ( method_exists( WC()->query, 'get_tax_query' ) ) {
			$tax_query = WC()->query->get_tax_query( $tax_query, true );
		}

		// Category handling.
		if ( $this->args->category ) {
			$tax_query[] = $this->tax_query_item( $this->args->category, 'product_cat' );
		}
		if ( $this->args->exclude_category ) {
			$tax_query[] = $this->tax_query_item( $this->args->exclude_category, 'product_cat', 'NOT IN' );
		}

		// Tag handling.
		if ( $this->args->tag ) {
			$tax_query[] = $this->tax_query_item( $this->args->tag, 'product_tag' );
		}

		// Custom taxonomy/term handling.
		if ( $this->args->term ) {
			$term_query    = [];
			$relation      = 'OR';
			$term_taxonomy = false;

			if ( false !== strpos( $this->args->term, '+' ) ) {
				$term_array = explode( '+', $this->args->term );
				$relation   = 'AND';
			} else {
				$term_array = explode( ',', $this->args->term );
			}

			// Custom terms are in format <taxonomy>:<term slug or id> or a list using just one taxonomy, e.g. product_cat:term1,term2.
			foreach ( $term_array as $term ) {
				if ( '' === $term ) {
					continue;
				}
				// Split term around the colon and check valid
				$term_split = explode( ':', $term, 2 );

				if ( 1 === count( $term_split ) ) {
					if ( ! $term_taxonomy ) {
						continue;
					}
					$term = $term_split[0];
				} elseif ( 2 === count( $term_split ) ) {
					$term          = $term_split[1];
					$term_taxonomy = $term_split[0];
				}
				$term_query[] = $this->tax_query_item( $term, $term_taxonomy );
			}

			$term_query = $this->maybe_add_relation( $term_query, $relation );

			// If no tax query, set the whole tax query to the custom terms query, otherwise append terms as inner query.
			if ( empty( $tax_query ) ) {
				$tax_query = $term_query;
			} else {
				$tax_query[] = $term_query;
			}
		}

		return apply_filters( 'wc_product_table_tax_query', $this->maybe_add_relation( $tax_query ), $this );
	}

	private function add_user_search_args( array $query_args ) {
		if ( ! empty( $this->args->search_filters ) ) {
			$query_args['tax_query'] = $this->build_search_filters_tax_query( $query_args['tax_query'] );
		}

		if ( ! empty( $this->args->user_search_term ) ) {
			$query_args['s'] = $this->args->user_search_term;
		}

		return $query_args;
	}

	private function build_search_filters_tax_query( $tax_query = [] ) {
		if ( ! is_array( $tax_query ) ) {
			$tax_query = [];
		}

		if ( empty( $this->args->search_filters ) ) {
			return $tax_query;
		}

		$search_filters_query = [];

		// Add tax queries for search filter drop-downs.
		foreach ( $this->args->search_filters as $taxonomy => $term ) {
			// Search filters always use term IDs
			$search_filters_query[] = $this->tax_query_item( $term, $taxonomy, 'IN', 'term_id' );
		}

		$search_filters_query = $this->maybe_add_relation( $search_filters_query );

		// If no tax query, set the whole tax query to the filters query, otherwise append filters as inner query
		if ( empty( $tax_query ) ) {
			// If no tax query, set the whole tax query to the filters query.
			$tax_query = $search_filters_query;
		} elseif ( isset( $tax_query['relation'] ) && 'OR' === $tax_query['relation'] ) {
			// If tax query is an OR, nest it with the search filters query and join with AND.
			$tax_query = [
				$tax_query,
				$search_filters_query,
				'relation' => 'AND'
			];
		} else {
			// Otherwise append search filters and ensure it's AND.
			$tax_query[]           = $search_filters_query;
			$tax_query['relation'] = 'AND';
		}

		return $tax_query;
	}

	private function get_user_products() {
		// Retrieve the current user's orders
		$order_args = [
			'customer_id' => get_current_user_id(),
			'limit'       => 500,
		];

		$orders = wc_get_orders( apply_filters( 'wc_product_table_user_products_query_args', $order_args ) );

		// Loop through the orders and retrieve the product IDs
		$product_ids = [];

		foreach ( $orders as $order ) {
			$products = $order->get_items();

			foreach ( $products as $product ) {
				$product_id    = $product->get_product_id();
				$product_ids[] = $product_id;
			}

			$product_ids = array_unique( $product_ids );

			// Quit checking orders if the product limit is reached
			if ( $this->args->product_limit > 0 && count( $product_ids ) >= $this->args->product_limit ) {
				break;
			}
		}

		// Prevent all products from being displayed if no user products
		if ( empty( $product_ids ) ) {
			$product_ids = [ 0 ];
		}

		return $product_ids;
	}

	private function tax_query_item( $terms, $taxonomy, $operator = 'IN', $field = '' ) {
		$and_relation = 'AND' === $operator;

		if ( ! is_array( $terms ) ) {
			// Comma-delimited list = OR, plus-delimited list = AND
			if ( false !== strpos( $terms, '+' ) ) {
				$terms        = explode( '+', $terms );
				$and_relation = true;
			} else {
				$terms = explode( ',', $terms );
			}
		}

		// If no field provided, work out whether we have term slugs or ids.
		if ( ! $field ) {
			$using_term_ids = count( $terms ) === count( array_filter( $terms, 'is_numeric' ) );
			$field          = $using_term_ids && ! $this->args->numeric_terms ? 'term_id' : 'slug';
		}

		// There's a strange bug when using 'operator' => 'AND' for individual tax queries.
		// So we need to split these into separate 'IN' arrays joined by and outer relation => 'AND'
		if ( $and_relation && count( $terms ) > 1 ) {
			$result = [ 'relation' => 'AND' ];

			foreach ( $terms as $term ) {
				$result[] = [
					'taxonomy' => $taxonomy,
					'terms'    => $term,
					'operator' => 'IN',
					'field'    => $field
				];
			}

			return $result;
		} else {
			return [
				'taxonomy' => $taxonomy,
				'terms'    => $terms,
				'operator' => $operator,
				'field'    => $field
			];
		}
	}

	private function build_meta_query() {
		// First, build the WooCommerce meta query.
		$meta_query = WC()->query->get_meta_query();

		if ( $this->args->cf ) {
			$custom_field_query = [];
			$relation           = 'OR';

			// Comma-delimited = OR, plus-delimited = AND.
			if ( false !== strpos( $this->args->cf, '+' ) ) {
				$field_array = explode( '+', $this->args->cf );
				$relation    = 'AND';
			} else {
				$field_array = explode( ',', $this->args->cf );
			}

			// Custom fields are in format <field_key>:<field_value>
			foreach ( $field_array as $field ) {
				// Split custom field around the colon and check valid
				$field_split = explode( ':', $field, 2 );

				if ( 2 === count( $field_split ) ) {
					// We have a field key and value
					$field_key   = $field_split[0];
					$field_value = $field_split[1];
					$compare     = '=';

					// If we're selecting based on an ACF field, field value could be stored as an array, so use RLIKE with a test for serialized array pattern
					if ( Lib_Util::is_acf_active() ) {
						$compare     = 'REGEXP';
						$field_value = sprintf( '^%1$s$|s:%2$u:"%1$s";', $field_value, strlen( $field_value ) );
					}

					$custom_field_query[] = [
						'key'     => $field_key,
						'value'   => $field_value,
						'compare' => $compare
					];
				} elseif ( 1 === count( $field_split ) ) {
					// Field key only, so do an 'exists' check instead
					$custom_field_query[] = [
						'key'     => $field_split[0],
						'compare' => 'EXISTS'
					];
				}
			}

			if ( 0 < count( $custom_field_query ) ) {
				// If only one CF query, we can use as a top-level meta query, otherwise we need to add a relation.
				if ( 1 === count( $custom_field_query ) ) {
					$custom_field_query = reset( $custom_field_query );
				} else {
					$custom_field_query = $this->maybe_add_relation( $custom_field_query, $relation );
				}

				$meta_query['product_table'] = $custom_field_query;
			}
		} // if $this->args->cf

		// Are we sorting by custom field? If so, we add a custom order clause.
		if ( Columns_Util::is_custom_field( $this->args->sort_by ) ) {
			$field = Columns_Util::get_custom_field( $this->args->sort_by );
			$type  = in_array( 'cf:' . $field, $this->args->date_columns, true ) ? 'DATE' : 'CHAR';

			$meta_query['product_table_order_clause'] = [
				'key'  => $field,
				'type' => apply_filters( 'wc_product_table_sort_by_custom_field_type', $type, $field )
			];
		} elseif ( 'sku' === $this->args->sort_by ) {
			// Sort by SKU.
			$numeric_skus    = apply_filters( 'wc_product_table_use_numeric_skus', false );
			$order_by_clause = [
				'key'  => '_sku',
				'type' => $numeric_skus ? 'NUMERIC' : 'CHAR'
			];

			if ( $numeric_skus ) {
				$order_by_clause['value']   = 0;
				$order_by_clause['compare'] = '>=';
			}

			$meta_query['product_table_order_clause'] = $order_by_clause;
		}

		return apply_filters( 'wc_product_table_meta_query', $this->maybe_add_relation( $meta_query ), $this );
	}

	/**
	 * Add the ordering args for our product query.
	 * Note: for standard loading, DataTables will re-sort the results if the sort column is present in table.
	 *
	 * @param array $query_args The query args.
	 * @return array The updated query args.
	 */
	private function append_ordering_args( $query_args ) {
		$order   = strtoupper( $this->args->sort_order );
		$orderby = $this->args->sort_by;

		if ( ! empty( $query_args['meta_query']['product_table_order_clause'] ) ) {
			// Use named order clause if we have one.
			$query_args['orderby'] = 'product_table_order_clause';
			$query_args['order']   = $order;
		} else {
			// Replace column name with correct sort_by item used by WP_Query.
			if ( in_array( $orderby, [ 'name', 'reviews' ], true ) ) {
				$orderby = str_replace( [ 'name', 'reviews' ], [ 'title', 'rating' ], $orderby );
			}

			// Bail if we don't have a valid orderby arg.
			// Note! Custom field and SKU sorting is handled by build_meta_query().
			if ( ! in_array( $orderby, [ 'id', 'title', 'menu_order', 'rand', 'relevance', 'price', 'popularity', 'rating', 'date', 'modified' ], true ) ) {
				return $query_args;
			}

			// Use WC to get standard ordering args and add extra query filters.
			$wc_ordering = WC()->query->get_catalog_ordering_args( $orderby, $order );

			// Additional orderby options.
			if ( 'modified' === $orderby ) {
				$wc_ordering['orderby'] = 'modified ID';
			}

			if ( empty( $wc_ordering['meta_key'] ) ) {
				unset( $wc_ordering['meta_key'] );
			}

			$query_args = array_merge( $query_args, $wc_ordering );
		}

		return $query_args;
	}

	private function maybe_add_relation( $query, $relation = 'AND' ) {
		if ( count( $query ) > 1 && empty( $query['relation'] ) ) {
			$query['relation'] = $relation;
		}

		return $query;
	}

	private function run_product_query( $query_args ) {
		$query_hooks = new Query_Hooks( $this->args );
		$query_hooks->register();

		do_action( 'wc_product_table_before_product_query', $this );

		// @todo: Use 'wc_get_products' instead of WP_Query. We can't yet as price filter widget and other meta queries are not passed through.
		// Run the product query.
		$query = new WP_Query( $query_args );

		$query_hooks->reset();

		// We call WC()->query->get_catalogue_ordering_args() while building our product query, which adds various filters.
		// These can interfere with any subsequent queries while building table data, so we need to remove them.
		WC()->query->remove_ordering_args();

		do_action( 'wc_product_table_after_product_query', $this );

		return $query;
	}

	private function check_within_product_limit( $count ) {
		return is_int( $this->args->product_limit ) && $this->args->product_limit > 0 ? min( $this->args->product_limit, $count ) : $count;
	}

}
