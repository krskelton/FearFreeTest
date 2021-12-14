<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Table_Shortcode,
	Barn2\WPT_Lib\Table\Table_Data_Interface,
	Barn2\Plugin\WC_Product_Table\Util\Columns_Util,
	Barn2\Plugin\WC_Product_Table\Util\Util,
	WC_Product,
	WP_Term;

/**
 * Abstract data class used to fetch data for a product in the table.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
abstract class Abstract_Product_Data implements Table_Data_Interface {

	protected $product;
	protected $links;
	protected $parent_product;

	public function __construct( WC_Product $product, $links = '' ) {
		$this->product        = $product;
		$this->links          = ! empty( $links ) ? (array) $links : [];
		$this->parent_product = Util::maybe_get_parent( $this->product );
	}

	public function get_filter_data() {
		return ''; // subclasses override if they need to add filter data.
	}

	public function get_sort_data() {
		return ''; // subclasses override if they need to add sort data.
	}

	protected function get_product_id() {
		return $this->product->get_id();
	}

	protected function get_parent_product() {
		return $this->parent_product;
	}

	protected function get_parent_product_id() {
		return $this->parent_product->get_id();
	}

	protected function get_product_taxonomy_terms( $column ) {
		$taxonomy = Columns_Util::get_column_taxonomy( $column );

		if ( ! $taxonomy ) {
			return '';
		}

		$item_type = 'terms';

		if ( in_array( $column, [ 'categories', 'tags' ] ) ) {
			$item_type = $column;
		} elseif ( taxonomy_is_product_attribute( $taxonomy ) ) {
			$item_type = 'attributes';
		}

		$terms = wc_get_product_terms( $this->get_parent_product_id(), $taxonomy, [ 'fields' => 'all' ] );

		if ( is_wp_error( $terms ) || ! $terms ) {
			return '';
		}

		$result = [];
		$links  = array_intersect( [ 'all', $item_type ], $this->links );

		foreach ( $terms as $term ) {
			$result[] = self::format_term_data( $term, $links, $column );
		}

		if ( $links ) {
			$result = apply_filters( "term_links-{$taxonomy}", $result );
		}

		return $result ? implode( self::get_separator( $item_type ), $result ) : '';
	}

	protected static function format_term_data( WP_Term $term, $show_links = false, $column = null ) {
		$result = sprintf( '<span data-slug="%s">%s</span>', $term->slug, esc_html( $term->name ) );

		if ( $show_links && $column ) {
			$term_link = get_term_link( $term, $term->taxonomy );

			if ( ! is_wp_error( $term_link ) ) {
				$result = sprintf( '<a href="%s" data-column="%s" rel="tag">%s</a>', esc_url( $term_link ), Columns_Util::get_column_name( $column ), $result );
			}
		}

		return $result;
	}

	protected static function get_product_attribute( $attribute, $attributes ) {
		if ( ! $attributes ) {
			return false;
		}

		$sanitized_attribute = sanitize_title( $attribute );

		if ( isset( $attributes[$sanitized_attribute] ) ) {
			return $attributes[$sanitized_attribute];
		} elseif ( isset( $attributes['pa_' . $sanitized_attribute] ) ) {
			return $attributes['pa_' . $sanitized_attribute];
		}
		return false;
	}

	protected static function get_separator( $item_type ) {
		$sep = ', ';

		if ( 'custom_field_row' === $item_type ) {
			$sep = '<br/>';
		}

		return apply_filters( 'wc_product_table_separator', apply_filters( "wc_product_table_separator_{$item_type}", $sep ) );
	}

	protected static function maybe_strip_shortcodes( $text, $process_shortcodes = false ) {
		// Strip the product table shortcode from content - processing a shortcode within a shortcode could cause an infinite loop.
		$text = preg_replace( sprintf( '#\[%s.*?\]#', Table_Shortcode::SHORTCODE ), '', $text );

		if ( $text && ! $process_shortcodes && ! apply_filters( 'wc_product_table_process_shortcodes', false ) ) {
			$text = strip_shortcodes( $text );
		}

		return $text;
	}

}
