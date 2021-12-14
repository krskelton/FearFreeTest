<?php
namespace Barn2\WPT_Lib\WooCommerce;

use Barn2\WPT_Lib\Template_Loader;

/**
 * A WooCommerce template loader.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.1
 */
class Templates implements Template_Loader {

    private $template_path;
    private $default_path;

    public function __construct( $theme_dir = '', $default_path = '' ) {
        if ( function_exists( 'WC' ) ) {
            $this->template_path = $theme_dir ? trailingslashit( WC()->template_path() . $theme_dir ) : '';
        }
        $this->default_path = $default_path ? trailingslashit( $default_path ) : '';
    }

    public function get_template( $template_name, array $args = [] ) {
        return wc_get_template_html(
            $this->expand_template( $template_name ),
            $args,
            $this->get_template_path(),
            $this->get_default_path()
        );
    }

    public function load_template( $template_name, array $args = [] ) {
        wc_get_template(
            $this->expand_template( $template_name ),
            $args,
            $this->get_template_path(),
            $this->get_default_path()
        );
    }

    public function get_template_path() {
        return $this->template_path;
    }

    public function get_default_path() {
        return $this->default_path;
    }

    private function expand_template( $template_name ) {
        /*
         * If the template ends with a folder rather than a PHP file, we expand the template name using the
         * terminating folder to build the full template name.
         * E.g. /my-templates/cool/ becomes /my-templates/cool/cool.php
         */
        if ( '.php' !== substr( $template_name, -4 ) ) {
            $template_name  = rtrim( $template_name, '/ ' );
            $last_backslash = strrpos( $template_name, '/' );

            if ( false !== $last_backslash ) {
                $last_folder   = substr( $template_name, $last_backslash + 1 );
                $template_name = "{$template_name}/{$last_folder}.php";
            } else {
                $template_name = "{$template_name}/{$template_name}.php";
            }
        }

        return $template_name;
    }

}
