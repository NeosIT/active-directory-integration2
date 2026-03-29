<?php

namespace Dreitier\Nadi;

use Dreitier\Nadi\Authentication\LoginService;
use Dreitier\Nadi\Authentication\PasswordValidationService;
use Dreitier\Nadi\Authentication\SingleSignOn\Ui\ShowSingleSignOnLink;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Cron\UrlTrigger;
use Dreitier\Nadi\Multisite\Site\Ui\ExtendSiteList;
use Dreitier\Nadi\Multisite\Ui\MultisiteMenu;
use Dreitier\Nadi\Ui\Menu\Menu;
use Dreitier\Nadi\User\LoginSucceededService;
use Dreitier\Nadi\User\Manager;
use Dreitier\Nadi\User\Profile\Ui\PreventEmailChange;
use Dreitier\Nadi\User\Profile\Ui\PreventPasswordChange;
use Dreitier\Nadi\User\Profile\Ui\ProvideDisableUserOption;
use Dreitier\Nadi\User\Profile\Ui\ShowLdapAttributes;
use Dreitier\Nadi\User\Profile\Ui\TriggerActiveDirectorySynchronization;
use Dreitier\Nadi\User\Ui\ExtendUserList;
use Dreitier\Test\BasicTestCase;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access private
 */
class InitTest extends BasicTestCase
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @issue #204
	 */
	#[Test]
	public function initialize_loadsLanguageFile()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$fakeService = $this->createInitializeEnvironment($dc);
		$pluginFolderName = basename(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH);

		\WP_Mock::userFunction('plugin_basename', array(
			'args' => array(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH),
			'times' => 1,
			'return' => $pluginFolderName
		));

		\WP_Mock::userFunction('load_plugin_textdomain', array(
			'args' => array(
				'next-active-directory-integration',
				false,
				$pluginFolderName . '/languages',
			),
			'times' => 1));

		$sut->_init();
	}

	/**
	 * @param $dc
	 * @return Requirements
	 * @throws \PHPUnit\Framework\MockObject\Exception
	 */
	private function createActivationEnvironment($dc): Requirements
	{
		$r = $this->createMock(Requirements::class);
		$dc->expects($this->once())
			->method('getRequirements')
			->willReturn($r);

		$dc->method('getProfileConfigurationRepository')
			->willReturn($this->createMock(ProfileConfigurationRepository::class));

		$dc->method('getBlogConfigurationRepository')
			->willReturn($this->createMock(BlogConfigurationRepository::class));

		$dc->expects($this->any())
			->method('getUserManager')
			->willReturn($this->createMock(Manager::class));

		$dc->expects($this->any())
			->method('getProfileRepository')
			->willReturn($this->createMock(ProfileRepository::class));

		$dc->expects($this->any())
			->method('getMultisiteConfigurationService')
			->willReturn($this->createMock(Service::class));

		return $r;
	}

	private function createInitializeEnvironment($dc): Service
	{
		$multisiteConfigurationService = $this->createMock(Service::class);

		$dc->expects($this->any())
			->method('getMultisiteConfigurationService')
			->willReturn($multisiteConfigurationService);

		return $multisiteConfigurationService;
	}

	#[Test]
	public function activation_itNeverInsertsDefaultProfile_whenCheckFailed()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$requirements = $this->createActivationEnvironment($dc);

		\WP_Mock::userFunction('set_transient', array(
			'times' => 1,
			'args' => array(Init::NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_HAS_BEEN_ENABLED, true, 10)
		));

		$this->behave($requirements, 'check', false);

		$dc->getProfileRepository()->expects($this->never())
			->method('insertDefaultProfile');

		$sut->activation();
	}

	#[Test]
	public function activation_itInsertsDefaultProfile()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$requirements = $this->createActivationEnvironment($dc);

		\WP_Mock::userFunction('set_transient', array(
			'times' => 1,
			'args' => array(Init::NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_HAS_BEEN_ENABLED, true, 10)
		));

		$this->behave($requirements, 'check', true);

		$dc->getProfileRepository()->expects($this->once())
			->method('insertDefaultProfile');

		$sut->activation();
	}

	#[Test]
	public function activation_itMigratesAdi1xUsers()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$requirements = $this->createActivationEnvironment($dc);

		\WP_Mock::userFunction('set_transient', array(
			'times' => 1,
			'args' => array(Init::NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_HAS_BEEN_ENABLED, true, 10)
		));

		$requirements->expects($this->once())
			->method('check')
			->with(true, true)
			->willReturn(true);

		$dc->getUserManager()->expects($this->once())
			->method('migratePreviousVersion');

		$sut->activation();
	}

	#[Test]
	public function activation_itExcludesCurrentUserInNetwork_whenDefaultProfileHasBeenAdded()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$requirements = $this->createActivationEnvironment($dc);

		\WP_Mock::userFunction('wp_get_current_user', array(
			'times' => 1,
			'return' => (object)array('user_login' => 'username')));

		\WP_Mock::userFunction('is_multisite', array(
			'times' => 1,
			'return' => true));

		\WP_Mock::userFunction('set_transient', array(
			'times' => 1,
			'args' => array(Init::NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_HAS_BEEN_ENABLED, true, 10)
		));

		$this->behave($requirements, 'check', true);
		$this->behave($dc->getProfileRepository(), 'insertDefaultProfile', 666);

		$dc->getProfileConfigurationRepository()->expects($this->once())
			->method('persistSanitizedValue')
			->with(666, Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION, 'username');

		$sut->activation();
	}

	#[Test]
	public function activation_itExcludesCurrentUserInSingleSite_whenDefaultProfileHasBeenAdded()
	{
		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);
		$requirements = $this->createActivationEnvironment($dc);

		\WP_Mock::userFunction('wp_get_current_user', array(
			'times' => 1,
			'return' => (object)array('user_login' => 'username')));

		\WP_Mock::userFunction('is_multisite', array(
			'times' => 1,
			'return' => false));

		\WP_Mock::userFunction('set_transient', array(
			'times' => 1,
			'args' => array(Init::NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_HAS_BEEN_ENABLED, true, 10)
		));

		$this->behave($requirements, 'check', true);
		$this->behave($dc->getProfileRepository(), 'insertDefaultProfile', 666);

		$dc->getBlogConfigurationRepository()->expects($this->once())
			->method('persistSanitizedValue')
			->with(0, Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION, 'username');

		$sut->activation();
	}

	#[Test]
	public function postActivation_itRegistersPostActivationOfOptionsImporter()
	{
		global $pagenow;
		$pagenow = 'plugins.php';
		$_REQUEST['activate'] = 'true';

		\WP_Mock::userFunction('is_plugin_active', array(
			'args' => NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_FILE,
			'times' => 1,
			'return' => true));

		$sut = $this->sut(array('dc'));
		$dc = $this->mockDependencyContainer($sut);

		$sut->postActivation();
	}

	#[Test]
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

	#[Test] //TODO Revisit
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

	#[Test]
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

	#[Test]
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

	#[Test]
	public function registerHooks_addsAllActions()
	{
		$sut = $this->sut();

		\WP_Mock::expectActionAdded(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'register_form_login_services', array($sut, 'registerFormLoginServices'), 10, 0);

		$sut->registerHooks();
		\WP_Mock::assertHooksAdded();
	}

	#[Test]
	public function registerAdministrationMenu_itRegistersTheAdministrationMenu()
	{
		$sut = $this->sut(array('dc'));
		$menu = $this->createMock(Menu::class);
		$dc = $this->mockDependencyContainer($sut);

		$dc->expects($this->exactly(1))
			->method('getMenu')
			->willReturn($menu);

		$sut->registerAdministrationMenu();
	}

	#[Test]
	public function isActive_itReturnsValue()
	{
		$sut = $this->sut(array('dc'));

		$multisiteConfigurationService = $this->createMock(Service::class);
		$multisiteConfigurationService->expects($this->once())
			->method('getOptionValue')
			->with(Options::IS_ACTIVE)
			->willReturn(true);

		$dc = $this->mockDependencyContainer($sut);

		$dc->expects($this->exactly(1))
			->method('getMultisiteConfigurationService')
			->willReturn($multisiteConfigurationService);

		$this->assertTrue($sut->isActive());
	}

	#[Test]
	public function registerCore_registersUrlTriggerHook()
	{
		$sut = $this->sut(array('registerUrlTriggerHook'));

		$_POST = array(UrlTrigger::TASK => UrlTrigger::SYNC_TO_AD);

		$sut->expects($this->once())
			->method('registerUrlTriggerHook');

		$this->assertFalse($sut->registerCore());
	}

	#[Test]
	public function run_itRegistersHooks()
	{
		$sut = $this->sut(array('isOnNetworkDashboard', 'initialize', 'isActive',
			'registerCore', 'registerAdministrationMenu', 'finishRegistration'));

		$sut->expects($this->once())->method('isOnNetworkDashboard')->willReturn(false);
		$sut->expects($this->once())->method('isActive')->willReturn(true);
		$sut->expects($this->once())->method('registerCore')->willReturn(true);

		$sut->expects($this->once())->method('registerAdministrationMenu');
		$sut->expects($this->once())->method('finishRegistration');

		$sut->run();
	}

	#[Test]
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

	#[Test]
	public function runMultisite_itRegistersTheSharedAdministrationHooks_whenInMultisiteEnvironment()
	{
		$sut = $this->sut(array('dc', 'isOnNetworkDashboard', 'initialize', 'registerSharedAdministrationHooks',
			'finishRegistration'));
		$dc = $this->mockDependencyContainer($sut);

		$this->loginUser($sut, null, null);

		$sut->method('isOnNetworkDashboard')
			->willReturn(true);

		$extendSiteList = $this->createMock(ExtendSiteList::class);
		$multisiteMenu = $this->createMock(MultisiteMenu::class);

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

	#[Test]
	public function runMultisite_itRegistersTheMultisiteAdministrationHooks_whenInMultisiteEnvironment()
	{
		$sut = $this->sut(array('dc', 'isOnNetworkDashboard', 'initialize', 'registerSharedAdministrationHooks',
			'finishRegistration'));
		$dc = $this->mockDependencyContainer($sut);

		$this->loginUser($sut, null, null);

		$sut->method('isOnNetworkDashboard')
			->willReturn(true);

		$extendSiteList = $this->createMock(ExtendSiteList::class);
		$multisiteMenu = $this->createMock(MultisiteMenu::class);

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

	#[Test]
	public function isOnNetworkDashboard_itReturnsFalse_whenNotViewingTheDashboard()
	{
		$sut = $this->sut();
		$this->multisite(false, true, true);
		$this->assertFalse($sut->isOnNetworkDashboard());
	}

	#[Test]
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
		\WP_Mock::userFunction('is_multisite', array(
			'return' => $isMultisite,
			'times' => 1));

		\WP_Mock::userFunction('is_super_admin', array(
			'return' => $isSuperAdmin,
			'times' => $isMultisite ? 1 : 0));

		\WP_Mock::userFunction('is_network_admin', array(
			'return' => $isOnNetworkDashboard,
			'times' => $isSuperAdmin && $isMultisite ? 1 : 0));
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
		$userManager = $this->createMock(Manager::class);

		$dc = $this->mockDependencyContainer($sut);

		if ($userId) {
			\WP_Mock::userFunction('wp_get_current_user', array(
				'times' => 1,
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
	 * Mock the dependency container and overwrites the 'dc' method in Init
	 *
	 * @param $sut
	 *
	 * @return MockObject
	 */
	private function mockDependencyContainer($sut): Dependencies
	{
		$dc = $this->createMock(Dependencies::class);

		$sut->expects($this->any())
			->method('dc')
			->willReturn($dc);

		return $dc;
	}

	#[Test]
	public function finishRegistration()
	{
		\WP_Mock::expectAction('next_ad_int_loaded');

		$sut = $this->sut();
		$sut->finishRegistration();
	}

	#[Test]
	public function registerUrlTriggerHook()
	{
		$sut = $this->sut(array('initialize', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$urlTrigger = $this->createMock(UrlTrigger::class);

		$dc->expects($this->once())
			->method('getUrlTrigger')
			->willReturn($urlTrigger);

		$urlTrigger->expects($this->exactly(1) /* previous service calls */)
			->method('register');

		$sut->registerUrlTriggerHook();
	}

	#[Test]
	public function registerUserProfileHooks()
	{
		$sut = $this->sut(array('initialize', 'dc'));
		$dc = $this->mockDependencyContainer($sut);

		$showLdapAttributes = $this->createMock(ShowLdapAttributes::class);
		$showLdapAttributes->expects($this->once())
			->method('register');

		$dc->expects($this->once())
			->method('getShowLdapAttributes')
			->willReturn($showLdapAttributes);

		$preventEmailChange = $this->createMock(PreventEmailChange::class);
		$preventEmailChange->expects($this->once())
			->method('register');

		$dc->expects($this->once())
			->method('getPreventEmailChange')
			->willReturn($preventEmailChange);

		$profilePreventPasswordChange = $this->createMock(PreventPasswordChange::class);
		$profilePreventPasswordChange->expects($this->once())
			->method('register');

		$dc->expects($this->once())
			->method('getProfilePreventPasswordChange')
			->willReturn($profilePreventPasswordChange);

		$triggerActiveDirectorySynchronization = $this->createMock(TriggerActiveDirectorySynchronization::class);
		$triggerActiveDirectorySynchronization->expects($this->once())
			->method('register');

		$dc->expects($this->once())
			->method('getTriggerActiveDirectorySynchronization')
			->willReturn($triggerActiveDirectorySynchronization);

		$provideDisableUserOption = $this->createMock(ProvideDisableUserOption::class);
		$provideDisableUserOption->expects($this->once())
			->method('register');

		$dc->expects($this->once())
			->method('getProvideDisableUserOption')
			->willReturn($provideDisableUserOption);

		$sut->registerUserProfileHooks();
	}

	#[Test]
	public function registerAdministrationHooks()
	{
		$sut = $this->sut(array('initialize', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$extendUserList = $this->createMock(ExtendUserList::class);

		$dc->expects($this->once())
			->method('getExtendUserList')
			->willReturn($extendUserList);

		$extendUserList->expects($this->exactly(1) /* getExtendUserList */)
			->method('register');

		$sut->registerAdministrationHooks();
	}

	#[Test]
	public function isOnLoginPage_itReturnsTrue_whenOnLoginPage()
	{
		$sut = $this->sut();

		$_SERVER['PHP_SELF'] = 'https://localhost/wp-login.php';

		$this->assertTrue($sut->isOnLoginPage());
	}

	#[Test]
	public function isOnLoginPage_itReturnsFalse_whenAnywhereElse()
	{
		$sut = $this->sut();

		$_SERVER['PHP_SELF'] = 'https://localhost/wp-admin.php';

		$this->assertFalse($sut->isOnLoginPage());
	}

	/**
	 * @issue ADI-410
	 */
	#[Test]
	public function ADI_410_isOnLoginPage_itReturnsTrue_whenFilterOverwritesValue()
	{
		$sut = $this->sut();

		$_SERVER['PHP_SELF'] = 'https://localhost/wp-admin.php';

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_enable_login_check')
			->with(false)
			->reply(true);

		$this->assertTrue($sut->isOnLoginPage());
	}

	/**
	 * @issue #154
	 */
	#[Test]
	public function GH_154_isNotOnCustomLoginPage_whenDefinedInUIButUriDoesNotMatch()
	{
		$sut = $this->sut(array('dc', 'isOnXmlRpcPage'));
		$dc = $this->mockDependencyContainer($sut);
		$configurationService = $this->createMock(Service::class);

		$sut->expects($this->once())->method('isOnXmlRpcPage')->willReturn(false);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->atLeast(2))->method('getMultisiteConfigurationService')->willReturn($configurationService);

		$definedCustomLoginPage = '/my-custom-login-page';

		$configurationService->method('getOptionValue')
			->with(...self::withConsecutive(
				[Options::CUSTOM_LOGIN_PAGE_ENABLED],
				[Options::CUSTOM_LOGIN_PAGE_URI])
			)
			->willReturnOnConsecutiveCalls(
			// enable custom login page
				true,
				// overwrite default value for this setting
				$definedCustomLoginPage
			);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_enable_login_check')
			->with(false)
			->reply(false);

		$_SERVER["REQUEST_URI"] = 'https://localhost/unknown-login-page';
		$this->assertFalse($sut->isOnLoginPage());
	}

	/**
	 * @issue #154
	 */
	#[Test]
	public function GH_154_isOnCustomLoginPage_whenDefinedInUI()
	{
		$sut = $this->sut(array('dc', 'isOnXmlRpcPage'));
		$dc = $this->mockDependencyContainer($sut);
		$configurationService = $this->createMock(Service::class);

		$sut->expects($this->once())->method('isOnXmlRpcPage')->willReturn(false);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->atLeast(2))->method('getMultisiteConfigurationService')->willReturn($configurationService);

		$definedCustomLoginPage = '/my-custom-login-page';

		$configurationService->method('getOptionValue')
			->with(...self::withConsecutive(
				[Options::CUSTOM_LOGIN_PAGE_ENABLED],
				[Options::CUSTOM_LOGIN_PAGE_URI])
			)
			->willReturnOnConsecutiveCalls(
			// enable custom login page
				true,
				// overwrite default value for this setting
				$definedCustomLoginPage
			);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_enable_login_check')
			->with(true)
			->reply(true);

		$_SERVER["REQUEST_URI"] = 'https://localhost' . $definedCustomLoginPage;
		$this->assertTrue($sut->isOnLoginPage());
	}

	/**
	 * @param null $methods
	 *
	 * @return Init|MockObject
	 */
	private function sut(array $methods = [])
	{
		return $this->getMockBuilder(Init::class)
			->setConstructorArgs(
				[]
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @issue ADI-665
	 */
	#[Test]
	public function ADI_665_isOnTestAuthenticationPage_returnsTrue()
	{
		$sut = $this->sut();

		$_GET['page'] = 'next_ad_int_test_connection';

		$actual = $sut->isOnTestAuthenticationPage();

		$this->assertTrue($actual);
	}

	/**
	 * @issue ADI-665
	 */
	#[Test]
	public function ADI_665_isOnTestAuthenticationPage_returnsFalse()
	{
		$sut = $this->sut();

		$_GET['page'] = 'next_ad_int_sync_to_wordpress';

		$actual = $sut->isOnTestAuthenticationPage();

		$this->assertFalse($actual);
	}

	#[Test]
	public function registerCore_willCall_registerAuthentication()
	{
		$sut = $this->sut(array('registerAuthentication'));

		$sut->expects($this->once())->method('registerAuthentication');

		$sut->registerCore();
	}

	#[Test]
	public function registerAuthentication_onTestAuthenticationPage_willRegisterHooks_returnsTrue()
	{
		$sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'dc', 'isOnTestAuthenticationPage'));
		$dc = $this->mockDependencyContainer($sut);
		$authorizationService = $this->createMock(\Dreitier\Nadi\Authorization\Service::class);

		$sut->expects($this->once())->method('isOnLoginPage')->willReturn(false);
		$sut->expects($this->once())->method('isSsoenabled')->willreturn(false);
		$sut->expects($this->once())->method('isOnTestAuthenticationPage')->willreturn(true);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->once())->method('getAuthorizationService')->willReturn($authorizationService);

		// check method calls on mocked services
		$authorizationService->expects($this->once())->method('register');

		// invoke method call
		$actual = $sut->registerAuthentication();

		// assertions
		$this->assertTrue($actual);
	}

	#[Test]
	public function registerAuthentication_onLoginPage_SsoDisabled_willRegisterHooks_returnsFalse()
	{
		$sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$authorizationService = $this->createMock(\Dreitier\Nadi\Authorization\Service::class);
		$loginSucceededService = $this->createMock(LoginSucceededService::class);

		$sut->expects($this->once())->method('isOnLoginPage')->willReturn(true);
		$sut->expects($this->once())->method('isSsoEnabled')->willReturn(false);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->once())->method('getAuthorizationService')->willReturn($authorizationService);
		$dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);
		$dc->expects($this->never())->method('getSsoService');

		\WP_Mock::expectAction(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'register_form_login_services');

		// check method calls on mocked services
		$authorizationService->expects($this->once())->method('register');
		$loginSucceededService->expects($this->once())->method('register');

		// invoke method call
		$actual = $sut->registerAuthentication();

		// assertions
		$this->assertFalse($actual);
	}

	#[Test]
	public function registerAuthentication_notOnLoginPage_SsoDisabled_willRegisterHooks_returnsTrue()
	{
		$sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$authorizationService = $this->createMock(\Dreitier\Nadi\Authorization\Service::class);
		$loginSucceededService = $this->createMock(LoginSucceededService::class);

		$sut->expects($this->once())->method('isOnLoginPage')->willReturn(false);
		$sut->expects($this->once())->method('isSsoEnabled')->willReturn(false);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->once())->method('getAuthorizationService')->willReturn($authorizationService);
		$dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);

		// check method calls on mocked services
		$authorizationService->expects($this->once())->method('register');
		$loginSucceededService->expects($this->once())->method('register');

		// invoke method call
		$actual = $sut->registerAuthentication();

		// assertions
		$this->assertTrue($actual);
	}

	/**
	 * @issue NADIS-92, ADI-679
	 * @since 2.1.9
	 */
	#[Test]
	public function registerAuthentication_disableSsoForXmlRpc_notRegistersSsoService()
	{
		$sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'isOnXmlRpcPage', 'isSsoDisabledForXmlRpc', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$authorizationService = $this->createMock(\Dreitier\Nadi\Authorization\Service::class);
		$ssoService = $this->createMock(\Dreitier\Nadi\Authentication\SingleSignOn\Service::class);
		$loginSucceededService = $this->createMock(LoginSucceededService::class);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->once())->method('getAuthorizationService')->willReturn($authorizationService);
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

	#[Test]
	public function registerAuthentication_notOnLoginPage_SsoEnabled_willRegisterHooks_returnsTrue()
	{
		$sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'isOnXmlRpcPage', 'isSsoDisabledForXmlRpc', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$authorizationService = $this->createMock(\Dreitier\Nadi\Authorization\Service::class);
		$ssoService = $this->createMock(\Dreitier\Nadi\Authentication\SingleSignOn\Service::class);
		$loginSucceededService = $this->createMock(LoginSucceededService::class);
		$configurationService = $this->createMock(Service::class);

		$sut->expects($this->once())->method('isOnLoginPage')->willReturn(false);
		$sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);
		$sut->expects($this->once())->method('isOnXmlRpcPage')->willReturn(false);
		$sut->expects($this->once())->method('isSsoDisabledForXmlRpc')->willReturn(false);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->once())->method('getAuthorizationService')->willReturn($authorizationService);
		$dc->expects($this->once())->method('getSsoService')->willReturn($ssoService);
		$dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);
		$dc->expects($this->once())->method('getMultisiteConfigurationService')->willReturn($configurationService);

		// check method calls on mocked services
		$authorizationService->expects($this->once())->method('register');
		$loginSucceededService->expects($this->once())->method('register');
		$configurationService->expects($this->once())
			->method('getOptionValue')
			->with(Options::CUSTOM_LOGIN_PAGE_ENABLED)
			->willReturn(false);
		$ssoService->expects($this->once())->method('register');

		// invoke method call
		$actual = $sut->registerAuthentication();

		// assertions
		$this->assertTrue($actual);
	}

	#[Test]
	public function registerAuthentication_onLoginPage_SsoEnabled_willRegisterHooks_returnsFalse()
	{
		$sut = $this->sut(array('isOnLoginPage', 'isSsoEnabled', 'isOnXmlRpcPage', 'isSsoDisabledForXmlRpc', 'dc'));
		$dc = $this->mockDependencyContainer($sut);
		$authorizationService = $this->createMock(\Dreitier\Nadi\Authorization\Service::class);
		$ssoService = $this->createMock(\Dreitier\Nadi\Authentication\SingleSignOn\Service::class);
		$loginSucceededService = $this->createMock(LoginSucceededService::class);
		$configurationService = $this->createMock(Service::class);

		$sut->expects($this->once())->method('isOnLoginPage')->willReturn(true);
		$sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);
		$sut->expects($this->once())->method('isOnXmlRpcPage')->willReturn(false);
		$sut->expects($this->once())->method('isSsoDisabledForXmlRpc')->willReturn(false);

		// mock dependency container calls and return individual mocked services
		$dc->expects($this->once())->method('getAuthorizationService')->willReturn($authorizationService);
		$dc->expects($this->once())->method('getSsoService')->willReturn($ssoService);
		$dc->expects($this->once())->method('getLoginSucceededService')->willReturn($loginSucceededService);
		$dc->expects($this->once())->method('getMultisiteConfigurationService')->willReturn($configurationService);

		$sut->expects($this->once())->method('isOnLoginPage')->willReturn(true);
		$sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);

		// check method calls on mocked services
		$authorizationService->expects($this->once())->method('register');
		$loginSucceededService->expects($this->once())->method('register');
		$configurationService->expects($this->once())
			->method('getOptionValue')
			->with(Options::CUSTOM_LOGIN_PAGE_ENABLED)
			->willReturn(false);
		$ssoService->expects($this->once())->method('register');

		// invoke method call
		$actual = $sut->registerAuthentication();

		// assertions
		$this->assertFalse($actual);
	}

	#[Test]
	public function registerFormLoginServices_willRegisterHooks()
	{
		$sut = $this->sut(array('isSsoEnabled', 'dc'));
		$dc = $this->mockDependencyContainer($sut);

		$pwValidationService = $this->createMock(PasswordValidationService::class);
		$loginService = $this->createMock(LoginService::class);
		$ssoPage = $this->createMock(ShowSingleSignOnLink::class);

		$sut->expects($this->once())->method('isSsoEnabled')->willReturn(true);

		$dc->expects($this->once())->method('getPasswordValidationService')->willReturn($pwValidationService);
		$dc->expects($this->once())->method('getLoginService')->willReturn($loginService);
		$dc->expects($this->once())->method('getSsoPage')->willReturn($ssoPage);

		$loginService->expects($this->once())->method('register');
		$pwValidationService->expects($this->once())->method('register');
		$ssoPage->expects($this->once())->method('register');

		$sut->registerFormLoginServices();
	}

	#[Test]
	public function registerCore_willRegisterHooks()
	{
		$sut = $this->sut(array('registerAuthentication', 'dc',
			'registerSharedAdministrationHooks', 'registerUserProfileHooks', 'registerAdministrationHooks', 'registerSynchronizationHooks'));

		$sut->expects($this->once())->method('registerAuthentication')->willReturn(true);

		\WP_Mock::userFunction('wp_get_current_user', array(
			'times' => 1,
			'return' => (object)array('ID' => 555)));

		$sut->expects($this->once())->method('registerSharedAdministrationHooks');
		$sut->expects($this->once())->method('registerUserProfileHooks');
		$sut->expects($this->once())->method('registerAdministrationHooks');
		$sut->expects($this->once())->method('registerSynchronizationHooks');

		$actual = $sut->registerCore();

		$this->assertTrue($actual);
	}

	#[Test]
	public function registerCore_willReturnFalse_currentUserHasNoId()
	{
		$sut = $this->sut(array('registerAuthentication', 'dc'));
		$dc = $this->mockDependencyContainer($sut);

		$sut->expects($this->once())->method('registerAuthentication')->willReturn(true);

		\WP_Mock::userFunction('wp_get_current_user', array(
			'times' => 1,
			'return' => (object)array('ID' => 0)));  // Attribute ID will show 0 if there is no user.

		$dc->expects($this->never())->method('getUserManager');

		$actual = $sut->registerCore();

		$this->assertFalse($actual);
	}
}