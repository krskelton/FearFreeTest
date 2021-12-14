<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

/**
 * Gets data for the dimensions column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Dimensions extends Abstract_Product_Data {

	public function get_data() {
		$dimensions = $this->product->has_dimensions() ? wc_format_dimensions( $this->product->get_dimensions( false ) ) : '';
		return apply_filters( 'wc_product_table_data_dimensions', $dimensions, $this->product );
	}

}
