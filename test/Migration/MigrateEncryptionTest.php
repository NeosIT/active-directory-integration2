<?php

/**
 * Ut_NextADInt_Ldap_ConnectionTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Migration_MigrateEncryptionTest extends Ut_BasicTest
{
	const LDAPS_PREFIX = 'ldaps://';

	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileRepository|PHPUnit_Framework_MockObject_MockObject $profileRepository */
	private $profileRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository|PHPUnit_Framework_MockObject_MockObject $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository|PHPUnit_Framework_MockObject_MockObject $blogConfigurationRepository */
	private $blogConfigurationRepository;

	/** @var NextADInt_Adi_Dependencies|PHPUnit_Framework_MockObject_MockObject $dependencyContainer */
	private $dependencyContainer;

	public function setUp()
	{

		parent::setUp();
		$this->dependencyContainer = parent::createMock('NextADInt_Adi_Dependencies');


		$this->profileRepository = parent::createMock('NextADInt_Multisite_Configuration_Persistence_ProfileRepository');
		$this->profileConfigurationRepository = parent::createMock('NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository');
		$this->blogConfigurationRepository = parent::createMock('NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository');

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
	 * @return NextADInt_Migration_MigrateEncryption|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		return $connection = $this->getMockBuilder('NextADInt_Migration_MigrateEncryption')
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

		$expected = 1;

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
	public function migrateBlogs_executeMigrateConfig_withBlogId()
	{

		$sut = $this->sut(array('migrateProfiles', 'findAllBlogIds', 'migrateConfig'));

		$sut->expects($this->once())
			->method('findAllBlogIds')
			->willReturn(array(array('blog_id' => 1)));

		$sut->expects($this->once())
			->method('migrateConfig')
			->with($this->blogConfigurationRepository, 1);

		$sut->expects($this->once())
			->method('migrateProfiles');

		$sut->execute();
	}

	/**
	 * @test
	 */
	public function migrateProfiles_executeMigrateConfig_withBlogId()
	{

		$sut = $this->sut(array('migrateBlogs', 'migrateConfig'));

		$this->profileRepository->expects($this->once())
			->method('findAll')
			->willReturn(array(array('profileId' => 1)));

		$sut->expects($this->once())
			->method('migrateBlogs');

		$sut->expects($this->once())
			->method('migrateConfig')
			->with($this->profileConfigurationRepository, 1);

		$sut->execute();
	}

	/**
	 * @test
	 */
	public function migrateConfig_withBlogConfigurationRepository_persistStarttls()
	{
		$sut = $this->sut(array('migrateProfiles', 'findAllBlogIds'));

		$sut->expects($this->once())
			->method('findAllBlogIds')
			->willReturn(array(array('blog_id' => 1)));

		$this->blogConfigurationRepository->expects($this->exactly(2))
			->method('findSanitizedValue')
			->withConsecutive(
				array(1, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS),
				array(1, NextADInt_Adi_Configuration_Options::USE_TLS)
			)
			->will($this->onConsecutiveCalls(
				'127.0.0.1',
				'1'
			));

		$sut->expects($this->once())
			->method('migrateProfiles');

		$this->blogConfigurationRepository->expects($this->once())
			->method('persistSanitizedValue')
			->with(1, NextADInt_Adi_Configuration_Options::ENCRYPTION, 'starttls');


		$sut->execute();
	}

	/**
	 * @test
	 */
	public function migrateConfig_withBlogConfigurationRepository_persistNone()
	{
		$sut = $this->sut(array('migrateProfiles', 'findAllBlogIds'));

		$sut->expects($this->once())
			->method('findAllBlogIds')
			->willReturn(array(array('blog_id' => 1)));

		$this->blogConfigurationRepository->expects($this->exactly(2))
			->method('findSanitizedValue')
			->withConsecutive(
				array(1, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS),
				array(1, NextADInt_Adi_Configuration_Options::USE_TLS)
			)
			->will($this->onConsecutiveCalls(
				'127.0.0.1',
				'0'
			));

		$sut->expects($this->once())
			->method('migrateProfiles');

		$this->blogConfigurationRepository->expects($this->once())
			->method('persistSanitizedValue')
			->with(1, NextADInt_Adi_Configuration_Options::ENCRYPTION, 'none');


		$sut->execute();
	}

	/**
	 * @test
	 */
	public function migrateConfig_withBlogConfigurationRepository_persistLdaps()
	{
		$sut = $this->sut(array('migrateProfiles', 'findAllBlogIds'));

		$sut->expects($this->once())
			->method('findAllBlogIds')
			->willReturn(array(array('blog_id' => 1)));

		$this->blogConfigurationRepository->expects($this->exactly(2))
			->method('findSanitizedValue')
			->withConsecutive(
				array(1, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS),
				array(1, NextADInt_Adi_Configuration_Options::USE_TLS)
			)
			->will($this->onConsecutiveCalls(
				'ldaps://127.0.0.1',
				'0'
			));

		$sut->expects($this->once())
			->method('migrateProfiles');

		$this->blogConfigurationRepository->expects($this->exactly(3))
			->method('persistSanitizedValue')
			->withConsecutive(
				array(1, NextADInt_Adi_Configuration_Options::PORT, 636),
				array(1, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS, '127.0.0.1'),
				array(1, NextADInt_Adi_Configuration_Options::ENCRYPTION, NextADInt_Multisite_Option_Encryption::LDAPS)
			)
			->will($this->onConsecutiveCalls(
				'127.0.0.1',
				'ldaps'
			));


		$sut->execute();
	}

}
