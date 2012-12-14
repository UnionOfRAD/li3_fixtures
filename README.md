# Fixtures managment

## Installation

Checkout the code to either of your library directories:

	cd libraries
	git clone git@github.com:UnionOfRAD/li3_fixtures.git

Include the library in in your `/app/config/bootstrap/libraries.php`

	Libraries::add('li3_fixtures');

## Presentation

Thin plugin provide fixtures managment over connections. Should work with any kind of `Source` adapaters.

## Dependencies

This plugin needs [li3_sqltools](https://github.com/UnionOfRAD/li3_sqltools) if you want to make it work with li3's `Database` adapters. For schema less datasources, adapters must return false on `::enabled('schema')` call.

## API

### The Fixture class

Methods:
--------

- Fixture::create($safe); //Create the source only
- Fixture::save($safe); //Create the source + save the fixture's records in.
- Fixture::drop($safe); //Drop the source.
- Fixture::populate($records); //Insert a record in the database
- Fixture::alter($mode, $fieldname, $value); //Altering the schema before `::create()/::save()`.

Simple example of unit test:

	//app/tests/cases/models/SampleTest.php
	namespace app\tests\cases\models;

	use li3_fixtures\test\Fixture;

	class SampleTest extends \lithium\test\Unit {

		public function testFixture() {
			$fixture = new Fixture(array(
				'connection' => 'lithium_mysql_test',
				'source' => 'contacts',
				'fields' => array(
					'id' => array('type' => 'id'),
					'name' => array('type' => 'string')
				),
				'records' => array(
					array('id' => 1, 'name' => 'Nate'),
					array('id' => 2, 'name' => 'Gwoo')
				)
			));

			$fixture->save();

			$fixture->populate(array('id' => 3, 'name' => 'Mehlah'));

			$fixture->drop();
		}
	}

### The Fixtures class

`Fixture` is a kind of `Schema` which contain records and a source name or a reference to a model.
So let save the above fixture in a class.

	//app/tests/fixture/ContactsFixture.php
	namespace app\tests\fixture;

	class ContactsFixture extends \li3_fixtures\test\Fixture {

		protected $_model = 'app\models\Contacts';

		protected $_fields = array(
			'id' => array('type' => 'id'),
			'name' => array('type' => 'string')
		);

		protected $_records = array(
			array('id' => 1, 'name' => 'Nate'),
			array('id' => 2, 'name' => 'Gwoo')
		);
	}

	//app/models/Contact.php
	namespace app\models;

	class Contacts extends \lithium\data\Model {
	}

If you have numbers of fixtures, it will be interesting to use the `Fixtures` class.

Example of use case:

	//app/tests/integration/Sample2Test.php
	namespace app\tests\integration;

	use li3_fixtures\test\Fixtures;
	use app\models\Contacts;
	use app\models\Images;
	// and so on...

	class Sample2Test extends \lithium\test\Unit {

		public function setUp() {
			Fixtures::config(array(
				'db' => array(
					'adapter' => 'Connection',
					'connection' => 'lithium_mysql_test',
					'fixtures' => array(
						'contacts' => 'app\tests\fixture\ContactsFixture',
						'images' => 'app\tests\fixture\ImagesFixture'
						// and so on...
					)
				)
			));
			Fixtures::save('db');
		}

		public function tearDown() {
			Fixtures::reset('db');
		}

		public function testFixture() {
			var_export(Contacts::find('all')->data());
			var_export(Images::find('all')->data());
		}
	}

Ok so why it's better to set the `Fixture::_model` instead of `Fixture::_source` ? Long story short,
models had their own meta `'connection'` value. If a fixture is "linked" with a model, it will
automagically configure its meta `'connection'` to the fixture's connection when is created or saved.

Example:

	Fixtures::save('db', array('contacts'));
	Contacts::config(array('meta' => array('connection' => 'test'))); //This is not needed

### Advanced use case

For interoperability, sometimes it's usefull to adjust fixtures according a datasources.

You can alter `Fixture`'s instance before creating it like the following use case:

	$fixture->alter('add', array(
		'name' => 'enabled',
		'type' => 'boolean'
	); //Add a field

	$fixture->alter('change', array(
		'name' => 'published',
		'value' => function ($val) {
			return new MongoDate(strtotime($val));
		}
	); //Simple cast for fixture's values according the closure

	$fixture->alter('change', array(
		'name' => 'id',
		'to' => '_id',
		'value' => function ($val) {
			return new MongoId('4c3628558ead0e594' . (string) ($val + 1000000));
		}
	); //Renaming the field 'id' to '_id' + cast fixture's values according the closure

	$fixture->alter('change', array(
		'name' => 'bigintger',
		'type' => 'integer',
		'use' => 'bigint' //use db specific type
	); //Modifing a field type

	$fixture->alter('drop', 'bigintger'); //Simply dropping a field

Note :

You can recover a specific fixture's instance from `Fixtures` using:

	$fixture = Fixtures::get('db', 'contacts');