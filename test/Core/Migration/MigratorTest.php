<?php

class MigrationStubFail extends NextADInt_Migration_MigrateEncryption
{
	public function execute()
	{
		throw new Exception('Cannot run execution.');
	}

	public static function getId()
	{
		return 2;
	}
}

class MigrationStubSuccess extends NextADInt_Migration_MigrateEncryption
{
	public function execute()
	{
		return true;
	}

	public static function getId()
	{
		return 3;
	}
}

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_Core_Migration_MigratorTest extends Ut_BasicTest
{
	/** @var NextADInt_Adi_Dependencies | PHPUnit_Framework_MockObject_MockObject */
	private $dependencyContainer;
	/** @var NextADInt_Core_Migration_Persistence_MigrationRepository | PHPUnit_Framework_MockObject_MockObject */
	private $migrationRepository;

	public function setUp() : void
	{
		parent::setUp();

		$this->dependencyContainer = $this->createMock('NextADInt_Adi_Dependencies');
		$this->migrationRepository = $this->createMock('NextADInt_Core_Migration_Persistence_MigrationRepository');

		//TODO $this->createMock('Migration_MigrationStub');
	}

	/**
	 * @param null $methods
	 *
	 * @return NextADInt_Core_Migration_Service|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Core_Migration_Service')
			->setConstructorArgs(array(
				$this->dependencyContainer,
				$this->migrationRepository,
			))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getOrderedMigrations_withDuplicate_throwsException()
	{
		$sut = $this->sut(array('getMigrations'));
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('The migration with ID "2" has a duplicate.');

		$sut->expects($this->once())
			->method('getMigrations')
			->willReturn(array(
				'NextADInt_Migration_MigrateEncryption',
				'MigrationStubFail',
				'MigrationStubFail',
			));

		$this->invokeMethod($sut, 'getOrderedMigrations');
	}

	/**
	 * @test
	 */
	public function startMigration_withAlreadyExecutedMigrations_skipsMigrations()
	{
		$sut = $this->sut(array('getOrderedMigrations', 'executeMigration'));

		$this->migrationRepository->expects($this->once())
			->method('getLastMigration')
			->willReturn(1);

		$sut->expects($this->once())
			->method('getOrderedMigrations')
			->willReturn(array(
				1 => 'NextADInt_Migration_MigrateEncryption',
				3 => 'MigrationStubSuccess',
			));

		$sut->expects($this->once())
			->method('executeMigration')
			->with('MigrationStubSuccess');

		$sut->startMigration();
	}

	/**
	 * @test
	 */
	public function startMigration_worksProperly()
	{
		$sut = $this->sut(array('getOrderedMigrations'));

		$this->migrationRepository->expects($this->once())
			->method('getLastMigration')
			->willReturn(1);

		$this->migrationRepository->expects($this->once())
			->method('setLastMigration')
			->with(3);

		$sut->expects($this->once())
			->method('getOrderedMigrations')
			->willReturn(array(
				3 => 'MigrationStubSuccess',
			));

		$sut->startMigration();
	}

	/**
	 * @test
	 */
	public function executeMigration_withSuccessfulExecution_returnsId()
	{
		$sut = $this->sut(array('findId'));

		$this->expects($sut, $this->once(), 'findId', 'MigrationStubSuccess', 2);

		$actual = $this->invokeMethod($sut, 'executeMigration', array('MigrationStubSuccess'));
		$this->assertEquals(2, $actual);
	}

	/**
	 * @test
	 */
	public function executeMigration_withErrorOnExecution_returnsFalse()
	{
		$sut = $this->sut(array('findId'));

		$this->expects($sut, $this->once(), 'findId', 'MigrationStubFail', 2);

		$actual = $this->invokeMethod($sut, 'executeMigration', array('MigrationStubFail'));
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function executeMigration_smoke()
	{
		$sut = $this->sut();

		$actual = $this->invokeMethod($sut, 'executeMigration', array('MigrationStubSuccess'));
		$this->assertEquals(3, $actual);
	}

	/**
	 * @test
	 */
	public function findId_withExistingClass_returnsIdFromMigrationClass()
	{
		$sut = $this->sut();

		$expected = 2;
		$actual = $this->invokeMethod($sut, 'findId', array('MigrationStubFail'));

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findId_withoutExistingClass_returnsFalse()
	{
		$sut = $this->sut();

		$actual = $this->invokeMethod($sut, 'findId', array('NonExistingClass'));

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function getOrderedMigrations_returnsCorrectOrder()
	{
		$sut = $this->sut(array('getMigrations'));

		$sut->expects($this->once())
			->method('getMigrations')
			->willReturn(array(
				'MigrationStubFail',
				'NextADInt_Migration_MigrateEncryption',
			));

		$expected = array(
			1 => 'NextADInt_Migration_MigrateEncryption',
			2 => 'MigrationStubFail',
		);
		$actual = $this->invokeMethod($sut, 'getOrderedMigrations');

		$this->assertSame($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getMigrations_containsAllMigrations()
	{
		$sut = $this->sut();

		$expected = array(
			'NextADInt_Migration_MigrateEncryption',
			'NextADInt_Migration_MigrateUseSamAccountNameForNewCreatedUsers',
			'NextADInt_Migration_MigratePasswordEncryption'
		);
		$actual = $this->invokeMethod($sut, 'getMigrations');

		$this->assertEquals($expected, $actual);
	}
}