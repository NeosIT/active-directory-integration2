<?php

/**
 * Ut_Ldap_ConnectionTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Migration_MigrateUseSamAccountNameForNewCreatedUsersTest extends Ut_BasicTest
{

	/** @var Multisite_Configuration_Persistence_ProfileRepository|PHPUnit_Framework_MockObject_MockObject $profileRepository */
	private $profileRepository;

	/** @var Multisite_Configuration_Persistence_ProfileConfigurationRepository|PHPUnit_Framework_MockObject_MockObject $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/** @var Multisite_Configuration_Persistence_BlogConfigurationRepository|PHPUnit_Framework_MockObject_MockObject $blogConfigurationRepository */
	private $blogConfigurationRepository;

	/** @var Adi_Dependencies|PHPUnit_Framework_MockObject_MockObject $dependencyContainer */
	private $dependencyContainer;

	public function setUp()
	{
		parent::setUp();
		$this->dependencyContainer = parent::createMock('Adi_Dependencies');


		$this->profileRepository = parent::createMock('Multisite_Configuration_Persistence_ProfileRepository');
		$this->profileConfigurationRepository = parent::createMock('Multisite_Configuration_Persistence_ProfileConfigurationRepository');
		$this->blogConfigurationRepository = parent::createMock('Multisite_Configuration_Persistence_BlogConfigurationRepository');

		$this->dependencyContainer->expects($this->once())
			->method('getProfileRepository')
			->willReturn($this->profileRepository);

		$this->dependencyContainer->expects($this->once())
			->method('getProfileConfigurationRepository')
			->willReturn($this->profileConfigurationRepository);

		$this->dependencyContainer->expects($this->once())
			->method('getBlogConfigurationRepository')
			->willReturn($this->blogConfigurationRepository);
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return Migration_MigrateUseSamAccountNameForNewCreatedUsers|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $connection = $this->getMockBuilder('Migration_MigrateUseSamAccountNameForNewCreatedUsers')
			->setConstructorArgs(array(
				$this->dependencyContainer,
			))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getId()
	{
		$sut = $this->sut(null);

		$actual = $sut->getId();

		$expected = 2;

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function execute_triggersCorrectMethodes()
	{

		$sut = $this->sut(array('migrateBlogs', 'migrateProfiles'));

		$sut->expects($this->once())
			->method('migrateBlogs');

		$sut->expects($this->once())
			->method('migrateProfiles');

		$sut->execute();
	}

	/**
	 * @test
	 */
	public function migrateConfig_triggersExpectedMethods()
	{
		$sut = $this->sut(array('migratePermission', 'migrateValue'));

		$sut->expects($this->once())
			->method('migratePermission');

		$sut->expects($this->once())
			->method('migrateValue');

		$this->invokeMethod($sut, 'migrateConfig', array($this->blogConfigurationRepository, 1));
	}

	/**
	 * @test
	 */
	public function migrateValue_withoutValue()
	{
		$sut = $this->sut();

		$this->blogConfigurationRepository->expects($this->once())
			->method('findSanitizedValue')
			->with(1, Migration_MigrateUseSamAccountNameForNewCreatedUsers::APPEND_SUFFIX_TO_NEW_USERS)
			->willReturn('');

		$this->blogConfigurationRepository->expects($this->once())
			->method('persistSanitizedValue')
			->with(1, Adi_Configuration_Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS, 1);

		$this->invokeMethod($sut, 'migrateValue', array($this->blogConfigurationRepository, 1));
	}

	/**
	 * @test
	 */
	public function migrateValue_withValue()
	{
		$sut = $this->sut();

		$this->blogConfigurationRepository->expects($this->once())
			->method('findSanitizedValue')
			->with(1, Migration_MigrateUseSamAccountNameForNewCreatedUsers::APPEND_SUFFIX_TO_NEW_USERS)
			->willReturn(1);

		$this->blogConfigurationRepository->expects($this->once())
			->method('persistSanitizedValue')
			->with(1, Adi_Configuration_Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS, '');

		$this->invokeMethod($sut, 'migrateValue', array($this->blogConfigurationRepository, 1));
	}


	/**
	 * @test
	 */
	public function migratePermission_withPermissionFalse_doesNotPersistsValue()
	{
		$sut = $this->sut();

		$this->profileConfigurationRepository->expects($this->once())
			->method('findSanitizedPermission')
			->with(1, Migration_MigrateUseSamAccountNameForNewCreatedUsers::APPEND_SUFFIX_TO_NEW_USERS)
			->willReturn(false);

		$this->profileConfigurationRepository->expects($this->never())
			->method('persistSanitizedPermission');

		$this->invokeMethod($sut, 'migratePermission', array($this->profileConfigurationRepository, 1));
	}

	/**
	 * @test
	 */
	public function migratePermission_withValidPermission_persistsOldValueAsNewOne()
	{
		$sut = $this->sut();

		$this->profileConfigurationRepository->expects($this->once())
			->method('findSanitizedPermission')
			->with(1, Migration_MigrateUseSamAccountNameForNewCreatedUsers::APPEND_SUFFIX_TO_NEW_USERS)
			->willReturn(2);

		$this->profileConfigurationRepository->expects($this->once())
			->method('persistSanitizedPermission')
			->with(1, Adi_Configuration_Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS, 2);

		$this->invokeMethod($sut, 'migratePermission', array($this->profileConfigurationRepository, 1));
	}
}
