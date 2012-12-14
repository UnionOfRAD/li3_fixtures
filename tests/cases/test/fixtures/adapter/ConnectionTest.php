<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_fixtures\tests\cases\test\fixtures\adapter;

use li3_fixtures\extensions\adapter\test\fixtures\Connection;

class ConnectionTest extends \lithium\test\Unit {

	protected $_connection = 'fixture_test';

	protected $_adapter;

	public function setUp() {
		$this->_adapter = new Connection(array(
			'connection' => $this->_connection,
			'alters' => array(
				'add' => array('custom' => array('type' => 'string'))
			),
			'fixtures' => array(
				'gallery' => 'li3_fixtures\tests\mocks\core\MockLogCall',
				'image' => 'li3_fixtures\tests\mocks\core\MockLogCall'
			)
		));
	}

	public function testInitMissingConnection() {
		$this->expectException("The `'connection'` option must be set.");
		new Connection();
	}

	public function testInstantiateFixture() {
		$callable = $this->_adapter->get('gallery');
		$expected = array(
			'connection' => $this->_connection,
			'alters' => array('add' => array('custom' => array('type' => 'string')))
		);
		$this->assertEqual($expected, $callable->construct[0]);
	}

	public function testMissingFixture() {
		$this->expectException("Undefined fixture named: `foo`.");
		$this->_adapter->get('foo');
	}

	public function testCreate() {
		$this->_adapter->create();

		$callable = $this->_adapter->get('image');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);
	}

	public function testCreateSingle() {
		$this->_adapter->create(array('gallery'));

		$callable = $this->_adapter->get('image');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual(0, count($callable->call));

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);
	}

	public function testDrop() {
		$this->_adapter->create();
		$this->_adapter->drop();

		$callable = $this->_adapter->get('image');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('image');
		$expected = array('method' => 'drop', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[1]);

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'drop', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[1]);
	}

	public function testDropLoadedFixtureOnly() {
		$this->_adapter->create(array('gallery'));
		$this->_adapter->drop();

		$callable = $this->_adapter->get('image');
		$this->assertEqual(0, count($callable->call));

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'drop', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[1]);
	}

	public function testTruncate() {
		$this->_adapter->create();
		$this->_adapter->truncate();

		$callable = $this->_adapter->get('image');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('image');
		$expected = array('method' => 'truncate', 'params' => array());
		$this->assertEqual($expected, $callable->call[1]);

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'truncate', 'params' => array());
		$this->assertEqual($expected, $callable->call[1]);
	}

	public function testTruncateLoadedFixtureOnly() {
		$this->_adapter->create(array('gallery'));
		$this->_adapter->truncate();

		$callable = $this->_adapter->get('image');
		$this->assertEqual(0, count($callable->call));

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'create', 'params' => array(true));
		$this->assertEqual($expected, $callable->call[0]);

		$callable = $this->_adapter->get('gallery');
		$expected = array('method' => 'truncate', 'params' => array());
		$this->assertEqual($expected, $callable->call[1]);
	}
}
?>
