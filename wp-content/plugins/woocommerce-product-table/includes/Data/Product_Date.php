<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Util;

/**
 * Gets data for the date column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Date extends Abstract_Product_Data {

	private $date_format;

	public function __construct( $product, $date_format ) {
		parent::__construct( $product );

		$this->date_format = $date_format;
	}

	public function get_data() {
		$date = Util::empty_if_false( get_the_date( $this->date_format, $this->get_parent_product_id() ) );
		return apply_filters( 'wc_product_table_data_date', $date, $this->product );
	}

	public function get_sort_data() {
		return get_the_date( 'U', $this->get_parent_product_id() );
	}

}
