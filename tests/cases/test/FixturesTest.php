<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_fixtures\tests\cases\test;

use li3_fixtures\tests\mocks\core\MockLogCall;
use li3_fixtures\test\Fixtures;

class FixturesTest extends \lithium\test\Unit {

	protected $_callable;

	public function setUp() {
		Fixtures::reset();
		$this->_callable = new MockLogCall();
		Fixtures::config(array(
			'fixture_test' => array(
				'object' => $this->_callable,
				'fixtures' => array(
					'image' => 'name\spa\ce',
					'gallery' => 'na\mespac\e',
				)
			)
		));
	}

	public function tearDown() {
		$this->_callable->__clear();
		Fixtures::reset();
	}

	public function testConstructPassedParams() {
		Fixtures::reset();
		$config = array(
			'adapter' => 'li3_fixtures\tests\mocks\core\MockLogCall',
			'fixtures' => array(
				'image' => 'name\spa\ce',
				'gallery' => 'na\mespac\e',
			)
		);
		Fixtures::config(array(
			'fixture_test' => $config
		));
		$callable = Fixtures::adapter('fixture_test');
		$expected = $config + array('filters' => array());
		$this->assertEqual($expected, $callable->construct[0]);
	}

	public function testCallStatic() {
		$result = Fixtures::methodName('fixture_test', array('parameter' => 'value'), 'param');
		$expected = array(
			'method' => 'methodName',
			'params' => array(
				array(
					'parameter' => 'value',
				),
				'param'
			)
		);
		$this->assertEqual($expected, $this->_callable->call[0]);
	}
}

?>