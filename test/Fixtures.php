<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_fixtures\test;

use lithium\core\ConfigException;

class Fixtures extends \lithium\core\Adaptable {

	/**
	 * Stores configuration arrays for session adapters, keyed by configuration name.
	 *
	 * @var array
	 */
	protected static $_configurations = [];

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.test.fixtures';

	/**
	 * Delegate calls to adapters
	 *
	 * @param string $method The called method name.
	 * @param array $params The parameters array.
	 * @return mixed
	 */
	public static function __callStatic($method, $params) {
		$name = array_shift($params);

		if (($config = static::_config($name)) === null) {
			throw new ConfigException("Configuration `{$name}` has not been defined.");
		}
		return call_user_func_array([static::adapter($name), $method], $params);
	}
}

?>