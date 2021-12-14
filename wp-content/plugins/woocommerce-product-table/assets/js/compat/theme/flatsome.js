( function( $ ) {
	$( '.wc-product-table' ).on( 'responsiveDisplay.wcpt draw.wcpt', function() {
		if ( $.fn.addQty ) {
			$( this )
				.find( '.cart .quantity' )
				.remove( '.plus', '.minus' )
				.addQty();
		}
	} );
} )( jQuery );