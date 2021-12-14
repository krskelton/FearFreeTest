<?php

namespace Barn2\Plugin\WC_Product_Table;

use Barn2\Plugin\WC_Product_Table\Util\Util;
use Barn2\WPT_Lib\Conditional;
use Barn2\WPT_Lib\Registerable;
use Barn2\WPT_Lib\Service;
use Barn2\WPT_Lib\Util as Lib_Util;

/**
 * This class handles caching for the product tables.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Cart_Handler implements Service, Registerable, Conditional {

	public function is_required() {
		return Lib_Util::is_front_end();
	}

	public function register() {
		add_action( 'wp_loaded', [ $this, 'process_multi_cart' ], 20 );
	}

	public function process_multi_cart() {
		// Make sure we don't process the form twice when adding via AJAX.
		if ( defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( ! filter_input( INPUT_POST, 'multi_cart', FILTER_VALIDATE_INT ) ) {
			return;
		}

		$product_ids = filter_input( INPUT_POST, 'product_ids', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
		$cart_data   = self::get_multi_cart_data();

		if ( ! is_array( $product_ids ) || ! is_array( $cart_data ) ) {
			return;
		}

		if ( empty( $product_ids ) || empty( $cart_data ) ) {
			if ( function_exists( 'wc_add_notice' ) ) {
				wc_add_notice( __( 'Please select one or more products.', 'woocommerce-product-table' ), 'error' );
			}
			return;
		}

		if ( $added = self::add_to_cart_multi( array_intersect_key( $cart_data, array_flip( $product_ids ) ) ) ) {
			wc_add_to_cart_message( $added, true );

			if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}
	}

	/**
	 * Add multiple products to the cart in a single step.
	 *
	 * @param type $products - An array of products (including quantities and variation data) to add to the cart
	 * @return array An array of product IDs => quantity added
	 */
	public static function add_to_cart_multi( $products ) {
		$added_to_cart = [];

		if ( ! $products ) {
			return $added_to_cart;
		}

		// If using Product Addons, we need to remove and add some filters to process the multi cart data correctly.
		if ( isset( $GLOBALS['Product_Addon_Cart'] ) ) {
			remove_filter( 'woocommerce_add_cart_item_data', [ $GLOBALS['Product_Addon_Cart'], 'add_cart_item_data' ], 10 );
			add_filter( 'woocommerce_add_cart_item_data', [ __CLASS__, 'product_addons_cart_item_data_wrapper' ], 10, 2 );

			remove_filter( 'woocommerce_add_to_cart_validation', [ $GLOBALS['Product_Addon_Cart'], 'validate_add_cart_item' ], 999 );
			add_filter( 'woocommerce_add_to_cart_validation', [ __CLASS__, 'product_addons_validate_cart_item' ], 999, 3 );
		}

		foreach ( $products as $product_id => $data ) {
			$quantity           = isset( $data['quantity'] ) ? $data['quantity'] : 1;
			$variation_id       = isset( $data['variation_id'] ) ? $data['variation_id'] : false;
			$product_variations = $variation_id ? Util::extract_attributes( $data ) : false;

			if ( ! empty( $data['parent_id'] ) ) {
				$product_id = $data['parent_id'];
			}

			if ( self::add_to_cart( $product_id, $quantity, $variation_id, $product_variations ) ) {
				if ( isset( $added_to_cart[ $product_id ] ) ) {
					$quantity += $added_to_cart[ $product_id ];
				}
				$added_to_cart[ $product_id ] = $quantity;
			}
		}
		return $added_to_cart;
	}

	public static function add_to_cart( $product_id, $quantity = 1, $variation_id = false, $variations = false ) {
		if ( ! $product_id ) {
			wc_add_notice( __( 'No product selected. Please try again.', 'woocommerce-product-table' ), 'error' );
			return false;
		}

		$qty = wc_stock_amount( $quantity );

		if ( ! $qty ) {
			wc_add_notice( __( 'Please enter a quantity greater than 0.', 'woocommerce-product-table' ), 'error' );
			return false;
		}

		$product      = wc_get_product( $product_id );
		$product_type = $product->get_type();

		// Bail if product not doesn't exist or isn't published.
		if ( ! $product || 'publish' !== $product->get_status() ) {
			wc_add_notice( __( 'This product is no longer available. Please select an alternative.', 'woocommerce-product-table' ), 'error' );
			return false;
		}

		//@deprecated 2.7.1 Replaced by 'wc_product_table_[product_type]_add_to_cart'
		$handle_custom_type_deprecated = apply_filters( 'wc_product_table_handle_' . $product_type . '_add_to_cart', false );

		// Allow products to be handled by themes/plugins.
		if ( has_action( 'wc_product_table_' . $product_type . '_add_to_cart' ) || $handle_custom_type_deprecated ) {
			do_action( 'wc_product_table_' . $product_type . '_add_to_cart', $product, $qty, $variation_id, $variations );
			return true;
		}

		// Grouped and external products not allowed.
		if ( $product->is_type( [ 'grouped', 'external' ] ) ) {
			return false;
		}

		// Check product passes validation checks.
		if ( ! apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $qty, $variation_id, $variations ) ) {
			return false;
		}

		if ( false !== \WC()->cart->add_to_cart( $product_id, $qty, $variation_id, $variations ) ) {
			return true;
		}

		return false;
	}

	public static function product_addons_cart_item_data_wrapper( $cart_item_data, $product_id ) {
		if ( ! isset( $GLOBALS['Product_Addon_Cart'] ) ) {
			return $cart_item_data;
		}

		$cart_data = self::get_multi_cart_data();
		$post_data = [];

		if ( isset( $cart_data[ $product_id ] ) && is_array( $cart_data[ $product_id ] ) ) {
			$post_data = $cart_data[ $product_id ];
		} else {
			return $cart_item_data;
		}

		if ( class_exists( 'Product_Addon_Cart' ) && $GLOBALS['Product_Addon_Cart'] instanceof \Product_Addon_Cart ) {
			// Back compat addons v2.
			return $GLOBALS['Product_Addon_Cart']->add_cart_item_data( $cart_item_data, $product_id, $post_data );
		} else {
			return self::product_addons_add_cart_item_data( $cart_item_data, $product_id, $post_data );
		}
	}

	private static function product_addons_add_cart_item_data( $cart_item_data, $product_id, $post_data ) {
		if ( ! defined( 'WC_PRODUCT_ADDONS_PLUGIN_PATH' ) || empty( $post_data ) ) {
			return $cart_item_data;
		}

		$product_addons = Util::get_product_addons( $product_id );

		if ( empty( $cart_item_data['addons'] ) ) {
			$cart_item_data['addons'] = [];
		}

		if ( is_array( $product_addons ) && ! empty( $product_addons ) ) {
			include_once( WC_PRODUCT_ADDONS_PLUGIN_PATH . '/includes/fields/abstract-wc-product-addons-field.php' );

			foreach ( $product_addons as $addon ) {
				// If type is heading, skip.
				if ( 'heading' === $addon['type'] ) {
					continue;
				}

				$value = isset( $post_data[ 'addon-' . $addon['field_name'] ] ) ? $post_data[ 'addon-' . $addon['field_name'] ] : '';

				if ( is_array( $value ) ) {
					$value = array_map( 'stripslashes', $value );
				} else {
					$value = stripslashes( $value );
				}

				switch ( $addon['type'] ) {
					case 'checkbox':
						include_once( WC_PRODUCT_ADDONS_PLUGIN_PATH . '/includes/fields/class-wc-product-addons-field-list.php' );
						$field = new \WC_Product_Addons_Field_List( $addon, $value );
						break;
					case 'multiple_choice':
						switch ( $addon['display'] ) {
							case 'radiobutton':
								include_once( WC_PRODUCT_ADDONS_PLUGIN_PATH . '/includes/fields/class-wc-product-addons-field-list.php' );
								$field = new \WC_Product_Addons_Field_List( $addon, $value );
								break;
							case 'images':
							case 'select':
								include_once( WC_PRODUCT_ADDONS_PLUGIN_PATH . '/includes/fields/class-wc-product-addons-field-select.php' );
								$field = new \WC_Product_Addons_Field_Select( $addon, $value );
								break;
						}
						break;
					case 'custom_text':
					case 'custom_textarea':
					case 'custom_price':
					case 'input_multiplier':
						include_once( WC_PRODUCT_ADDONS_PLUGIN_PATH . '/includes/fields/class-wc-product-addons-field-custom.php' );
						$field = new \WC_Product_Addons_Field_Custom( $addon, $value );
						break;
					case 'file_upload':
						include_once( WC_PRODUCT_ADDONS_PLUGIN_PATH . '/includes/fields/class-wc-product-addons-field-file-upload.php' );
						$field = new \WC_Product_Addons_Field_File_Upload( $addon, $value );
						break;
				}

				$data = $field->get_cart_item_data();

				if ( is_wp_error( $data ) ) {
					// Throw exception for add_to_cart to pickup.
					throw new \Exception( $data->get_error_message() );
				} elseif ( $data ) {
					$cart_item_data['addons'] = array_merge( $cart_item_data['addons'], apply_filters( 'woocommerce_product_addon_cart_item_data', $data, $addon, $product_id, $post_data ) );
				}
			}
		}

		return $cart_item_data;
	}

	public static function product_addons_validate_cart_item( $passed, $product_id, $qty ) {
		if ( ! isset( $GLOBALS['Product_Addon_Cart'] ) ) {
			return $passed;
		}

		$cart_data = self::get_multi_cart_data();
		$post_data = [];

		if ( isset( $cart_data[ $product_id ] ) && is_array( $cart_data[ $product_id ] ) ) {
			$post_data = $cart_data[ $product_id ];
		}

		return $passed && $GLOBALS['Product_Addon_Cart']->validate_add_cart_item( $passed, $product_id, $qty, $post_data );
	}

	/**
	 * Get the posted multi cart data as an array, with the correct integer product IDs.
	 *
	 * @return array The multi cart data (product IDs => product data)
	 */
	public static function get_multi_cart_data() {
		return self::fix_cart_data_product_ids( filter_input( INPUT_POST, 'cart_data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ) );
	}

	/**
	 * Posted cart_data uses indexes of the form 'p1234' where '1234' is the product ID.
	 * This is because of a limitation of the JS serializeObject function.
	 * We run this function to remove the 'p' prefix from each index in the array.
	 *
	 * @param array $cart_data The cart data to be sanitized
	 * @return array The same array with keys replaced with the corresponding product ID
	 */
	private static function fix_cart_data_product_ids( $cart_data ) {
		if ( empty( $cart_data ) ) {
			return [];
		}

		$fixed_keys = preg_replace( '/^p(\d+)$/', '$1', array_keys( $cart_data ) );
		return array_combine( $fixed_keys, $cart_data );
	}

}
