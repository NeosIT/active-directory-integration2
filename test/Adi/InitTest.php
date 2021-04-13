<?php

/**
 * @author Christopher Klein <ckl@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_InitTest extends Ut_BasicTest
{
	public function setUp() : void
	{
		parent::setUp();
	}

	public function tearDown() : void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function initialize_loadsLanguageFile()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createInitializeEnvironment($dc);
        $pluginFolderName = basename(NEXT_AD_INT_PATH);

		$fakeService->expects($this->exactly(2))
			->method('getOptionValue');

        WP_Mock::wpFunction('plugin_basename', array(
            'args' => array(NEXT_AD_INT_PATH),
            'times' => 1,
            'return' => $pluginFolderName
        ));

		WP_Mock::wpFunction('load_plugin_textdomain', array(
			'args'  => array(
				'next-active-directory-integration',
				false,
                $pluginFolderName . '/languages',
			),
			'times' => 1));

		$sut->initialize();
	}

	private function createActivationEnvironment($dc)
	{
		$fakeService = $this->createAnonymousMock(array('check', 'register', 'insertDefaultProfile', 'autoImport',
			'migratePreviousVersion', 'persistSanitizedValue', 'getOptionValue'));
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

		$dc->expects($this->any())
			->method('getConfiguration')
			->willReturn($fakeService);

		return $fakeService;
	}

	private function createInitializeEnvironment($dc)
	{
		$fakeService = $this->createAnonymousMock(array('getOptionValue'));

		$dc->expects($this->any())
			->method('getConfiguration')
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

        \WP_Mock::wpFunction( 'set_transient', array(
            'times' => 1,
            'args' => array( NextADInt_Adi_Init::NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED, true, 10)
        ));

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
			->with(666, NextADInt_Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION, 'username');

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
			->with(0, NextADInt_Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION, 'username');

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
			'args'   => NEXT_AD_INT_PLUGIN_FILE,
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
	 * @test
	 */
	public function run_itDoesNotProceed_ifNoMultisite()
	{
		$sut = $this->sut(array('registerHooks', 'isOnNetworkDashboard', 'initialize'));

		$sut->expects($this->once())
            ->method('registerHooks');

		$sut->expects($this->once())
			->method('isOnNetworkDashboard')
			->willReturn(true);

		$sut->expects($this->never())
			->method('initialize');

		$sut->run();
	}

	/**
	 * @test
	 */ //TODO Revisit
	public function run_itDoesNotRegisterCore_whenNotActive()
	{
		$sut = $this->sut(array(
			'isOnNetworkDashboard',
			'initialize',
			'isActive',
			'registerCore',
			'registerAdministrationMenu',
			'finishRegistration'
		));
		$this->mockFunction__();

		$this->mockWordpressFunction('get_current_blog_id');
		$this->mockWordpressFunction('is_multisite');
		$this->mockWordpressFunction('get_option');
		$this->mockWordpressFunction('get_site_option');

		$sut->expects($this->once())
			->method('isActive')
			->willReturn(false);

		$sut->expects($this->once())
			->method('initialize');

		$sut->expects($this->never())
			->method('registerCore');

		$sut->expects($this->once())
			->method('registerAdministrationMenu');

		$sut->expects($this->once())
			->method('finishRegistration');

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
			'registerAdministrationMenu', 'finishRegistration'));

		$sut->expects($this->once())
			->method('isActive')
			->willReturn(true);

		$sut->expects($this->once())
			->method('registerCore')
			->willReturn(true);

		$sut->expects($this->once())
			->method('registerAdministrationMenu')
			->willReturn(true);

		$sut->expects($this->once())
            ->method('finishRegistration');

		$sut->run();
	}

    /**
     * @test
     */
    public function registerHooks_addsAllActions() {
        $sut = $this->sut(null);

        \WP_Mock::expectActionAdded(NEXT_AD_INT_PREFIX . 'register_form_login_services', array($sut, 'registerFormLoginServices'), 10, 0);

        $sut->registerHooks();
        \WP_Mock::assertHooksAdded();
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
			->with(NextADInt_Adi_Configuration_Options::IS_ACTIVE)
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

		$_POST = array(NextADInt_Adi_Cron_UrlTrigger::TASK => NextADInt_Adi_Cron_UrlTrigger::SYNC_TO_AD);

		$sut->expects($this->once())
			->method('registerUrlTriggerHook');

		$this->assertFalse($sut->registerCore());
	}

	/**
	 * @test
	 */
	public function run_itRegistersHooks()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'initialize', 'registerMigrationHook', 'isActive',
            'registerCore', 'registerAdministrationMenu', 'finishRegistration'));

        $sut->expects($this->once())->method('isOnNetworkDashboard')->willReturn(false);
        $sut->expects($this->once())->method('isActive')->willReturn(true);
        $sut->expects($this->once())->method('registerCore')->willReturn(true);

        $sut->expects($this->once())->method('registerMigrationHook');
        $sut->expects($this->once())->method('registerAdministrationMenu');
        $sut->expects($this->once())->method('finishRegistration');

		$sut->run();
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
			'registerMigrationHook', 'finishRegistration'));
		$dc = $this->mockDependencyContainer($sut);

		$this->loginUser($sut, null, null);

		$sut->method('isOnNetworkDashboard')
			->willReturn(true);

		$extendSiteList = $this->createMock('NextADInt_Adi_Multisite_Site_Ui_ExtendSiteList');
		$multisiteMenu = $this->createMock('NextADInt_Adi_Multisite_Ui_Menu');

		$dc->expects($this->once())
			->method('getExtendSiteList')
			->willReturn($extendSiteList);

		$dc->expects($this->once())
			->method('getMultisiteMenu')
			->willReturn($multisiteMenu);

		$sut->expects($this->once())
			->method('registerSharedAdministrationHooks');

		$sut->expects($this->once())
            ->method('finishRegistration');

		$sut->runMultisite();
	}

	/**
	 * @test
	 */
	public function runMultisite_itRegistersTheMultisiteAdministrationHooks_whenInMultisiteEnvironment()
	{
		$sut = $this->sut(array('dc', 'isOnNetworkDashboard', 'initialize', 'registerSharedAdministrationHooks',
			'registerMigrationHook', 'finishRegistration'));
		$dc = $this->mockDependencyContainer($sut);

		$this->loginUser($sut, null, null);

		$sut->method('isOnNetworkDashboard')
			->willReturn(true);

		$extendSiteList = $this->createMock('NextADInt_Adi_Multisite_Site_Ui_ExtendSiteList');
		$multisiteMenu = $this->createMock('NextADInt_Adi_Multisite_Ui_Menu');

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

        $sut->expects($this->once())
            ->method('finishRegistration');

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
		$userManager = $this->createMock('NextADInt_Adi_User_Manager');

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
	 * Mock the dependency container and overwrites the 'dc' method in NextADInt_Adi_Init
	 *
	 * @param $sut
	 *
	 * @return PHPUnit_Framework_MockObject_MockObject
	 */
	private function mockDependencyContainer($sut)
	{
		$dc = $this->createMock('NextADInt_Adi_Dependencies');

		$sut->expects($this->any())
			->method('dc')
			->willReturn($dc);

		return $dc;
	}

    /**
     * @test
     */
	public function finishRegistration() {
	    WP_Mock::expectAction('next_ad_int_loaded');

	    $sut = $this->sut();
	    $sut->finishRegistration();
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
	 * @test
	 * @issue ADI-410
	 */
	public function ADI_410_isOnLoginPage_itReturnsTrue_whenFilterOverwritesValue() {
		$sut = $this->sut();

		$_SERVER['PHP_SELF'] = 'https://localhost/wp-admin.php';

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'auth_enable_login_check')
			->with(false)
			->reply(true);

		$this->assertTrue($sut->isOnLoginPage());
	}

	/**
	 * @param null $methods
	 *
	 * @return NextADInt_Adi_Init|PHPUnit_Framework_MockObject_MockObject
	 */
	private function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Init')
			->setConstructorArgs(
				array()
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 * @issue ADI-665
	 */
    public function ADI_665_isOnTestAuthenticationPage_returnsTrue()
    {
    	$sut = $this->sut();

	    $_GET['page'] = 'next_ad_int_test_connection';

	    $actual = $sut->isOnTestAuthenticationPage();

    	$this->assertTrue($actual);
    }

	/**
	 * @test
	 * @issue ADI-665
	 */
	public function ADI_665_isOnTestAuthenticationPage_returnsFalse()
	{
		$sut = $this->sut();

		$_GET['page'] = 'next_ad_int_sync_to_wordpress';

		$actual = $sut->isOnTestAuthenticationPage();

		$this->assertFalse($actual);
	}

    /**
     * @test
     */
	public function registerCore_willCall_registerAuthentication()
    {
        $sut = $this->sut(array('registerAuthentication'));

        $sut->expects($this->once())->method('registerAuthentication');

        $sut->registerCore();
    }

	/**
	 * @test
	 */
	public function registerAuthentication_onTestAuthenticationPage_willRegisterHooks_returnsTrue()
	{
		$sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'dc', 'isOnTestAuthenticationPage'));
		$dc = $this->mockDependencyContainer($sut);
		$authService = $this->createAnonymousMock(array('register'));

		$sut->expects($this->once())->method('isOnLoginPage')->willReturn(false);
		$sut->expects($this->once())->method('isSsoenabled')->willreturn(false);
		$sut->expects($this->once())->method('isOnTestAuthenticationPage')->willreturn(true);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->once())->method('getAuthorizationService')->willReturn($authService);

		// check method calls on mocked services
		$authService->expects($this->once())->method('register');

		// invoke method call
		$actual = $sut->registerAuthentication();

		// assertions
		$this->assertTrue($actual);
	}

    /**
     * @test
     */
    public function registerAuthentication_onLoginPage_SsoDisabled_willRegisterHooks_returnsFalse()
    {
        $sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'dc'));
        $dc = $this->mockDependencyContainer($sut);
        $authService = $this->createAnonymousMock(array('register'));
	    $loginSucceededService = $this->createAnonymousMock(array('register'));

        $sut->expects($this->once())->method('isOnLoginPage')->willReturn(true);
        $sut->expects($this->once())->method('isSsoEnabled')->willReturn(false);

        // mock dependency container calls and return individual mocked services
        $dc->expects($this->once())->method('getAuthorizationService')->willReturn($authService);
        $dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);
        $dc->expects($this->never())->method('getSsoService');

        \WP_Mock::expectAction(NEXT_AD_INT_PREFIX . 'register_form_login_services');

        // check method calls on mocked services
        $authService->expects($this->once())->method('register');
	    $loginSucceededService->expects($this->once())->method('register');

        // invoke method call
        $actual = $sut->registerAuthentication();

        // assertions
        $this->assertFalse($actual);
    }

    /**
     * @test
     */
    public function registerAuthentication_notOnLoginPage_SsoDisabled_willRegisterHooks_returnsTrue()
    {
        $sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'dc'));
        $dc = $this->mockDependencyContainer($sut);
        $authService = $this->createAnonymousMock(array('register'));
	    $loginSucceededService = $this->createAnonymousMock(array('register'));

	    $sut->expects($this->once())->method('isOnLoginPage')->willReturn(false);
        $sut->expects($this->once())->method('isSsoEnabled')->willReturn(false);

        // mock dependency container calls and return individual mocked services
        $dc->expects($this->once())->method('getAuthorizationService')->willReturn($authService);
	    $dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);

	    // check method calls on mocked services
        $authService->expects($this->once())->method('register');
	    $loginSucceededService->expects($this->once())->method('register');

	    // invoke method call
        $actual = $sut->registerAuthentication();

        // assertions
        $this->assertTrue($actual);
    }
    /**
     * @see NADIS-92, ADI-679
     * @since 2.1.9
     * @test
     */
    public function registerAuthentication_disableSsoForXmlRpc_notRegistersSsoService()
    {
        $sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'isOnXmlRpcPage', 'isSsoDisabledForXmlRpc', 'dc'));
        $dc = $this->mockDependencyContainer($sut);
        $authService = $this->createAnonymousMock(array('register'));
        $ssoService = $this->createAnonymousMock(array('register'));
        $loginSucceededService = $this->createAnonymousMock(array('register'));
        $configurationService  = $this->createAnonymousMock(array('getOptionValue'));

        // mock dependency container calls and return individual mocked services
        $dc->expects($this->once())->method('getAuthorizationService')->willReturn($authService);
        $dc->expects($this->never())->method('getSsoService');
        $dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);

        $sut->expects($this->once())->method('isOnLoginPage')->willReturn(false);
        $sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);
        $sut->expects($this->once())->method('isOnXmlRpcPage')->willReturn(true);
        $sut->expects($this->once())->method('isSsoDisabledForXmlRpc')->willReturn(true);

        $ssoService->expects($this->never())->method('register');

        // invoke method call
        $actual = $sut->registerAuthentication();
    }

    /**
     * @test
     */
    public function registerAuthentication_notOnLoginPage_SsoEnabled_willRegisterHooks_returnsTrue()
    {
        $sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'isOnXmlRpcPage', 'isSsoDisabledForXmlRpc', 'dc'));
        $dc = $this->mockDependencyContainer($sut);
        $authService = $this->createAnonymousMock(array('register'));
        $ssoService = $this->createAnonymousMock(array('register', 'registerAuthenticationHooks'));
	    $loginSucceededService = $this->createAnonymousMock(array('register'));
	    $configurationService  = $this->createAnonymousMock(array('getOptionValue'));

        $sut->expects($this->once())->method('isOnLoginPage')->willReturn(false);
        $sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);
        $sut->expects($this->once())->method('isOnXmlRpcPage')->willReturn(false);
        $sut->expects($this->once())->method('isSsoDisabledForXmlRpc')->willReturn(false);

        // mock dependency container calls and return individual mocked services
        $dc->expects($this->once())->method('getAuthorizationService')->willReturn($authService);
        $dc->expects($this->once())->method('getSsoService')->willReturn($ssoService);
	    $dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);
	    $dc->expects($this->once())->method('getConfiguration')->willReturn($configurationService);

	    // check method calls on mocked services
        $authService->expects($this->once())->method('register');
	    $loginSucceededService->expects($this->once())->method('register');
	    $configurationService->expects($this->once())
	                         ->method('getOptionValue')
	                         ->with(NextADInt_Adi_Configuration_Options::CUSTOM_LOGIN_PAGE_ENABLED)
	                         ->willReturn(false);
	    $ssoService->expects($this->once())->method('register');

        // invoke method call
        $actual = $sut->registerAuthentication();

        // assertions
        $this->assertTrue($actual);
    }

    /**
     * @test
     */
    public function registerAuthentication_onLoginPage_SsoEnabled_willRegisterHooks_returnsFalse()
    {
        $sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'isOnXmlRpcPage', 'isSsoDisabledForXmlRpc', 'dc'));
        $dc = $this->mockDependencyContainer($sut);
        $authService = $this->createAnonymousMock(array('register'));
        $ssoService = $this->createAnonymousMock(array('register', 'registerAuthenticationHooks'));
	    $loginSucceededService = $this->createAnonymousMock(array('register'));
	    $configurationService  = $this->createAnonymousMock(array('getOptionValue'));

        $sut->expects($this->once())->method('isOnLoginPage')->willReturn(true);
        $sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);
        $sut->expects($this->once())->method('isOnXmlRpcPage')->willReturn(false);
        $sut->expects($this->once())->method('isSsoDisabledForXmlRpc')->willReturn(false);

        // mock dependency container calls and return individual mocked services
        $dc->expects($this->once())->method('getAuthorizationService')->willReturn($authService);
        $dc->expects($this->once())->method('getSsoService')->willReturn($ssoService);
	    $dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);
	    $dc->expects($this->once())->method('getConfiguration')->willReturn($configurationService);

        $sut->expects($this->once())->method('isOnLoginPage')->willReturn(true);
        $sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);

	    // check method calls on mocked services
        $authService->expects($this->once())->method('register');
	    $loginSucceededService->expects($this->once())->method('register');
	    $configurationService->expects($this->once())
	                         ->method('getOptionValue')
	                         ->with(NextADInt_Adi_Configuration_Options::CUSTOM_LOGIN_PAGE_ENABLED)
	                         ->willReturn(false);
	    $ssoService->expects($this->once())->method('register');

        // invoke method call
        $actual = $sut->registerAuthentication();

        // assertions
        $this->assertFalse($actual);
    }

    /**
     * @test
     */
    public function registerFormLoginServices_willRegisterHooks()
    {
        $sut = $this->sut(array('isSsoEnabled', 'dc'));
        $dc = $this->mockDependencyContainer($sut);

        $pwValidationService = $this->createAnonymousMock(array('register'));
        $loginService = $this->createAnonymousMock(array('register', 'registerAuthenticationHooks'));
        $ssoPage = $this->createAnonymousMock(array('register'));

        $sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);

        $dc->expects($this->once())->method('getPasswordValidationService')->willReturn($pwValidationService);
        $dc->expects($this->once())->method('getLoginService')->willReturn($loginService);
        $dc->expects($this->once())->method('getSsoPage')->willReturn($ssoPage);

        $loginService->expects($this->once())->method('register');
        $pwValidationService->expects($this->once())->method('register');
        $ssoPage->expects($this->once())->method('register');

        $sut->registerFormLoginServices();
    }

    /**
     * @test
     */
    public function registerCore_willRegisterHooks()
    {
        $sut = $this->sut(array('registerAuthentication', 'dc',
            'registerSharedAdministrationHooks', 'registerUserProfileHooks', 'registerAdministrationHooks', 'registerSynchronizationHooks'));

        $sut->expects($this->once())->method('registerAuthentication')->willReturn(true);

        WP_Mock::wpFunction('wp_get_current_user', array(
            'times' => 1,
            'return' => (object)array('ID' => 555)));

        $sut->expects($this->once())->method('registerSharedAdministrationHooks');
        $sut->expects($this->once())->method('registerUserProfileHooks');
        $sut->expects($this->once())->method('registerAdministrationHooks');
		$sut->expects($this->once())->method('registerSynchronizationHooks');
		
        $actual = $sut->registerCore();

        $this->assertTrue($actual);
    }

    /**
     * @test
     */
    public function registerCore_willReturnFalse_currentUserHasNoId()
    {
        $sut = $this->sut(array('registerAuthentication', 'dc'));
        $dc = $this->mockDependencyContainer($sut);

        $sut->expects($this->once())->method('registerAuthentication')->willReturn(true);

        WP_Mock::wpFunction('wp_get_current_user', array(
            'times' => 1,
            'return' => (object)array('ID'=>0)));  // Attribute ID will show 0 if there is no user.

        $dc->expects($this->never())->method('getUserManager');

        $actual = $sut->registerCore();

        $this->assertFalse($actual);
    }
}