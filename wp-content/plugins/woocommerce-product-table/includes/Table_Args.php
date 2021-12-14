<?php

namespace Barn2\Plugin\WC_Product_Table;

use Barn2\Plugin\WC_Product_Table\Util\Columns_Util;
use Barn2\Plugin\WC_Product_Table\Util\Settings;
use Barn2\Plugin\WC_Product_Table\Util\Util;

/**
 * Responsible for storing and validating the product table arguments.
 * Parses an array of args into the corresponding properties.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Table_Args {

	/**
	 * @var array The original args array.
	 */
	private $args = [];

	/* Table params */
	public $columns;
	public $headings; // built from columns
	public $widths;
	public $auto_width;
	public $priorities;
	public $column_breakpoints;
	public $responsive_control;
	public $responsive_display;
	public $wrap;
	public $show_footer;
	public $search_on_click;
	public $filters;
	public $filter_headings; // built from filters
	public $quantities;
	public $variations;
	public $cart_button;
	public $ajax_cart;
	public $scroll_offset;
	public $description_length;
	public $links;
	public $lazy_load;
	public $cache;
	public $image_size;
	public $lightbox;
	public $shortcodes;
	public $button_text;
	public $date_format;
	public $date_columns;
	public $no_products_message;
	public $no_products_filtered_message;
	public $paging_type;
	public $page_length;
	public $search_box;
	public $totals;
	public $pagination;
	public $reset_button;
	public $add_selected_button;
	public $user_products;

	/* Query params */
	public $rows_per_page;
	public $product_limit;
	public $sort_by;
	public $sort_order;
	public $status;
	public $category;
	public $exclude_category;
	public $tag;
	public $term;
	public $numeric_terms;
	public $cf;
	public $year;
	public $month;
	public $day;
	public $exclude;
	public $include;
	public $search_term;

	/* Internal params */
	public $show_hidden;

	/* Lazy load params */
	public $offset         = 0;
	public $user_search_term;
	public $search_filters = [];

	/**
	 * @var array The default table args.
	 */
	public static $default_args = [
		'columns'                      => 'image,name,short-description,price,buy',
		// any from $standard_columns, any attribute (att:pa_colour), taxonomy (tax:product_vendor) or custom field (cf:my_field)
		'widths'                       => '',
		'auto_width'                   => true,
		'priorities'                   => '',
		'column_breakpoints'           => '',
		'responsive_control'           => 'inline',
		// inline or column
		'responsive_display'           => 'child_row',
		// child_row, child_row_visible, or modal
		'wrap'                         => true,
		'show_footer'                  => false,
		'search_on_click'              => true,
		'filters'                      => false,
		'quantities'                   => false,
		'variations'                   => false,
		'cart_button'                  => 'button',
		// button, button_checkbox, checkbox
		'ajax_cart'                    => true,
		'scroll_offset'                => 15,
		'description_length'           => 15,
		// number of words
		'links'                        => 'all',
		// allowed: all, none, or any combination of id, sku, name, image, tags, categories, terms, attributes
		'lazy_load'                    => false,
		'cache'                        => false,
		'image_size'                   => '70x70',
		'lightbox'                     => true,
		'shortcodes'                   => false,
		'button_text'                  => '',
		'date_format'                  => '',
		'date_columns'                 => '',
		'no_products_message'          => '',
		'no_products_filtered_message' => '',
		'paging_type'                  => 'numbers',
		'page_length'                  => 'bottom',
		'search_box'                   => 'top',
		'totals'                       => 'bottom',
		'pagination'                   => 'bottom',
		'reset_button'                 => true,
		'add_selected_button'          => 'top',
		'user_products'                => false,
		'rows_per_page'                => 25,
		'product_limit'                => 500,
		'sort_by'                      => 'menu_order',
		'sort_order'                   => '',
		// no default set - @see parse_args
		'status'                       => 'publish',
		'category'                     => '',
		// list of slugs or IDs
		'exclude_category'             => '',
		// list of slugs or IDs
		'tag'                          => '',
		// list of slugs or IDs
		'term'                         => '',
		// list of terms of the form <taxonomy>:<term>
		'numeric_terms'                => false,
		// set to true if using categories, tags or terms with numeric slugs
		'cf'                           => '',
		// list of custom fields of the form <field_key>:<field_value>
		'year'                         => '',
		// four digit year, e.g. 2011
		'month'                        => '',
		// two digit month, e.g. 12
		'day'                          => '',
		// two digit day, e.g. 03
		'exclude'                      => '',
		// list of post IDs
		'include'                      => '',
		// list of post IDs
		'search_term'                  => '',
		'show_hidden'                  => false
	];

	/**
	 * @var array The full list of standard columns (excludes prefixed columns such as custom fields, taxonomies, and attributes).
	 */
	private static $standard_columns = [
		'id',
		'sku',
		'name',
		'description',
		'short-description',
		'date',
		'categories',
		'tags',
		'image',
		'reviews',
		'stock',
		'weight',
		'dimensions',
		'price',
		'buy',
		'button'
	];

	/**
	 * @var array Some column replacements used for correcting misspelled columns.
	 */
	private static $column_replacements = [
		'ID'          => 'id',
		'SKU'         => 'sku',
		'title'       => 'name',
		'content'     => 'description',
		'excerpt'     => 'short-description',
		'category'    => 'categories',
		'rating'      => 'reviews',
		'add-to-cart' => 'buy'
	];

	/**
	 * @var array The default table args after merging with the saved settings.
	 */
	private static $_user_defaults = null;

	public function __construct( array $args ) {
		$this->set_args( $args );
	}

	public function __get( $name ) {
		if ( 'show_quantity' === $name ) {
			// Back-compat: old property name.
			return $this->quantities;
		} elseif ( isset( $this->$name ) ) {
			return $this->name;
		}

		return null;
	}

	public function get_args() {
		return $this->args;
	}

	public function set_args( array $args ) {
		// Check for old arg names.
		$args = self::back_compat_args( $args );

		// Lazy load args need to be merged in.
		$lazy_load_args = [
			'offset'           => $this->offset,
			'user_search_term' => $this->user_search_term,
			'search_filters'   => $this->search_filters
		];

		// Update by merging new args into previous args.
		$this->args = array_merge( self::get_user_defaults(), $lazy_load_args, $this->args, $args );

		// Parse/validate args & update properties.
		$this->parse_args( $this->args );
	}

	public function is_multi_add_to_cart() {
		return in_array( $this->cart_button, [ 'checkbox', 'button_checkbox' ] ) && in_array( 'buy', $this->columns );
	}

	private function parse_args( array $args ) {
		$defaults = self::get_user_defaults();

		// Define custom validation callbacks.
		$sanitize_list = [
			'filter'  => FILTER_CALLBACK,
			'options' => [ Util::class, 'sanitize_list_arg' ]
		];

		$sanitize_numeric_list = [
			'filter'  => FILTER_CALLBACK,
			'options' => [ Util::class, 'sanitize_numeric_list_arg' ]
		];

		$sanitize_string_array = [
			'filter' => FILTER_SANITIZE_STRING,
			'flags'  => FILTER_REQUIRE_ARRAY
		];

		$sanitize_search_term = [
			'filter' => FILTER_SANITIZE_STRING,
			'flags'  => FILTER_FLAG_NO_ENCODE_QUOTES
		];

		$sanitize_string_or_bool = [
			'filter'  => FILTER_CALLBACK,
			'options' => [ Util::class, 'sanitize_string_or_bool_arg' ]
		];

		// Setup validation array.
		$validation = [
			'columns'                      => is_array( $args['columns'] ) ? $sanitize_string_array : FILTER_SANITIZE_STRING,
			'widths'                       => $sanitize_list,
			'auto_width'                   => FILTER_VALIDATE_BOOLEAN,
			'priorities'                   => $sanitize_numeric_list,
			'column_breakpoints'           => $sanitize_list,
			'responsive_control'           => FILTER_SANITIZE_STRING,
			'responsive_display'           => FILTER_SANITIZE_STRING,
			'wrap'                         => FILTER_VALIDATE_BOOLEAN,
			'show_footer'                  => FILTER_VALIDATE_BOOLEAN,
			'search_on_click'              => FILTER_VALIDATE_BOOLEAN,
			'filters'                      => $sanitize_string_or_bool,
			'quantities'                   => FILTER_VALIDATE_BOOLEAN,
			'variations'                   => $sanitize_string_or_bool,
			'cart_button'                  => FILTER_SANITIZE_STRING,
			'ajax_cart'                    => FILTER_VALIDATE_BOOLEAN,
			'scroll_offset'                => [
				'filter'  => FILTER_VALIDATE_INT,
				'options' => [
					'default' => $defaults['scroll_offset']
				]
			],
			'description_length'           => [
				'filter'  => FILTER_VALIDATE_INT,
				'options' => [
					'default'   => $defaults['description_length'],
					'min_range' => -1
				]
			],
			'links'                        => $sanitize_string_or_bool,
			'lazy_load'                    => FILTER_VALIDATE_BOOLEAN,
			'cache'                        => FILTER_VALIDATE_BOOLEAN,
			'image_size'                   => $sanitize_list,
			'lightbox'                     => FILTER_VALIDATE_BOOLEAN,
			'shortcodes'                   => FILTER_VALIDATE_BOOLEAN,
			'button_text'                  => FILTER_SANITIZE_STRING,
			'date_format'                  => FILTER_SANITIZE_STRING,
			'date_columns'                 => $sanitize_list,
			'no_products_message'          => FILTER_SANITIZE_STRING,
			'no_products_filtered_message' => FILTER_SANITIZE_STRING,
			'paging_type'                  => FILTER_SANITIZE_STRING,
			'page_length'                  => $sanitize_string_or_bool,
			'search_box'                   => $sanitize_string_or_bool,
			'totals'                       => $sanitize_string_or_bool,
			'pagination'                   => $sanitize_string_or_bool,
			'reset_button'                 => FILTER_VALIDATE_BOOLEAN,
			'add_selected_button'          => FILTER_SANITIZE_STRING,
			'user_products'                => FILTER_VALIDATE_BOOLEAN,
			'rows_per_page'                => [
				'filter'  => FILTER_VALIDATE_INT,
				'options' => [
					'default'   => $defaults['rows_per_page'],
					'min_range' => -1
				]
			],
			'product_limit'                => [
				'filter'  => FILTER_VALIDATE_INT,
				'options' => [
					'default'   => $defaults['product_limit'],
					'min_range' => -1,
					'max_range' => 5000,
				]
			],
			'sort_by'                      => FILTER_SANITIZE_STRING,
			'sort_order'                   => FILTER_SANITIZE_STRING,
			'status'                       => $sanitize_list,
			'category'                     => $sanitize_list,
			'exclude_category'             => $sanitize_list,
			'tag'                          => $sanitize_list,
			'term'                         => $sanitize_list,
			'numeric_terms'                => FILTER_VALIDATE_BOOLEAN,
			'cf'                           => [
				'filter'  => FILTER_CALLBACK,
				'options' => [ Util::class, 'sanitize_list_arg_allow_space' ]
			],
			'year'                         => [
				'filter'  => FILTER_VALIDATE_INT,
				'options' => [
					'default'   => $defaults['year'],
					'min_range' => 1
				]
			],
			'month'                        => [
				'filter'  => FILTER_VALIDATE_INT,
				'options' => [
					'default'   => $defaults['month'],
					'min_range' => 1,
					'max_range' => 12
				]
			],
			'day'                          => [
				'filter'  => FILTER_VALIDATE_INT,
				'options' => [
					'default'   => $defaults['day'],
					'min_range' => 1,
					'max_range' => 31
				]
			],
			'exclude'                      => $sanitize_numeric_list,
			'include'                      => $sanitize_numeric_list,
			'search_term'                  => $sanitize_search_term,
			// Internal params
			'show_hidden'                  => FILTER_VALIDATE_BOOLEAN,
			// Lazy load params
			'offset'                       => [
				'filter'  => FILTER_VALIDATE_INT,
				'options' => [
					'default'   => 0,
					'min_range' => 0,
				]
			],
			'user_search_term'             => $sanitize_search_term,
			'search_filters'               => $sanitize_string_array
		];

		// Sanitize/validate all args.
		$args = filter_var_array( $args, $validation );

		// Set properties from the sanitized args.
		Util::set_object_vars( $this, $args );

		// Fill in any blanks.
		foreach ( [ 'columns', 'status', 'image_size', 'sort_by', 'links' ] as $arg ) {
			if ( empty( $this->$arg ) ) {
				$this->$arg = $defaults[ $arg ];
			}
		}

		// Make sure boolean args are definitely booleans - sometimes filter_var_array doesn't convert them properly
		foreach ( array_filter( $validation, [ self::class, 'array_filter_validate_boolean' ] ) as $arg => $val ) {
			$this->$arg = ( $this->$arg === true || $this->$arg === 'true' ) ? true : false;
		}

		// Convert some list args to arrays - columns, filters, links, category, tag, term, and cf are handled separately.
		foreach ( [ 'widths', 'priorities', 'column_breakpoints', 'status', 'include', 'exclude', 'exclude_category', 'date_columns' ] as $arg ) {
			$this->$arg = Util::string_list_to_array( $this->$arg );
		}

		// Columns, headings and filters.
		$this->parse_columns( $this->columns );
		$this->parse_filters( $this->filters, $this->columns, $this->variations );

		// Column widths
		if ( $this->widths ) {
			$this->widths = Util::array_pad_and_slice( $this->widths, count( $this->columns ), 'auto' );
		}

		// Responsive options
		if ( $this->priorities ) {
			$this->priorities = Util::array_pad_and_slice( $this->priorities, count( $this->columns ), 'default' );
		}

		if ( ! in_array( $this->responsive_control, [ 'inline', 'column' ] ) ) {
			$this->responsive_control = $defaults['responsive_control'];
		}

		if ( ! in_array( $this->responsive_display, [ 'child_row', 'child_row_visible', 'modal' ] ) ) {
			$this->responsive_display = $defaults['responsive_display'];
		}

		if ( $this->column_breakpoints ) {
			$this->column_breakpoints = Util::array_pad_and_slice( $this->column_breakpoints, count( $this->columns ), 'default' );
		}

		// Variations
		if ( true === $this->variations ) {
			$this->variations = 'dropdown';
		} elseif ( ! in_array( $this->variations, [ 'dropdown', 'separate' ] ) ) {
			$this->variations = false;
		}

		// Separate variations not currently supported for lazy load
		if ( 'separate' === $this->variations && $this->lazy_load ) {
			$this->variations = 'dropdown';
		}

		// Cart button
		if ( ! in_array( $this->cart_button, [ 'button', 'button_checkbox', 'checkbox' ] ) ) {
			$this->cart_button = $defaults['cart_button'];
		}

		// Add selected button
		if ( ! in_array( $this->add_selected_button, [ 'top', 'bottom', 'both' ] ) ) {
			$this->add_selected_button = $defaults['add_selected_button'];
		}

		// Text for 'button' column button
		if ( ! $this->button_text ) {
			$this->button_text = __( 'Show details', 'woocommerce-product-table' );
		}

		// Display options (page length, etc)
		foreach ( [ 'page_length', 'search_box', 'totals', 'pagination' ] as $display_option ) {
			if ( ! in_array( $this->$display_option, [ 'top', 'bottom', 'both', false ], true ) ) {
				$this->$display_option = $defaults[ $display_option ];
			}
		}

		// Links - used to control whether certain data items are links or plain text
		$this->links = is_string( $this->links ) ? strtr( strtolower( $this->links ), self::$column_replacements ) : $this->links;

		if ( true === $this->links || 'all' === $this->links ) {
			$this->links = [ 'all' ];
		} elseif ( false === $this->links || 'none' === $this->links ) {
			$this->links = [];
		} else {
			$this->links = array_intersect( explode( ',', $this->links ), [ 'sku', 'name', 'image', 'categories', 'tags', 'terms', 'attributes' ] );
		}

		// Paging type
		if ( ! in_array( $this->paging_type, [ 'numbers', 'simple', 'simple_numbers', 'full', 'full_numbers' ] ) ) {
			$this->paging_type = $defaults['paging_type'];
		}

		// Image size
		$this->image_size   = str_replace( [ ' ', ',' ], [ '', 'x' ], $this->image_size );
		$size_arr           = explode( 'x', $this->image_size );
		$size_numeric_count = count( array_filter( $size_arr, 'is_numeric' ) );

		if ( 1 === $size_numeric_count ) {
			// One number, so use for both width and height
			$this->image_size = [ $size_arr[0], $size_arr[0] ];
		} elseif ( 2 === $size_numeric_count ) {
			// Width and height specified
			$this->image_size = $size_arr;
		} // otherwise assume it's a text-based image size, e.g. 'thumbnail'

		$this->set_image_column_width();

		// Disable lightbox if Photoswipe not available
		if ( ! Util::doing_lazy_load() ) {
			$this->lightbox = $this->lightbox && wp_script_is( 'photoswipe-ui-default', 'registered' );
		}

		// Disable lightbox if explicitly linking from image column
		if ( in_array( 'image', $this->links ) ) {
			$this->lightbox = false;
		}

		// Validate date columns - only custom fields or taxonomies allowed
		if ( $this->date_columns ) {
			$this->date_columns = array_filter( $this->date_columns, [ self::class, 'array_filter_custom_field_or_taxonomy' ] );
		}

		// Sort by
		$this->sort_by = strtr( $this->sort_by, self::$column_replacements );

		// If sorting by attribute, make sure it uses the full attribute name.
		if ( $sort_att = Columns_Util::get_product_attribute( $this->sort_by ) ) {
			$this->sort_by = 'att:' . Util::get_attribute_name( $sort_att );
		}

		// Sort order - set default if not specified or invalid
		$this->sort_order = strtolower( $this->sort_order );

		if ( ! in_array( $this->sort_order, [ 'asc', 'desc' ] ) ) {
			// Default to descending if sorting by date, ascending for everything else
			$this->sort_order = in_array( $this->sort_by, array_merge( [ 'date', 'modified' ], $this->date_columns ) ) ? 'desc' : 'asc';
		}

		// Search terms
		if ( ! Util::is_valid_search_term( $this->search_term ) ) {
			$this->search_term = '';
		}

		if ( ! Util::is_valid_search_term( $this->user_search_term ) ) {
			$this->user_search_term = '';
		}

		// Product limit
		$this->product_limit = (int) apply_filters( 'wc_product_table_max_product_limit', $this->product_limit, $this );

		// Description length & rows per page - can be positive int or -1
		foreach ( [ 'description_length', 'rows_per_page', 'product_limit' ] as $arg ) {
			// Sanity check in case filter set an invalid value
			if ( ! is_int( $this->$arg ) || $this->$arg < -1 ) {
				$this->$arg = $defaults[ $arg ];
			}
			if ( 0 === $this->$arg ) {
				$this->$arg = -1;
			}
		}

		// Ignore product limit if lazy loading and the default product limit is used.
		if ( $this->lazy_load && (int) $defaults['product_limit'] === $this->product_limit ) {
			$this->product_limit = -1;
		}

		// If enabling shortcodes, display the full content
		if ( $this->shortcodes ) {
			$this->description_length = -1;
		}

		// If auto width disabled, we must use the inline +/- control otherwise control column is always shown
		if ( ! $this->auto_width ) {
			$this->responsive_control = 'inline';
		}

		do_action( 'wc_product_table_parse_args', $this );
	}

	private function parse_columns( $columns ) {
		$parsed = self::parse_columns_arg( $columns );

		if ( empty( $parsed['columns'] ) ) {
			$defaults = self::get_user_defaults();
			$parsed   = self::parse_columns_arg( $defaults['columns'] );
		}

		$this->columns  = $parsed['columns'];
		$this->headings = $parsed['headings'];
	}

	private function parse_filters( $filters, array $columns, $variations ) {
		$parsed = self::parse_filters_arg( $filters, $columns, $variations );

		$this->filters         = $parsed['filters'] ? $parsed['filters'] : false;
		$this->filter_headings = $parsed['headings'];
	}

	private function set_image_column_width() {
		if ( false === ( $image_col = array_search( 'image', $this->columns ) ) ) {
			return;
		}

		if ( $this->widths && isset( $this->widths[ $image_col ] ) && 'auto' !== $this->widths[ $image_col ] ) {
			return;
		}

		if ( $image_col_width = Util::get_image_size_width( $this->image_size ) ) {
			if ( ! $this->widths ) {
				$this->widths = array_fill( 0, count( $this->columns ), 'auto' );
			}
			$this->widths[ $image_col ] = $image_col_width . 'px';
		}
	}

	public static function get_user_defaults() {
		if ( null === self::$_user_defaults ) {
			self::$_user_defaults = array_merge( self::$default_args, self::settings_to_args( self::back_compat_args( Settings::get_setting_table_defaults() ) ) );
		}

		return self::$_user_defaults;
	}

	public static function parse_columns_arg( $columns ) {
		$parsed   = [];
		$headings = [];

		if ( ! is_array( $columns ) ) {
			$columns = Util::string_list_to_array( $columns );
		}

		foreach ( $columns as $column ) {
			$prefix = sanitize_key( strtok( $column, ':' ) );
			$col    = false;

			if ( in_array( $prefix, [ 'cf', 'att', 'tax' ] ) ) {
				// Custom field, product attribute or taxonomy.
				$suffix = trim( strtok( ':' ) );

				if ( ! $suffix ) {
					continue; // no custom field, attribute, or taxonomy specified
				} elseif ( 'att' === $prefix ) {
					$suffix = Util::get_attribute_name( $suffix );
				} elseif ( 'tax' === $prefix && ! taxonomy_exists( $suffix ) ) {
					continue; // invalid taxonomy
				}

				$col = "{$prefix}:{$suffix}";
			} else {
				// Standard or custom column.
				$col = $prefix;

				// Check for common typos in column names.
				$check = strtr( $prefix, self::$column_replacements );

				if ( in_array( $check, self::$standard_columns ) ) {
					$col = $check;
				}
			}


			// Only add column if valid and not added already.
			if ( $col && ! in_array( $col, $parsed ) ) {
				$parsed[]   = $col;
				$headings[] = strtok( '' ); // fetch rest of heading
			}
		}

		return [
			'columns'  => $parsed,
			'headings' => $headings
		];
	}

	public static function parse_filters_arg( $filters, array $columns = [], $variations = false ) {
		$parsed   = [];
		$headings = [];

		// If filters is true, set based on table columns.
		if ( true === $filters ) {
			$filters = (array) $columns;

			// If displaying variations, we add all attribute filters to the list by including 'attributes' in the filters list.
			if ( (bool) $variations ) {
				if ( false !== ( $buy_index = array_search( 'buy', $filters ) ) ) {
					$filters[ $buy_index ] = 'attributes';
				}
			}
		}

		if ( ! is_array( $filters ) ) {
			$filters = Util::string_list_to_array( $filters );
		}

		// Re-key.
		$filters = array_values( $filters );

		// If the 'attributes' keyword is specified, replace it with all attribute taxonomies.
		if ( false !== ( $attributes_index = array_search( 'attributes', $filters ) ) ) {
			// 'attributes' keyword found - replace with all global product attributes.
			$attribute_filters = preg_replace( '/^/', 'att:', wc_get_attribute_taxonomy_names() );
			$before            = array_slice( $filters, 0, $attributes_index );
			$after             = array_slice( $filters, $attributes_index + 1 );
			$filters           = array_merge( $before, $attribute_filters, $after );
		}

		foreach ( $filters as $filter ) {
			$f      = false;
			$prefix = strtok( $filter, ':' );

			if ( 'tax' === $prefix ) {
				// Custom taxonomy filter.
				$taxonomy = trim( strtok( ':' ) );

				if ( taxonomy_exists( $taxonomy ) ) {
					$f = 'tax:' . $taxonomy;
				}
			} elseif ( 'att' === $prefix ) {
				// Attribute filter.
				$attribute = Util::get_attribute_name( trim( strtok( ':' ) ) );

				// Only global attributes (i.e. taxonomies) are allowed as a filter
				if ( taxonomy_is_product_attribute( $attribute ) ) {
					$f = 'att:' . $attribute;
				}
			} elseif ( in_array( $prefix, [ 'categories', 'tags' ] ) ) {
				// Categories or tags filter.
				$f = $prefix;
			}

			if ( $f && ! in_array( $f, $parsed ) ) {
				$parsed[]   = $f;
				$headings[] = strtok( '' );
			}
		}

		return [
			'filters'  => $parsed,
			'headings' => $headings
		];
	}

	/**
	 * Maintain support for old args names.
	 *
	 * @param array $args The array of product table attributes
	 * @return array The updated attributes with old ones replaced with their new equivalent
	 */
	public static function back_compat_args( array $args ) {
		if ( empty( $args ) ) {
			return $args;
		}

		$compat = [
			'add_to_cart'          => 'cart_button',
			'display_page_length'  => 'page_length',
			'display_totals'       => 'totals',
			'display_pagination'   => 'pagination',
			'display_search_box'   => 'search_box',
			'display_reset_button' => 'reset_button',
			'show_quantities'      => 'show_quantity',
			'show_quantity'        => 'quantities'
		];

		foreach ( $compat as $old => $new ) {
			if ( isset( $args[ $old ] ) ) {
				$args[ $new ] = $args[ $old ];
				unset( $args[ $old ] );
			}
		}

		// 'add_selected_text' used to be stored in the table args.
		unset( $args['add_selected_text'] );

		return $args;
	}

	private static function settings_to_args( $settings ) {
		if ( empty( $settings ) ) {
			return $settings;
		}

		// Custom filter option
		if ( isset( $settings['filters'] ) && 'custom' === $settings['filters'] ) {
			if ( empty( $settings['filters_custom'] ) ) {
				$settings['filters'] = self::$default_args['filters'];
			} else {
				$settings['filters'] = $settings['filters_custom'];
			}
		}

		// Custom sort by option
		if ( isset( $settings['sort_by'] ) && 'custom' === $settings['sort_by'] ) {
			if ( empty( $settings['sort_by_custom'] ) ) {
				$settings['sort_by'] = self::$default_args['sort_by'];
			} else {
				$settings['sort_by'] = $settings['sort_by_custom'];
			}
		}

		// Unset settings that don't map to shortcode args
		unset( $settings['filters_custom'] );
		unset( $settings['sort_by_custom'] );

		// Check for empty settings
		foreach ( [ 'columns', 'image_size', 'links' ] as $arg ) {
			if ( empty( $settings[ $arg ] ) ) {
				$settings[ $arg ] = self::$default_args[ $arg ];
			}
		}

		// Ensure int settings are valid
		foreach ( [ 'rows_per_page', 'description_length', 'product_limit' ] as $arg ) {
			if ( isset( $settings[ $arg ] ) ) {
				$settings[ $arg ] = (int) $settings[ $arg ];

				if ( 0 === $settings[ $arg ] || $settings[ $arg ] < -1 ) {
					$settings[ $arg ] = self::$default_args[ $arg ];
				}
			}
		}

		return $settings;
	}

	private static function array_filter_validate_boolean( $var ) {
		return $var === FILTER_VALIDATE_BOOLEAN;
	}

	private static function array_filter_custom_field_or_taxonomy( $column ) {
		return Columns_Util::is_custom_field( $column ) || Columns_Util::is_custom_taxonomy( $column );
	}

	/**
	 * @deprecated 2.8 Replaced by self::get_user_defaults.
	 */
	public static function get_defaults() {
		//_deprecated_function( __METHOD__, '2.8', self::class . '::get_user_defaults' );
		return self::get_user_defaults();
	}

}
