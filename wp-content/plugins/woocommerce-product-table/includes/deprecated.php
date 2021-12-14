<?php
/**
 * Provides backwards compatibility for deprecated code.
 *
 * @package   Barn2\woocommerce-product-table
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */

use Barn2\Plugin\WC_Product_Table\Config_Builder;
use Barn2\Plugin\WC_Product_Table\Data\Abstract_Product_Data;
use Barn2\Plugin\WC_Product_Table\Plugin;
use Barn2\Plugin\WC_Product_Table\Product_Table;
use Barn2\Plugin\WC_Product_Table\Table_Args;
use Barn2\Plugin\WC_Product_Table\Table_Columns;
use Barn2\Plugin\WC_Product_Table\Table_Query;
use Barn2\Plugin\WC_Product_Table\Util\Settings;
use Barn2\Plugin\WC_Product_Table\Util\Util;
use Barn2\WPT_Lib\Table\Table_Data_Interface;
use function Barn2\Plugin\WC_Product_Table\wpt;

// Prevent direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound, PSR1.Classes.ClassDeclaration.MultipleClasses, PSR1.Classes.ClassDeclaration.MissingNamespace, Generic.Commenting.DocComment.MissingShort, Squiz.Commenting.FunctionComment.Missing

if ( ! class_exists( 'WCPT_Util' ) ) {

	/**
	 * @deprecated 2.8 Replaced by Barn2\Plugin\WC_Product_Table\Util\Util
	 */
	final class WCPT_Util {

		public static function __callStatic( $name, $args ) {
			if ( method_exists( Util::class, $name ) ) {
				_deprecated_function( __METHOD__, '2.8', Util::class . "::$name" );
				return call_user_func_array( [ Util::class, $name ], $args );
			}

			return null;
		}

	}

}

if ( ! class_exists( 'WCPT_Settings' ) ) {

	/**
	 * @deprecated 2.8 Replaced by Barn2\Plugin\WC_Product_Table\Util\Settings
	 */
	final class WCPT_Settings {

		/**
		 * @deprecated 2.8 Replaced by constants in Barn2\Plugin\WC_Product_Table\Util\Settings
		 */
		const OPTION_TABLE_STYLING  = Settings::OPTION_TABLE_STYLING;
		const OPTION_TABLE_DEFAULTS = Settings::OPTION_TABLE_DEFAULTS;
		const OPTION_MISC           = Settings::OPTION_MISC;
		const SECTION_SLUG          = Settings::SECTION_SLUG;

		public static function __callStatic( $name, $args ) {
			if ( method_exists( Settings::class, $name ) ) {
				_deprecated_function( __METHOD__, '2.8', Settings::class . "::$name" );
				return call_user_func_array( [ Settings::class, $name ], $args );
			}

			return null;
		}

		public static function get_setting_table_defaults() {
			// Not deprecated for the time being while other plugins update.
			return Settings::get_setting_table_defaults();
		}

		public static function get_setting_misc() {
			// Not deprecated for the time being while other plugins update.
			return Settings::get_setting_misc();
		}

	}

}

if ( ! interface_exists( 'Product_Table_Data' ) ) {

	/**
	 * @deprecated 2.8 Replaced by Barn2\WPT_Lib\Table\Table_Data_Interface.
	 */
	interface Product_Table_Data extends Table_Data_Interface {
	}

}

if ( ! class_exists( 'Abstract_Product_Table_Data' ) ) {

	/**
	 * @deprecated 2.7 Replaced by Barn2\Plugin\WC_Product_Table\Data\Abstract_Product_Data.
	 */
	abstract class Abstract_Product_Table_Data extends Abstract_Product_Data {

		public function __construct( WC_Product $product, $links = '' ) {
			_deprecated_function( __METHOD__, '2.7', Abstract_Product_Data::class );
			parent::__construct( $product, $links );
		}

	}

}

if ( ! class_exists( 'WC_Product_Table_Plugin' ) ) {

	/**
	 * @deprecated 2.7 Replaced by Barn2\Plugin\WC_Product_Table\Plugin.
	 */
	class WC_Product_Table_Plugin extends Plugin {

		/**
		 * @deprecated 2.6.4 Replaced by Barn2\Plugin\WC_Product_Table\PLUGIN_VERSION
		 */
		const VERSION = Barn2\Plugin\WC_Product_Table\PLUGIN_VERSION;

		/**
		 * @deprecated 2.6.4 Replaced by Barn2\Plugin\WC_Product_Table\PLUGIN_FILE
		 */
		const FILE = Barn2\Plugin\WC_Product_Table\PLUGIN_FILE;

		public function __construct( $file, $version = '1.0' ) {
			_deprecated_function( __METHOD__, '2.7', Plugin::class );
			parent::__construct( $file, $version );
		}

		public function load_services() {
			_deprecated_function( __METHOD__, '2.7', 'Barn2\\Plugin\\WC_Product_Table\\Plugin->register_services' );
			wpt()->register_services();
		}

		public static function instance() {
			_deprecated_function( __METHOD__, '2.7', 'Barn2\\Plugin\\WC_Product_Table\\wpt' );
			return wpt();
		}

	}

}

if ( ! class_exists( 'WC_Product_Table' ) ) {

	/**
	 * @deprecated 2.7 Replaced by Barn2\Plugin\WC_Product_Table\Product_Table.
	 */
	class WC_Product_Table extends Product_Table {

		public function __construct( $id, $args = [] ) {
			_deprecated_function( __METHOD__, '2.7', Product_Table::class );
			parent::__construct( $id, $args );
		}

	}

}

if ( ! class_exists( 'WC_Product_Table_Columns' ) ) {

	/**
	 * @deprecated 2.7 Replaced by Barn2\Plugin\WC_Product_Table\Table_Columns.
	 */
	class WC_Product_Table_Columns extends Table_Columns {
	}

}

if ( ! class_exists( 'WC_Product_Table_Query' ) ) {

	/**
	 * @deprecated 2.7 Replaced by Barn2\Plugin\WC_Product_Table\Table_Query.
	 */
	class WC_Product_Table_Query extends Table_Query {
	}

}

if ( ! class_exists( 'WC_Product_Table_Config_Builder' ) ) {

	/**
	 * @deprecated 2.7 Replaced by Barn2\Plugin\WC_Product_Table\Config_Builder.
	 */
	class WC_Product_Table_Config_Builder extends Config_Builder {
	}

}

if ( ! class_exists( 'WC_Product_Table_Args' ) ) {

	/**
	 * @deprecated 2.7 Replaced by Barn2\Plugin\WC_Product_Table\Table_Args.
	 */
	class WC_Product_Table_Args extends Table_Args {

		public function __construct( array $args = [] ) {
			_deprecated_function( __METHOD__, '2.7', Table_Args::class );
			parent::__construct( $args );
		}

		public function get_args() {
			_deprecated_function( __METHOD__, '2.7', Table_Args::class . '->get_args' );
			return parent::get_args();
		}

		public function set_args( array $args ) {
			_deprecated_function( __METHOD__, '2.7', Table_Args::class . '->set_args' );
			parent::set_args( $args );
		}

	}

}

