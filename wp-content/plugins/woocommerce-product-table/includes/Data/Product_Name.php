<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Util;

/**
 * Gets data for the name column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Name extends Abstract_Product_Data {

	public function get_data() {
		$name = Util::get_product_name( $this->product );

		if ( array_intersect( [ 'all', 'name' ], $this->links ) ) {
			$name = Util::format_product_link( $this->product, $name );
		}

		return apply_filters( 'wc_product_table_data_name', $name, $this->product );
	}

}
