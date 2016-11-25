<?php

/**
 * Ut_DatabaseTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
abstract class Ut_DatabaseTest extends PHPUnit_Extensions_Database_TestCase
{
	const DATABASE_IP = 'localhost';
	const DATABASE_NAME = 'test';
	const DATABASE_USER = 'root';
	const DATABASE_PASSWORD = 'root';

	public function setUp()
	{
		WP_Mock::setUp();

		WP_Mock::wpFunction(
			'__', array(
				'args'       => array(WP_Mock\Functions::type('string'), 'next-active-directory-integration'),
				'return_arg' => 0
			)
		);

		$this->cleanDatabase();
	}

	public function tearDown()
	{
		WP_Mock::tearDown();
	}

	public function createMock($className)
	{
		if ( ! class_exists($className) || ! interface_exists($className)) {
			echo "You create a new class/interface '$className'. Be careful.";
		}

		return $this->getMockBuilder($className)
			->disableOriginalConstructor()
			->disableProxyingToOriginalMethods()
			->getMock();
	}

	/**
	 * @param $class
	 * @param $constructor
	 * @param $methods
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($class, $constructor, $methods)
	{
		return $this->getMockBuilder($class)
			->setConstructorArgs($constructor)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * Returns the test database connection.
	 *
	 * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
	 */
	protected function getConnection()
	{
		$pdo = new PDO('mysql:host=' . self::DATABASE_IP . ';dbname=' . self::DATABASE_NAME, self::DATABASE_USER, self::DATABASE_PASSWORD);
		$pdo->exec('USE ' . self::DATABASE_NAME . ';');
		return $this->createDefaultDBConnection($pdo, 'test');
	}

	/**
	 *
	 */
	protected function cleanDatabase()
	{
		$pdo = new PDO('mysql:host=' . self::DATABASE_IP . ';dbname=' . self::DATABASE_NAME, self::DATABASE_USER, self::DATABASE_PASSWORD);
		$pdo->exec('DROP DATABASE ' . self::DATABASE_NAME . ';');
		$pdo->exec('CREATE DATABASE ' . self::DATABASE_NAME . ';');
		$pdo->exec('USE ' . self::DATABASE_NAME . ';');
	}

	/**
	 * Returns the test dataset.
	 *
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	protected function getDataSet()
	{
		return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
	}
}