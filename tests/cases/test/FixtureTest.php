<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_fixtures\tests\cases\test;

use MongoId;
use lithium\data\Connections;
use li3_fixtures\tests\mocks\core\MockLogCall;
use li3_fixtures\test\Fixture;

class FixtureTest extends \lithium\test\Unit {

	protected $_connection = 'fixture_test';

	protected $_callable = null;

	public function skip() {
		$this->_callable = new MockLogCall();
		Connections::add($this->_connection, array(
			'object' => $this->_callable
		));
	}

	public function tearDown() {
		$this->_callable->__clear();
	}

	public function testInitMissingConnection() {
		$this->expectException("The `'connection'` option must be set.");
		new Fixture();
	}

	public function testInitMissingModelAndSource() {
		$this->expectException("The `'model'` or `'source'` option must be set.");
		new Fixture(array('connection' => $this->_connection));
	}

	public function testCreate() {
		$fields = array(
			'id' => array('type' => 'id'),
			'name' => array('type' => 'string')
		);

		$fixture = new Fixture(array(
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields,
			'records' => array(
				array('id' => 1, 'name' => 'Nate'),
				array('id' => 2, 'name' => 'Gwoo')
			)
		));

		$fixture->create(false);
		$this->assertEqual(1, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);
		$this->assertEqual($fields, $call['params'][1]->fields());

		$fixture->create();
		$call = $this->_callable->call[1];
		$this->assertEqual('sources', $call['method']);
	}

	public function testDrop() {
		$fixture = new Fixture(array(
			'connection' => $this->_connection,
			'source' => 'contacts'
		));

		$fixture->drop(false);
		$call = $this->_callable->call[0];
		$this->assertEqual('dropSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);

		$fixture->drop();
		$call = $this->_callable->call[1];
		$this->assertEqual('sources', $call['method']);
	}

	public function testTruncate() {
		$fixture = new Fixture(array(
			'connection' => $this->_connection,
			'source' => 'contacts'
		));

		$fixture->truncate();
		$call = $this->_callable->call[0];
		$this->assertEqual('delete', $call['method']);
	}

	public function testSave() {
		$fields = array(
			'id' => array('type' => 'id'),
			'name' => array('type' => 'string')
		);

		$records = array(
			array('id' => 1, 'name' => 'Nate'),
			array('id' => 2, 'name' => 'Gwoo')
		);

		$fixture = new Fixture(array(
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields,
			'records' => $records
		));

		$fixture->save(false);
		$this->assertEqual(3, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);
		$this->assertEqual($fields, $call['params'][1]->fields());

		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual($records[0], $query->data());

		$call = $this->_callable->call[2];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual($records[1], $query->data());
	}

	public function testAlter() {
		$fields = array(
			'id' => array('type' => 'id'),
			'name' => array('type' => 'string'),
			'useless' => array('type' => 'string')
		);

		$records = array(
			array('id' => 1, 'name' => 'Nate', 'useless' => 'a'),
			array('id' => 2, 'name' => 'Gwoo', 'useless' => 'b')
		);

		$alters = array(
			'add' => array(
				'lastname' => array('type' => 'string', 'default' => 'li3')
			),
			'change' => array(
				'id' => array(
					'type' => 'string',
					'length' => '24',
					'to' => '_id',
					'value' => function ($val) {
						return new MongoId('4c3628558ead0e594' . (string) ($val + 1000000));
					}
				),
				'name' => array(
					'to' => 'firstname'
				)
			),
			'drop' => array(
				'useless'
			)
		);

		$fixture = new Fixture(array(
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields,
			'records' => $records,
			'alters' => $alters
		));

		$this->assertEqual($alters, $fixture->alter());

		$fixture->save(false);
		$this->assertEqual(3, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);

		$expected = array(
			'_id' => array('type' => 'string', 'length' => 24),
			'firstname' => array('type' => 'string'),
			'lastname' => array('type' => 'string', 'default' => 'li3')
		);
		$this->assertEqual($expected, $call['params'][1]->fields());

		$expected = array(
			array('_id' => new MongoId('4c3628558ead0e5941000001'), 'firstname' => 'Nate'),
			array('_id' => new MongoId('4c3628558ead0e5941000002'), 'firstname' => 'Gwoo')
		);
		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual($expected[0], $query->data());

		$call = $this->_callable->call[2];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual($expected[1], $query->data());
	}

	public function testPopulate() {
		$fields = array(
			'id' => array('type' => 'id'),
			'name' => array('type' => 'string'),
			'useless' => array('type' => 'string')
		);

		$fixture = new Fixture(array(
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields
		));

		$fixture->create(false);
		$this->assertEqual(1, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);

		$record = array('id' => 1, 'name' => 'Nate', 'useless' => 'a');
		$fixture->populate($record);
		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$this->assertEqual($record, $query->data());
	}

	public function testLiveAlter() {
		$fields = array(
			'id' => array('type' => 'id'),
			'name' => array('type' => 'string'),
			'useless' => array('type' => 'string')
		);

		$fixture = new Fixture(array(
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => $fields
		));

		$fixture->alter('change', 'id', array(
			'type' => 'string',
			'length' => '24',
			'to' => '_id',
			'value' => function ($val) {
				return new MongoId('4c3628558ead0e594' . (string) ($val + 1000000));
			}
		));
		$fixture->alter('change', 'name', array('to' => 'firstname'));
		$fixture->alter('drop', 'useless');
		$fixture->alter('add', 'lastname', array('type' => 'string', 'default' => 'li3'));

		$fixture->create(false);
		$this->assertEqual(1, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('createSchema', $call['method']);
		$this->assertEqual('contacts', $call['params'][0]);

		$record = array('id' => 1, 'name' => 'Nate', 'useless' => 'a');
		$fixture->populate($record);
		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$expected = array('_id' => new MongoId('4c3628558ead0e5941000001'), 'firstname' => 'Nate');
		$this->assertEqual($expected, $query->data());

		$record = array('id' => 1, 'name' => 'Nate', 'useless' => 'a');
		$fixture->populate($record, false);
		$call = $this->_callable->call[2];
		$this->assertEqual('create', $call['method']);
		$query = $call['params'][0];
		$this->assertEqual('create', $query->type());
		$expected = array('id' => 1, 'name' => 'Nate');
		$this->assertEqual(array(), $query->data());
	}

	public function testSchemaLess() {
		$record = array(
			'_id' => new MongoId('4c3628558ead0e5941000001'),
			'name' => 'John'
		);
		$fixture = new Fixture(array(
			'connection' => $this->_connection,
			'source' => 'contacts',
			'fields' => array(),
			'records' => array($record),
			'locked' => false
		));

		MockLogCall::$returnStatic = array('enabled' => false);
		$fixture->drop();
		$call = $this->_callable->call[0];
		$this->assertEqual('delete', $call['method']);

		$this->_callable->__clear();
		$fixture->create();
		$call = $this->_callable->call[0];
		$this->assertEqual(1, count($this->_callable->call));
		$this->assertEqual('delete', $call['method']);


		$this->_callable->__clear();
		$fixture->save();
		$this->assertEqual(2, count($this->_callable->call));
		$call = $this->_callable->call[0];
		$this->assertEqual('delete', $call['method']);
		$call = $this->_callable->call[1];
		$this->assertEqual('create', $call['method']);
		$this->assertEqual($record, $call['params'][0]->data());
	}
}

?>