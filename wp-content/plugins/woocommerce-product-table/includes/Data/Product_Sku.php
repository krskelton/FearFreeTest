<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Util;

/**
 * Gets data for the SKU column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Sku extends Abstract_Product_Data {

	public function get_data() {
		$sku = $this->product->get_sku();

		if ( $sku && array_intersect( [ 'all', 'sku' ], $this->links ) ) {
			$sku = Util::format_product_link( $this->product, $sku );
		}
		return apply_filters( 'wc_product_table_data_sku', $sku, $this->product );
	}

}
