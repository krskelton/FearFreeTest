( function( $, window, document, params, undefined ) {
	"use strict";

	const blockConfig = {
		message: null,
		overlayCSS: {
			background: '#fff',
			opacity: 0.6
		}
	};

	function addRowAttributes( $row ) {
		return function( key, value ) {
			if ( 'class' === key ) {
				$row.addClass( value );
			} else {
				$row.attr( key, value );
			}
		};
	}

	function appendFilterOptions( $select, items, depth ) {
		depth = ( typeof depth !== 'undefined' ) ? depth : 0;

		// Add each term to filter drop-down
		$.each( items, function( i, item ) {
			let name = item.name,
				value = 'slug' in item ? item.slug : name,
				pad = '';

			if ( depth ) {
				pad = Array( ( depth * 2 ) + 1 ).join( '\u00a0' ) + '\u2013\u00a0';
			}

			$select.append( '<option value="' + value + '">' + pad + name + '</option>' );

			if ( 'children' in item ) {
				appendFilterOptions( $select, item.children, depth + 1 );
			}
		} );
	}

	// Filters the terms for a filter, returning only those which are in the required list.
	function removeHiddenItems( allTerms, required ) {
		let term,
			result = JSON.parse( JSON.stringify( allTerms ) ); // clone the terms array, so the original is unmodified.

		for ( let i = result.length - 1; i >= 0; i-- ) {
			term = result[i];

			if ( term.hasOwnProperty( 'children' ) ) {
				term.children = removeHiddenItems( term.children, required );

				if ( 0 === term.children.length ) {
					// No children left, so delete property from term.
					delete term.children;
				}
			}

			// Keep the term if it's found in requiredSlugs or it has children.
			if ( -1 === required.indexOf( term.slug ) && ! term.hasOwnProperty( 'children' ) ) {
				result.splice( i, 1 );
			}
		}

		return result;
	}

	function flattenObjectArray( arr, childProp ) {
		let result = [];

		for ( let i = 0; i < arr.length; i++ ) {
			if ( typeof arr[i] !== 'object' ) {
				continue;
			}
			result.push( arr[i] );

			for ( let prop in arr[i] ) {
				if ( prop === childProp ) {
					Array.prototype.push.apply( result, flattenObjectArray( arr[i][prop], childProp ) );
					delete arr[i][prop];
				}
			}
		}

		return result;
	}

	function getCurrentUrlWithoutFilters() {
		let url = window.location.href.split( '?' )[0];

		if ( window.location.search ) {
			let params = window.location.search.substring( 1 ).split( '&' ),
				newParams = [];

			for ( let i = 0; i < params.length; i++ ) {
				if ( params[i].indexOf( 'min_price' ) === -1 &&
					params[i].indexOf( 'max_price' ) === -1 &&
					params[i].indexOf( 'filter_' ) === -1 &&
					params[i].indexOf( 'rating_filter' ) === -1 &&
					params[i].indexOf( 'query_type' ) === -1
				) {
					newParams.push( params[i] );
				}
			}

			if ( newParams.length ) {
				url += '?' + newParams.join( '&' );
			}
		}

		return url;
	}

	function initContent( $el ) {
		initMedia( $el );
		initVariations( $el );
		initProductAddons( $el );
	}

	function initMedia( $el ) {
		if ( ! $el || ! $el.length ) {
			return;
		}

		if ( typeof WPPlaylistView !== 'undefined' ) {
			// Initialise audio and video playlists
			$el.find( '.wp-playlist' ).filter( function() {
				return $( '.mejs-container', this ).length === 0; // exclude playlists already initialized
			} ).each( function() {
				return new WPPlaylistView( { el: this } );
			} );
		}

		// Initialise audio and video shortcodes
		if ( 'wp' in window && 'mediaelement' in window.wp ) {
			$( window.wp.mediaelement.initialize );
		}

		// Run fitVids to ensure videos in table have correct proportions
		if ( $.fn.fitVids ) {
			$el.fitVids();
		}
	}

	function initProductAddons( $el ) {
		// Triggering this event will initialize the addons for all visible cart forms on the page.
		$el.trigger( 'quick-view-displayed' );
	}

	function initVariations( $el ) {
		if ( ! $el || ! $el.length || typeof wc_add_to_cart_variation_params === 'undefined' ) {
			return;
		}

		$el.find( '.wpt_variations_form' ).filter( function() {
			return ! $( this ).hasClass( 'initialised' ); // exclude variations already initialized
		} ).each( function() {
			$( this ).wc_variation_form();
		} );
	}

	/*
	 * A renderer for $.fn.DataTables.Responsive to display all visible content for a row when using modal responsive display.
	 *
	 * @see https://datatables.net/reference/option/responsive.details.renderer
	 */
	function responsiveRendererAllVisible( options ) {
		options = $.extend( {
			tableClass: ''
		}, options );

		return function( api, rowIdx, columns ) {
			var data = $.map( columns, function( col ) {
				// Bail if column data is hidden.
				if ( ! api.column( col.columnIndex ).visible() ) {
					return '';
				}

				return '<tr data-dt-row="' + col.rowIndex + '" data-dt-column="' + col.columnIndex + '">' +
					'<td>' + ( col.title ? col.title + ':' : '' ) + '</td> ' +
					'<td>' + col.data + '</td>' +
					'</tr>';
			} ).join( '' );

			let $table = $( '<table class="' + options.tableClass + ' dtr-details" width="100%"/>' ).append( data );
			initContent( $table );

			return $table;
		};
	}

	function selectWooEnabled() {
		return ( 'selectWoo' in $.fn ) && params.enable_select2;
	}

	function setMultiCartMessage( message, $multiCartForm ) {
		$multiCartForm.closest( '.wc-product-table-controls' ).append( $( '<div class="multi-cart-message"></div>' ).append( message ) );
	}

	function setVariationImage( $form, variation ) {
		let $productRow = $form.closest( 'tr' );

		// If variations form is in a parent row, check for image in child row and vice versa
		if ( $productRow.hasClass( 'parent' ) ) {
			$productRow = $productRow.add( $productRow.next( '.child' ) );
		} else if ( $productRow.hasClass( 'child' ) ) {
			$productRow = $productRow.add( $productRow.prev( '.parent' ) );
		}

		let $productImg = $productRow.find( 'img.product-thumbnail' ).eq( 0 );

		if ( ! $productImg.length ) {
			return;
		}

		let props = false,
			$productGalleryWrap = $productImg.closest( '.woocommerce-product-gallery__image', $productRow ).eq( 0 ),
			$productGalleryLink = false;

		if ( $productGalleryWrap.length ) {
			$productGalleryLink = $productGalleryWrap.find( 'a' ).eq( 0 );
		}

		if ( variation ) {
			if ( 'image' in variation ) {
				props = variation.image;
			} else if ( 'image_src' in variation ) {
				// Back compat: different object structure used in WC < 3.0
				props = {
					src: variation.image_src,
					src_w: '',
					src_h: '',
					full_src: variation.image_link,
					full_src_w: '',
					full_src_h: '',
					thumb_src: variation.image_src,
					thumb_src_w: '',
					thumb_src_h: '',
					srcset: variation.image_srcset,
					sizes: variation.image_sizes,
					title: variation.image_title,
					alt: variation.image_alt,
					caption: variation.image_caption
				};
			}
		}

		if ( props && props.thumb_src.length ) {
			$productImg.wc_set_variation_attr( 'src', props.thumb_src );
			$productImg.wc_set_variation_attr( 'title', props.title );
			$productImg.wc_set_variation_attr( 'alt', props.alt );
			$productImg.wc_set_variation_attr( 'data-src', props.full_src );
			$productImg.wc_set_variation_attr( 'data-caption', props.caption );
			$productImg.wc_set_variation_attr( 'data-large_image', props.full_src );
			$productImg.wc_set_variation_attr( 'data-large_image_width', props.full_src_w );
			$productImg.wc_set_variation_attr( 'data-large_image_height', props.full_src_h );

			if ( $productGalleryWrap.length ) {
				$productGalleryWrap.wc_set_variation_attr( 'data-thumb', props.thumb_src );
			}

			if ( $productGalleryLink.length ) {
				$productGalleryLink.wc_set_variation_attr( 'href', props.full_src );
			}
		} else {
			$productImg.wc_reset_variation_attr( 'src' );
			$productImg.wc_reset_variation_attr( 'width' );
			$productImg.wc_reset_variation_attr( 'height' );
			$productImg.wc_reset_variation_attr( 'title' );
			$productImg.wc_reset_variation_attr( 'alt' );
			$productImg.wc_reset_variation_attr( 'data-src' );
			$productImg.wc_reset_variation_attr( 'data-caption' );
			$productImg.wc_reset_variation_attr( 'data-large_image' );
			$productImg.wc_reset_variation_attr( 'data-large_image_width' );
			$productImg.wc_reset_variation_attr( 'data-large_image_height' );

			if ( $productGalleryWrap.length ) {
				$productGalleryWrap.wc_reset_variation_attr( 'data-thumb' );
			}

			if ( $productGalleryLink.length ) {
				$productGalleryLink.wc_reset_variation_attr( 'href' );
			}
		}
	}

	function updateMultiHiddenField( field, val, $multiCheck ) {
		//Find the multi-cart input which corresponds to the changed cart input
		let $multiCartInput = $multiCheck.find( 'input[data-input-name="' + field + '"]' );

		if ( $multiCartInput.length ) {
			// Update the hidden input to match the cart form value
			$multiCartInput.val( val );
		}
	}

	/******************************************
	 * PRODUCTTABLE PROTOTYPE
	 ******************************************/

	let ProductTable = function( $table ) {
		// Properties
		this.$table = $table;
		this.id = $table.attr( 'id' );
		this.dataTable = null;
		this.config = null;
		this.initialState = null;
		this.ajaxData = [];
		this.hasAdminBar = $( '#wpadminbar' ).length > 0;

		this.$filters = [];
		this.$tableWrapper = [];
		this.$pagination = [];
		this.$tableControls = [];

		// Register events
		$table
			.on( 'draw.dt', { table: this }, onDraw )
			.on( 'init.dt', { table: this }, onInit )
			.on( 'page.dt', { table: this }, onPage )
			.on( 'processing.dt', { table: this }, onProcessing )
			.on( 'responsive-display.dt', { table: this }, onResponsiveDisplay )
			.on( 'stateLoadParams.dt', { table: this }, onStateLoadParams )
			.on( 'xhr.dt', { table: this }, onAjaxLoad )
			.on( 'submit.wcpt', '.cart', { table: this }, onAddToCart );

		$( window ).on( 'load.wcpt', { table: this }, onWindowLoad );

		// Show the table - loading class removed on init.dt
		$table.addClass( 'loading' ).css( 'visibility', 'visible' );
	};

	ProductTable.prototype.buildConfig = function() {
		let config = {
			retrieve: true, // so subsequent calls to DataTable() return the same API instance
			responsive: true,
			orderMulti: false, // disable ordering by multiple columns at once
			stateSave: true,
			language: params.language
		};

		// Get config for this table instance.
		let tableConfig = this.$table.data( 'config' );

		if ( tableConfig ) {
			// We need to do deep copy for the 'language' property to be merged correctly.
			config = $.extend( true, {}, config, tableConfig );
		}

		// Build AJAX data for loading products.
		let ajaxData = {
			table_id: this.id,
			action: 'wcpt_load_products',
			_ajax_nonce: params.ajax_nonce
		};

		// If query string present, add parameters to data to send (e.g. filter attributes)
		// .substring(1) removes the '?' at the beginning
		if ( window.location.search ) {
			let vars = window.location.search.substring( 1 ).split( '&' );

			for ( let i = 0; i < vars.length; i++ ) {
				let pair = vars[i].split( '=', 2 );

				if ( 2 === pair.length ) {
					ajaxData[pair[0]] = pair[1].replace( /%2C/g, ',' );
				}
			}
		}

		// If English language, replace 'products' with 'product' when there's only 1 result.
		if ( 'info' in config.language && -1 !== config.language.info.indexOf( 'products' ) ) {
			config.infoCallback = function( settings, start, end, max, total, pre ) {
				if ( pre && 1 === total ) {
					return pre.replace( 'products', 'product' );
				}
				return pre;
			};
		}

		// Config for server-side processing
		if ( config.serverSide && 'ajax_url' in params ) {
			config.deferRender = true;
			config.ajax = {
				url: params.ajax_url,
				type: 'POST',
				data: ajaxData,
				xhrFields: {
					withCredentials: true
				}
			};
		}

		// Set responsive display and renderer functions
		if ( 'responsive' in config && ( typeof config.responsive === 'object' ) && 'details' in config.responsive && 'display' in config.responsive.details ) {
			if ( 'child_row_visible' === config.responsive.details.display ) {
				config.responsive.details.display = $.fn.dataTable.Responsive.display.childRowImmediate;
				config.responsive.details.renderer = $.fn.dataTable.Responsive.renderer.listHidden();

			} else if ( 'modal' === config.responsive.details.display ) {
				config.responsive.details.display = $.fn.dataTable.Responsive.display.modal();
				config.responsive.details.renderer = responsiveRendererAllVisible( { tableClass: 'wc-product-table' } );
			}
		}

		// Legacy config for language (we now use Gettext for translation).
		if ( 'lang_url' in params ) {
			config.language = { url: params.lang_url };
		}

		return config;
	};

	ProductTable.prototype.checkFormAttributeSupport = function( $form ) {
		let table = this;

		// Check for support for HTML5 form attribute
		if ( ! $form.is( 'form' ) ) {
			return table;
		}

		if ( ! $form[0] || ! ( 'elements' in $form[0] ) ) {
			return table;
		}

		if ( $form[0].elements.length > 2 ) {
			// If we have more than 2 form elements (i.e. the form button and hidden 'multi_cart' field)
			// then HTML5 form attribute must be supported natively in browser, so no need to continue.
			return table;
		}

		table.getDataTable()
			.$( '.multi-cart-check input[type="checkbox"]' ) // get all multi checkboxes in table
			.add( table.$table.find( 'td.child .multi-cart-check input[type="checkbox"]' ) ) // including checkboxes in responsive child rows
			.filter( ':checked:enabled' ) // just the selected and enabled products
			.each( function() {
				// Then add all multi fields for checked products to the parent multi-cart form
				$( this ).clone().appendTo( $form );
				$( this ).siblings( 'input[type="hidden"]' ).clone().appendTo( $form );
			} );

		return table;
	};

	/*
	 * Gets the current list of filter items for the $select based on the filter data attached to the table.
	 *
	 * For standard loading, the list is restricted to just those items currently visible in the table.
	 */
	ProductTable.prototype.getCurrentFilterItems = function( $select ) {
		let table = this,
			filters = table.$table.data( 'filters' ),
			tax = $select.data( 'tax' );

		if ( ! filters || ! ( tax in filters ) ) {
			return null;
		}

		let terms = filters[tax].terms;

		if ( ! terms ) {
			return [];
		}

		if ( ! table.config.serverSide ) {
			// For standard load, find all data items in search column so we can restrict filter to relevant data only.
			let searchData = table.getDataTable()
				.column( $select.data( 'searchColumn' ) + ':name', { search: 'applied' } )
				.data();

			if ( searchData.any() ) {
				let searchDataSlugs = searchData.join( ' ' ).split( ' ' );
				terms = removeHiddenItems( terms, searchDataSlugs );
			}
		}

		return terms;
	};

	ProductTable.prototype.getDataTable = function() {
		if ( ! this.dataTable ) {
			this.init();
		}

		return this.dataTable;
	};

	ProductTable.prototype.init = function() {
		let table = this;

		table.$table.trigger( 'preInit.wcpt', [table] );

		// Initialize DataTables instance.
		table.config = table.buildConfig();
		table.dataTable = table.$table.DataTable( table.config );

		return table;
	};

	ProductTable.prototype.initFilters = function() {
		let table = this,
			filtersData = table.$table.data( 'filters' );

		if ( ! filtersData ) {
			return table;
		}

		let dataTable = table.getDataTable(),
			$filtersWrap = $( '<div class="wc-product-table-select-filters" id="' + table.id + '_select_filters" />' ),
			savedColumnSearches = {},
			filtersAdded = 0;

		if ( 'filterBy' in params.language && params.language.filterBy ) {
			$filtersWrap.append( '<label class="filter-label">' + params.language.filterBy + '</label>' );
		}

		// Setup initial state (if using).
		if ( table.initialState && 'columns' in table.initialState ) {
			// If we have an initial state, convert to a more workable object of the form: { 'column_name': 'previous search' }
			for ( let i = 0; i < table.initialState.columns.length; i++ ) {
				if ( ! ( 'search' in table.initialState.columns[i] ) || ! table.initialState.columns[i].search.search ) {
					continue;
				}

				if ( ( 0 === dataTable.column( i ).length ) || typeof dataTable.column( i ).dataSrc() !== 'string' ) {
					continue;
				}

				let search = table.initialState.columns[i].search.search;

				if ( search && table.initialState.columns[i].search.regex ) {
					search = search.replace( '(^|, )', '' ).replace( '(, |$)', '' );
				}

				// Bug in DataTables - column().name() not working so we need to pull name from header node
				savedColumnSearches[$( dataTable.column( i ).header() ).data( 'name' )] = search;
			}
		}

		// Build the filters
		for ( let tax in filtersData ) {
			// Create <select> for the filter.
			let selectAtts = {
				'name': 'wcpt_filter_' + tax,
				'data-tax': tax,
				'data-column': filtersData[tax].column,
				'data-search-column': filtersData[tax]['search-column'],
				'aria-label': filtersData[tax].heading,
				'data-placeholder': filtersData[tax].heading
			};

			if ( filtersData[tax].class ) {
				selectAtts['class'] = filtersData[tax].class;
			}

			let $select = $( '<select/>' ).attr( selectAtts );

			table.refreshFilterItems( $select );

			// Don't add the filter if we have no items (length will be 1 because of default value).
			if ( $select.children().length <= 1 ) {
				continue;
			}

			// Determine the initial filter selection (if any)
			let value = '';

			if ( 'selected' in filtersData[tax] && $select.children( 'option[value="' + filtersData[tax].selected + '"]' ).length ) {
				// Set selection based on active filter widget
				value = filtersData[tax].selected;
			} else if ( filtersData[tax].column in savedColumnSearches ) {
				// Set selection based on previous saved table state
				let prevSearch = savedColumnSearches[filtersData[tax].column];

				// Flatten terms to make searching through them easier
				let flatTerms = flattenObjectArray( filtersData[tax].terms, 'children' );

				// Search the filter terms for the previous search value, which will be the <option> text rather than its value.
				// We could use Array.find() here if browser support was better.
				$.each( flatTerms, function( i, term ) {
					if ( 'name' in term && term.name === prevSearch ) {
						value = 'slug' in term ? term.slug : term.name;
						return false; // break the $.each loop
					}
				} );
			}

			// Set the initial value and append select to wrapper
			$select
				.val( value )
				.on( 'change.wcpt', { table: table }, onFilterChange )
				.appendTo( $filtersWrap );

			filtersAdded++;
		} // foreach filter

		// Add filters to table - before search box if present, otherwise as first element above table
		if ( filtersAdded > 0 ) {
			// Add filters to table
			let $searchBox = table.$tableControls.find( '.dataTables_filter' );

			if ( $searchBox.length ) {
				$filtersWrap.prependTo( $searchBox.closest( '.wc-product-table-controls' ) );
			} else {
				$filtersWrap.prependTo( table.$tableControls.filter( '.wc-product-table-above' ) );
			}
		}

		// Store filters here as we use this when searching columns.
		table.$filters = table.$tableControls.find( '.wc-product-table-select-filters select' );

		return table;
	};

	ProductTable.prototype.initMultiCart = function() {
		let table = this;

		if ( ! table.config.multiAddToCart || ! table.$tableWrapper.length ) {
			return table;
		}

		if ( ! ( 'multiCartButton' in params.language ) ) {
			params.language.multiCartButton = 'Add to cart';
		}

		// Create the multi cart form and append above/below table
		let $multiForm =
			$( '<form class="multi-cart-form" method="post" />' )
				.append( '<input type="submit" class="' + params.multi_cart_button_class + '" value="' + params.language.multiCartButton + '" />' )
				.append( '<input type="hidden" name="multi_cart" value="1" />' )
				.on( 'submit.wcpt', { table: table }, onAddToCartMulti );

		$multiForm = $( '<div class="wc-product-table-multi-form" />' ).append( $multiForm );

		if ( $.inArray( table.config.multiCartLocation, ['top', 'both'] ) > -1 ) {
			table.$tableControls.filter( '.wc-product-table-above' ).append( $multiForm );
		}

		if ( $.inArray( table.config.multiCartLocation, ['bottom', 'both'] ) > -1 ) {
			table.$tableControls.filter( '.wc-product-table-below' ).append( $multiForm.clone( true ) );
		}

		table.registerMultiCartEvents();
		return table;
	};

	ProductTable.prototype.initPhotoswipe = function() {
		let table = this;

		if ( typeof PhotoSwipe === 'undefined' || typeof PhotoSwipeUI_Default === 'undefined' ) {
			return table;
		}

		table.$table
			.find( 'tbody' )
			.off( 'click.wcpt', '.woocommerce-product-gallery__image a' )
			.on( 'click.wcpt', '.woocommerce-product-gallery__image a', onOpenPhotoswipe );

		return table;
	};

	ProductTable.prototype.initQuickViewPro = function() {
		let table = this;

		if ( ! window.WCQuickViewPro ) {
			return table;
		}

		// If links should open in Quick View, register events.
		if ( params.open_links_in_quick_view ) {
			// Handle clicks on single product links.
			table.$table.on( 'click.wcpt', '.single-product-link', WCQuickViewPro.handleQuickViewClick );

			// Handle clicks on loop read more buttons (e.g. 'Select options', 'View products', etc).
			table.$table.on( 'click.wcpt', '.add-to-cart-wrapper a[data-product_id]', function( event ) {
				// Don't open quick view for external products.
				if ( $( this ).hasClass( 'product_type_external' ) ) {
					return true;
				}

				WCQuickViewPro.handleQuickViewClick( event );
				return false;
			} );
		}

		return table;
	};

	ProductTable.prototype.initResetButton = function() {
		let table = this;

		if ( ! table.config.resetButton || ! ( 'resetButton' in params.language ) ) {
			return table;
		}

		let $resetButton =
			$( '<div class="wc-product-table-reset"><a class="reset" href="#">' + params.language.resetButton + '</a></div>' )
				.on( 'click.wcpt', 'a', { table: table }, onReset );

		// Append reset button
		let $firstChild = table.$tableControls
			.filter( '.wc-product-table-above' )
			.children( '.wc-product-table-select-filters, .dataTables_length, .dataTables_filter' )
			.eq( 0 );

		if ( $firstChild.length ) {
			$firstChild.append( $resetButton );
		} else {
			table.$tableControls
				.filter( '.wc-product-table-above' )
				.prepend( $resetButton );
		}

		return table;
	};

	ProductTable.prototype.initSearchOnClick = function() {
		let table = this;

		if ( table.config.clickFilter ) {
			// 'search_on_click' - add click handler for relevant links. When clicked, the table will filter by the link text.
			table.$table.on( 'click.wcpt', 'a[data-column]', { table: table }, onClickToSearch );
		}

		return table;
	};

	ProductTable.prototype.initSelectWoo = function() {
		let table = this;

		if ( ! selectWooEnabled() ) {
			return table;
		}

		let selectWooOpts = {
			dropdownCssClass: 'wc-product-table-dropdown'
		};

		// Initialize selectWoo for search filters.
		if ( table.$filters.length ) {
			table.$filters.selectWoo(
				Object.assign( selectWooOpts, { minimumResultsForSearch: 7 } )
			);

			/*
			 * Fix for select2 which doesn't correctly calculate the filter width in Safari, causing the placeholder text to overflow.
			 * This code tests the width of the placeholder, and if this is greater than the select2 width (minus padding), then we
			 * increase the width of the select2 element slightly.
			 */
			table.$filters.each( function() {
				let $select2 = $( this ).next(),
					selectWidth = $select2.width(),
					$placeholder = $select2.find( '.select2-selection__placeholder' );

				// The select2 padding is 28px but we add 2px to allow for rounding errors.
				if ( ( selectWidth - 30 ) < $placeholder.width() ) {
					$select2.width( selectWidth + 15 );
				}
			} );
		}

		// Initialize selectWoo for page length - minimumResultsForSearch of -1 disables the search box.
		table.$tableControls.find( '.dataTables_length select' ).selectWoo(
			Object.assign( selectWooOpts, { minimumResultsForSearch: -1 } )
		);

		return table;
	};

	ProductTable.prototype.processAjaxData = function() {
		let table = this;

		if ( ! table.config.serverSide || ! table.ajaxData.length ) {
			return table;
		}

		let $rows = table.$table.find( 'tbody tr' );

		// Add row attributes to each row in table
		if ( $rows.length ) {
			for ( let i = 0; i < table.ajaxData.length; i++ ) {
				if ( '__attributes' in table.ajaxData[i] && $rows.eq( i ).length ) {
					$.each( table.ajaxData[i].__attributes, addRowAttributes( $rows.eq( i ) ) );
				}
			}
		}

		return table;
	};

	ProductTable.prototype.refreshFilterItems = function( $select ) {
		let table = this,
			filters = table.$table.data( 'filters' ),
			tax = $select.data( 'tax' ),
			val = $select.val(); // Store value so we can reset later.

		if ( ! filters || ! ( tax in filters ) ) {
			return table;
		}

		// Drop all filter items.
		$select.empty();

		// Add the default option.
		$( '<option value="" />' ).text( filters[tax].heading ).prependTo( $select );

		// Add the <option> elements to filter
		appendFilterOptions( $select, table.getCurrentFilterItems( $select ) );

		// Restore previous selected value.
		$select.val( val );

		return table;
	};

	ProductTable.prototype.registerMultiCartEvents = function() {
		let table = this;

		if ( ! table.config.multiAddToCart ) {
			return table;
		}

		// Quantities - update hidden fields when changed
		table.$table.on( 'change', '.cart .qty', function() {
			let $cart = $( this ).closest( '.cart' ),
				$multiFields = $cart.siblings( '.multi-cart-check' ),
				$multiCheckbox = $multiFields.children( 'input[type="checkbox"]' ),
				qtyFloat = parseFloat( $( this ).val() );

			if ( ! isNaN( qtyFloat ) && ! $multiCheckbox.prop( 'disabled' ) ) {
				if ( 0 === qtyFloat ) {
					// Untick multi checkbox if quantity is 0.
					$multiCheckbox.prop( 'checked', false );
				} else {
					let multiQtyFloat = parseFloat( $multiFields.children( 'input[data-input-name="quantity"]' ).val() );

					if ( ! isNaN( multiQtyFloat ) && qtyFloat !== multiQtyFloat ) {
						// Tick multi checkbox if quantity has changed.
						$multiCheckbox.prop( 'checked', true );
					}
				}
			}

			// Update quantity field
			updateMultiHiddenField( 'quantity', qtyFloat, $multiFields );
		} );

		// Variations - update multi cart fields when updated.
		table.$table.on( 'found_variation', '.wpt_variations_form', function( event, variation ) {
			let $cart = $( this ),
				$multiFields = $cart.siblings( '.multi-cart-check' );

			// Variation attributes
			if ( 'attributes' in variation ) {
				for ( let attribute in variation.attributes ) {
					updateMultiHiddenField( attribute, variation.attributes[attribute], $multiFields );
				}
			}
			// Variation ID
			if ( 'variation_id' in variation ) {
				updateMultiHiddenField( 'variation_id', variation.variation_id, $multiFields );
			}
		} );

		// Enable or disable multi checkbox based on whether current variation is purchasable.
		table.$table.on( 'show_variation', '.wpt_variations_form', function( event, variation, purchasable ) {
			let $cart = $( this );

			// Only update checkbox after the variations form has initialised. This ensures we only update in response to
			// user input and prevents checking the box during initial load when a default variation is set.
			if ( ! $cart.hasClass( 'initialised' ) ) {
				return true;
			}

			let $checkbox = $cart.siblings( '.multi-cart-check' ).children( 'input[type="checkbox"]' );

			if ( purchasable ) {
				$checkbox.prop( { disabled: false, checked: true } );
			} else {
				$checkbox.prop( { disabled: true, checked: false } );
			}
		} );

		// Disable multi checkbox on variation hide.
		table.$table.on( 'hide_variation', '.wpt_variations_form', function() {
			$( this )
				.siblings( '.multi-cart-check' )
				.children( 'input[type="checkbox"]' )
				.prop( { disabled: true, checked: false } );
		} );

		// Product Addons - update multi cart fields when updated.
		table.$table.on( 'updated_addons', function( event ) {
			let $cart = $( event.target ),
				$multiFields = $cart.siblings( '.multi-cart-check' );

			if ( ! $multiFields.length || ! $cart.is( '.cart' ) ) {
				return;
			}

			// Loop through each addon and update the corresponding hidden field in the .multi-cart-check section.
			$cart.find( '.wc-pao-addon-field' ).each( function() {
				let $input = $( this ),
					val = $input.val(),
					inputName = $input.prop( 'name' );

				if ( ! inputName || 'quantity' === inputName ) { // quantity change handled above.
					return;
				}

				// For checkbox addons the input names are arrays, e.g. addon-check[].
				// We need to add an integer index to the name to make sure we update the correct hidden field
				if ( 'checkbox' === $input.attr( 'type' ) ) {
					// Pull the index from the parent wrapper class (e.g. wc-pao-addon-123-collection-0)
					let match = $input.closest( '.form-row', $cart.get( 0 ) ).attr( 'class' ).match( /(wc-pao-addon-).+?-(\d+)($|\s)/ );

					if ( match && 4 === match.length ) {
						// match[2] is the index of the checkbox within the checkbox group.
						inputName = inputName.replace( '[]', '[' + match[2] + ']' );
					}

					// Clear value if unchecked.
					if ( ! $input.prop( 'checked' ) ) {
						val = '';
					}

					updateMultiHiddenField( inputName, val, $multiFields );
				} else if ( 'radio' === $input.attr( 'type' ) ) {
					if ( $input.prop( 'checked' ) ) {
						// Replace [] at the end of the input name, so 'radio-field[]' becomes 'radio-field'.
						// Needed to match hidden field in multi cart section.
						inputName = inputName.replace( /\[\]$/, '' );

						updateMultiHiddenField( inputName, val, $multiFields );
					}
				} else {
					updateMultiHiddenField( inputName, val, $multiFields );
				}

			} ); // each addon field
		} ); // on updated_addons

		return table;
	};

	ProductTable.prototype.registerVariationEvents = function() {
		let table = this;

		if ( 'dropdown' !== this.config.variations ) {
			return table;
		}

		// Add class when form initialised so we can filter these out later
		table.$table.on( 'wc_variation_form', '.wpt_variations_form', function() {
			$( this ).addClass( 'initialised' );
		} );

		// Update image column when variation found
		table.$table.on( 'found_variation', '.wpt_variations_form', function( event, variation ) {
			setVariationImage( $( this ), variation );
		} );

		// Show variation and enable cart button
		table.$table.on( 'show_variation', '.wpt_variations_form', function( event, variation, purchasable ) {
			// Older versions of WC didn't pass the purchasable parameter, so we need to work this out
			if ( typeof purchasable === 'undefined' ) {
				purchasable = variation.is_purchasable && variation.is_in_stock && variation.variation_is_visible;
			}

			$( this ).find( '.added_to_cart' ).remove();
			$( this ).find( '.single_add_to_cart_button' ).prop( 'disabled', ! purchasable ).removeClass( 'added disabled' );
			$( this ).find( '.single_variation' ).slideDown( 200 );
		} );

		// Hide variation and disable cart button
		table.$table.on( 'hide_variation', '.wpt_variations_form', function() {
			$( this ).find( '.single_add_to_cart_button' ).prop( 'disabled', true );
			$( this ).find( '.single_variation' ).slideUp( 200 );
		} );

		// Reset the variation image
		table.$table.on( 'reset_image', '.wpt_variations_form', function() {
			setVariationImage( $( this ), false );
		} );

		return table;
	};

	ProductTable.prototype.resetMultiCartCheckboxes = function( $cart ) {
		let table = this,
			$multiFields = [];

		if ( $cart && $cart.length && $cart.is( '.cart' ) ) {
			$multiFields = $cart.siblings( '.multi-cart-check' );
		} else {
			$multiFields = table.getDataTable()
				.$( '.multi-cart-check' )
				.add( table.$table.find( 'tr.child .multi-cart-check' ) );
		}

		$multiFields
			.children( 'input[type="checkbox"]' )
			.prop( 'checked', false );

		return table;
	};

	ProductTable.prototype.resetQuantities = function( $cart ) {
		let table = this;

		// If no cart given, reset all visible cart forms in table.
		if ( ! $cart || ! $cart.length ) {
			$cart = table.getDataTable()
				.$( '.cart' )
				.add( table.$table.find( 'tr.child .cart' ) );
		}

		$cart.find( 'input[name="quantity"]' ).val( function( index, value ) {
			if ( $.isNumeric( $( this ).attr( 'value' ) ) ) {
				value = $( this ).attr( 'value' );
			}
			return value;
		} ).trigger( 'change' );

		return table;
	};

	ProductTable.prototype.resetVariations = function( $cart ) {
		let table = this;

		// If no cart given, reset all visible variation forms in table.
		if ( ! $cart || ! $cart.length ) {
			$cart = table.getDataTable()
				.$( '.wpt_variations_form' )
				.add( table.$table.find( 'tr.child .wpt_variations_form' ) );
		}

		$cart.each( function() {
			if ( ! $( this ).is( '.wpt_variations_form' ) ) {
				return;
			}

			$( this ).find( 'select' ).val( '' );
			$( this ).find( '.single_variation' ).slideUp( 200 ).css( 'display', 'none' ); // ensure variation is hidden (e.g. on other results pages)
			$( this ).find( '.single_add_to_cart_button' ).addClass( 'disabled', true );
			$( this )
				.siblings( '.multi-cart-check' )
				.children( 'input[type="checkbox"]' )
				.prop( 'checked', false )
				.prop( 'disabled', true );

		} );

		return table;
	};

	ProductTable.prototype.resetProductAddons = function( $cart ) {
		let table = this;

		// If no cart given, reset all visible cart forms in table.
		if ( ! $cart || ! $cart.length ) {
			$cart = table.getDataTable()
				.$( '.cart' )
				.add( table.$table.find( 'tr.child .cart' ) );
		}

		let $addons = $cart.find( '.wc-pao-addon, .product-addon' );

		$addons.find( 'select, textarea' ).val( '' ).trigger( 'change' );

		$addons.find( 'input' ).each( function() {
			if ( 'radio' === $( this ).attr( 'type' ) || 'checkbox' === $( this ).attr( 'type' ) ) {
				$( this ).prop( 'checked', false );
			} else {
				$( this ).val( '' );
			}
			$( this ).trigger( 'change' );
		} );

		return table;
	};

	ProductTable.prototype.scrollToTop = function() {
		let table = this,
			scroll = table.config.scrollOffset;

		if ( false !== scroll && ! isNaN( scroll ) ) {
			let tableOffset = table.$tableWrapper.offset().top - scroll;

			if ( table.hasAdminBar ) { // Adjust offset for WP admin bar
				tableOffset -= 32;
			}
			$( 'html,body' ).animate( { scrollTop: tableOffset }, 300 );
		}

		return table;
	};

	ProductTable.prototype.showHidePagination = function() {
		let table = this;

		// Hide pagination if we only have 1 page
		if ( table.$pagination.length ) {
			let pageInfo = table.getDataTable().page.info();

			if ( pageInfo && pageInfo.pages <= 1 ) {
				table.$pagination.hide( 0 );
			} else {
				table.$pagination.show();
			}
		}

		return table;
	};

	/******************************************
	 * EVENTS
	 ******************************************/

	function onAddToCart( event ) {
		let table = event.data.table,
			$cart = $( this ),
			$button = $cart.find( '.single_add_to_cart_button' ),
			productId = $cart.find( '[name="add-to-cart"]' ).val();

		// If not using AJAX, set form action to blank so current page is reloaded, rather than single product page
		if ( ! table.config.ajaxCart ) {
			$cart.attr( 'action', '' );
			return true;
		}

		if ( ! productId || ! $cart.length || $button.hasClass( 'disabled' ) ) {
			return true;
		}

		event.preventDefault();

		$cart.siblings( 'p.cart-error' ).remove();
		table.$tableControls.find( '.multi-cart-message' ).remove();

		$button
			.removeClass( 'added' )
			.addClass( 'loading' )
			.siblings( 'a.added_to_cart' )
			.remove();

		let data = $cart.serializeObject();
		delete data['add-to-cart']; // Make sure 'add-to-cart' isn't included as we use 'product_id'

		data.product_id = productId;
		data.action = 'wcpt_add_to_cart';
		data._ajax_nonce = params.ajax_nonce;

		$( document.body ).trigger( 'adding_to_cart', [$button, data] );

		$.ajax( {
			url: params.ajax_url,
			type: 'POST',
			data: data,
			xhrFields: {
				withCredentials: true
			}
		} ).done( function( response ) {
			if ( response.error ) {
				if ( response.error_message ) {
					$cart.append( response.error_message );
				}
				return;
			}

			// Product sucessfully added - redirect to cart or show 'View cart' link.
			if ( 'yes' === wc_add_to_cart_params.cart_redirect_after_add ) {
				window.location = wc_add_to_cart_params.cart_url;
				return;
			} else {
				// Reset stuff on successful addition.
				table
					.resetQuantities( $cart )
					.resetVariations( $cart )
					.resetProductAddons( $cart )
					.resetMultiCartCheckboxes( $cart );

				// Trigger WooCommerce added_to_cart event - add-to-cart.js in WooCommerce will handle adding
				// the 'View cart' link, add classes to $button, and update the cart fragments.
				$cart.trigger( 'added_to_cart', [response.fragments, response.cart_hash, $button] );
			}
		} ).always( function() {
			$button.removeClass( 'loading' );
		} );

		return false;
	}

	// Submit event for multi add to cart form
	function onAddToCartMulti( event ) {
		let table = event.data.table,
			dataTable = table.getDataTable(),
			$multiForm = $( this ),
			data = {};

		// Add id="multi-cart" to form via JS as we can have several multi cart forms on a single page.
		// This keeps the HTML valid and makes sure each form can be submitted correctly.
		$multiForm.attr( 'id', 'multi-cart' );

		table.$tableControls.find( '.multi-cart-message' ).remove();
		table.$table.find( 'p.cart-error, a.added_to_cart' ).remove();

		// Find all checked products and loop through each to build product IDs and quantities.
		// dataTable.$() doesn't work with :checked selector in responsive rows, so we need add them manually to the result set.
		dataTable
			.$( '.multi-cart-check input[type="checkbox"]' ) // all checkboxes
			.add( table.$table.find( 'td.child .multi-cart-check input[type="checkbox"]' ) ) // add checkboxes in responsive child rows
			.filter( ':checked:enabled' ) // just the selected and enabled products
			.each( function() {
				// Add all the hidden fields to our data to be posted
				$.extend( true, data, $( this ).siblings( 'input[type="hidden"]' ).serializeObject() );
			} );

		// Show error if no products were selected
		if ( $.isEmptyObject( data ) && ( 'multiCartNoSelection' in params.language ) ) {
			setMultiCartMessage( '<p class="cart-error">' + params.language.multiCartNoSelection + '</p>', $multiForm );
			return false;
		}

		// Return here if we're not using AJAX.
		if ( ! table.config.ajaxCart ) {
			table.checkFormAttributeSupport( $multiForm );
			return true;
		}

		// AJAX enabled, so block table and do the AJAX post
		table.$tableWrapper.block( blockConfig );

		data.action = 'wcpt_add_to_cart_multi';
		data._ajax_nonce = params.ajax_nonce;

		$( document.body ).trigger( 'adding_to_cart', [$multiForm, data] );

		$.ajax( {
			url: params.ajax_url,
			type: 'POST',
			data: data,
			xhrFields: {
				withCredentials: true
			}
		} ).done( function( response ) {
			if ( response.error ) {
				if ( response.error_message ) {
					setMultiCartMessage( response.error_message, $multiForm );
				}
				return;
			}

			if ( 'yes' === wc_add_to_cart_params.cart_redirect_after_add ) {
				// Redirect after add to cart.
				window.location = wc_add_to_cart_params.cart_url;
				return;
			} else {
				// Replace fragments
				if ( response.fragments ) {
					$.each( response.fragments, function( key, value ) {
						$( key ).replaceWith( value );
					} );
				}

				if ( response.cart_message ) {
					setMultiCartMessage( response.cart_message, $multiForm );
				}

				// Reset all the things.
				table
					.resetQuantities()
					.resetVariations()
					.resetProductAddons()
					.resetMultiCartCheckboxes();

				// Trigger event so themes can refresh other areas.
				$( document.body ).trigger( 'added_to_cart', [response.fragments] );
			}
		} ).always( function() {
			table.$tableWrapper.unblock();
			$multiForm.removeAttr( 'id' );
		} );

		return false;
	}

	function onAjaxLoad( event, settings, json, xhr ) {
		let table = event.data.table;

		if ( null !== json && 'data' in json && $.isArray( json.data ) ) {
			table.ajaxData = json.data;
		}

		table.$table.trigger( 'lazyload.wcpt', [table] );
	}

	function onClickToSearch( event ) {
		let $link = $( this ),
			table = event.data.table,
			columnName = $link.data( 'column' ),
			slug = $link.children( '[data-slug]' ).length ? $link.children( '[data-slug]' ).data( 'slug' ) : '';

		// If we have filters, update selection to match the value being searched for, and let onFilterChange handle the column searching.
		if ( slug && table.$filters.length ) {
			let $filter = table.$filters.filter( '[data-column="' + columnName + '"]' ).first();

			// Check the filter for this column exists, and has the clicked value present.
			if ( $filter.length && $filter.children( 'option[value="' + slug + '"]' ).length ) {
				$filter.val( slug ).trigger( 'change' );

				table.scrollToTop();
				return false;
			}
		}

		let dataTable = table.getDataTable(),
			column = dataTable.column( columnName + ':name' );

		if ( table.config.serverSide ) {
			// Bail if lazy loading and we don't have a slug (can't do search without it).
			if ( '' === slug ) {
				return true;
			}

			column.search( slug ).draw();
		} else {
			// Standard loading uses the link text to search column.
			let searchVal = '(^|, )' + $.fn.dataTable.util.escapeRegex( $link.text() ) + '(, |$)';
			column.search( searchVal, true, false ).draw();
		}

		table.scrollToTop();
		return false;
	}

	function onDraw( event ) {
		let table = event.data.table;

		// Add row attributes to each <tr> if using lazy load
		if ( table.config.serverSide ) {
			table.processAjaxData();
		}

		initContent( table.$table );

		if ( table.config.multiAddToCart && table.$tableWrapper.length ) {
			table.$tableWrapper.find( '.multi-cart-message' ).remove();
		}

		table.showHidePagination();
		table.$table.trigger( 'draw.wcpt', [table] );
	}

	function onFilterChange( event, setValueOnly ) {
		let $select = $( this ),
			table = event.data.table;

		if ( setValueOnly ) {
			return true;
		}

		let value = $select.val(),
			taxonomy = $select.data( 'tax' ),
			dataTable = table.getDataTable(),
			column = dataTable.column( $select.data( 'searchColumn' ) + ':name' );

		if ( table.config.serverSide ) {
			column.search( value ).draw();
		} else {
			let searchVal = value ? '(^| )' + value + '( |$)' : '';
			column.search( searchVal, true, false ).draw();
		}

		let $thisFilterGroup = table.$filters.filter( '[data-tax="' + taxonomy + '"]' ),
			$otherFilters = table.$filters.not( $thisFilterGroup );

		// If we have filters above and below table, update corresponding filter to match.
		$thisFilterGroup
			.not( $select[0] )
			.val( value )
			.trigger( 'change', [true] );

		if ( ! table.config.serverSide ) {
			// Update other filters to show only relevant search items.
			$otherFilters.each( function() {
				table.refreshFilterItems( $( this ) );
			} );
		}
	}

	function onInit( event ) {
		let table = event.data.table;

		table.$tableWrapper = table.$table.parent();
		table.$pagination = table.$tableWrapper.find( '.dataTables_paginate' );
		table.$tableControls = table.$tableWrapper.find( '.wc-product-table-controls' );

		table
			.initFilters()
			.initSelectWoo()
			.initResetButton()
			.registerVariationEvents()
			.initMultiCart()
			.initSearchOnClick()
			.initPhotoswipe()
			.initQuickViewPro()
			.showHidePagination();

		// fitVids will run on every draw event for lazy load, but for standard loading
		// we need to run fitVids onInit as well as initMedia only runs on subsequent draws.
		if ( ! table.config.serverSide && $.fn.fitVids ) {
			table.$table.fitVids();
		}

		table.$table
			.removeClass( 'loading' )
			.trigger( 'init.wcpt', [table] );
	}

	function onOpenPhotoswipe( event ) {
		event.stopPropagation();

		// Only open for click events.
		if ( 'click' !== event.type ) {
			return false;
		}

		let pswpElement = $( '.pswp' )[0],
			$target = $( event.target ),
			$galleryImage = $target.closest( '.woocommerce-product-gallery__image' ),
			items = [];

		if ( $galleryImage.length > 0 ) {
			$galleryImage.each( function( i, el ) {
				let img = $( el ).find( 'img' ),
					large_image_src = img.attr( 'data-large_image' ),
					large_image_w = img.attr( 'data-large_image_width' ),
					large_image_h = img.attr( 'data-large_image_height' ),
					item = {
						src: large_image_src,
						w: large_image_w,
						h: large_image_h,
						title: ( img.attr( 'data-caption' ) && img.attr( 'data-caption' ).length ) ? img.attr( 'data-caption' ) : img.attr( 'title' )
					};
				items.push( item );
			} );
		}

		let options = {
			index: 0,
			shareEl: false,
			closeOnScroll: false,
			history: false,
			hideAnimationDuration: 0,
			showAnimationDuration: 0
		};

		// Initializes and opens PhotoSwipe
		let photoswipe = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options );
		photoswipe.init();

		return false;
	}

	function onPage( event ) {
		// Animate back to top of table on next/previous page event
		event.data.table.scrollToTop();
	}

	function onProcessing( event, settings, processing ) {
		if ( processing ) {
			event.data.table.$table.block( blockConfig );
		} else {
			event.data.table.$table.unblock();
		}
	}

	function onReset( event ) {
		event.preventDefault();

		// Reload page without query params if we have them (e.g. layered nav filters)
		if ( window.location.search ) {
			window.location = getCurrentUrlWithoutFilters();
			return true;
		}

		let table = event.data.table,
			dataTable = table.getDataTable();

		// Reset responsive child rows
		table.$table.find( 'tr.child' ).remove();
		table.$table.find( 'tr.parent' ).removeClass( 'parent' );

		// Reset cart stuff
		table
			.resetQuantities()
			.resetVariations()
			.resetProductAddons()
			.resetMultiCartCheckboxes();

		// Remove add to cart notifications
		table.$tableWrapper.find( '.multi-cart-message' ).remove();
		table.$table.find( 'p.cart-error' ).remove();
		table.$table
			.find( '.cart .single_add_to_cart_button' )
			.removeClass( 'added' )
			.siblings( 'a.added_to_cart' ).remove();

		// Clear search for any filtered columns
		dataTable.columns( 'th[data-searchable="true"]' ).search( '' );

		// Reset ordering
		let initialOrder = table.$table.attr( 'data-order' );

		if ( initialOrder.length ) {
			let orderArray = initialOrder.replace( /[\[\]\" ]+/g, '' ).split( ',' );
			if ( 2 === orderArray.length ) {
				dataTable.order( orderArray );
			}
		}

		// Reset filters
		if ( table.$filters.length ) {
			table.$filters.val( '' ).trigger( 'change', [true] );

			if ( ! table.config.serverSide ) {
				table.$filters.each( function() {
					table.refreshFilterItems( $( this ) );
				} );
			}
		}

		// Reset initial search term
		let searchTerm = ( 'search' in table.config && 'search' in table.config.search ) ? table.config.search.search : '';

		// Set search, reset page length, then re-draw
		dataTable
			.search( searchTerm )
			.page.len( table.config.pageLength )
			.draw( true );

		if ( selectWooEnabled() ) {
			// If using selectWoo, setting the page length above won't update the select control, so we need to trigger change.
			table.$tableControls.find( '.dataTables_length select' ).trigger( 'change' );
		}
	}

	function onResponsiveDisplay( event, datatable, row, showHide, update ) {
		if ( showHide && ( typeof row.child() !== 'undefined' ) ) {

			// Initialise media and other content in child row
			initContent( row.child() );

			let table = event.data.table;

			table.$table.trigger( 'responsiveDisplay.wcpt', [table, datatable, row, showHide] );
		}
	}

	function onStateLoadParams( event, settings, data ) {
		let table = event.data.table;

		// Always reset to first page.
		data.start = 0;

		// If we have no active filter widgets, clear previous table search and reset ordering.
		if ( window.location.href === getCurrentUrlWithoutFilters() ) {

			// Reset page length
			if ( 'pageLength' in table.config ) {
				data.length = table.config.pageLength;
			}

			// Reset search
			if ( 'search' in table.config && 'search' in table.config.search ) {
				data.search.search = table.config.search.search;
			}

			// Clear any column searches
			for ( let i = 0; i < data.columns.length; i++ ) {
				data.columns[i].search.search = '';
			}

			// Reset ordering - use order from shortcode if specified, otherwise remove ordering
			if ( 'order' in table.config ) {
				data.order = table.config.order;
			}
		}

		// Store initial state
		table.initialState = data;
	}

	function onWindowLoad( event ) {
		let table = event.data.table;

		// Recalc column sizes on window load (e.g. to correctly contain media playlists)
		table.getDataTable()
			.columns.adjust()
			.responsive.recalc();

		table.$table.trigger( 'load.wcpt', [table] );
	}

	/******************************************
	 * JQUERY PLUGIN
	 ******************************************/

	/**
	 * jQuery plugin to create a product table for the current set of matched elements.
	 *
	 * @returns jQuery object - the set of matched elements the function was called with (for chaining)
	 */
	$.fn.productTable = function() {
		return this.each( function() {
			let table = new ProductTable( $( this ) );
			table.init();
		} );
	};

	$( function() {
		// Add support for hyphens and non-Roman characters in input names/keys in jquery-serialize-object.js
		if ( typeof FormSerializer !== 'undefined' ) {
			$.extend( FormSerializer.patterns, {
				validate: /^[a-z][a-z0-9_\-\%]*(?:\[(?:\d*|[a-z0-9_\-\%]+)\])*$/i,
				key: /[a-z0-9_\-\%]+|(?=\[\])/gi,
				named: /^[a-z0-9_\-\%]+$/i
			} );
		}

		if ( 'DataTable' in $.fn && $.fn.DataTable.ext ) {
			// Change DataTables error reporting to throw rather than alert
			$.fn.DataTable.ext.errMode = 'throw';
		}

		// Set fallback for WC add to cart params.
		if ( typeof wc_add_to_cart_params === 'undefined' ) {
			window.wc_add_to_cart_params = {
				cart_redirect_after_add: 'no',
				cart_url: '',
				i18n_view_cart: 'View cart'
			};
		}

		// Initialise all product tables
		$( '.wc-product-table' ).productTable();
	} );

} )( jQuery, window, document, product_table_params );