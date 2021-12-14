( function( $ ) {
	// Add quantity plus and minus button to responsive rows.
	$( '.wc-product-table' ).on( 'responsiveDisplay.wcpt', function( e, table, childRow ) {
		childRow
			.find( 'div.quantity:not(.buttons_added)' )
			.addClass( 'buttons_added' )
			.append( '<input type=\"button\" value=\"+\" class=\"plus\" />' )
			.prepend( '<input type=\"button\" value=\"-\" class=\"minus\" />' );
	} );
} )( jQuery );