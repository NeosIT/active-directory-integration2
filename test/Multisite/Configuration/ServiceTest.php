<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Multisite_Configuration_ServiceTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository| PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationRepository;

	/* @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository| PHPUnit_Framework_MockObject_MockObject */
	private $profileConfigurationRepository;

	/* @var NextADInt_Multisite_Configuration_Persistence_ProfileRepository|PHPUnit_Framework_MockObject_MockObject $profileRepository */
	private $profileRepository;

	public function setUp() : void
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->createMock('NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository');
		$this->profileConfigurationRepository = $this->createMock('NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository');
		$this->profileRepository = $this->createMock('NextADInt_Multisite_Configuration_Persistence_ProfileRepository');
	}

	public function tearDown() : void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return NextADInt_Multisite_Configuration_Service| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Multisite_Configuration_Service')
			->setConstructorArgs(
				array(
					$this->blogConfigurationRepository,
					$this->profileConfigurationRepository,
					$this->profileRepository,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function findAllProfiles_withGivenOptionNames_usesOnlyGivenOptions()
	{
		$sut = $this->sut(array('getProfileOptionsValues'));

		$profiles = array(
			1, 2, 3,
		);
		$options = array(
			'a', 'b', 'c',
		);

		$expected = array(
			1 => array('a' => 1, 'b' => 1, 'c' => 1),
			2 => array('a' => 2, 'b' => 2, 'c' => 2),
			3 => array('a' => 3, 'b' => 3, 'c' => 3),
		);

		$this->blogConfigurationRepository->expects($this->never())
			->method('getAllOptionNames')
			->willReturn($options);

		$this->profileRepository->expects($this->once())
			->method('findAllIds')
			->willReturn($profiles);

		$sut->expects($this->any())
			->method('getProfileOptionsValues')
			->withConsecutive(
				array(1, $options),
				array(2, $options),
				array(3, $options)
			)
			->willReturnOnConsecutiveCalls(
				$expected[1],
				$expected[2],
				$expected[3]
			);

		$actual = $sut->findAllProfiles($options);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findAllProfiles_withEmptyOptionNames_usesAllOptions()
	{
		$sut = $this->sut(array('getProfileOptionsValues'));

		$profiles = array(
			1, 2, 3,
		);
		$options = array(
			'a', 'b', 'c',
		);

		$expected = array(
			1 => array('a' => 1, 'b' => 1, 'c' => 1),
			2 => array('a' => 2, 'b' => 2, 'c' => 2),
			3 => array('a' => 3, 'b' => 3, 'c' => 3),
		);

		$this->blogConfigurationRepository->expects($this->once())
			->method('getAllOptionNames')
			->willReturn($options);

		$this->profileRepository->expects($this->once())
			->method('findAllIds')
			->willReturn($profiles);

		$sut->expects($this->any())
			->method('getProfileOptionsValues')
			->withConsecutive(
				array(1, $options),
				array(2, $options),
				array(3, $options)
			)
			->willReturnOnConsecutiveCalls(
				$expected[1],
				$expected[2],
				$expected[3]
			);

		$actual = $sut->findAllProfiles();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getOption_withoutBlogId_requestBlogId()
	{
		$sut = $this->sut(array('getProfileOptionValue', 'getValue', 'getPermission'));

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => 1,
			'return' => 33,
		));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findSanitizedValue')
			->with(33, 'port');

		$sut->expects($this->once())
			->method('getProfileOptionValue')
			->with('port', 33);

		$sut->getOption('port', null);
	}

	/**
	 * @test
	 */
	public function getOption_withBlogId_delegateToMethods()
	{
		$sut = $this->sut(array('getProfileOptionValue', 'getValue', 'getPermission'));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findSanitizedValue')
			->with(44 /* blogId */, 'port' /* option name */)
			->willReturn('389');

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(44)
			->willReturn(1);

		$sut->expects($this->exactly(2))
			->method('getProfileOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::DOMAIN_SID, 44),
				array(NextADInt_Adi_Configuration_Options::PORT, 44)
			)
			->will(
				$this->onConsecutiveCalls(
					'',
					'689'
				));

		$sut->expects($this->once())
			->method('getPermission')
			->with('port' /* option name */, 1 /* profileId */)
			->willReturn(3);

		$sut->expects($this->once())
			->method('getValue')
			->with(3 /* permission */, '689' /* profile option value */, '389' /* blog option value */)
			->willReturn('389');

		$expected = array(
			'option_name'       => 'port',
			'option_value'      => '389',
			'option_permission' => 3,
		);

		$actual = $sut->getOption('port', 44);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getOptionWithCache()
	{
		$sut = $this->sut(array('getProfileOptionValue', 'getValue', 'getPermission'));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findSanitizedValue')
			->with(44 /* blogId */, 'port' /* option name */)
			->willReturn('389');

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(44)
			->willReturn(1);

		$sut->expects($this->exactly(2))
			->method('getProfileOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::DOMAIN_SID, 44),
				array(NextADInt_Adi_Configuration_Options::PORT, 44)
			)
			->will(
				$this->onConsecutiveCalls(
					'',
					'689'
				));

		$sut->expects($this->once())
			->method('getPermission')
			->with('port' /* option name */, 1 /* profileId */)
			->willReturn(3);

		$sut->expects($this->once())
			->method('getValue')
			->with(3 /* permission */, '689' /* profile option value */, '389' /* blog option value */)
			->willReturn('389');

		$expected = array(
			'option_name'       => 'port',
			'option_value'      => '389',
			'option_permission' => 3,
		);

		$actual = $sut->getOption('port', 44);
		$this->assertEquals($expected, $actual);
		$actualWithCache = $sut->getOption('port', 44);
		$this->assertEquals($expected, $actualWithCache);
	}

	/**
	 * @test
	 */
	public function getProfileOptionValue_singleSite_returnNull()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => false,
		));

		$actual = $this->invokeMethod($sut, 'getProfileOptionValue', array('port', 77));
		$this->assertEquals(null, $actual);
	}

	/**
	 * @test
	 */
	public function getProfileOptionValue_multisite_returnProfileOptions()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => true,
		));

		$profileOption = (object)array(
			'option_name'       => 'port',
			'option_value'      => '999',
			'option_permission' => 2,
		);

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(77)
			->willReturn(66);

		$this->profileConfigurationRepository->expects($this->once())
			->method('findSanitizedValue')
			->with(66, 'port')
			->willReturn($profileOption);

		$actual = $this->invokeMethod($sut, 'getProfileOptionValue', array('port', 77));
		$this->assertEquals($profileOption, $actual);
	}

	/**
	 * @test
	 */
	public function getValue_optionPermissionEqual3_returnBlogOptionValue()
	{
		$sut = $this->sut(null);

		$actual = $this->invokeMethod($sut, 'getValue', array(3, '999', '389'));
		$this->assertEquals('389', $actual);
	}

	/**
	 * @test
	 */
	public function getValue_optionPermissionEqual1_returnProfileOptionValue()
	{
		$sut = $this->sut(null);

		$actual = $this->invokeMethod($sut, 'getValue', array(1, '999', '389'));
		$this->assertEquals('999', $actual);
	}

	/**
	 * @test
	 */
	public function getPermission_multiSite_returnPermission()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => true,
		));

		$this->profileConfigurationRepository->expects($this->once())
			->method('findSanitizedPermission')
			->with(5, 'port')
			->willReturn(1);

		$actual = $this->invokeMethod($sut, 'getPermission', array('port', 5));
		$this->assertEquals(1, $actual);
	}

	/**
	 * @test
	 */
	public function getPermission_singleSite_returnPermission()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => false,
		));

		$actual = $this->invokeMethod($sut, 'getPermission', array('port'));
		$this->assertEquals(3, $actual);
	}

	/**
	 * @test
	 */
	public function addProfileInformation_returnsExpectedResult()
	{
		$sut = $this->sut();

		$this->profileRepository->expects($this->once())
			->method('findName')
			->with(1)
			->willReturn('name');

		$expected = array(
			NextADInt_Adi_Configuration_Options::PROFILE_NAME => array(
				'option_value'      => 'name',
				'option_permission' => NextADInt_Multisite_Configuration_Service::DISABLED_FOR_BLOG_ADMIN,
			),
		);

		$result = $this->invokeMethod($sut, 'addProfileInformation', array(1, array()));

		$this->assertEquals($expected, $result);
	}

	/**
	 * @test
	 */
	public function isEnvironmentOption_whenCheckingEnvironmentOption_itReturnsTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isEnvironmentOption(NextADInt_Adi_Configuration_Options::PORT);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isEnvironmentOption_whenCheckingNonEnvironmentOption_itReturnsFalse()
	{
		$sut = $this->sut(null);

		$actual = $sut->isEnvironmentOption(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER);
		$this->assertFalse($actual);
	}


}
