<?php

namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Settings;
use Barn2\Plugin\WC_Product_Table\Util\Util;
use Barn2\WPT_Lib\Util as Lib_Util;

/**
 * Gets data for the add to cart column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Cart extends Abstract_Product_Data {

	const MULTI_CART_FORM_ID = 'multi-cart';

	private $variations;
	private $quantities;
	private $cart_button;
	private $multi_cart;
	private $default_quantity = 1;

	public function __construct( $product, $variations = false, $quantities = false, $cart_button = 'button', $multi_cart = false ) {
		parent::__construct( $product );

		$this->variations       = $variations;
		$this->quantities       = $quantities;
		$this->cart_button      = $cart_button;
		$this->multi_cart       = $multi_cart;
		$this->default_quantity = $product->get_min_purchase_quantity();
	}

	public function get_data() {
		add_filter( 'woocommerce_quantity_input_args', [ $this, 'ignore_posted_quantity' ], 1, 2 );
		add_filter( 'woocommerce_quantity_input_args', [ $this, 'store_product_default_quantity' ], 9999, 2 );

		$purchasable_from_table = $this->product->is_purchasable() && $this->product->is_in_stock();

		// If variations are disabled then variable products cannot be purchased.
		if ( 'variable' === $this->product->get_type() && 'dropdown' !== $this->variations ) {
			$purchasable_from_table = false;
		}

		// Composite products cannot be purchased from table.
		if ( in_array( $this->product->get_type(), [ 'bundle', 'composite', 'variable-subscription' ], true ) ) {
			$purchasable_from_table = false;
		}

		$purchasable_from_table = apply_filters( 'wc_product_table_product_purchasable_from_table', $purchasable_from_table, $this->product );

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', $this->get_add_to_cart_class( $purchasable_from_table ) ) ); ?>">
			<?php
			if ( $purchasable_from_table ) {
				woocommerce_template_single_add_to_cart();
			} else {
				// Out of stock products, grouped products, external products, etc.
				woocommerce_template_loop_add_to_cart();
			}
			?>
			<?php if ( $this->multi_cart ) : ?>
				<div class="multi-cart-check">
					<?php if ( $purchasable_from_table ) : ?>
						<input type="checkbox" name="product_ids[]" value="<?php echo esc_attr( $this->get_product_id() ); ?>" form="<?php echo esc_attr( self::MULTI_CART_FORM_ID ); ?>"/>
						<?php $this->add_multi_cart_hidden_fields(); ?>
					<?php else : ?>
						<input type="checkbox" name="product_ids[]" value="<?php echo esc_attr( $this->get_product_id() ); ?>" disabled="disabled"/>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		remove_filter( 'woocommerce_quantity_input_args', [ $this, 'ignore_posted_quantity' ], 1 );
		remove_filter( 'woocommerce_quantity_input_args', [ $this, 'store_product_default_quantity' ], 9999 );

		$cart = trim( ob_get_clean() );

		// Normalize whitespace then strip all new line characters.
		$cart = str_replace( "\n", '', normalize_whitespace( $cart ) );

		return apply_filters( 'wc_product_table_data_add_to_cart', $cart, $this->product );
	}

	/**
	 * WooCommerce sets the quantity input_value based on the posted quantity, and if not set, uses get_min_purchase_quantity.
	 * When ajax_cart is disabled, we don't want to show the posted quantity as this  means all products in the table show
	 * the quantity for the previous product added to the cart. This filter runs early so any quantity plugins running can
	 * update as needed.
	 *
	 * @param array $quantity_args
	 * @param WC_Product $product
	 * @return array
	 * @see woocommerce/templates/single-product/add-to-cart/simple.php
	 */
	public function ignore_posted_quantity( $quantity_args, $product ) {
		// Check the product ID matches.
		if ( $product->get_id() !== $this->get_product_id() ) {
			return $quantity_args;
		}

		if ( isset( $_POST['quantity'] ) && isset( $quantity_args['input_value'] ) ) {
			$quantity_args['input_value'] = $product->get_min_purchase_quantity();
		}

		return $quantity_args;
	}

	/**
	 * Store the default quantity for this product based on the input value set in woocommerce_quantity_input.
	 *
	 * @param array $quantity_args
	 * @param WC_Product $product
	 * @return array
	 */
	public function store_product_default_quantity( $quantity_args, $product ) {
		// Check the product ID matches.
		if ( $product->get_id() !== $this->get_product_id() ) {
			return $quantity_args;
		}

		if ( isset( $quantity_args['input_value'] ) ) {
			$this->default_quantity = filter_var( $quantity_args['input_value'], FILTER_VALIDATE_FLOAT );
		}

		return $quantity_args;
	}

	private function get_add_to_cart_class( $purchasable = true ) {
		$cart_class   = [ 'add-to-cart-wrapper' ];
		$cart_class[] = $purchasable ? 'purchasable' : 'not-purchasable';

		if ( $this->multi_cart ) {
			$cart_class[] = 'multi-cart';
		}

		if ( 'checkbox' === $this->cart_button ) {
			$cart_class[] = 'no-cart-button';
		}

		if ( ! $this->quantities ) {
			$cart_class[] = 'no-quantity';
		}

		if ( Lib_Util::is_product_addons_active() ) {
			$misc_settings = Settings::get_setting_misc();

			if ( ! empty( $misc_settings['addons_layout'] ) ) {
				$cart_class[] = esc_attr( 'addons-' . $misc_settings['addons_layout'] );
			}

			if ( ! empty( $misc_settings['addons_option_layout'] ) ) {
				$cart_class[] = esc_attr( 'addons-options-' . $misc_settings['addons_option_layout'] );
			}
		}

		return apply_filters( 'wc_product_table_add_to_cart_class', $cart_class );
	}

	private function add_multi_cart_hidden_fields() {
		$data = [
			'quantity' => $this->default_quantity
		];

		// Variation data
		if ( $this->variations && 'variable' === $this->product->get_type() ) {
			$data['variation_id'] = 0;

			foreach ( array_keys( Util::get_variation_attributes( $this->product ) ) as $attribute ) {
				$attribute_name          = 'attribute_' . sanitize_title( $attribute );
				$data[ $attribute_name ] = '';
			}
		} elseif ( $this->variations && 'variation' === $this->product->get_type() ) {
			$data['variation_id'] = $this->get_product_id();
			$data['parent_id']    = $this->product->get_parent_id();

			foreach ( Util::get_variation_attributes( $this->product ) as $attribute => $value ) {
				$attribute_name          = 'attribute_' . sanitize_title( str_replace( 'attribute_', '', $attribute ) );
				$data[ $attribute_name ] = $value;
			}
		}

		// Product Addons data
		if ( $product_addons = Util::get_product_addons( $this->get_product_id() ) ) {
			foreach ( $product_addons as $addon ) {
				$key = 'addon-' . $addon['field_name'];

				if ( 'checkbox' === $addon['type'] ) {
					if ( ! empty( $addon['options'] ) ) {
						foreach ( $addon['options'] as $option_key => $option ) {
							$sub_key          = $key . '[' . $option_key . ']';
							$data[ $sub_key ] = '';
						}
					}
				} else {
					$data[ $key ] = '';
				}
			}
		}

		// Filter data to allow more to be added
		$data = apply_filters( 'wc_product_table_multi_add_to_cart_data', $data, $this->product );

		// Loop through each piece of cart data and render a hidden field
		foreach ( $data as $name => $value ) {
			$bracket_pos = strpos( $name, '[' );
			if ( false === $bracket_pos ) {
				$bracket_pos = strlen( $name );
			}

			$name_key = '[' . substr_replace( $name, ']', $bracket_pos, 0 );
			if ( ']' !== substr( $name_key, -1 ) ) {
				$name_key .= ']';
			}

			// Render the hidden field - we need to use 'p' in front of product ID to allow serializeObject in JS to work.
			printf(
				'<input type="hidden" name="cart_data[p%1$u]%2$s" data-input-name="%3$s" value="%4$s" form="%5$s" />',
				esc_attr( $this->get_product_id() ),
				esc_attr( $name_key ),
				esc_attr( $name ),
				esc_attr( $value ),
				esc_attr( self::MULTI_CART_FORM_ID )
			);
		}
	}

}
