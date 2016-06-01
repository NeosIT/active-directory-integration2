<?php

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Multisite_View_TwigContainerTest extends Ut_BasicTest
{
	/** @var Multisite_Configuration_Persistence_BlogConfigurationRepository |PHPUnit_Framework_MockObject_MockObject */
	private $blogConfigurationRepository;

	/** @var Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/** @var Multisite_Configuration_Persistence_ProfileConfigurationRepository|PHPUnit_Framework_MockObject_MockObject */
	private $profileConfigurationRepository;

	/** @var Multisite_Configuration_Persistence_ProfileRepository|PHPUnit_Framework_MockObject_MockObject */
	private $profileRepository;

	/** @var Multisite_Configuration_Persistence_DefaultProfileRepository|PHPUnit_Framework_MockObject_MockObject */
	private $defaultProfileRepository;

	/** @var Multisite_Option_Provider|PHPUnit_Framework_MockObject_MockObject */
	private $optionProvider;
	
	/** @var Adi_Authentication_VerificationService|PHPUnit_Framework_MockObject_MockObject */
	private $verificationService;

	public function setUp()
	{
		parent::setUp();

		$this->blogConfigurationRepository = $this->createMock('Multisite_Configuration_Persistence_BlogConfigurationRepository');
		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->profileRepository = $this->createMock('Multisite_Configuration_Persistence_ProfileRepository');
		$this->profileConfigurationRepository = $this->createMock('Multisite_Configuration_Persistence_ProfileConfigurationRepository');
		$this->defaultProfileRepository = $this->createMock
		('Multisite_Configuration_Persistence_DefaultProfileRepository');
		$this->optionProvider = $this->createMock('Multisite_Option_Provider');
		$this->verificationService = $this->createMock('Adi_Authentication_VerificationService');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function register_registersNecessaryTwigFilters()
	{
		$sut = $this->sut(null);
		$sut->register();

		$twig = $sut->getTwig();

		$this->assertNotNull($twig->getFilter('__'));
		$this->assertNotNull($twig->getFilter('var_dump'));
	}

	/**
	 * @test
	 */
	public function register_registersNecessaryTwigFiltersWithCorrectCallback()
	{
		$sut = $this->sut(null);
		$sut->register();

		$twig = $sut->getTwig();

		$this->assertEquals('__', $twig->getFilter('__')->getCallable());
		$this->assertEquals('var_dump', $twig->getFilter('var_dump')->getCallable());
	}

	/**
	 * @test
	 */
	public function register_registersNecessaryTwigFunctions()
	{
		$sut = $this->sut(null);
		$sut->register();

		$twig = $sut->getTwig();

		$this->assertNotNull($twig->getFunction('getMetadata'));
		$this->assertNotNull($twig->getFunction('getOptionsGrouping'));

		$this->assertNotNull($twig->getFunction('isOptionGroupVisible'));

		$this->assertNotNull($twig->getFunction('getOptionPermission'));
		$this->assertNotNull($twig->getFunction('isOptionDisabled'));
		$this->assertNotNull($twig->getFunction('getOptionValue'));

		$this->assertNotNull($twig->getFunction('getBlogName'));
		$this->assertNotNull($twig->getFunction('getProfileIdOfBlog'));
		$this->assertNotNull($twig->getFunction('getSites'));
		$this->assertNotNull($twig->getFunction('findAllProfileIds'));
		$this->assertNotNull($twig->getFunction('findDefaultProfileId'));
		$this->assertNotNull($twig->getFunction('findProfileName'));
		$this->assertNotNull($twig->getFunction('findProfileDescription'));
	}

	/**
	 * @test
	 */
	public function register_registersNecessaryTwigFunctionsWithCorrectCallback()
	{
		$sut = $this->sut(null);
		$sut->register();

		$twig = $sut->getTwig();

		$this->assertEquals(
			array($sut, 'isOptionGroupVisible'),
			$twig->getFunction('isOptionGroupVisible')->getCallable()
		);

		$this->assertEquals(array($sut, 'getMetadata'), $twig->getFunction('getMetadata')->getCallable());
		$this->assertEquals(
			Adi_Configuration_Ui_Layout::get(), call_user_func($twig->getFunction('getOptionsGrouping')->getCallable())
		);
		$this->assertEquals(
			array($sut, 'getOptionPermission'), $twig->getFunction('getOptionPermission')->getCallable()
		);
		$this->assertEquals(array($sut, 'isOptionDisabled'), $twig->getFunction('isOptionDisabled')->getCallable());
		$this->assertEquals(array($sut, 'getOptionValue'), $twig->getFunction('getOptionValue')->getCallable());
		$this->assertEquals(array($sut, 'getBlogName'), $twig->getFunction('getBlogName')->getCallable());
		$this->assertEquals(array($sut, 'getProfileIdOfBlog'), $twig->getFunction('getProfileIdOfBlog')->getCallable());
		$this->assertEquals(array($sut, 'getSites'), $twig->getFunction('getSites')->getCallable());

		$this->assertEquals(
			array($this->profileRepository, 'findAllIds'), $twig->getFunction('findAllProfileIds')->getCallable()
		);
		$this->assertEquals(
			array($this->defaultProfileRepository, 'findProfileId'),
			$twig->getFunction('findDefaultProfileId')->getCallable()
		);
		$this->assertEquals(
			array($this->profileRepository, 'findName'), $twig->getFunction('findProfileName')->getCallable()
		);
		$this->assertEquals(
			array($this->profileRepository, 'findDescription'),
			$twig->getFunction('findProfileDescription')->getCallable()
		);
	}

	/**
	 * @test
	 */
	public function isOptionGroupVisible_MultisiteOnlyTrueAndOnNetworkDashboardTrue_returnsTrue()
	{
		$sut = $this->sut(array('isOnNetworkDashboard'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(true);

		$optionGroup = array(Adi_Configuration_Ui_Layout::MULTISITE_ONLY => true);

		$result = $sut->isOptionGroupVisible($optionGroup);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function isOptionGroupVisible_MultisiteOnlyTrueAndOnNetworkDashboardFalse_returnsFalse()
	{
		$sut = $this->sut(array('isOnNetworkDashboard'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(false);

		$optionGroup = array(Adi_Configuration_Ui_Layout::MULTISITE_ONLY => true);

		$result = $sut->isOptionGroupVisible($optionGroup);

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function isOptionGroupVisible_MultisiteOnlyFalseAndOnNetworkDashboardTrue_returnsTrue()
	{
		$sut = $this->sut(array('isOnNetworkDashboard'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(true);

		$optionGroup = array(Adi_Configuration_Ui_Layout::MULTISITE_ONLY => false);

		$result = $sut->isOptionGroupVisible($optionGroup);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function isOptionGroupVisible_MultisiteOnlyFalseAndOnNetworkDashboardFalse_returnsTrue()
	{
		$sut = $this->sut(array('isOnNetworkDashboard'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(false);

		$optionGroup = array(Adi_Configuration_Ui_Layout::MULTISITE_ONLY => false);

		$result = $sut->isOptionGroupVisible($optionGroup);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function getOptionValue_withProfileId_returnProfileValue()
	{
		$sut = $this->sut(null);

		$this->profileConfigurationRepository->expects($this->once())
			->method('findValueSanitized')
			->with(1, 'port')
			->willReturn('389');

		$actual = $sut->getOptionValue('port', 1);
		$this->assertEquals('389', $actual);
	}

	/**
	 * @test
	 */
	public function getOptionValue_withNoPermission_returnFalse()
	{
		$sut = $this->sut(null);

		$blogId = 4444;
		$profileId = 3333;

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => 1,
			'return' => $blogId,
		));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with($blogId)
			->willReturn($profileId);

		$this->profileConfigurationRepository->expects($this->once())
			->method('findPermissionSanitized')
			->with($profileId, 'port')
			->willReturn(1);

		$actual = $sut->getOptionValue('port', null);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function getOptionValue_withPermission_returnBlogValue()
	{
		$sut = $this->sut(array('getPermissionForOptionAndBlog'));

		$blogId = 4444;

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => 1,
			'return' => $blogId,
		));

		$sut->expects($this->once())
			->method('getPermissionForOptionAndBlog')
			->with('port')
			->willReturn(3);

		$this->blogConfigurationRepository->expects($this->once())
			->method('findSanitized')
			->with($blogId, 'port')
			->willReturn('389');

		$actual = $sut->getOptionValue('port', null);
		$this->assertEquals('389', $actual);
	}

	/**
	 * @test
	 */
	public function isOptionDisabled_withProfileId_returnFalse()
	{
		$sut = $this->sut(null);

		$actual = $sut->isOptionDisabled('port', 5);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function isOptionDisabled_withPermission_returnFalse()
	{
		$sut = $this->sut(array('getPermissionForOptionAndBlog'));

		$blogId = 4444;

		$sut->expects($this->once())
			->method('getPermissionForOptionAndBlog')
			->with('port')
			->willReturn(3);

		$actual = $sut->isOptionDisabled('port');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function isOptionDisabled_withoutPermission_returnTrue()
	{
		$sut = $this->sut(array('getPermissionForOptionAndBlog'));

		$sut->expects($this->once())
			->method('getPermissionForOptionAndBlog')
			->with('port')
			->willReturn(2);

		$actual = $sut->isOptionDisabled('port');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function getPermissionForOptionAndBlog_getBlogId_returnPermission()
	{
		$sut = $this->sut(null);

		$blogId = 4444;
		$profileId = 3333;
		$optionName = 'port';

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => 1,
			'return' => $blogId,
		));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with($blogId)
			->willReturn($profileId);

		$this->profileConfigurationRepository->expects($this->once())
			->method('findPermissionSanitized')
			->with($profileId, $optionName)
			->willReturn(2);

		$actual = $sut->getPermissionForOptionAndBlog($optionName);
		$this->assertEquals(2, $actual);
	}

	/**
	 * @test
	 */
	public function getOptionPermission_withoutProfileId_getProfileOption()
	{
		$sut = $this->sut(null);

		$blogId = 4444;
		$profileId = 5;
		$optionName = 'port';

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => 1,
			'return' => $blogId,
		));

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with($blogId)
			->willReturn($profileId);

		$this->profileConfigurationRepository->expects($this->once())
			->method('findPermissionSanitized')
			->with($profileId, $optionName)
			->willReturn(2);

		$actual = $sut->getOptionPermission(null, $optionName);
		$this->assertEquals(2, $actual);
	}

	/**
	 * @test
	 */
	public function getOptionPermission_withProfileId_getProfileOption()
	{
		$sut = $this->sut(null);

		$profileId = 5;
		$optionName = 'port';

		$this->profileConfigurationRepository->expects($this->once())
			->method('findPermissionSanitized')
			->with($profileId, $optionName)
			->willReturn(2);

		$actual = $sut->getOptionPermission($profileId, $optionName);
		$this->assertEquals(2, $actual);
	}

	/**
	 * @test
	 */
	public function getBlogName_withoutBlogId_delegateToGetOption()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction(
			'get_option', array(
				'args'   => 'blogname',
				'times'  => '1',
				'return' => 'testBlogName')
		);

		$returnedValue = $sut->getBlogName();
		$this->assertEquals('testBlogName', $returnedValue);
	}

	/**
	 * @test
	 */
	public function getBlogName_notMultisite_delegateToGetOption()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => 1,
				'return' => false)
		);

		WP_Mock::wpFunction(
			'get_option', array(
				'args'   => 'blogname',
				'times'  => '1',
				'return' => 'testBlogName',
			)
		);

		$returnedValue = $sut->getBlogName(1);
		$this->assertEquals('testBlogName', $returnedValue);
	}

	/**
	 * @test
	 */
	public function getBlogName_withBlogId_delegateToGetBlogOption()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction(
			'get_blog_option', array(
				'args'   => array(1, 'blogname'),
				'times'  => '1',
				'return' => 'testBlogName',)
		);

		WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => 1,
				'return' => true)
		);

		$returnedValue = $sut->getBlogName(1);
		$this->assertEquals('testBlogName', $returnedValue);
	}

	/**
	 * @test
	 */
	public function getProfileIdOfBlog()
	{
		$sut = $this->sut(null);

		$this->blogConfigurationRepository->expects($this->once())
			->method('findProfileId')
			->with(1)
			->willReturn(1);

		$returnedValue = $sut->getProfileIdOfBlog(1);
		$this->assertEquals(1, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getSites_isMultisite_returnSites()
	{
		$sut = $this->sut(null);

		$sites[0] = array(
			'network_id' => 1,
			'public'     => 'somethingPublic',
			'archived'   => 'somethingArchived',
			'mature'     => 'somethingMature',
			'spam'       => 'somethingSpam',
			'deleted'    => 'somethingDeleted',
			'limit'      => 100,
			'offset'     => 1,
		);

		WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => '1',
				'return' => true,)
		);

		WP_Mock::wpFunction(
			'wp_get_sites', array(
				'args'   => array(array('limit' => 9999)),
				'times'  => '1',
				'return' => $sites,)
		);

		$returnedValue = $this->invokeMethod($sut, 'getSites');
		$this->assertTrue(is_array($sites));
		$this->assertEquals($sites, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getSites_noMultisite_returnDefaultArray()
	{
		$sut = $this->sut(null);

		$sites = array();
		$sites[0] = array(
			'network_id' => 0,
			'public'     => null,
			'archived'   => null,
			'mature'     => null,
			'spam'       => null,
			'deleted'    => null,
			'limit'      => 100,
			'offset'     => 0,
		);

		WP_Mock::wpFunction(
			'is_multisite', array(
				'times'  => '1',
				'return' => false,)
		);

		$returnedValue = $this->invokeMethod($sut, 'getSites');
		$this->assertTrue(is_array($sites));
		$this->assertEquals($sites, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getTwig()
	{
		$sut = $this->sut(array('register'));

		$sut->expects($this->once())
			->method('register');

		$returnedValue = $sut->getTwig();

		$this->assertTrue(is_null($returnedValue));
	}

	/**
	 *
	 * @return Multisite_View_TwigContainer| PHPUnit_Framework_MockObject_MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder('Multisite_View_TwigContainer')
			->setConstructorArgs(
				array(
					$this->blogConfigurationRepository,
					$this->configuration,
					$this->profileConfigurationRepository,
					$this->profileRepository,
					$this->defaultProfileRepository,
					$this->optionProvider,
					$this->verificationService
				)
			)
			->setMethods($methods)
			->getMock();
	}
}