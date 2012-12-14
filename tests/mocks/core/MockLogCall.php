<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_fixtures\tests\mocks\core;

class MockLogCall extends \lithium\core\Object {

	public $construct = array();

	public $get = array();

	public $return = array();

	public static $returnStatic = array();

	public $call = array();

	public static $callStatic = array();

	public function __construct() {
		$this->construct = func_get_args();
	}

	public function __clear() {
		$this->call = array();
		$this->return = array();
		$this->get = array();
		static::$callStatic = array();
	}

	public function __call($method, $params = array()) {
		$call = compact('method', 'params');
		$this->call[] = $call;
		return isset($this->return[$method]) ? $this->return[$method]: $call;
	}

	public static function __callStatic($method, $params) {
		$callStatic = compact('method', 'params');
		static::$callStatic[] = $callStatic;
		return isset(static::$returnStatic[$method]) ? static::$returnStatic[$method]: $callStatic;
	}

	public function __get($value) {
		return $this->get[] = $value;
	}
}

?>