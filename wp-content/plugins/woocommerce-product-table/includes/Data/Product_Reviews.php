<?php
namespace Barn2\Plugin\WC_Product_Table\Data;

/**
 * Gets data for the reviews column.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Product_Reviews extends Abstract_Product_Data {

	public function get_data() {
		$rating_count = $this->get_parent_product()->get_rating_count();
		$average      = (float) $this->get_parent_product()->get_average_rating();

		ob_start();
		?>
		<div class="star-rating" title="<?php printf( __( 'Rated %s out of 5', 'woocommerce-product-table' ), $average ); ?>">
			<span style="width:<?php echo ( ( $average / 5 ) * 100 ); ?>%">
				<?php /* translators: The sentence is 'out of 5'. The '%s' characters are replaced with extra HTML text. */ ?>
				<strong itemprop="ratingValue" class="rating"><?php echo esc_html( $average ); ?></strong> <?php printf( __( 'out of %s5%s', 'woocommerce-product-table' ), '<span itemprop="bestRating">', '</span>' ); ?>
				<?php printf( _n( 'based on %s customer rating', 'based on %s customer ratings', $rating_count, 'woocommerce-product-table' ), '<span itemprop="ratingCount" class="rating">' . $rating_count . '</span>' ); ?>
			</span>
		</div>
		<?php
		return apply_filters( 'wc_product_table_data_reviews', ob_get_clean(), $this->product );
	}

}
