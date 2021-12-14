( function( $ ) {

	function enfoldOnQuantityClick( event ) {
		// Prevent any other handlers changing the quantity.
		event.stopImmediatePropagation();

		var $clicked = $( this ),
			isMinus = $clicked.is( '.minus' );

		$clicked.closest( '.quantity' ).find( '.qty' ).val( function( i, value ) {
			var $qty = $( this ),
				step = parseFloat( $qty.prop( 'step' ) ),
				min = parseFloat( $qty.prop( 'min' ) ),
				max = parseFloat( $qty.prop( 'max' ) );

			value = parseFloat( value );

			value = ! isNaN( value ) ? value : 1;
			step = ! isNaN( step ) ? step : 1;
			min = ! isNaN( min ) ? min : 1;
			max = ! isNaN( max ) ? max : 9999;

			step = isMinus ? -1 * step : step;
			value = value + step;

			if ( isMinus ) {
				return Math.max( value, min );
			} else {
				return Math.min( value, max );
			}
		} ).trigger( 'change' );
	}

	function enfoldOnDraw( event ) {
		$( this ).find( '.cart div.quantity:not(.buttons_added)' )
			.addClass( 'buttons_added' )
			.children( '.qty' )
			.before( '<input type=\"button\" value=\"-\" class=\"minus\">' )
			.after( '<input type=\"button\" value=\"+\" class=\"plus\">' );
	}

	$( '.wc-product-table' )
		.on( 'click', '.quantity .plus, .quantity .minus', enfoldOnQuantityClick )
		.on( 'responsiveDisplay.wcpt draw.wcpt', enfoldOnDraw );

} )( jQuery );