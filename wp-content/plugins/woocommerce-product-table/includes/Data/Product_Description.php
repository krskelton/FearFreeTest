<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Util;

/**
 * Gets data for the description column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Description extends Abstract_Product_Data {

	private $description_length; // number of words
	private $shortcodes;

	public function __construct( $product, $description_length, $shortcodes = false ) {
		parent::__construct( $product );

		$this->description_length = $description_length;
		$this->shortcodes         = $shortcodes;
	}

	public function get_data() {
		$description = $this->product->get_description();

		// For variations, if no variation description is set fall back to the parent variable product description
		if ( ! $description && 'variation' === $this->product->get_type() && $parent = Util::get_parent( $this->product ) ) {
			$description = $parent->get_description();
		}

		// Format the description and (optionally) process shortcodes
		$description = apply_filters( 'the_content', parent::maybe_strip_shortcodes( $description, $this->shortcodes ) );

		// Check length
		if ( $this->description_length > 0 ) {
			$description = wp_trim_words( $description, $this->description_length, ' &hellip;' ); // wp_trim_words() will also strip tags
		}

		return apply_filters( 'wc_product_table_data_description', $description, $this->product );
	}

}
