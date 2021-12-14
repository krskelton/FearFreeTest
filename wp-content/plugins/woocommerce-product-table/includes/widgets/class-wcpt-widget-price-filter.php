<?php

use Barn2\Plugin\WC_Product_Table\Util\Util;

if ( class_exists( 'WC_Product_Table_Widget' ) ) {

	/**
	 * Product Table implementation of WooCommerce Price Filter Widget.
	 *
	 * Based on version 2.3.0 of the WC_Widget_Price_Filter class.
	 *
	 * @package   Barn2\woocommerce-product-table
	 * @author    Barn2 Plugins <support@barn2.com>
	 * @license   GPL-3.0
	 * @copyright Barn2 Media Ltd
	 */
	class WC_Product_Table_Widget_Price_Filter extends WC_Product_Table_Widget {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->widget_cssclass    = 'woocommerce widget_price_filter';
			$this->widget_description = __( 'Display a slider to filter your product table by price.', 'woocommerce-product-table' );
			$this->widget_id          = 'woocommerce_pt_price_filter';
			$this->widget_name        = __( 'Product Table: Filter by Price', 'woocommerce-product-table' );
			$this->settings           = [
				'title' => [
					'type'  => 'text',
					'std'   => __( 'Filter by price', 'woocommerce-product-table' ),
					'label' => __( 'Title', 'woocommerce-product-table' ),
				],
			];
			$suffix                   = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			if ( ! wp_script_is( 'accounting', 'registered' ) ) {
				wp_register_script( 'accounting', WC()->plugin_url() . '/assets/js/accounting/accounting' . $suffix . '.js', [ 'jquery' ], '0.4.2' );
			}

			if ( ! wp_script_is( 'wc-jquery-ui-touchpunch', 'registered' ) ) {
				wp_register_script(
					'wc-jquery-ui-touchpunch',
					WC()->plugin_url() . '/assets/js/jquery-ui-touch-punch/jquery-ui-touch-punch' . $suffix . '.js',
					[ 'jquery-ui-slider' ],
					WC_VERSION,
					true
				);
			}

			if ( ! wp_script_is( 'wc-jquery-ui-touchpunch', 'registered' ) ) {
				wp_register_script(
					'wc-price-slider',
					WC()->plugin_url() . '/assets/js/frontend/price-slider' . $suffix . '.js',
					[
						'jquery-ui-slider',
						'wc-jquery-ui-touchpunch',
						'accounting'
					],
					WC_VERSION,
					true
				);

				wp_add_inline_script(
					'wc-price-slider',
					sprintf(
						'var woocommerce_price_slider_params = %s;',
						wp_json_encode(
							[
								'currency_format_num_decimals' => 0,
								'currency_format_symbol' => get_woocommerce_currency_symbol(),
								'currency_format_decimal_sep' => esc_attr( wc_get_price_decimal_separator() ),
								'currency_format_thousand_sep' => esc_attr( wc_get_price_thousand_separator() ),
								'currency_format'        => esc_attr( str_replace( [ '%1$s', '%2$s' ], [ '%s', '%v' ], get_woocommerce_price_format() ) ),
							]
						)
					),
					'before'
				);
			}

			parent::__construct();
		}

		/**
		 * Output widget.
		 *
		 * @param array $args
		 * @param array $instance
		 * @see WP_Widget
		 */
		public function widget( $args, $instance ) {
			global $wp;

			if ( ! Util::is_table_on_page() || ! is_singular() ) {
				return;
			}

			wp_enqueue_script( 'wc-price-slider' );

			// Find min and max price in current result set
			$prices = $this->get_filtered_price();
			$min    = floor( $prices->min_price );
			$max    = ceil( $prices->max_price );
			if ( $min === $max ) {
				return;
			}

			$this->widget_start( $args, $instance );

			if ( '' === get_option( 'permalink_structure' ) ) {
				$form_action = remove_query_arg( [ 'page', 'paged' ], add_query_arg( $wp->query_string, '', home_url( $wp->request ) ) );
			} else {
				$form_action = preg_replace( '%\/page/[0-9]+%', '', home_url( trailingslashit( $wp->request ) ) );
			}

			$min_price = isset( $_GET['min_price'] ) ? esc_attr( $_GET['min_price'] ) : apply_filters( 'woocommerce_price_filter_widget_min_amount', $min );
			$max_price = isset( $_GET['max_price'] ) ? esc_attr( $_GET['max_price'] ) : apply_filters( 'woocommerce_price_filter_widget_max_amount', $max );

			echo '<form method="get" action="' . esc_url( $form_action ) . '">
				<div class="price_slider_wrapper">
					<div class="price_slider" style="display:none;"></div>
					<div class="price_slider_amount">
						<input type="text" id="min_price" name="min_price" value="' . esc_attr( $min_price ) . '" data-min="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_min_amount', $min ) ) . '" placeholder="' . esc_attr__( 'Min price', 'woocommerce-product-table' ) . '" />
						<input type="text" id="max_price" name="max_price" value="' . esc_attr( $max_price ) . '" data-max="' . esc_attr( apply_filters( 'woocommerce_price_filter_widget_max_amount', $max ) ) . '" placeholder="' . esc_attr__( 'Max price', 'woocommerce-product-table' ) . '" />
						<button type="submit" class="button">' . esc_html__( 'Filter', 'woocommerce-product-table' ) . '</button>
						<div class="price_label" style="display:none;">
							' . esc_html__( 'Price:', 'woocommerce-product-table' ) . ' <span class="from"></span> &mdash; <span class="to"></span>
						</div>
						' . wc_query_string_form_fields( null, [ 'min_price', 'max_price' ], '', true ) . '
						<div class="clear"></div>
					</div>
				</div>
			</form>';

			$this->widget_end( $args );
		}

		/**
		 * Get filtered min price for current products.
		 *
		 * @return object
		 */
		protected function get_filtered_price() {
			global $wpdb;

			$tax_query  = parent::get_main_tax_query();
			$meta_query = parent::get_main_meta_query();

			if ( ! empty( $args['taxonomy'] ) && ! empty( $args['term'] ) ) {
				$tax_query[] = [
					'taxonomy' => $args['taxonomy'],
					'terms'    => [ $args['term'] ],
					'field'    => 'slug',
				];
			}

			foreach ( $meta_query + $tax_query as $key => $query ) {
				if ( ! empty( $query['price_filter'] ) || ! empty( $query['rating_filter'] ) ) {
					unset( $meta_query[ $key ] );
				}
			}

			$meta_query = new WP_Meta_Query( $meta_query );
			$tax_query  = new WP_Tax_Query( $tax_query );

			$meta_query_sql = $meta_query->get_sql( 'post', $wpdb->posts, 'ID' );
			$tax_query_sql  = $tax_query->get_sql( $wpdb->posts, 'ID' );

			$post_type_list = implode(
				"','",
				array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_post_type', [ 'product' ] ) )
			);

			$sql  = "SELECT min( FLOOR( price_meta.meta_value ) ) as min_price, max( CEILING( price_meta.meta_value ) ) as max_price FROM {$wpdb->posts} ";
			$sql .= " LEFT JOIN {$wpdb->postmeta} as price_meta ON {$wpdb->posts}.ID = price_meta.post_id " . $tax_query_sql['join'] . $meta_query_sql['join'];
			$sql .= " 	WHERE {$wpdb->posts}.post_type IN ('" . $post_type_list . "')
			  AND {$wpdb->posts}.post_status = 'publish'
			  AND price_meta.meta_key IN ('" . implode( "','", array_map( 'esc_sql', apply_filters( 'woocommerce_price_filter_meta_keys', [ '_price' ] ) ) ) . "')
			  AND price_meta.meta_value > '' ";
			$sql .= $tax_query_sql['where'] . $meta_query_sql['where'];

			return $wpdb->get_row( $sql );
		}

	}

} // if WC_Product_Table_Widget
