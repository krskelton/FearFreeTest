<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

/**
 * Gets data for the price column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Price extends Abstract_Product_Data {

	public function get_data() {
		return apply_filters( 'wc_product_table_data_price', $this->product->get_price_html(), $this->product );
	}

	public function get_sort_data() {
		$price = floatval( $this->product->get_price() );
		return $price ? number_format( $price, 2, '.', '' ) : '0.00';
	}

}
