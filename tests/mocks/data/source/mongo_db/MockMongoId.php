<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2012, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace li3_fixtures\tests\mocks\data\source\mongo_db;

class MockMongoId {

	protected $_name;

	public function __construct($name) {
		$this->_name = $name;
	}

	public function __toString() {
		return $this->_name;
	}
}

?>