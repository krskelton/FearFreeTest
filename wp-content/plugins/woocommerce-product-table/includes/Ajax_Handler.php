<?php

namespace Barn2\Plugin\WC_Product_Table;

use Barn2\Plugin\WC_Product_Table\Util\Columns_Util;
use Barn2\Plugin\WC_Product_Table\Util\Util;
use Barn2\WPT_Lib\Conditional;
use Barn2\WPT_Lib\Registerable;
use Barn2\WPT_Lib\Service;
use Barn2\WPT_Lib\Util as Lib_Util;
use WP_Term;

/**
 * Handles the AJAX requests for product tables.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Ajax_Handler implements Service, Registerable, Conditional {

	public function is_required() {
		return Lib_Util::is_front_end();
	}

	public function register() {
		$ajax_events = [
			'wcpt_load_products'     => 'load_products',
			'wcpt_add_to_cart'       => 'add_to_cart',
			'wcpt_add_to_cart_multi' => 'add_to_cart_multi'
		];

		foreach ( $ajax_events as $action => $handler ) {
			add_action( 'wp_ajax_nopriv_' . $action, [ $this, $handler ] );
			add_action( 'wp_ajax_' . $action, [ $this, $handler ] );
		}
	}

	public function load_products() {
		$table_id = filter_input( INPUT_POST, 'table_id', FILTER_SANITIZE_STRING );
		$table    = Table_Factory::fetch( $table_id );

		if ( ! $table ) {
			wp_die( 'Error: product table could not be loaded.' );
		}

		// Build the args to update
		$new_args                  = [];
		$new_args['rows_per_page'] = filter_input( INPUT_POST, 'length', FILTER_VALIDATE_INT );
		$new_args['offset']        = filter_input( INPUT_POST, 'start', FILTER_VALIDATE_INT );

		$columns    = filter_input( INPUT_POST, 'columns', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$search     = filter_input( INPUT_POST, 'search', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$order      = filter_input( INPUT_POST, 'order', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$main_order = ! empty( $order[0] ) ? $order[0] : [];

		// Set sort column and direction
		if ( isset( $main_order['column'] ) ) {
			$order_col_index = filter_var( $main_order['column'], FILTER_VALIDATE_INT );

			if ( false !== $order_col_index && isset( $columns[ $order_col_index ]['data'] ) ) {
				$new_args['sort_by'] = filter_var( $columns[ $order_col_index ]['data'], FILTER_SANITIZE_STRING );
			}
			if ( ! empty( $main_order['dir'] ) ) {
				$new_args['sort_order'] = filter_var( $main_order['dir'], FILTER_SANITIZE_STRING );
			}
		}

		$new_args['user_search_term'] = '';
		$new_args['search_filters']   = [];

		// Set search term
		if ( ! empty( $search['value'] ) ) {
			$search_term = filter_var( $search['value'], FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES );

			// Don't search unless they've typed at least 3 characters.
			if ( Util::is_valid_search_term( $search_term ) ) {
				$new_args['user_search_term'] = $search_term;
			}
		}

		// Set search filters
		if ( ! empty( $columns ) ) {
			foreach ( $columns as $column ) {
				if ( empty( $column['data'] ) || empty( $column['search']['value'] ) ) {
					continue;
				}

				$column_name = Columns_Util::is_hidden_filter_column( $column['data'] ) ? Columns_Util::get_hidden_filter_column( $column['data'] ) : $column['data'];

				if ( $taxonomy = Columns_Util::get_column_taxonomy( $column_name ) ) {
					$term = get_term_by( 'slug', $column['search']['value'], $taxonomy );

					if ( $term instanceof WP_Term ) {
						$new_args['search_filters'][ $taxonomy ] = $term->term_id;
					}
				}
			}
		}

		// Merge layered nav params (if passed) into $_GET so WooCommerce picks them up.
		if ( $layered_nav_params = Util::get_layered_nav_params( true ) ) {
			$_GET = array_merge( $_GET, $layered_nav_params );
		}

		// Retrieve the new table and convert to array
		$table->update( $new_args );

		// Build output
		$output['draw']            = filter_input( INPUT_POST, 'draw', FILTER_VALIDATE_INT );
		$output['recordsFiltered'] = $table->query->get_total_filtered_products();
		$output['recordsTotal']    = $table->query->get_total_products();

		$table_data = $table->get_data( 'array' );
		$data       = [];

		if ( is_array( $table_data ) ) {
			// We don't need the cell attributes, so flatten data and append row attributes under the key '__attributes'.
			foreach ( $table_data as $row ) {
				$data[] = array_merge( [
					'__attributes' => $row['attributes']
				], wp_list_pluck( $row['cells'], 'data' )
				);
			}
		}

		$output['data'] = $data;

		wp_send_json( $output );
	}

	public function add_to_cart() {
		ob_start();

		$product_id   = apply_filters( 'woocommerce_add_to_cart_product_id', filter_input( INPUT_POST, 'product_id', FILTER_VALIDATE_INT ) );
		$quantity     = filter_input( INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT, [ 'options' => [ 'default' => 1, 'min_range' => 0 ] ] );
		$variation_id = filter_input( INPUT_POST, 'variation_id', FILTER_VALIDATE_INT );
		$variations   = $variation_id ? Util::extract_attributes( $_POST ) : false;

		if ( Cart_Handler::add_to_cart( $product_id, $quantity, $variation_id, $variations ) ) {

			do_action( 'woocommerce_ajax_added_to_cart', $product_id );

			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				wc_add_to_cart_message( [ $product_id => $quantity ], true );
			}

			// Return fragments
			$data = $this->get_refreshed_fragments();
		} else {
			// If there was an error adding to the cart
			$data = [
				'error'         => true,
				'error_message' => $this->format_errors( false )
			];
		}

		wp_send_json( $data );
	}

	public function add_to_cart_multi() {
		ob_start();

		$products     = Cart_Handler::get_multi_cart_data();
		$cart_message = '';

		if ( $added = Cart_Handler::add_to_cart_multi( $products ) ) {
			foreach ( $added as $product_id => $quantity ) {
				do_action( 'woocommerce_ajax_added_to_cart', $product_id );
			}

			// Return fragments
			$data = $this->get_refreshed_fragments();

			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				wc_add_to_cart_message( $added, true );
			} else {
				$view_cart_link = sprintf( '<a href="%s" class="added_to_cart wc-forward">%s</a>', esc_url( wc_get_page_permalink( 'cart' ) ), esc_html__( 'View Cart', 'woocommerce-product-table' ) );
				$cart_message   .= sprintf( '<p class="cart-success">%s</p>%s', wc_add_to_cart_message( $added, true, true ), $view_cart_link );

				// Clear any errors which were added for products which couldn't be added.
				wc_clear_notices();
			}

			if ( $cart_message ) {
				$data['cart_message'] = $cart_message;
			}
		} else {
			// If there was an error adding to the cart
			$data = [
				'error'         => true,
				'error_message' => $this->format_errors()
			];
		}

		wp_send_json( $data );
	}

	private function format_errors( $show_all_errors = true ) {
		$errors = wc_get_notices( 'error' );

		if ( ! $errors ) {
			$errors = [ __( 'There was an error adding to the cart. Please try again.', 'woocommerce-product-table' ) ];
		}

		$result    = '';
		$error_fmt = apply_filters( 'wc_product_table_cart_error_format', '<p class="cart-error">%s</p>' );

		if ( ! apply_filters( 'wc_product_table_show_all_cart_errors', $show_all_errors ) ) {
			$errors = [ $errors[0] ];
		}

		foreach ( $errors as $error ) {
			$notice_text = isset( $error['notice'] ) ? $error['notice'] : $error;
			$result      .= sprintf( $error_fmt, $notice_text );
		}

		wc_clear_notices();
		return $result;
	}

	private function get_refreshed_fragments() {
		// Get mini cart
		ob_start();

		woocommerce_mini_cart();

		$mini_cart    = ob_get_clean();
		$cart_session = \WC()->cart->get_cart_for_session();

		// Fragments and mini cart are returned
		$data = [
			'fragments' => apply_filters( 'woocommerce_add_to_cart_fragments', [
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>'
				]
			),
			'cart_hash' => apply_filters( 'woocommerce_add_to_cart_hash', $cart_session ? md5( json_encode( $cart_session ) ) : '', $cart_session )
		];

		return $data;
	}

}
