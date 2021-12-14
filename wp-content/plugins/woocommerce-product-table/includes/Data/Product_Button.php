<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Util;

/**
 * Gets data for the button column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Button extends Abstract_Product_Data {

	private $button_text;

	public function __construct( $product, $button_text ) {
		parent::__construct( $product );

		$this->button_text = $button_text;
	}

	public function get_data() {
		$button_class = trim( 'product-details-button ' . Util::get_button_class() );

		/* @deprecated 2.7.1 - Will be removed in a later version */
		if ( apply_filters( 'wc_product_table_wrap_button_text', false ) ) {
			$button_class .= ' wrap';
		}

		$button = Util::format_product_link( $this->product, $this->button_text, $button_class );

		return apply_filters( 'wc_product_table_data_button', $button, $this->product );
	}

}
