<?php

namespace Barn2\Plugin\WC_Product_Table;

use Barn2\Plugin\WC_Product_Table\Util\Util;
use Barn2\WPT_Lib\Util as Lib_Util;
use WC_Query;
use WP_Scoped_Hooks;

/**
 * Responsible for managing the actions and filter hooks for an individual product table.
 *
 * Hooks are registered in a temporary hook environment (@see class WP_Scoped_Hooks), and only
 * apply while the data is loaded into the table.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Hook_Manager extends WP_Scoped_Hooks {

	public $args;

	public function __construct( Table_Args $args ) {
		parent::__construct();
		$this->args = $args;
	}

	public function register() {
		// Maybe add target="_blank" for add to cart buttons
		$this->add_filter( 'woocommerce_loop_add_to_cart_link', [ Util::class, 'format_loop_add_to_cart_link' ] );

		// Adjust class for button when using loop add to cart template
		$this->add_filter( 'woocommerce_loop_add_to_cart_args', [ $this, 'loop_add_to_cart_args' ] );

		// Remove srcset and sizes for images in table as they don't apply (to reduce bandwidth)
		$this->add_filter( 'wp_get_attachment_image_attributes', [ $this, 'remove_image_srcset' ] );

		// Filter stock HTML
		$this->add_filter( 'woocommerce_get_stock_html', [ $this, 'get_stock_html' ], 10, 2 );

		// Wrap quantity and add to cart button with extra div
		$this->add_action( 'woocommerce_before_add_to_cart_button', [ __CLASS__, 'before_add_to_cart_button' ], 30 );
		$this->add_action( 'woocommerce_after_add_to_cart_button', [ __CLASS__, 'after_add_to_cart_button' ] );

		$this->add_filter( 'woocommerce_product_add_to_cart_text', [ __CLASS__, 'set_external_product_button_text' ], 10, 2 );

		// Override the 'add to cart' form action for each product.
		$this->add_filter( 'woocommerce_add_to_cart_form_action', [ __CLASS__, 'add_to_cart_form_action' ] );

		if ( 'dropdown' === $this->args->variations ) {
			// Move variation description, price & stock below the add to cart button and variations.
			$this->remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation', 10 );
			$this->add_action( 'woocommerce_after_variations_form', [ __CLASS__, 'woocommerce_single_variation' ] );

			// Use custom template for the add to cart area for variable products.
			$this->remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );
			$this->add_action( 'woocommerce_variable_add_to_cart', [ __CLASS__, 'woocommerce_variable_add_to_cart' ], 30 );

			// Format variation price
			$this->add_filter( 'woocommerce_get_price_html', [ __CLASS__, 'format_price_for_variable_products' ], 10, 2 );

			// Set image variation props
			$this->add_filter( 'woocommerce_available_variation', [ $this, 'variations_dropdown_set_variation_image_props' ], 10, 3 );
		} elseif ( 'separate' === $this->args->variations ) {
			// Custom add to cart for separate variations.
			$this->add_action( 'woocommerce_variation_add_to_cart', [ __CLASS__, 'woocommerce_variation_add_to_cart' ], 30 );
			$this->add_action( 'woocommerce_get_children', [ __CLASS__, 'variations_separate_remove_filtered' ], 10, 3 );
		}

		if ( $this->args->shortcodes ) {
			$this->add_filter( 'wc_product_table_data_custom_field', 'do_shortcode' );
		} else {
			$this->remove_filter( 'woocommerce_short_description', 'do_shortcode', 11 );
		}

		// Product Addons extension
		if ( Lib_Util::is_product_addons_active() ) {
			// Adjust template for <select> type product addons.
			$this->add_filter( 'wc_get_template', [ __CLASS__, 'load_product_addons_template' ], 10, 5 );

			// Reset the product add-ons hooks after displaying add-ons for variable products, as it affects subsequent products in the table.
			$this->add_action( 'woocommerce_after_variations_form', [ __CLASS__, 'product_addons_reset_display_hooks' ] );

			if ( isset( $GLOBALS['Product_Addon_Display'] ) ) {
				// Move the product add-on totals below the add to cart form
				$this->remove_action( 'woocommerce-product-addons_end', [ $GLOBALS['Product_Addon_Display'], 'totals' ], 10 );

				if ( defined( 'WC_PRODUCT_ADDONS_VERSION' ) && version_compare( WC_PRODUCT_ADDONS_VERSION, '3.0.0', '<' ) ) {
					$this->add_action( 'woocommerce_after_add_to_cart_button', [ __CLASS__, 'product_addons_show_totals' ] );
				} else {
					$this->add_filter( 'woocommerce_product_addons_show_grand_total', '__return_false' );
				}
			}
		}

		do_action( 'wc_product_table_hooks_before_register', $this );

		parent::register();

		do_action( 'wc_product_table_hooks_after_register', $this );
	}

	public function get_stock_html( $html, $product = false ) {
		if ( ! $product ) {
			return $html;
		}

		$types_to_check = ( 'dropdown' === $this->args->variations ) ? [ 'variable', 'variation' ] : [ 'variable' ];

		// Hide stock text in add to cart column, unless it's out of stock or a variable product
		if ( ! in_array( $product->get_type(), $types_to_check ) && $product->is_in_stock() ) {
			$html = '';
		}
		return apply_filters( 'wc_product_table_stock_html', $html, $product );
	}

	// For WC < 3.0 only
	public function get_stock_html_legacy( $html, $availability = false, $product = false ) {
		return $this->get_stock_html( $html, $product );
	}

	public function loop_add_to_cart_args( $args ) {
		if ( isset( $args['class'] ) ) {
			if ( false === strpos( $args['class'], 'alt' ) ) {
				$args['class'] = $args['class'] . ' alt';
			}
			if ( ! $this->args->ajax_cart ) {
				$args['class'] = str_replace( ' ajax_add_to_cart', '', $args['class'] );
			}
		}
		return $args;
	}

	/**
	 * Return a blank action for add to cart forms in the product table. This allows any non-AJAX actions to return back to the current page.
	 *
	 * @param string $url
	 * @return string The URL
	 */
	public static function add_to_cart_form_action( $url ) {
		return '';
	}

	public function remove_image_srcset( $attr ) {
		unset( $attr['srcset'] );
		unset( $attr['sizes'] );
		return $attr;
	}

	public function variations_dropdown_set_variation_image_props( $variation_data, $product, $variation ) {
		if ( empty( $variation_data['image'] ) || ! is_array( $variation_data['image'] ) ) {
			return $variation_data;
		}

		// Replace thumb with correct size needed for table
		if ( ! empty( $variation_data['image']['thumb_src'] ) ) {
			$thumb = wp_get_attachment_image_src( $variation->get_image_id(), $this->args->image_size );

			if ( is_array( $thumb ) && $thumb ) {
				$variation_data['image']['thumb_src']   = $thumb[0];
				$variation_data['image']['thumb_src_w'] = $thumb[1];
				$variation_data['image']['thumb_src_h'] = $thumb[2];
			}
		}

		// Caption fallback
		if ( empty( $variation_data['image']['caption'] ) ) {
			$variation_data['image']['caption'] = trim( strip_tags( Util::get_product_name( $product ) ) );
		}

		return $variation_data;
	}

	public static function format_price_for_variable_products( $price_html, $product ) {
		if ( 'variation' === $product->get_type() ) {
			$price_html = '<strong>' . $price_html . '</strong>';
		}
		return $price_html;
	}

	public static function product_addons_reset_display_hooks() {
		if ( isset( $GLOBALS['Product_Addon_Display'] ) &&
			 false === has_action( 'woocommerce_before_add_to_cart_button', [ $GLOBALS['Product_Addon_Display'], 'display' ] ) ) {

			add_action( 'woocommerce_before_add_to_cart_button', [ $GLOBALS['Product_Addon_Display'], 'display' ], 10 );
		}
	}

	/**
	 * Load any custom templates for WooCommerce Product Addons. Templates are located under /templates/addons/
	 */
	public static function load_product_addons_template( $located, $template_name, $args, $template_path, $default_path ) {
		if ( 'woocommerce-product-addons' === $template_path ) {
			$template = Util::get_template_path() . $template_name;

			if ( file_exists( $template ) ) {
				$located = $template;
			}
		}
		return $located;
	}

	public static function product_addons_show_totals() {
		global $product;

		if ( isset( $GLOBALS['Product_Addon_Display'] ) ) {
			$GLOBALS['Product_Addon_Display']->totals( $product->get_id() );
		}
	}

	public static function simple_product_button_open_wrapper() {
		global $product;

		if ( 'simple' === $product->get_type() ) {
			echo '<div class="woocommerce-simple-add-to-cart">';
		}
	}

	public static function simple_product_button_close_wrapper() {
		global $product;

		if ( 'simple' === $product->get_type() ) {
			echo '</div>';
		}
	}

	public static function before_add_to_cart_button() {
		echo '<div class="add-to-cart-button">';
	}

	public static function after_add_to_cart_button() {
		echo '</div>';
	}

	/**
	 * When using separate variation rows with the layered nav widgets, we need to filter out variations which don't match the current search criteria.
	 *
	 * @param type $child_ids
	 * @param type $product
	 * @param type $visible_only
	 * @return type
	 */
	public static function variations_separate_remove_filtered( $child_ids, $product = false, $visible_only = false ) {
		if ( ! $child_ids || ! is_array( $child_ids ) ) {
			return $child_ids;
		}

		$child_products = array_filter( array_map( 'wc_get_product', $child_ids ) );

		if ( empty( $child_products ) ) {
			return $child_ids;
		}

		$hide_out_of_stock = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' );
		$min_price         = filter_input( INPUT_GET, 'min_price', FILTER_VALIDATE_FLOAT );
		$max_price         = filter_input( INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT );
		$chosen_attributes = WC_Query::get_layered_nav_chosen_attributes();

		if ( ! $hide_out_of_stock && ! is_float( $min_price ) && ! is_float( $max_price ) && ! $chosen_attributes ) {
			return $child_ids;
		}

		foreach ( $child_products as $key => $child_product ) {
			$child_attributes = $child_product->get_attributes();

			if ( $hide_out_of_stock && ! $child_product->is_in_stock() ) {
				unset( $child_ids[ $key ] );
				continue;
			}

			if ( $chosen_attributes ) {
				foreach ( $chosen_attributes as $attribute => $chosen_attribute ) {
					if ( isset( $child_attributes[ $attribute ] ) && ! empty( $chosen_attribute['terms'] ) ) {
						if ( ! in_array( $child_attributes[ $attribute ], $chosen_attribute['terms'] ) ) {
							unset( $child_ids[ $key ] );
							continue 2;
						}
					}
				}
			}

			if ( is_float( $min_price ) || is_float( $max_price ) ) {
				$price = (float) $child_product->get_price();

				if ( ( is_float( $min_price ) && $price < $min_price ) || ( is_float( $max_price ) && $price > $max_price ) ) {
					unset( $child_ids[ $key ] );
					continue;
				}
			}
		} // foreach product

		return array_values( $child_ids );
	}

	// Make sure external product button text is not blank
	public static function set_external_product_button_text( $button_text, $product ) {
		if ( ! $button_text && 'external' === $product->get_type() ) {
			return __( 'Buy product', 'woocommerce-product-table' );
		}
		return $button_text;
	}

	public static function woocommerce_single_variation() {
		global $product;

		if ( 'variable' === $product->get_type() ) {
			// Back compat: Add 'single_variation_wrap' class for compatibilitiy with WC 2.4
			$single_variation_wrap = version_compare( WC_VERSION, '2.5', '<' ) ? ' single_variation_wrap' : '';
			echo '<div class="woocommerce-variation single_variation' . $single_variation_wrap . '"></div>';
		}
	}

	/**
	 * The add to cart template for variable products.
	 *
	 * @global WC_Product $product
	 */
	public static function woocommerce_variable_add_to_cart() {
		global $product;

		// Get available variations?
		$get_variations       = count( $product->get_children() ) <= apply_filters( 'woocommerce_ajax_variation_threshold', 30, $product );
		$available_variations = $get_variations ? $product->get_available_variations() : false;

		$variations_json = wp_json_encode( $available_variations );
		$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

		do_action( 'woocommerce_before_add_to_cart_form' );
		?>

		<form class="wpt_variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post"
			  enctype='multipart/form-data' data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; ?>">
			<?php do_action( 'woocommerce_before_variations_form' ); ?>

			<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
				<p class="stock out-of-stock"><?php _e( 'This product is currently out of stock and unavailable.', 'woocommerce-product-table' ); ?></p>
			<?php else : ?>
				<?php
				$variation_attributes = Util::get_variation_attributes( $product );
				?>
				<div class="variations">
					<?php foreach ( $variation_attributes as $attribute_name => $options ) : ?>
						<?php
						// Set the default variation if the product has a default attribute.
						$selected = $product->get_variation_default_attribute( $attribute_name );

						// Append a random number to the attribute ID to prevent clashes with multiple attributes in the same table.
						wc_dropdown_variation_attribute_options( [
							'options'          => $options,
							'attribute'        => $attribute_name,
							'id'               => $product->get_id() . '_' . sanitize_title( $attribute_name ) . '_' . rand( 0, 5000 ),
							'product'          => $product,
							'selected'         => $selected,
							'show_option_none' => Util::get_attribute_label( $attribute_name, $product )
						] );
						?>
					<?php endforeach; ?>
				</div>

				<div class="single_variation_wrap">
					<?php
					do_action( 'woocommerce_before_single_variation' );
					do_action( 'woocommerce_single_variation' );
					do_action( 'woocommerce_after_single_variation' );
					?>
				</div>

			<?php endif; // if available variations           ?>

			<?php do_action( 'woocommerce_after_variations_form' ); ?>
		</form>

		<?php
		do_action( 'woocommerce_after_add_to_cart_form' );
	}

	public static function woocommerce_variation_add_to_cart() {
		global $product;

		if ( ! $product->is_purchasable() ) {
			return;
		}

		echo wc_get_stock_html( $product );

		if ( ! $product->is_in_stock() ) {
			return;
		}

		do_action( 'woocommerce_before_add_to_cart_form' );
		?>

		<form class="cart" method="post" enctype='multipart/form-data'>
			<?php
			do_action( 'woocommerce_before_add_to_cart_button' );
			do_action( 'woocommerce_before_add_to_cart_quantity' );

			woocommerce_quantity_input( [
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( $_POST['quantity'] ) : $product->get_min_purchase_quantity()
			] );

			do_action( 'woocommerce_after_add_to_cart_quantity' );
			?>

			<button type="submit" name="add-to-cart" value="<?php echo \absint( $product->get_parent_id() ); ?>"
					class="single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>

			<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

			<input type="hidden" name="variation_id" value="<?php echo absint( $product->get_id() ); ?>"/>

			<div class="variations hidden">
				<?php foreach ( Util::get_variation_attributes( $product ) as $attribute => $value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( sanitize_title( $attribute ) ); ?>" value="<?php echo esc_attr( $value ); ?>"/>
				<?php endforeach; ?>
			</div>
		</form>

		<?php
		do_action( 'woocommerce_after_add_to_cart_form' );
	}

}
