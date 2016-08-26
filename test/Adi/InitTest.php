<?php

/**
 * @author Christopher Klein <ckl@neos-it.de>
 * @access private
 */
class Ut_Adi_InitTest extends Ut_BasicTest
{
	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function initialize_loadsLanguageFile()
	{
		$sut = $this->sut();

		WP_Mock::wpFunction('load_plugin_textdomain', array(
			'args'  => array(
				ADI_I18N,
				false,
				NEXT_AD_INT_PATH . '/languages/',
			),
			'times' => 1));

		$sut->initialize();
	}

	private function createActivationEnvironment($dc)
	{
		$fakeService = $this->createAnonymousMock(array('check', 'register', 'insertDefaultProfile', 'autoImport',
			'migratePreviousVersion', 'persistSanitizedValue'));
		$dc->expects($this->once())
			->method('getRequirements')
			->willReturn($fakeService);

		$dc->expects($this->any())
			->method('getImportService')
			->willReturn($fakeService);

		$dc->expects($this->any())
			->method('getUserManager')
			->willReturn($fakeService);

		$dc->expects($this->any())
			->method('getProfileRepository')
			->willReturn($fakeService);

		return $fakeService;
	}

	/**
	 * @test
	 */
	public function activation_itDoesNotRegisterImportService_whenCheckFailed()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createActivationEnvironment($dc);

		$this->behave($fakeService, 'check', false);

		$fakeService->expects($this->never())
			->method('register');

		$sut->activation();
	}

	/**
	 * @test
	 */
	public function activation_itRegistersImport()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createActivationEnvironment($dc);

		$this->behave($fakeService, 'check', true);

		$fakeService->expects($this->once())
			->method('register');

		$sut->activation();
	}

	/**
	 * @test
	 */
	public function activation_itInsertsDefaultProfile()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createActivationEnvironment($dc);

		$this->behave($fakeService, 'check', true);

		$fakeService->expects($this->once())
			->method('insertDefaultProfile');

		$sut->activation();
	}

	/**
	 * @test
	 */
	public function activation_itAutoImportsOldSettings()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createActivationEnvironment($dc);

		$fakeService->expects($this->once())
			->method('check')
			->with(true, true)
			->willReturn(true);

		$fakeService->expects($this->once())
			->method('autoImport');

		$sut->activation();
	}

	/**
	 * @test
	 */
	public function activation_itMigratesAdi1xUsers()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createActivationEnvironment($dc);

		$fakeService->expects($this->once())
			->method('check')
			->with(true, true)
			->willReturn(true);

		$fakeService->expects($this->once())
			->method('migratePreviousVersion');

		$sut->activation();
	}

	/**
	 * @test
	 */
	public function activation_itExcludesCurrentUserInNetwork_whenDefaultProfileHasBeenAdded()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createActivationEnvironment($dc);

		WP_Mock::wpFunction('wp_get_current_user', array(
			'times'  => 1,
			'return' => (object)array('user_login' => 'username')));

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => true));

		$this->behave($fakeService, 'check', true);
		$this->behave($fakeService, 'insertDefaultProfile', 666);
		$this->behave($dc, 'getProfileConfigurationRepository', $fakeService);

		$fakeService->expects($this->once())
			->method('persistSanitizedValue')
			->with(666, Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION, 'username');

		$sut->activation();
	}

	/**
	 * @test
	 */
	public function activation_itExcludesCurrentUserInSingleSite_whenDefaultProfileHasBeenAdded()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createActivationEnvironment($dc);

		WP_Mock::wpFunction('wp_get_current_user', array(
			'times'  => 1,
			'return' => (object)array('user_login' => 'username')));

		WP_Mock::wpFunction('is_multisite', array(
			'times'  => 1,
			'return' => false));

		$this->behave($fakeService, 'check', true);
		$this->behave($fakeService, 'insertDefaultProfile', 666);
		$this->behave($dc, 'getBlogConfigurationRepository', $fakeService);

		$fakeService->expects($this->once())
			->method('persistSanitizedValue')
			->with(0, Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION, 'username');

		$sut->activation();
	}

	/**
	 * @test
	 */
	public function postActivation_itRegistersPostActivationOfOptionsImporter()
	{
		global $pagenow;
		$pagenow = 'plugins.php';
		$_REQUEST['activate'] = 'true';


		WP_Mock::wpFunction('is_plugin_active', array(
			'args'   => 'active-directory-integration2/index.php',
			'times'  => 1,
			'return' => true));

		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);

		$fakeService = $this->createAnonymousMock(array('registerPostActivation'));
		$dc->expects($this->once())
			->method('getImportService')
			->willReturn($fakeService);

		$fakeService->expects($this->once())
			->method('registerPostActivation');

		$sut->postActivation();
	}

	/**
	 * @since ADI-295
	 * @test
	 */
	public function postActivation_itRegistersShowLicensePurchaseInformation()
	{
		global $pagenow;
		$pagenow = 'plugins.php';
		$_REQUEST['activate'] = 'false';

		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);

		WP_Mock::expectActionAdded('after_plugin_row_' . ADI_PLUGIN_FILE,
			array($sut, 'showLicensePurchaseInformation'),
		99,2);

		$sut->postActivation();
	}

	/**
	 * @since ADI-295
	 * @outputBuffering
	 * @test
	 */
	public function showLicensePurchaseInformation_itShowsPurchaseInformation() {
		WP_Mock::wpFunction('is_plugin_active', array(
			'args'   => 'active-directory-integration2/index.php',
			'times'  => 1,
			'return' => true));

		\WP_Mock::wpFunction('__', array(
			'args'  => array(WP_Mock\Functions::type('string')),
			'times' => '1',
			'return' => 'Please purchase'
		));

		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);

		$fakeService = $this->createAnonymousMock(array('getOptionValue'));
		$dc->expects($this->once())
			->method('getConfigurationService')
			->willReturn($fakeService);

		$fakeService->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SUPPORT_LICENSE_KEY)
			->willReturn("");

		$this->expectOutputRegex('/Please purchase/');
		$sut->showLicensePurchaseInformation(null, null);
	}

	/**
	 * @test
	 */
	public function run_itDoesNotProceed_ifNoMultisite()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'initialize'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(true);

		$sut->expects($this->never())
			->method('initialize');

		$sut->run();
	}

	/**
	 * @test
	 */
	public function run_itDoesNotRegisterCore_whenNotActive()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'initialize', 'isActive', 'registerCore'));

		$sut->expects($this->once())
			->method('isActive')
			->willReturn(false);

		$sut->expects($this->never())
			->method('registerCore');

		$sut->run();
	}

	/**
	 * @test
	 */
	public function run_itRegistersAdministrationMenu_evenWhenNotActive()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'initialize', 'isActive', 'registerAdministrationMenu'));

		$sut->expects($this->once())
			->method('isActive')
			->willReturn(false);

		$sut->expects($this->once())
			->method('registerAdministrationMenu');

		$sut->run();
	}

	/**
	 * @test
	 */
	public function run_itRegisterCore_whenActive()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'initialize', 'isActive', 'registerCore',
			'registerAdministrationMenu'));

		$sut->expects($this->once())
			->method('isActive')
			->willReturn(true);

		$sut->expects($this->once())
			->method('registerCore')
			->willReturn(true);

		$sut->expects($this->once())
			->method('registerAdministrationMenu');

		$sut->run();
	}

	/**
	 * @test
	 */
	public function registerAdministrationMenu_itRegistersTheAdministrationMenu()
	{
		$sut = $this->sut(array('dc'));
		$fakeService = $this->createAnonymousMock(array('register'));
		$dc = $this->mockDependencyContainer($sut);

		$dc->expects($this->exactly(1))
			->method('getMenu')
			->willReturn($fakeService);

		$sut->registerAdministrationMenu();
	}

	/**
	 * @test
	 */
	public function isActive_itReturnsValue()
	{
		$sut = $this->sut(array('dc'));

		$fakeService = $this->createAnonymousMock(array('getOptionValue'));
		$fakeService->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::IS_ACTIVE)
			->willReturn(true);

		$dc = $this->mockDependencyContainer($sut);

		$dc->expects($this->exactly(1))
			->method('getConfiguration')
			->willReturn($fakeService);

		$this->assertTrue($sut->isActive());
	}

	/**
	 * @test
	 */
	public function registerCore_registersUrlTriggerHook()
	{
		$sut = $this->sut(array('registerUrlTriggerHook'));

		$_POST = array(Adi_Cron_UrlTrigger::TASK => Adi_Cron_UrlTrigger::SYNC_TO_AD);

		$sut->expects($this->once())
			->method('registerUrlTriggerHook');

		$this->assertFalse($sut->registerCore());
	}

	/**
	 * @test
	 */
	public function registerCore_itRegistersLoginHooks_whenUserIsOnLoginPage()
	{
		$sut = $this->sut(array('registerLoginHooks', 'isSsoEnabled', 'isOnLoginPage'));

		$sut->expects($this->once())
			->method('isOnLoginPage')
			->willReturn(true);

		$sut->expects($this->once())
			->method('registerLoginHooks');

		$this->assertFalse($sut->registerCore());
	}

	/**
	 * @test
	 */
	public function registerCore_itLogsOutTheCurrentUser_whenUserIsDisabled()
	{
		$sut = $this->sut(array('dc', 'isSsoEnabled', 'registerSharedAdministrationHooks'));

		$this->loginUser($sut, 666, true);

		WP_Mock::wpFunction('wp_logout', array(
			'times' => 1));

		$sut->expects($this->never())
			->method('registerSharedAdministrationHooks');

		$this->assertFalse($sut->registerCore());
	}

	/**
	 * @test
	 */
	public function run_itRegistersTheMigrationHook()
	{
		$sut = $this->sut(array('dc', 'isActive', 'isOnNetworkDashboard', 'initialize',
			'registerSharedAdministrationHooks', 'registerUserProfileHooks', 'registerAdministrationHooks',
			'registerAdministrationMenu', 'registerMigrationHook', 'isSsoEnabled'));
		$this->loginUser($sut, 666, false);

		$sut->expects($this->once())
			->method('isActive')
			->willReturn(true);

		$sut->expects($this->once())
			->method('registerMigrationHook');

		$sut->run();
	}

	/**
	 * @test
	 */
	public function run_itRegistersTheSharedAdministrationHooks()
	{
		$sut = $this->sut(array('dc', 'isActive', 'isOnNetworkDashboard', 'initialize',
			'registerSharedAdministrationHooks', 'registerUserProfileHooks', 'registerAdministrationHooks',
			'registerAdministrationMenu', 'registerMigrationHook', 'isSsoEnabled'));
		$this->loginUser($sut, 666, false);

		$sut->expects($this->once())
			->method('isActive')
			->willReturn(true);

		$sut->expects($this->once())
			->method('registerSharedAdministrationHooks');

		$sut->run();
	}

	/**
	 * @test
	 */
	public function run_itRegistersTheUserProfileHooks()
	{
		$sut = $this->sut(array('dc', 'isOnNetworkDashboard', 'initialize', 'registerSharedAdministrationHooks',
			'registerUserProfileHooks', 'registerAdministrationHooks', 'registerAdministrationMenu', 'isSsoEnabled'));
		$this->loginUser($sut, 666, false);

		$sut->expects($this->once())
			->method('registerUserProfileHooks');

		$this->assertTrue($sut->registerCore());
	}

	/**
	 * @test
	 */
	public function registerCore_itRegistersTheAdministrationHooks()
	{
		$sut = $this->sut(array('dc', 'isOnNetworkDashboard', 'initialize', 'registerSharedAdministrationHooks',
			'registerUserProfileHooks', 'registerAdministrationHooks', 'registerAdministrationMenu', 'isSsoEnabled'));
		$this->loginUser($sut, 666, false);

		$sut->expects($this->once())
			->method('registerAdministrationHooks');

		$this->assertTrue($sut->registerCore());
	}

	/**
	 * @test
	 */
	public function runMultisite_itReturns_whenNotViewingNetworkDashboard()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'initialize'));

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(false);

		$sut->expects($this->never())
			->method('initialize');

		$sut->runMultisite();
	}

	/**
	 * @test
	 */
	public function runMultisite_itRegistersTheSharedAdministrationHooks_whenInMultisiteEnvironment()
	{
		$sut = $this->sut(array('dc', 'isOnNetworkDashboard', 'initialize', 'registerSharedAdministrationHooks',
			'registerMigrationHook'));
		$dc = $this->mockDependencyContainer($sut);

		$this->loginUser($sut, null, null);

		$sut->method('isOnNetworkDashboard')
			->willReturn(true);

		$extendSiteList = $this->createMock('Adi_Multisite_Site_Ui_ExtendSiteList');
		$multisiteMenu = $this->createMock('Adi_Multisite_Ui_Menu');

		$dc->expects($this->once())
			->method('getExtendSiteList')
			->willReturn($extendSiteList);

		$dc->expects($this->once())
			->method('getMultisiteMenu')
			->willReturn($multisiteMenu);

		$sut->expects($this->once())
			->method('registerSharedAdministrationHooks');

		$sut->runMultisite();
	}

	/**
	 * @test
	 */
	public function runMultisite_itRegistersTheMultisiteAdministrationHooks_whenInMultisiteEnvironment()
	{
		$sut = $this->sut(array('dc', 'isOnNetworkDashboard', 'initialize', 'registerSharedAdministrationHooks',
			'registerMigrationHook'));
		$dc = $this->mockDependencyContainer($sut);

		$this->loginUser($sut, null, null);

		$sut->method('isOnNetworkDashboard')
			->willReturn(true);

		$extendSiteList = $this->createMock('Adi_Multisite_Site_Ui_ExtendSiteList');
		$multisiteMenu = $this->createMock('Adi_Multisite_Ui_Menu');

		$dc->expects($this->once())
			->method('getExtendSiteList')
			->willReturn($extendSiteList);

		$extendSiteList->expects($this->once())
			->method('register');

		$dc->expects($this->once())
			->method('getMultisiteMenu')
			->willReturn($multisiteMenu);

		$multisiteMenu->expects($this->once())
			->method('register');

		$sut->runMultisite();
	}

	/**
	 * @test
	 */
	public function isOnNetworkDashboard_itReturnsFalse_whenNotViewingTheDashboard()
	{
		$sut = $this->sut();
		$this->multisite(false, true, true);
		$this->assertFalse($sut->isOnNetworkDashboard());
	}

	/**
	 * @test
	 */
	public function isOnNetworkDashboard_itReturnsTrue_whenViewingTheDashboard()
	{
		$sut = $this->sut();
		$this->multisite(true, true, true);
		$this->assertTrue($sut->isOnNetworkDashboard());
	}

	/**
	 * Sets multisite environment
	 *
	 * @param $isMultisite
	 * @param $isSuperAdmin
	 */
	private function multisite($isMultisite, $isSuperAdmin, $isOnNetworkDashboard = false)
	{
		WP_Mock::wpFunction('is_multisite', array(
			'return' => $isMultisite,
			'times'  => 1));

		WP_Mock::wpFunction('is_super_admin', array(
			'return' => $isSuperAdmin,
			'times'  => $isMultisite ? 1 : 0));

		WP_Mock::wpFunction('is_network_admin', array(
			'return' => $isOnNetworkDashboard,
			'times'  => $isSuperAdmin && $isMultisite ? 1 : 0));
	}

	/**
	 * Log the given user in.
	 *
	 * @param $sut method 'dc' must be mocked!
	 * @param $userId
	 * @param $disabled
	 */
	private function loginUser($sut, $userId, $disabled)
	{
		$userManager = $this->createMock('Adi_User_Manager');

		$dc = $this->mockDependencyContainer($sut);

		if ($userId) {
			WP_Mock::wpFunction('wp_get_current_user', array(
				'times'  => 1,
				'return' => (object)array('ID' => $userId)));

			$dc->expects($this->once())
				->method('getUserManager')
				->willReturn($userManager);

			$userManager->expects($this->once())
				->method('isDisabled')
				->with($userId)
				->willReturn($disabled);

		}

	}

	/**
	 * Mock the dependency container and overwrites the 'dc' method in Adi_Init
	 *
	 * @param $sut
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function mockDependencyContainer($sut)
	{
		$dc = $this->createMock('Adi_Dependencies');

		$sut->expects($this->any())
			->method('dc')
			->willReturn($dc);

		return $dc;
	}

	/**
	 * @test
	 */
	public function registerLoginHooks()
	{
		$sut = $this->sut(array('initialize', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createAnonymousMock(array('register'));

		$dc->expects($this->once())
			->method('getLoginService')
			->willReturn($fakeService);

		$dc->expects($this->once())
			->method('getPasswordValidationService')
			->willReturn($fakeService);

		$fakeService->expects($this->exactly(2) /* previous service calls */)
			->method('register');

		$sut->registerLoginHooks();
	}

	/**
	 * @test
	 */
	public function registerUrlTriggerHook()
	{
		$sut = $this->sut(array('initialize', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createAnonymousMock(array('register'));

		$dc->expects($this->once())
			->method('getUrlTrigger')
			->willReturn($fakeService);

		$fakeService->expects($this->exactly(1) /* previous service calls */)
			->method('register');

		$sut->registerUrlTriggerHook();
	}

	/**
	 * @test
	 */
	public function registerUserProfileHooks()
	{
		$sut = $this->sut(array('initialize', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createAnonymousMock(array('register'));

		$dc->expects($this->once())
			->method('getShowLdapAttributes')
			->willReturn($fakeService);

		$dc->expects($this->once())
			->method('getPreventEmailChange')
			->willReturn($fakeService);

		$dc->expects($this->once())
			->method('getProfilePreventPasswordChange')
			->willReturn($fakeService);

		$dc->expects($this->once())
			->method('getTriggerActiveDirectorySynchronization')
			->willReturn($fakeService);

		$dc->expects($this->once())
			->method('getProvideDisableUserOption')
			->willReturn($fakeService);

		$fakeService->expects($this->exactly(5) /* previous service calls */)
			->method('register');

		$sut->registerUserProfileHooks();
	}

	/**
	 * @test
	 */
	public function registerAdministrationHooks()
	{
		$sut = $this->sut(array('initialize', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createAnonymousMock(array('register', 'registerAjaxListener',
			'registerBlogAndNetworkMenu'));

		$dc->expects($this->once())
			->method('getExtendUserList')
			->willReturn($fakeService);

		$fakeService->expects($this->exactly(1) /* getExtendUserList */)
			->method('register');

		$sut->registerAdministrationHooks();
	}

	/**
	 * @test
	 */
	public function isOnLoginPage_itReturnsTrue_whenOnLoginPage()
	{
		$sut = $this->sut();

		$_SERVER['PHP_SELF'] = 'https://localhost/wp-login.php';

		$this->assertTrue($sut->isOnLoginPage());
	}

	/**
	 * @test
	 */
	public function isOnLoginPage_itReturnsFalse_whenAnywhereElse()
	{
		$sut = $this->sut();

		$_SERVER['PHP_SELF'] = 'https://localhost/wp-admin.php';

		$this->assertFalse($sut->isOnLoginPage());
	}

	/**
	 * @param null $methods
	 *
	 * @return Adi_Init|PHPUnit_Framework_MockObject_MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Init')
			->setConstructorArgs(
				array()
			)
			->setMethods($methods)
			->getMock();
	}
}