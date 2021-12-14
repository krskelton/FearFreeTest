<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

/**
 * Gets data for the stock column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Stock extends Abstract_Product_Data {

	public function get_data() {
		$availability = $this->product->get_availability();

		if ( empty( $availability['availability'] ) && $this->product->is_in_stock() ) {
			$availability['availability'] = __( 'In stock', 'woocommerce-product-table' );
		}
		$stock = '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . $availability['availability'] . '</p>';

		return apply_filters( 'wc_product_table_data_stock', $stock, $this->product );
	}

}
