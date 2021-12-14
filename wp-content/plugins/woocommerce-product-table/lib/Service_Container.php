<?php
namespace Barn2\WPT_Lib;

use Barn2\WPT_Lib\Util;

/**
 * A trait for a service container.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.2
 */
trait Service_Container {

	private $services = [];

	public function register_services() {
		Util::register_services( $this->_get_services() );
	}

	public function get_service( $id ) {
		$services = $this->_get_services();
		return isset( $services[ $id ] ) ? $services[ $id ] : null;
	}

	public function get_services() {
		// Overidden by classes using this trait.
		return [];
	}

	private function _get_services() { //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		if ( empty( $this->services ) ) {
			$this->services = $this->get_services();
		}

		return $this->services;
	}

}
