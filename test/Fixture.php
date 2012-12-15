<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_fixtures\test;

use lithium\core\ConfigException;

class Fixture extends \lithium\data\Schema {

	/**
	 * Classes used by `Fixture`.
	 *
	 * @var array
	 */
	protected $_classes = array(
		'connections' => 'lithium\data\Connections',
		'schema' => 'lithium\data\Schema',
		'query' => 'lithium\data\model\Query'
	);
	/**
	 * Auto configuration properties.
	 *
	 * @var array
	 */
	protected $_autoConfig = array(
		'connection', 'source', 'locked', 'model', 'fields' => 'merge', 'meta', 'records'
	);

	/**
	 * The connection name
	 *
	 * @var string
	 */
	protected $_connection = null;

	/**
	 * The name of the source. Used if `Fixture::_model` is not set (i.e. `null`).
	 *
	 * @var string
	 */
	protected $_source = null;

	/**
	 * The fully-namespaced attached model class name
	 *
	 * @var string
	 */
	protected $_model = null;

	/**
	 * Fields definition
	 *
	 * Example:
	 * {{{
	 * protected $_fields = array(
	 *     'id'  => array('type' => 'id'),
	 *     'firstname' => array('type' => 'string', 'default' => 'foo', 'null' => false),
	 *     'lastname' => array('type' => 'string', 'default' => 'bar', 'null' => false)
	 * );
	 * }}}
	 *
	 * @var array
	 */
	protected $_fields = array();

	/**
	 * Alteres dields definition
	 *
	 * @var array
	 */
	protected $_alteredFields = array();

	/**
	 * Metas for the fixture.
	 *
	 * Example:
	 * {{{
	 * protected $_meta = array(
	 *     'constraints' => array(
	 *         array(
	 *             'type' => 'foreign_key',
	 *             'column' => 'id',
	 *             'toColumn' => 'id',
	 *             'to' => 'other_table'
	 *         )
	 *      ),
	 *     'table' => array('charset' => 'utf8', 'engine' => 'InnoDB')
	 * );
	 * }}}
	 *
	 * @var array
	 */
	protected $_meta = array();

	/**
	 * The records should be an array of rows. Each row should have values keyed by
	 * the column name.
	 *
	 * Example:
	 * {{{
	 * protected $_records = array(
	 *     array('id' => 1, 'firstname' => 'John', 'lastname' => 'Doe'),
	 *     array('id' => 2, 'firstname' => 'Pamela', 'lastname' => 'A.'),
	 *     array('id' => 6, 'firstname' => 'Jay', 'lastname' => 'Miner'),
	 *     array('id' => 9, 'firstname' => 'Obi-Wan', 'lastname' => 'Kenobi')
	 * );
	 * }}}
	 *
	 * @var array
	 */
	protected $_records = array();

	/**
	 * If `true` only fields in `Fixture::_fields` are allowed in records (kind of whitelist).
	 *
	 * @var boolean
	 */
	protected $_locked = null;

	/**
	 * Initializes class configuration (`$_config`), and assigns object properties using the
	 * `_init()` method, unless otherwise specified by configuration. See below for details.
	 *
	 * @see lithium\core\Object::__construct()
	 * @param array $config The configuration options
	 */
	public function __construct($config = array()) {
		parent::__construct($config + array('alters' => array()));
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

		if (!$this->_source && !$this->_model) {
			throw new ConfigException("The `'model'` or `'source'` option must be set.");
		}

		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);

		if ($model = $this->_model) {
			$model::config(array('meta' => array('connection' => $this->_connection)));
			$this->_source = $this->_source ? : $model::meta('source');
			$this->_locked = ($this->_locked === null) ? $model::meta('locked') : $this->_locked;
		}

		if ($this->_locked === null) {
			if ($db::enabled('schema')) {
				$this->_locked = true;
			} else {
				$this->_locked = false;
			}
		}

		foreach ($this->_config['alters'] as $mode => $values) {
			foreach ($values as $key => $value) {
				$this->alter($mode, $key, $value);
			}
		}
	}

	/**
	 * Create the fixture's schema.
	 *
	 * @return boolean Returns `true` on success or if there are no records to import,
	 *         return `false` on failure.
	 */
	public function create($drop = true) {
		return $this->_create($drop, false);
	}

	/**
	 * Create the fixture's schema and import records.
	 *
	 * @return boolean Returns `true` on success or if there are no records to import,
	 *         return `false` on failure.
	 */
	public function save($drop = true) {
		return $this->_create($drop, true);
	}

	/**
	 * Create the fixture's schema and import records.
	 *
	 * @param boolean $drop If `true` drop the fixture before creating it
	 * @param boolean $load If `true` load fixture's records
	 * @return boolean True on success, false on failure
	 */
	public function _create($drop = true, $save = true) {
		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);

		if ($drop && !$this->drop()) {
			return false;
		}

		$this->_alteredFields = $this->_alterFields($this->_fields);
		$return = true;

		if ($db::enabled('schema')) {
			$schema = $this->_instance('schema', array(
				'fields' => $this->_alteredFields,
				'meta' => $this->_meta,
				'locked' => $this->_locked
			));

			$return = $db->createSchema($this->_source, $schema);
		}

		if ($return && $save) {
			foreach ($this->_records as $record) {
				if (!$this->populate($record, true)) {
					return false;
				}
			}
		}
		return $return;
	}

	/**
	 * Drop table for this fixture.
	 *
	 * @param boolean $soft If `true` and there's not existing schema, no drop query is generated.
	 * @return boolean True on success, false on failure
	 */
	public function drop($soft = true) {
		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);
		if (!$db::enabled('schema')) {
			return $this->truncate();
		}
		if ($soft) {
			$sources = $db->sources();
			if(!in_array($this->_source, $sources)) {
				return true;
			}
		}
		return $db->dropSchema($this->_source);
	}

	/**
	 * Populate a custom records in the database.
	 *
	 * @param array $record The data of the record
	 * @param boolean $alter If true, the `$record` will be altered according the alter rules.
	 * @return boolean Returns `true` on success `false` otherwise.
	 */
	public function populate(array $record = array(), $alter = true) {
		if (!$record) {
			return true;
		}
		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);
		$data = $alter ? $this->_alterRecord($record) : $record;
		if ($this->_locked) {
			$data = array_intersect_key($data, $this->_alteredFields);
		}
		$options = array(
			'type' => 'create', 'source' => $this->_source, 'data' => array('data' => $data)
		);
		$query = $this->_instance('query', $options);
		return $db->create($query);
	}

	/**
	 * Truncates the current fixture.
	 *
	 * @param boolean $soft If `true` and there's not existing schema, no truncate is generated.
	 * @return boolean
	 */
	public function truncate($soft = true) {
		$connections = $this->_classes['connections'];
		$db = $connections::get($this->_connection);
		if ($soft) {
			$sources = $db->sources();
			if(!in_array($this->_source, $sources)) {
				return true;
			}
		}
		$options = array('source' => $this->_source);
		$query = $this->_instance('query', $options);
		return $db->delete($query);
	}

	public function alter($mode = null, $key = null, $value = array()) {
		if ($mode === null) {
			return $this->_config['alters'];
		}
		if ($key && $mode === 'drop') {
			$this->_config['alters']['drop'][] = $key;
			return;
		}
		if ($key && $value) {
			$this->_config['alters'][$mode][$key] = $value;
		}
	}

	/**
	 * Apply the configured value mapping.
	 *
	 * @param array $record The record array.
	 * @return array Returns the modified record.
	 */
	public function _alterRecord(array $record = array()) {
		$result = array();
		foreach ($record as $name => $value) {
			if (isset($this->_config['alters']['change'][$name])) {
				$alter = $this->_config['alters']['change'][$name];
				if (isset($alter['value'])) {
					$function = $alter['value'];
					$value = $function($record[$name]);
				} else {
					$value = $record[$name];
				}
				if (isset($alter['to'])) {
					$result[$alter['to']] = $value;
				} else {
					$result[$name] = $value;
				}
			} else {
				$result[$name] = $value;
			}
		}
		return $result;
	}

	public function _alterFields(array $fields = array()) {
		foreach ($this->_config['alters'] as $mode => $values) {
			foreach ($values as $key => $value) {
				switch($mode) {
					case 'add':
						$fields[$key] = $value;
						break;
					case 'change':
						if (isset($fields[$key]) && isset($value['to'])) {
							$field = $fields[$key];
							unset($fields[$key]);
							$to = $value['to'];
							unset($value['to']);
							unset($value['value']);
							$fields[$to] = $value + $field;
						}
						break;
					case 'drop':
						unset($fields[$value]);
						break;
				}
			}
		}
		return $fields;
	}
}

?>