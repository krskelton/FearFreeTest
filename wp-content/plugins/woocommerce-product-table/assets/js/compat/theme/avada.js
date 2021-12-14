( function( $ ) {
	// Remove Avada styling for select elements in product tables.
	$( '.wc-product-table' ).on( 'load.wcpt', function() {
		$( this ).find( '.avada-select-parent' ).children( 'select' ).unwrap().siblings( '.select-arrow' ).remove();
	} );

	// Add quantity boxes in responsive child rows.
	$( '.wc-product-table' ).on( 'responsiveDisplay.wcpt', function() {
		if ( ! $( this ).hasClass( 'loading' ) && typeof avadaAddQuantityBoxes === 'function' ) {
			avadaAddQuantityBoxes();
		}
	} );
} )( jQuery );