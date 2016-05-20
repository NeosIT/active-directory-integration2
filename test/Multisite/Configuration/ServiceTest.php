<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Multisite_Configuraiton_ServiceTest extends Ut_BasicTest
{
	/* @var Multisite_Configuration_Persistence_BlogConfigurationRepository| PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationRepository;

	/* @var Multisite_Configuration_Persistence_ProfileConfigurationRepository| PHPUnit_Framework_MockObject_MockObject */
	private $profileConfigurationRepository;

	/* @var Multisite_Configuration_Persistence_ProfileRepository|PHPUnit_Framework_MockObject_MockObject $profileRepository */
	private $profileRepository;

	public function setUp()
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->createMock('Multisite_Configuration_Persistence_BlogConfigurationRepository');
		$this->profileConfigurationRepository = $this->createMock('Multisite_Configuration_Persistence_ProfileConfigurationRepository');
		$this->profileRepository = $this->createMock('Multisite_Configuration_Persistence_ProfileRepository');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return Multisite_Configuration_Service| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Multisite_Configuration_Service')
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
	public function getOption_withoutBlogId_requestBlogId()
	{
		$sut = $this->sut(array('getProfileOptionValue', 'getValue', 'getPermission'));

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => 1,
			'return' => 33,
		));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findSanitized')
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
			->method('findSanitized')
			->with(44 /* blogId */, 'port' /* option name */)
			->willReturn('389');

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(44)
			->willReturn(1);

		$sut->expects($this->once())
			->method('getProfileOptionValue')
			->with('port', 44)
			->willReturn('689');

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
	public function getOptionWithCache() {
		$sut = $this->sut(array('getProfileOptionValue', 'getValue', 'getPermission'));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findSanitized')
			->with(44 /* blogId */, 'port' /* option name */)
			->willReturn('389');

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(44)
			->willReturn(1);

		$sut->expects($this->once())
			->method('getProfileOptionValue')
			->with('port', 44)
			->willReturn('689');

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
			->method('findValueSanitized')
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
			->method('findPermissionSanitized')
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
			Adi_Configuration_Options::PROFILE_NAME      => array(
				'option_value'      => 'name',
				'option_permission' => Multisite_Configuration_Service::DISABLED_FOR_BLOG_ADMIN
			),
		);

		$result = $this->invokeMethod($sut, 'addProfileInformation', array(1, array()));

		$this->assertEquals($expected, $result);
	}
}
