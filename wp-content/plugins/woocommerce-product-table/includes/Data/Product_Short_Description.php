<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Util;

/**
 * Gets data for the short description column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Short_Description extends Abstract_Product_Data {

	private $process_shortcodes;

	public function __construct( $product, $process_shortcodes = false ) {
		parent::__construct( $product );

		$this->process_shortcodes = $process_shortcodes;
	}

	public function get_data() {
		$post              = Util::get_post( $this->get_parent_product() );
		$short_description = parent::maybe_strip_shortcodes( $post->post_excerpt, $this->process_shortcodes );

		if ( $short_description ) {
			$short_description = apply_filters( 'woocommerce_short_description', $short_description );
		}

		return apply_filters( 'wc_product_table_data_short_description', $short_description, $this->product );
	}

}
