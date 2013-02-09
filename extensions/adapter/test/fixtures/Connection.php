<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_fixtures\extensions\adapter\test\fixtures;

use lithium\core\ConfigException;
use UnexpectedValueException;

class Connection extends \lithium\core\Object {

	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array('connection', 'fixtures');

	/**
	 * Connection name to use.
	 *
	 * @var string
	 */
	protected $_connection = null;

	/**
	 * Holds the fixture classes that where instantiated.
	 *
	 * @var array
	 */
	protected $_loaded = array();

	/**
	 * Holds the fixture classes to be instantiated indexed by a name.
	 *
	 * @var array
	 */
	protected $_fixtures = array();

	/**
	 * Initializes class configuration (`$_config`), and assigns object properties using the
	 * `_init()` method, unless otherwise specified by configuration. See below for details.
	 *
	 * @see lithium\core\Object::__construct()
	 * @param array $config The configuration options
	 */
	public function __construct(array $config = array()) {
		$defaults = array('alters' => array());
		parent::__construct($config + $defaults);
	}

	/**
	 * Initializer function called by the constructor unless the constructor
	 *
	 * @see lithium\core\Object::_init()
	 * @throws ConfigException
	 */
	protected function _init() {
		parent::_init();

		if (!$this->_connection) {
			throw new ConfigException("The `'connection'` option must be set.");
		}
	}

	/**
	 * Instantiate a fixture
	 *
	 * @param array $name The fixture name to instantiate
	 * @return boolean Returns `true` on success
	 */
	protected function _instantiate($name) {
		if (isset($this->_fixtures[$name])) {
			$options = array(
				'connection' => $this->_connection,
				'alters' => $this->_config['alters']
			);
			$this->_loaded[$name] = new $this->_fixtures[$name]($options);
			return true;
		} else {
			throw new UnexpectedValueException("Undefined fixture named: `{$name}`.");
		}
	}

	/**
	 * Getting a fixture.
	 *
	 * @param mixed $name The fixture name to get.
	 * @return mixed Returns a fixture object or `null` if doesn't exist.
	 */
	public function get($name) {
		if (isset($this->_loaded[$name]) || $this->_instantiate($name)) {
			return $this->_loaded[$name];
		}
	}

	/**
	 * Creates the schema of fixtures
	 *
	 * @param mixed $names An array of fixture name.
	 * @param boolean $drop If `true` drop the fixture before creating it
	 */
	public function create(array $names = array(), $drop = true) {
		$this->_create($names, $drop, false);
	}

	/**
	 * Creates the fixtures tables and inserts data on them
	 *
	 * @param mixed $names An array of fixture name.
	 * @param boolean $drop If `true` drop the fixture before loading it
	 */
	public function save(array $names = array(), $drop = true) {
		$this->_create($names, $drop, true);
	}

	/**
	 * Build fixtures
	 *
	 * @param mixed $names An array of fixture name.
	 * @param boolean $drop If `true` drop the fixture before creating it
	 * @param boolean $save If `true` save fixture's records in the database
	 */
	protected function _create($names = array(), $drop = true, $save = true) {
		$names = $names ?: array_keys($this->_fixtures);

		foreach ((array) $names as $name) {
			if (isset($this->_loaded[$name]) || $this->_instantiate($name)) {
				$fixture = $this->_loaded[$name];
				if ($save) {
					$fixture->save($drop);
				} else {
					$fixture->create($drop);
				}
			}
		}
	}

	/**
	 * Trucantes the fixtures tables
	 *
	 * @param mixed $names The fixtures name to truncate.
	 */
	public function truncate(array $names = array()) {
		$names = $names ?: array_keys($this->_loaded);
		foreach ($names as $name) {
			$fixture = $this->get($name);
			$fixture->truncate();
		}
	}

	/**
	 * Drop all fixture tables loaded by this class.
	 *
	 * @param array $names The fixtures name to drop.
	 * @param boolean $safe If `true` drop the fixture only if exists
	 */
	public function drop(array $names = array(), $safe = true) {
		$names = $names ?: array_keys($this->_loaded);
		foreach ($names as $name) {
			$fixture = $this->get($name);
			$fixture->drop($safe);
		}
	}

	/**
	 * Drop all fixture tables loaded by this class.
	 *
	 * @param array $names The fixtures name to drop.
	 */
	public function reset() {
		foreach ($this->_loaded as $name => $fixture) {
			$fixture->drop();
		}
		$this->_loaded = array();
		$this->_config = array();
	}
}

?>