<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Util;

/**
 * Gets data for the ID column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Id extends Abstract_Product_Data {

	public function get_data() {
		$id = $this->get_product_id();

		if ( array_intersect( [ 'all', 'id' ], $this->links ) ) {
			$id = Util::format_product_link( $this->product, $id );
		}

		return apply_filters( 'wc_product_table_data_id', $id, $this->product );
	}

	public function get_sort_data() {
		return $this->get_product_id();
	}

}
