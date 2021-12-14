<?php

namespace Barn2\Plugin\WC_Product_Table\Admin;

use Barn2\Plugin\WC_Product_Table\Table_Args;
use Barn2\Plugin\WC_Product_Table\Util\Settings;
use Barn2\Plugin\WC_Product_Table\Util\Util;
use Barn2\WPT_Lib\Plugin\Licensed_Plugin;
use Barn2\WPT_Lib\Registerable;
use Barn2\WPT_Lib\Util as Lib_Util;
use Barn2\WPT_Lib\WooCommerce\Admin\Custom_Settings_Fields;
use WC_Admin_Settings;
use WC_Barn2_Plugin_Promo;

/**
 * Provides functions for the plugin settings page in the WordPress admin.
 *
 * Settings can be accessed at WooCommerce -> Settings -> Products -> Product tables.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Settings_Page implements Registerable {

	private $plugin;

	public function __construct( Licensed_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	public function register() {
		// Register our custom settings types.
		$extra_setting_fields = new Custom_Settings_Fields();
		$extra_setting_fields->register();

		// Add sections & settings.
		add_filter( 'woocommerce_get_sections_products', [ $this, 'add_section' ] );
		add_filter( 'woocommerce_get_settings_products', [ $this, 'add_settings' ], 10, 2 );

		// Support old settings structure.
		add_filter( 'woocommerce_settings_products', [ $this, 'back_compat_settings' ], 5 );

		// Sanitize settings
		$license_setting = $this->plugin->get_license_setting();
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . $license_setting->get_license_setting_name(), [ $license_setting, 'save_license_key' ] );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . Settings::OPTION_TABLE_STYLING, array( self::class, 'sanitize_option_table_styling' ), 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . Settings::OPTION_TABLE_DEFAULTS, [ self::class, 'sanitize_option_table_defaults' ], 10, 3 );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . Settings::OPTION_MISC, [ self::class, 'sanitize_option_misc' ], 10, 3 );

		// Add plugin promo section.
		if ( class_exists( 'WC_Barn2_Plugin_Promo' ) ) {
			$plugin_promo = new WC_Barn2_Plugin_Promo( $this->plugin->get_item_id(), $this->plugin->get_file(), Settings::SECTION_SLUG );
			$plugin_promo->register();
		}
	}

	public function add_section( $sections ) {
		$sections[ Settings::SECTION_SLUG ] = __( 'Product tables', 'woocommerce-product-table' );

		return $sections;
	}

	public function add_settings( $settings, $current_section ) {
		// Check we're on the correct settings section
		if ( Settings::SECTION_SLUG !== $current_section ) {
			return $settings;
		}

		// Settings wrapper.
		$plugin_settings = [
			[
				'id'    => 'product_table_settings_start',
				'type'  => 'settings_start',
				'class' => 'product-table-settings'
			]
		];

		// License key setting.
		$plugin_settings = array_merge( $plugin_settings, [
			[
				'title' => __( 'Product tables', 'woocommerce-product-table' ),
				'type'  => 'title',
				'id'    => 'product_table_settings_license',
				'desc'  => '<p>' . __( 'The following options control the WooCommerce Product Table extension.', 'woocommerce-product-table' ) . '<p>'
				           . '<p>'
				           . Lib_Util::format_link( $this->plugin->get_documentation_url(), __( 'Documentation', 'woocommerce-product-table' ) ) . ' | '
				           . Lib_Util::barn2_link( 'support-center/', __( 'Support', 'woocommerce-product-table' ) )
				           . '</p>'
			],
			$this->plugin->get_license_setting()->get_license_key_setting(),
			$this->plugin->get_license_setting()->get_license_override_setting(),
			[
				'type' => 'sectionend',
				'id'   => 'product_table_settings_license'
			]
		] );

		// Table design settings.
		$plugin_settings = array_merge( $plugin_settings, [
			[
				'title' => __( 'Table design', 'woocommerce-product-table' ),
				'desc'  => __( 'You can customize the table design to suit your requirements.', 'woocommerce-product-table' ),
				'type'  => 'title',
				'id'    => 'product_table_settings_design',
			],
			[
				'title'             => __( 'Design', 'woocommerce-product-table' ),
				'type'              => 'radio',
				'id'                => Settings::OPTION_TABLE_STYLING . '[use_theme]',
				'options'           => [
					'theme'  => __( 'Default', 'woocommerce-product-table' ),
					'custom' => __( 'Custom', 'woocommerce-product-table' )
				],
				'default'           => 'theme',
				'class'             => 'toggle-parent',
				'custom_attributes' => [
					'data-child-class' => 'custom-style',
					'data-toggle-val'  => 'custom'
				]
			],
			[
				'type'  => 'help_note',
				'desc'  => __( 'Choose your custom table styles below. Any settings you leave blank will default to your theme styles.', 'woocommerce-product-table' ),
				'class' => 'custom-style'
			],
			[
				'title'    => __( 'Borders', 'woocommerce-product-table' ),
				'type'     => 'color_size',
				'id'       => Settings::OPTION_TABLE_STYLING . '[border_outer]',
				'desc'     => $this->get_icon( 'external-border.svg', __( 'External border icon', 'woocommerce-product-table' ) ) . __( 'External', 'woocommerce-product-table' ),
				'desc_tip' => __( 'The border for the outer edges of the table.', 'woocommerce-product-table' ),
				'class'    => 'custom-style',
			],
			[
				'type'     => 'color_size',
				'id'       => Settings::OPTION_TABLE_STYLING . '[border_header]',
				/* translators: 'Header' in this context refers to the heading row of a table. */
				'desc'     => $this->get_icon( 'header-border.svg', __( 'Header border icon', 'woocommerce-product-table' ) ) . __( 'Header', 'woocommerce-product-table' ),
				'desc_tip' => __( 'The border for the bottom of the header row.', 'woocommerce-product-table' ),
				'class'    => 'custom-style',
			],
			[
				'type'     => 'color_size',
				'id'       => Settings::OPTION_TABLE_STYLING . '[border_cell]',
				/* translators: 'Cell' in this context refers to a cell in a table or spreadsheet. */
				'desc'     => $this->get_icon( 'cell-border.svg', __( 'Cell border icon', 'woocommerce-product-table' ) ) . __( 'Cell', 'woocommerce-product-table' ),
				'desc_tip' => __( 'The border between cells in your table.', 'woocommerce-product-table' ),
				'class'    => 'custom-style',
			],
			[
				'title'       => __( 'Header background', 'woocommerce-product-table' ),
				'type'        => 'color',
				'id'          => Settings::OPTION_TABLE_STYLING . '[header_bg]',
				'desc_tip'    => __( 'The header background color.', 'woocommerce-product-table' ),
				'placeholder' => __( 'Color', 'woocommerce-product-table' ),
				'class'       => 'custom-style ',
				'css'         => 'width:6.7em'
			],
			[
				'title'       => __( 'Cell background', 'woocommerce-product-table' ),
				'type'        => 'color',
				'id'          => Settings::OPTION_TABLE_STYLING . '[cell_bg]',
				'desc_tip'    => __( 'The main background color used for the table contents.', 'woocommerce-product-table' ),
				'placeholder' => __( 'Color', 'woocommerce-product-table' ),
				'class'       => 'custom-style ',
				'css'         => 'width:6.7em'
			],
			[
				'title'    => __( 'Header font', 'woocommerce-product-table' ),
				'type'     => 'color_size',
				'id'       => Settings::OPTION_TABLE_STYLING . '[header_font]',
				'desc_tip' => __( 'The font used in the table header.', 'woocommerce-product-table' ),
				'min'      => 1,
				'class'    => 'custom-style',
			],
			[
				'title'    => __( 'Cell font', 'woocommerce-product-table' ),
				'type'     => 'color_size',
				'id'       => Settings::OPTION_TABLE_STYLING . '[cell_font]',
				'desc_tip' => __( 'The font used for the table contents.', 'woocommerce-product-table' ),
				'min'      => 1,
				'class'    => 'custom-style',
			],
			[
				'type' => 'sectionend',
				'id'   => 'product_table_settings_design'
			]
		] );

		$default_args       = Settings::to_woocommerce_settings( Table_Args::$default_args );
		$link_fmt           = '<a href="%s" target="_blank">';
		$table_display_desc = '<p>' . __( 'The following options replace the standard WooCommerce template with a product table. You can also add the [product_table] shortcode to any post or page.', 'woocommerce-product-table' ) . '</p>';

		$plugin_settings = array_merge( $plugin_settings, array_merge(
			[
				[
					'title' => __( 'Shop integration', 'woocommerce-product-table' ),
					'type'  => 'title',
					'id'    => 'product_table_settings_selecting',
					'desc'  => apply_filters( 'wc_product_table_display_admin_description', $table_display_desc ),
				],
				[
					'title'         => __( 'Where to display product tables', 'woocommerce-product-table' ),
					'type'          => 'checkbox',
					'id'            => Settings::OPTION_MISC . '[shop_override]',
					'desc'          => __( 'Shop page', 'woocommerce-product-table' ),
					'default'       => 'no',
					'checkboxgroup' => 'start'
				],
				[
					'type'          => 'checkbox',
					'id'            => Settings::OPTION_MISC . '[search_override]',
					'desc'          => __( 'Product search results', 'woocommerce-product-table' ),
					'default'       => 'no',
					'checkboxgroup' => ''
				],
				[
					'type'          => 'checkbox',
					'id'            => Settings::OPTION_MISC . '[archive_override]',
					'desc'          => __( 'Product categories', 'woocommerce-product-table' ),
					'default'       => 'no',
					'checkboxgroup' => ''
				],
				[
					'type'          => 'checkbox',
					'id'            => Settings::OPTION_MISC . '[product_tag_override]',
					'desc'          => __( 'Product tags', 'woocommerce-product-table' ),
					'default'       => 'no',
					'checkboxgroup' => ''
				],
				[
					'type'          => 'checkbox',
					'id'            => Settings::OPTION_MISC . '[attribute_override]',
					'desc'          => __( 'Product attributes', 'woocommerce-product-table' ),
					'default'       => 'no',
					'checkboxgroup' => ''
				],
			],
			$this->get_taxonomy_settings(),
			[
				[
					'type' => 'sectionend',
					'id'   => 'product_table_settings_selecting'
				]
			]
		) );

		$plugin_settings = array_merge( $plugin_settings, [
			[
				'title' => __( 'Table content', 'woocommerce-product-table' ),
				'type'  => 'title',
				'id'    => 'product_table_settings_content',
				'desc'  => '<p>' . __( 'These options set defaults for all product tables. You can override them in the shortcode for individual tables.', 'woocommerce-product-table' ) . '</p>'
				           . '<p>' . Lib_Util::barn2_link( 'kb/product-table-options', __( 'See the full list of shortcode options', 'woocommerce-product-table' ) ) . '</p>'
			],
			[
				'title'   => __( 'Columns', 'woocommerce-product-table' ),
				'type'    => 'text',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[columns]',
				'desc'    => __( 'Enter the columns for your product tables.', 'woocommerce-product-table' ) . ' ' . Lib_Util::barn2_link( 'kb/product-table-columns' ),
				'default' => $default_args['columns'],
				'css'     => 'width:600px;max-width:100%;'
			],
			[
				'title'    => __( 'Image size', 'woocommerce-product-table' ),
				'type'     => 'text',
				'id'       => Settings::OPTION_TABLE_DEFAULTS . '[image_size]',
				'desc'     => __( 'W x H in pixels, e.g. 70x50', 'woocommerce-product-table' ),
				'desc_tip' => __( 'You can also enter a standard image size such as thumbnail, shop_thumbnail, medium, etc.', 'woocommerce-product-table' ),
				'default'  => $default_args['image_size'],
				'css'      => 'width:200px;max-width:100%;'
			],
			[
				'title'   => __( 'Image lightbox', 'woocommerce-product-table' ),
				'type'    => 'checkbox',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[lightbox]',
				'desc'    => __( 'Show product images in a lightbox', 'woocommerce-product-table' ),
				'default' => $default_args['lightbox'],
			],
			[
				'title'             => __( 'Description length', 'woocommerce-product-table' ),
				'type'              => 'number',
				'id'                => Settings::OPTION_TABLE_DEFAULTS . '[description_length]',
				'desc'              => __( 'words', 'woocommerce-product-table' ),
				'desc_tip'          => __( 'Enter -1 to show the full product description including formatting.', 'woocommerce-product-table' ),
				'default'           => $default_args['description_length'],
				'css'               => 'width:75px',
				'custom_attributes' => [
					'min' => -1
				]
			],
			[
				'title'    => __( 'Product links', 'woocommerce-product-table' ),
				'type'     => 'text',
				'id'       => Settings::OPTION_TABLE_DEFAULTS . '[links]',
				'desc'     => __( 'Include links to the relevant product, category, tag, or attribute.', 'woocommerce-product-table' ) . ' ' . Lib_Util::barn2_link( 'kb/product-table-links' ),
				'desc_tip' => __( "Enter all, none, or a combination of: sku, name, image, tags, categories, terms, or attributes as a comma-separated list.", 'woocommerce-product-table' ),
				'default'  => $default_args['links'],
				'css'      => 'width:200px;max-width:100%;'
			],
			[
				'type' => 'sectionend',
				'id'   => 'product_table_settings_content'
			]
		] );

		$plugin_settings = array_merge( $plugin_settings, [
			[
				'title' => __( 'Loading', 'woocommerce-product-table' ),
				'type'  => 'title',
				'id'    => 'product_table_settings_loading'
			],
			[
				'title'             => __( 'Lazy load', 'woocommerce-product-table' ),
				'type'              => 'checkbox',
				'id'                => Settings::OPTION_TABLE_DEFAULTS . '[lazy_load]',
				'desc'              => __( 'Load products one page at a time', 'woocommerce-product-table' ),
				'desc_tip'          => __( 'Enable this if you have many products or experience slow page load times.', 'woocommerce-product-table' ) . '<br/>' .
				                       sprintf(
					                       __( 'Warning: Lazy load has %1$ssome limitations%2$s &mdash; it limits the search, sorting, dropdown filters, and variations.', 'woocommerce-product-table' ),
					                       Lib_Util::format_barn2_link_open( 'kb/lazy-load' ),
					                       '</a>'
				                       ),
				'default'           => $default_args['lazy_load'],
				'class'             => 'toggle-parent',
				'custom_attributes' => [
					'data-child-class' => 'toggle-product-limit',
					'data-toggle-val'  => 0
				]
			],
			[
				'title'             => __( 'Product limit', 'woocommerce-product-table' ),
				'type'              => 'number',
				'id'                => Settings::OPTION_TABLE_DEFAULTS . '[product_limit]',
				'desc'              => __( 'The maximum number of products in one table.', 'woocommerce-product-table' ),
				'desc_tip'          => __( "Enter -1 to show all products.", 'woocommerce-product-table' ),
				'default'           => $default_args['product_limit'],
				'class'             => 'toggle-product-limit',
				'custom_attributes' => [
					'min' => -1
				],
				'css'               => 'width:75px'
			],
			[
				'title'             => __( 'Rows per page', 'woocommerce-product-table' ),
				'type'              => 'number',
				'id'                => Settings::OPTION_TABLE_DEFAULTS . '[rows_per_page]',
				'desc'              => __( 'The number of products per page of results.', 'woocommerce-product-table' ),
				'desc_tip'          => __( "Enter -1 to show all products on a single page.", 'woocommerce-product-table' ),
				'default'           => $default_args['rows_per_page'],
				'css'               => 'width:75px',
				'custom_attributes' => [
					'min' => -1
				]
			],
			[
				'type' => 'sectionend',
				'id'   => 'product_table_settings_loading'
			]
		] );

		$plugin_settings = array_merge( $plugin_settings, [
			[
				'title' => __( 'Sorting', 'woocommerce-product-table' ),
				'type'  => 'title',
				'id'    => 'product_table_settings_sorting'
			],
			[
				'title'             => __( 'Sort by', 'woocommerce-product-table' ),
				'type'              => 'select',
				'id'                => Settings::OPTION_TABLE_DEFAULTS . '[sort_by]',
				'options'           => [
					'menu_order' => __( 'As listed in the Products screen (menu order)', 'woocommerce-product-table' ),
					'sku'        => __( 'SKU', 'woocommerce-product-table' ),
					'name'       => __( 'Name', 'woocommerce-product-table' ),
					'id'         => __( 'ID', 'woocommerce-product-table' ),
					'price'      => __( 'Price', 'woocommerce-product-table' ),
					'popularity' => __( 'Number of sales', 'woocommerce-product-table' ),
					'reviews'    => __( 'Average reviews', 'woocommerce-product-table' ),
					'date'       => __( 'Date added', 'woocommerce-product-table' ),
					'modified'   => __( 'Date modified', 'woocommerce-product-table' ),
					'custom'     => __( 'Other', 'woocommerce-product-table' )
				],
				'desc'              => __( 'The initial sort order applied to the table.', 'woocommerce-product-table' ) . ' ' . Lib_Util::barn2_link( 'kb/product-table-sort-options' ),
				'default'           => $default_args['sort_by'],
				'class'             => 'toggle-parent wc-enhanced-select',
				'custom_attributes' => [
					'data-child-class' => 'custom-sort',
					'data-toggle-val'  => 'custom'
				]
			],
			[
				'title' => __( 'Sort column', 'woocommerce-product-table' ),
				'type'  => 'text',
				'id'    => Settings::OPTION_TABLE_DEFAULTS . '[sort_by_custom]',
				'class' => 'custom-sort',
				'desc'  => __( 'Enter a column name, e.g. description, att:size, etc. Will only work when lazy load is disabled.', 'woocommerce-product-table' ),
				'css'   => 'width:200px;max-width:100%;'
			],
			[
				'title'   => __( 'Sort direction', 'woocommerce-product-table' ),
				'type'    => 'select',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[sort_order]',
				'options' => [
					''     => __( 'Automatic', 'woocommerce-product-table' ),
					'asc'  => __( 'Ascending (A to Z, 1 to 99)', 'woocommerce-product-table' ),
					'desc' => __( 'Descending (Z to A, 99 to 1)', 'woocommerce-product-table' )
				],
				'default' => $default_args['sort_order'],
				'class'   => 'wc-enhanced-select'
			],
			[
				'type' => 'sectionend',
				'id'   => 'product_table_settings_sorting'
			]
		] );

		$plugin_settings = array_merge( $plugin_settings, [
			[
				'title' => __( 'Add to cart', 'woocommerce-product-table' ),
				'type'  => 'title',
				'id'    => 'product_table_settings_cart'
			],
			[
				'title'   => __( 'Adding products to cart', 'woocommerce-product-table' ),
				'type'    => 'select',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[cart_button]',
				'options' => [
					'button'          => __( 'Add to cart buttons', 'woocommerce-product-table' ),
					'checkbox'        => __( 'Add to cart checkboxes', 'woocommerce-product-table' ),
					'button_checkbox' => __( 'Buttons and checkboxes', 'woocommerce-product-table' )
				],
				'desc'    => __( 'How a customer orders products from the table.', 'woocommerce-product-table' ) . ' ' . Lib_Util::barn2_link( 'kb/add-to-cart-buttons' ),
				'default' => $default_args['cart_button'],
				'class'   => 'wc-enhanced-select'
			],
			[
				'title'   => __( 'Quantities', 'woocommerce-product-table' ),
				'type'    => 'checkbox',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[quantities]',
				'desc'    => __( 'Show a quantity box for each product', 'woocommerce-product-table' ),
				'default' => $default_args['quantities']
			],
			[
				'title'   => __( 'Variations', 'woocommerce-product-table' ),
				'type'    => 'select',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[variations]',
				'options' => [
					'dropdown' => __( 'Show as dropdown lists', 'woocommerce-product-table' ),
					'separate' => __( 'Show one variation per row', 'woocommerce-product-table' ),
					'false'    => __( 'Show a Read More button linking to product page', 'woocommerce-product-table' ),
				],
				'desc'    => __( 'How to display options for variable products.', 'woocommerce-product-table' ) . ' ' . Lib_Util::barn2_link( 'kb/product-variations' ),
				'default' => $default_args['variations'],
				'class'   => 'wc-enhanced-select'
			],
			[
				'title'    => __( 'Multi-select cart button', 'woocommerce-product-table' ),
				'type'     => 'select',
				'id'       => Settings::OPTION_TABLE_DEFAULTS . '[add_selected_button]',
				'options'  => [
					'top'    => __( 'Above table', 'woocommerce-product-table' ),
					'bottom' => __( 'Below table', 'woocommerce-product-table' ),
					'both'   => __( 'Above and below table', 'woocommerce-product-table' )
				],
				'desc'     => __( "The position of the 'Add to cart' button to add multiple products from the table.", 'woocommerce-product-table' ),
				'desc_tip' => __( "Only applicable if using checkboxes for the 'Adding products to cart' option above", 'woocommerce-product-table' ),
				'default'  => $default_args['add_selected_button'],
				'class'    => 'wc-enhanced-select'
			],
			[
				'title'   => __( "Multi-select button text", 'woocommerce-product-table' ),
				'type'    => 'text',
				'id'      => Settings::OPTION_MISC . '[add_selected_text]',
				'desc'    => __( 'The text for the multi-select add to cart button.', 'woocommerce-product-table' ),
				'default' => Settings::add_selected_to_cart_default_text()
			],
			[
				'type' => 'sectionend',
				'id'   => 'product_table_settings_cart'
			]
		] );

		$plugin_settings = array_merge( $plugin_settings, [
			[
				'title' => __( 'Table controls', 'woocommerce-product-table' ),
				'type'  => 'title',
				'id'    => 'product_table_settings_controls'
			],
			[
				'title'             => __( 'Product filters', 'woocommerce-product-table' ),
				'type'              => 'select',
				'id'                => Settings::OPTION_TABLE_DEFAULTS . '[filters]',
				'options'           => [
					'false'  => __( 'Disabled', 'woocommerce-product-table' ),
					'true'   => __( 'Show based on table content', 'woocommerce-product-table' ),
					'custom' => __( 'Custom', 'woocommerce-product-table' )
				],
				'desc'              => __( 'Display dropdown lists to filter the table by category, tag, attribute or taxonomy.', 'woocommerce-product-table' ) . ' ' . Lib_Util::barn2_link( 'kb/wpt-filters/#filter-dropdowns' ),
				'default'           => $default_args['filters'],
				'class'             => 'toggle-parent wc-enhanced-select',
				'custom_attributes' => [
					'data-child-class' => 'custom-search-filter',
					'data-toggle-val'  => 'custom'
				]
			],
			[
				'title'    => __( 'Custom filters', 'woocommerce-product-table' ),
				'type'     => 'text',
				'id'       => Settings::OPTION_TABLE_DEFAULTS . '[filters_custom]',
				'desc'     => __( 'Enter the filters as a comma-separated list.', 'woocommerce-product-table' ) . ' ' . Lib_Util::barn2_link( 'kb/wpt-filters/#filter-dropdowns' ),
				'desc_tip' => __( 'E.g. categories,tags,att:color', 'woocommerce-product-table' ),
				'class'    => 'regular-text custom-search-filter'
			],
			[
				'title'   => __( 'Search box', 'woocommerce-product-table' ),
				'type'    => 'select',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[search_box]',
				'options' => [
					'top'    => __( 'Above table', 'woocommerce-product-table' ),
					'bottom' => __( 'Below table', 'woocommerce-product-table' ),
					'both'   => __( 'Above and below table', 'woocommerce-product-table' ),
					'false'  => __( 'Hidden', 'woocommerce-product-table' )
				],
				'default' => $default_args['search_box'],
				'class'   => 'wc-enhanced-select'
			],
			[
				'title'   => __( 'Reset link', 'woocommerce-product-table' ),
				'type'    => 'checkbox',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[reset_button]',
				'desc'    => __( 'Show the reset link above the table', 'woocommerce-product-table' ),
				'default' => $default_args['reset_button']
			],
			[
				'title'   => __( 'Page length', 'woocommerce-product-table' ),
				'type'    => 'select',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[page_length]',
				'options' => [
					'top'    => __( 'Above table', 'woocommerce-product-table' ),
					'bottom' => __( 'Below table', 'woocommerce-product-table' ),
					'both'   => __( 'Above and below table', 'woocommerce-product-table' ),
					'false'  => __( 'Hidden', 'woocommerce-product-table' )
				],
				'desc'    => __( "The position of the 'Show [x] per page' option.", 'woocommerce-product-table' ),
				'default' => $default_args['page_length'],
				'class'   => 'wc-enhanced-select'
			],
			[
				'title'   => __( 'Product totals', 'woocommerce-product-table' ),
				'type'    => 'select',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[totals]',
				'options' => [
					'top'    => __( 'Above table', 'woocommerce-product-table' ),
					'bottom' => __( 'Below table', 'woocommerce-product-table' ),
					'both'   => __( 'Above and below table', 'woocommerce-product-table' ),
					'false'  => __( 'Hidden', 'woocommerce-product-table' )
				],
				'default' => $default_args['totals'],
				'class'   => 'wc-enhanced-select'
			],
			[
				'title'   => __( 'Pagination', 'woocommerce-product-table' ),
				'type'    => 'select',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[pagination]',
				'options' => [
					'top'    => __( 'Above table', 'woocommerce-product-table' ),
					'bottom' => __( 'Below table', 'woocommerce-product-table' ),
					'both'   => __( 'Above and below table', 'woocommerce-product-table' ),
					'false'  => __( 'Hidden', 'woocommerce-product-table' )
				],
				'default' => $default_args['pagination'],
				'class'   => 'wc-enhanced-select'
			],
			[
				'type' => 'sectionend',
				'id'   => 'product_table_settings_controls'
			]
		] );

		$plugin_settings = array_merge( $plugin_settings, [
			[
				'title' => __( 'Advanced', 'woocommerce-product-table' ),
				'type'  => 'title',
				'id'    => 'product_table_settings_advanced'
			],
			[
				'title'   => __( 'Pagination type', 'woocommerce-product-table' ),
				'type'    => 'select',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[paging_type]',
				'options' => [
					'numbers'        => __( 'Page numbers', 'woocommerce-product-table' ),
					'simple'         => __( 'Prev - Next', 'woocommerce-product-table' ),
					'simple_numbers' => __( 'Prev - Page numbers - Next', 'woocommerce-product-table' ),
					'full'           => __( 'First - Prev - Next - Last', 'woocommerce-product-table' ),
					'full_numbers'   => __( 'First - Prev - Page numbers - Next - Last', 'woocommerce-product-table' )
				],
				'default' => $default_args['paging_type'],
				'class'   => 'wc-enhanced-select'
			],
			[
				'title'   => __( 'AJAX', 'woocommerce-product-table' ),
				'type'    => 'checkbox',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[ajax_cart]',
				'desc'    => __( 'Use AJAX when adding to the cart', 'woocommerce-product-table' ),
				'default' => $default_args['ajax_cart']
			],
			[
				'title'   => __( 'Shortcodes', 'woocommerce-product-table' ),
				'type'    => 'checkbox',
				'id'      => Settings::OPTION_TABLE_DEFAULTS . '[shortcodes]',
				'desc'    => __( 'Show shortcodes, HTML and other formatting in the table', 'woocommerce-product-table' ),
				'default' => $default_args['shortcodes']
			],
			[
				'title'             => __( 'Caching', 'woocommerce-product-table' ),
				'type'              => 'checkbox',
				'id'                => Settings::OPTION_TABLE_DEFAULTS . '[cache]',
				'desc'              => __( 'Cache table contents to improve load times', 'woocommerce-product-table' ),
				'default'           => $default_args['cache'],
				'class'             => 'toggle-parent',
				'custom_attributes' => [
					'data-child-class' => 'toggle-cache'
				]
			],
			[
				'title'             => __( 'Cache expiration', 'woocommerce-product-table' ),
				'type'              => 'number',
				'id'                => Settings::OPTION_MISC . '[cache_expiry]',
				'desc'              => __( 'hours', 'woocommerce-product-table' ),
				'desc_tip'          => __( 'Your data will be refreshed after this length of time.', 'woocommerce-product-table' ),
				'default'           => 6,
				'class'             => 'toggle-cache',
				'css'               => 'width:75px',
				'custom_attributes' => [
					'min' => 1,
					'max' => 9999
				]
			],
			[
				'type' => 'sectionend',
				'id'   => 'product_table_settings_advanced'
			]
		] );

		if ( Lib_Util::is_quick_view_pro_active() ) {
			$plugin_settings = array_merge( $plugin_settings, [
				[
					'title' => __( 'Quick View Pro', 'woocommerce-product-table' ),
					'type'  => 'title',
					'id'    => 'product_table_settings_quick_view'
				],
				[
					'title'    => __( 'Product links', 'woocommerce-product-table' ),
					'type'     => 'checkbox',
					'id'       => Settings::OPTION_MISC . '[quick_view_links]',
					'desc'     => __( 'Replace links to the product page with a Quick View', 'woocommerce-product-table' ),
					'desc_tip' => sprintf(
						__( '%sLearn how%s to correctly configure this option.', 'woocommerce-product-table' ),
						Lib_Util::format_barn2_link_open( 'kb/product-table-quick-view/#replace-links-to-the-single-product-page-with-quick-view-links' ),
						'</a>'
					),
					'default'  => 'no'
				],
				[
					'type' => 'sectionend',
					'id'   => 'product_table_settings_quick_view'
				]
			] );
		}

		if ( Lib_Util::is_product_addons_active() ) {
			$plugin_settings = array_merge( $plugin_settings, [
				[
					'title' => __( 'Product Addons', 'woocommerce-product-table' ),
					'type'  => 'title',
					'id'    => 'product_table_settings_addons'
				],
				[
					'title'    => __( 'Addons layout', 'woocommerce-product-table' ),
					'type'     => 'select',
					'options'  => [
						'block'  => __( 'Vertical', 'woocommerce-product-table' ),
						'inline' => __( 'Horizontal', 'woocommerce-product-table' ),
					],
					'id'       => Settings::OPTION_MISC . '[addons_layout]',
					'desc_tip' => __( 'Should product addons display horizontally or vertically within the table?', 'woocommerce-product-table' ),
					'default'  => 'block',
					'class'    => 'wc-enhanced-select'
				],
				[
					'title'    => __( 'Addon options layout', 'woocommerce-product-table' ),
					'type'     => 'select',
					'options'  => [
						'block'  => __( 'Vertical', 'woocommerce-product-table' ),
						'inline' => __( 'Horizontal', 'woocommerce-product-table' ),
					],
					'id'       => Settings::OPTION_MISC . '[addons_option_layout]',
					'desc_tip' => __( 'Should individual options for each addon display horizontally or vertically?', 'woocommerce-product-table' ),
					'default'  => 'block',
					'class'    => 'wc-enhanced-select'
				],
				[
					'type' => 'sectionend',
					'id'   => 'product_table_settings_addons'
				]
			] );
		}

		$plugin_settings[] = [
			'id'   => 'product_table_settings_end',
			'type' => 'settings_end'
		];

		return $plugin_settings;
	}

	public function get_taxonomy_settings() {
		$settings                 = [];
		$attribute_taxonomies     = [];
		$attribute_taxonomy_names = wc_get_attribute_taxonomy_names();

		$public_taxonomies = wp_filter_object_list(
			get_object_taxonomies( 'product', 'objects' ),
			[ 'public' => true ]
		);

		if ( ! empty( $public_taxonomies ) ) {
			foreach ( $public_taxonomies as $public_taxonomy ) {
				if ( empty( $public_taxonomy->label ) || empty( $public_taxonomy->name ) ) {
					continue;
				}

				if ( in_array( $public_taxonomy->name, $attribute_taxonomy_names ) ) {
					$attribute_taxonomies[] = $public_taxonomy;
					continue;
				}

				$tax_slug = '';

				if ( $public_taxonomy->name == 'product_shipping_class' || $public_taxonomy->name == 'product_cat' || $public_taxonomy->name == 'product_tag' ) {
					continue;
				}

				$settings[] = [
					'type'          => 'checkbox',
					'id'            => Settings::OPTION_MISC . '[' . $public_taxonomy->name . '_override]',
					'desc'          => $public_taxonomy->label . ' - <code>' . $public_taxonomy->name . '</code>',
					'default'       => 'no',
					'checkboxgroup' => ''
				];
			}

			if ( ! empty( $settings ) ) {
				$settings[ count( $settings ) - 1 ]['checkboxgroup'] = 'end';
			}
		}

		return $settings;
	}

	public function back_compat_settings() {
		$shortcode_defaults = get_option( Settings::OPTION_TABLE_DEFAULTS, [] );

		if ( ! empty( $shortcode_defaults['add_selected_text'] ) ) {
			$misc_settings                      = get_option( Settings::OPTION_MISC, [] );
			$misc_settings['add_selected_text'] = $shortcode_defaults['add_selected_text'];
			update_option( Settings::OPTION_MISC, $misc_settings );

			unset( $shortcode_defaults['add_selected_text'] );
			update_option( Settings::OPTION_TABLE_DEFAULTS, $shortcode_defaults );
		}

		if ( isset( $shortcode_defaults['show_quantity'] ) ) {
			$shortcode_defaults['quantities'] = $shortcode_defaults['show_quantity'];

			unset( $shortcode_defaults['show_quantity'] );
			update_option( Settings::OPTION_TABLE_DEFAULTS, $shortcode_defaults );
		}
	}

	public static function sanitize_option_table_defaults( $value, $option, $raw_value ) {
		$error   = false;
		$setting = self::get_setting_name( $option, Settings::OPTION_TABLE_DEFAULTS );

		if ( ! $setting ) {
			return $value;
		}

		// Check for empty settings.
		if ( '' === $value ) {
			if ( in_array( $setting, [ 'columns', 'image_size', 'links' ] ) ) {
				$value = Table_Args::$default_args[ $setting ];
			}
		}

		switch ( $setting ) {
			case 'columns':
				$columns = Table_Args::parse_columns_arg( $value );

				if ( empty( $columns['columns'] ) ) {
					$error = __( 'The columns option is invalid. Please check you have entered valid column names.', 'woocommerce-product-table' );
					$value = '';
				} else {
					$columns_combined = [];

					foreach ( $columns['columns'] as $i => $column ) {
						$c = $column;

						if ( ! empty( $columns['headings'][ $i ] ) ) {
							$c .= ':' . $columns['headings'][ $i ];
						}
						$columns_combined[] = $c;
					}

					$value = implode( ',', $columns_combined );
				}
				break;
			case 'image_size':
				$value = preg_replace( '/[^\wx\-]/', '', $value );
				break;
			case 'rows_per_page':
			case 'description_length':
			case 'product_limit':
				// Check integer settings.
				if ( 0 === (int) $value ) {
					$value = -1;
				}
				if ( ! is_numeric( $value ) || (int) $value < -1 ) {
					$value = Table_Args::$default_args[ $setting ];
				}
				break;
		}

		if ( $error ) {
			WC_Admin_Settings::add_error( $error );
		}

		return $value;
	}

	public static function sanitize_option_table_styling( $value, $option, $raw_value ) {
		if ( 'color_size' === $option['type'] && ! empty( $value['color'] ) ) {
			$value['color'] = sanitize_hex_color( $value['color'] );
		} elseif ( 'color' === $option['type'] && ! empty( $value ) ) {
			$value = sanitize_hex_color( $value );
		}

		return $value;
	}

	public static function sanitize_option_misc( $value, $option, $raw_value ) {
		$setting = self::get_setting_name( $option, Settings::OPTION_MISC );

		if ( ! $setting ) {
			return $value;
		}

		if ( 'cache_expiry' === $setting ) {
			$value = absint( $value );
		} elseif ( 'add_selected_text' === $setting && '' === $value ) {
			$value = Settings::add_selected_to_cart_default_text();
		}

		return $value;
	}

	private static function get_setting_name( $option, $option_name ) {
		$option_name_array = [];
		parse_str( $option['id'], $option_name_array );

		return isset( $option_name_array[ $option_name ] ) ? key( $option_name_array[ $option_name ] ) : false;
	}

	private function get_icon( $icon, $alt_text = '' ) {
		return sprintf(
			'<img src="%1$s" alt="%2$s" width="20" height="20" style="display:inline-block;position:relative;top:5px;padding:0 12px 0 8px;" />',
			Util::get_asset_url( 'images/' . ltrim( $icon, '/' ) ),
			$alt_text
		);
	}

}
