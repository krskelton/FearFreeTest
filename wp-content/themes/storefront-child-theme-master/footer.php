<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */

?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>
	
	<footer id="colophon" class="site-footer" role="contentinfo">
		<div class="row">
			<div class="col-3 p-5">
				<h4>Label 1</h4>
				<?php wp_nav_menu(array('theme_location' => 'secondary'));?>
			</div>
			<div class="col-3 p-5">
				<h4>Label 2</h4>
				<?php wp_nav_menu(array('theme_location' => 'secondary'));?>
			</div>
			<div class="col-3 p-5">
				<h4>Label 3</h4>
				<?php wp_nav_menu(array('theme_location' => 'secondary'));?>
			</div>
			<div class="col-3 p-5">
				<img src="http://localhost:8888/FearFreeTest/wp-content/uploads/2021/12/image-19.png" />
			</div>
		</div>
		<hr>
		<div class="row p-4 pb-0"><p>&copy; <?php echo date("Y");?> Fear Free, LLC. All Rights Reserved.</p></div>

	</footer><!-- #colophon -->

	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<?php wp_footer(); ?>
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous">
</body>
</html>
