<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

use Barn2\Plugin\WC_Product_Table\Util\Util,
	Barn2\Plugin\WC_Product_Table\Util\Settings;

/**
 * Gets data for the image column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Image extends Abstract_Product_Data {

	private $image_size;
	private $lightbox;

	public function __construct( $product, $links = '', $image_size = '', $lightbox = false ) {
		parent::__construct( $product, $links );

		$this->image_size = $image_size ? $image_size : 'thumbnail';
		$this->lightbox   = $lightbox;
	}

	public function get_data() {
		$thumbnail     = '';
		$attachment_id = $this->product->get_image_id();

		if ( $attachment_id ) {
			$full_size     = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
			$thumbnail_src = wp_get_attachment_image_src( $attachment_id, $this->image_size );
			$full_src      = wp_get_attachment_image_src( $attachment_id, $full_size );

			if ( $thumbnail_src && $full_src ) {
				$atts = [
					'title'                   => get_post_field( 'post_title', $attachment_id ),
					'alt'                     => trim( strip_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ),
					'data-caption'            => get_post_field( 'post_excerpt', $attachment_id ),
					'data-src'                => $full_src[0],
					'data-large_image'        => $full_src[0],
					'data-large_image_width'  => $full_src[1],
					'data-large_image_height' => $full_src[2],
					'class'                   => 'product-thumbnail product-table-image' // back-compat: product-table-image class. Remove in future release
				];

				// Caption fallback
				$atts['data-caption'] = empty( $atts['data-caption'] ) ? trim( strip_tags( Util::get_product_name( $this->product ) ) ) : $atts['data-caption'];

				// Alt fallbacks
				$atts['alt'] = empty( $atts['alt'] ) ? $atts['data-caption'] : $atts['alt'];
				$atts['alt'] = empty( $atts['alt'] ) ? $atts['title'] : $atts['alt'];
				$atts['alt'] = empty( $atts['alt'] ) && $this->product ? trim( strip_tags( Util::get_product_name( $this->product ) ) ) : $atts['alt'];

				// Get the image
				$image = wp_get_attachment_image( $attachment_id, $this->image_size, false, $atts );

				$wrapper_class = [ 'product-thumbnail-wrapper' ];

				// Maybe wrap with lightbox or product link
				if ( $this->lightbox && ! Settings::open_links_in_quick_view_pro() ) {
					$image = sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url( $full_src[0] ),
						$image
					);

					$wrapper_class[] = 'woocommerce-product-gallery__image';
				} elseif ( array_intersect( [ 'all', 'image' ], $this->links ) ) {
					$image = Util::format_product_link( $this->product, $image );
				}

				$thumbnail = sprintf(
					'<div data-thumb="%1$s" class="%2$s">%3$s</div>',
					esc_url( $thumbnail_src[0] ),
					esc_attr( implode( ' ', $wrapper_class ) ),
					$image
				);
			}
		}

		return apply_filters( 'wc_product_table_data_image', $thumbnail, $this->product );
	}

}
