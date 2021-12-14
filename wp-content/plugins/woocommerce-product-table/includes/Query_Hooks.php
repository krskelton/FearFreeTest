<?php

namespace Barn2\Plugin\WC_Product_Table;

use WC_Tax;
use WP_Scoped_Hooks;

/**
 * Handles the scoped hooks (actions and filters) which are used during the product table query.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Query_Hooks {

	private $args;
	private $scoped_hooks;

	public function __construct( Table_Args $args ) {
		$this->args         = $args;
		$this->scoped_hooks = new WP_Scoped_Hooks();
	}

	public function register() {
		$this->setup_hooks();
		$this->scoped_hooks->register();
	}

	public function reset() {
		$this->scoped_hooks->reset();
	}

	private function setup_hooks() {
		$this->reset();

		// Query optimisations.
		if ( apply_filters( 'wc_product_table_optimize_table_query', true, $this->args ) ) {
			$this->scoped_hooks->add_filter( 'posts_fields', [ $this, 'filter_wp_posts_selected_columns' ], 10, 2 );
		}

		// Adjust the meta query SQL for SKU searching using lazy load.
		if ( $this->is_lazy_load_search_by_sku() ) {
			$this->scoped_hooks->add_filter( 'posts_search', [ $this, 'search_by_sku_posts_search' ], 10, 2 );
			$this->scoped_hooks->add_filter( 'posts_clauses', [ $this, 'search_by_sku_posts_clauses' ], 10, 2 );
		}

		// Post clauses for price filter widget.
		$this->scoped_hooks->add_filter( 'posts_clauses', [ $this, 'price_filter_post_clauses' ], 10, 2 );
	}

	/**
	 * Removes unnecessary columns from the table query if we're not displayed description or short-description.
	 */
	public function filter_wp_posts_selected_columns( $fields, $query ) {
		global $wpdb;

		if ( "{$wpdb->posts}.*" !== $fields ) {
			return $fields;
		}

		if ( array_diff( [ 'description', 'short-description' ], $this->args->columns ) ) {
			$posts_columns = [
				'ID',
				'post_author',
				'post_date',
				'post_date_gmt',
				'post_title',
				'post_status',
				'comment_status',
				'ping_status',
				'post_password',
				'post_name',
				'to_ping',
				'pinged',
				'post_modified',
				'post_modified_gmt',
				'post_content_filtered',
				'post_parent',
				'guid',
				'menu_order',
				'post_type',
				'post_mime_type',
				'comment_count'
			];

			if ( in_array( 'description', $this->args->columns ) ) {
				$posts_columns[] = 'post_content';
			}
			if ( in_array( 'short-description', $this->args->columns ) ) {
				$posts_columns[] = 'post_excerpt';
				// We need the content as well, in case we need to auto-generate the excerpt from the content
				$posts_columns[] = 'post_content';
			}

			$fields = sprintf( implode( ', ', array_map( [ self::class, 'array_map_prefix_column' ], $posts_columns ) ), $wpdb->posts );
		}

		return $fields;
	}

	public function search_by_sku_posts_search( $search, $query ) {
		global $wpdb;

		if ( empty( $this->args->search_term ) && empty( $this->args->user_search_term ) ) {
			return $search;
		}

		// A user search (i.e. via the search box) takes precedence over search term used on load.
		$search_term = ! empty( $this->args->user_search_term ) ? $this->args->user_search_term : $this->args->search_term;

		// Build SKU where clause.
		$sku_like  = '%' . $wpdb->esc_like( $search_term ) . '%';
		$sku_like  = $wpdb->prepare( '%s', $sku_like );
		$sku_where = "( wpt1.meta_key = '_sku' AND wpt1.meta_value LIKE $sku_like )";

		// Perform a match on the search SQL so we can inject our SKU meta query into it.
		$matches = [];

		if ( preg_match( "/^ AND \((.+)\) ( AND \({$wpdb->posts}.post_password = ''\) )?$/U", $search, $matches ) ) {
			$search = ' AND (' . $sku_where . ' OR (' . $matches[1] . ')) ';

			// Add the post_password = '' clause if found.
			if ( isset( $matches[2] ) ) {
				$search .= $matches[2];
			}
		}

		return $search;
	}

	public function search_by_sku_posts_clauses( $clauses, $query ) {
		global $wpdb;

		// Add the meta query groupby clause.
		if ( empty( $clauses['groupby'] ) ) {
			$clauses['groupby'] = "{$wpdb->posts}.ID";
		}

		// Add our meta query join. We always need to do a separate join as other post meta joins may be present.
		$clauses['join'] .= " INNER JOIN {$wpdb->postmeta} AS wpt1 ON ( {$wpdb->posts}.ID = wpt1.post_id )";

		return $clauses;
	}

	public function price_filter_post_clauses( $args, $wp_query ) {
		global $wpdb;

		// Requires lookup table added in 3.6.
		if ( version_compare( get_option( 'woocommerce_db_version', null ), '3.6', '<' ) ) {
			return $args;
		}

		if ( ! isset( $_GET['max_price'] ) && ! isset( $_GET['min_price'] ) ) {
			return $args;
		}

		$current_min_price = isset( $_GET['min_price'] ) ? floatval( wp_unslash( $_GET['min_price'] ) ) : 0; // WPCS: input var ok, CSRF ok.
		$current_max_price = isset( $_GET['max_price'] ) ? floatval( wp_unslash( $_GET['max_price'] ) ) : PHP_INT_MAX; // WPCS: input var ok, CSRF ok.

		/**
		 * Adjust if the store taxes are not displayed how they are stored.
		 * Kicks in when prices excluding tax are displayed including tax.
		 */
		if ( wc_tax_enabled() && 'incl' === get_option( 'woocommerce_tax_display_shop' ) && ! wc_prices_include_tax() ) {
			$tax_class = apply_filters( 'woocommerce_price_filter_widget_tax_class', '' ); // Uses standard tax class.
			$tax_rates = WC_Tax::get_rates( $tax_class );

			if ( $tax_rates ) {
				$current_min_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_min_price, $tax_rates ) );
				$current_max_price -= WC_Tax::get_tax_total( WC_Tax::calc_inclusive_tax( $current_max_price, $tax_rates ) );
			}
		}

		$args['join']  = $this->append_product_sorting_table_join( $args['join'] );
		$args['where'] .= $wpdb->prepare(
			' AND wc_product_meta_lookup.min_price >= %f AND wc_product_meta_lookup.max_price <= %f ',
			$current_min_price,
			$current_max_price
		);
		return $args;
	}

	/**
	 * Join wc_product_meta_lookup to posts if not already joined.
	 *
	 * @param string $sql SQL join.
	 * @return string
	 */
	private function append_product_sorting_table_join( $sql ) {
		global $wpdb;

		if ( ! strstr( $sql, 'wc_product_meta_lookup' ) ) {
			$sql .= " LEFT JOIN {$wpdb->wc_product_meta_lookup} wc_product_meta_lookup ON $wpdb->posts.ID = wc_product_meta_lookup.product_id ";
		}
		return $sql;
	}

	private function is_lazy_load_search_by_sku() {
		return apply_filters( 'wc_product_table_enable_lazy_load_sku_search', true ) && $this->args->lazy_load && ( $this->args->search_term || $this->args->user_search_term );
	}

	private static function array_map_prefix_column( $n ) {
		return '%1$s.' . $n;
	}

}
