<?php

namespace Dreitier\Nadi;

use Dreitier\ActiveDirectory\Context;
use Dreitier\Ldap\Attribute\Repository as LdapAttributeRepository;
use Dreitier\Ldap\Attribute\Service as LdapAttributeService;
use Dreitier\Ldap\Connection;
use Dreitier\Nadi\Authentication\LoginService;
use Dreitier\Nadi\Authentication\PasswordValidationService;
use Dreitier\Nadi\Authentication\SingleSignOn\Profile\Locator;
use Dreitier\Nadi\Authentication\SingleSignOn\Service as SingleSignOnService;
use Dreitier\Nadi\Authentication\SingleSignOn\Ui\ShowSingleSignOnLink;
use Dreitier\Nadi\Authentication\SingleSignOn\Validator;
use Dreitier\Nadi\Authentication\VerificationService;
use Dreitier\Nadi\Authorization\Service as AuthorizationService;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Cron\UrlTrigger;
use Dreitier\Nadi\Multisite\Site\Ui\ExtendSiteList;
use Dreitier\Nadi\Multisite\Ui\MultisiteMenu;
use Dreitier\Nadi\Role\Manager as RoleManager;
use Dreitier\Nadi\Synchronization\ActiveDirectorySynchronizationService;
use Dreitier\Nadi\Synchronization\Ui\SyncToActiveDirectoryPage;
use Dreitier\Nadi\Synchronization\Ui\SyncToWordPressPage;
use Dreitier\Nadi\Synchronization\WordPressSynchronizationService;
use Dreitier\Nadi\Ui\ConnectivityTestPage;
use Dreitier\Nadi\Ui\Menu\Menu;
use Dreitier\Nadi\Ui\NadiMultisiteConfigurationPage;
use Dreitier\Nadi\User\Ui\ExtendUserList;
use Dreitier\Nadi\User\LoginSucceededService;
use Dreitier\Nadi\User\Manager as UserManager;
use Dreitier\Nadi\User\Persistence\Repository as UserRepository;
use Dreitier\Nadi\User\Meta\Persistence\Repository as UserMetaRepository;
use Dreitier\Nadi\User\Helper;
use Dreitier\Nadi\User\Profile\Ui\PreventEmailChange;
use Dreitier\Nadi\User\Profile\Ui\PreventPasswordChange;
use Dreitier\Nadi\User\Profile\Ui\ProvideDisableUserOption;
use Dreitier\Nadi\User\Profile\Ui\ShowLdapAttributes;
use Dreitier\Nadi\User\Profile\Ui\TriggerActiveDirectorySynchronization;
use Dreitier\Util\Encryption;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\DefaultProfileRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;
use Dreitier\WordPress\Multisite\Configuration\Service as MultisiteConfigurationService;
use Dreitier\WordPress\Multisite\Option\Provider;
use Dreitier\WordPress\Multisite\Option\Sanitizer;
use Dreitier\WordPress\Multisite\Ui\BlogConfigurationController;
use Dreitier\WordPress\Multisite\Ui\BlogProfileRelationshipController;
use Dreitier\WordPress\Multisite\Ui\BlogProfileRelationshipPage;
use Dreitier\WordPress\Multisite\Ui\ProfileConfigurationController;
use Dreitier\WordPress\Multisite\Ui\ProfileController;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use Dreitier\WordPress\WordPressRepository;
use Dreitier\Nadi\Ui\NadiSingleSiteConfigurationPage;

/**
 * Contains all dependencies with lazy loading to reduce the amount of loaded classes during the plug-in runtime.
 * We don't use a proprietary DI container to keep compatibility with PHP 5.3.
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
class Dependencies
{
	/**
	 * @var Dependencies
	 */
	private static $_instance = null;

	/**
	 * Provide Singleton
	 *
	 * @return Dependencies
	 */
	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new Dependencies();
		}

		return self::$_instance;
	}


	/**
	 * @var WordPressRepository
	 */
	private $wordPressRepository = null;

	/**
	 * @return WordPressRepository
	 */
	public function getWordPressRepository()
	{
		if ($this->wordPressRepository == null) {
			$this->wordPressRepository = new WordPressRepository();

		}

		return $this->wordPressRepository;
	}

	/**
	 * @var Sanitizer
	 */
	private $sanitizer = null;

	/**
	 * @return Sanitizer
	 */
	public function getSanitizer()
	{
		if ($this->sanitizer == null) {
			$this->sanitizer = new Sanitizer();
		}

		return $this->sanitizer;
	}

	/**
	 * @var Encryption
	 */
	private $encryptionHandler = null;

	/**
	 * @return Encryption
	 */
	public function getEncryptionHandler()
	{
		if ($this->encryptionHandler == null) {
			$this->encryptionHandler = new Encryption();
		}

		return $this->encryptionHandler;
	}

	/**
	 * @var Provider
	 */
	private $optionProvider = null;

	/**
	 * @return Provider|Options
	 */
	public function getOptionProvider()
	{
		if ($this->optionProvider == null) {
			$this->optionProvider = new Options();
		}

		return $this->optionProvider;
	}

	/**
	 * @var MultisiteConfigurationService
	 */
	private $multisiteConfigurationService;

	/**
	 * @return MultisiteConfigurationService
	 */
	public function getMultisiteConfigurationService()
	{
		if ($this->multisiteConfigurationService == null) {
			$this->multisiteConfigurationService = new MultisiteConfigurationService(
				$this->getBlogConfigurationRepository(),
				$this->getProfileConfigurationRepository(),
				$this->getProfileRepository()
			);
		}

		return $this->multisiteConfigurationService;
	}

	/**
	 * @var BlogConfigurationRepository
	 */
	private $blogConfigurationRepository = null;

	/**
	 * @return BlogConfigurationRepository
	 */
	public function getBlogConfigurationRepository()
	{
		if ($this->blogConfigurationRepository == null) {
			$this->blogConfigurationRepository = new BlogConfigurationRepository(
				$this->getSanitizer(),
				$this->getEncryptionHandler(),
				$this->getOptionProvider(),
				$this->getProfileConfigurationRepository(),
				$this->getDefaultProfileRepository()
			);
		}

		return $this->blogConfigurationRepository;
	}

	/**
	 * @var ProfileConfigurationRepository
	 */
	private $profileConfigurationRepository = null;

	/**
	 * @return ProfileConfigurationRepository
	 */
	public function getProfileConfigurationRepository()
	{
		if ($this->profileConfigurationRepository == null) {
			$this->profileConfigurationRepository = new ProfileConfigurationRepository(
				$this->getSanitizer(),
				$this->getEncryptionHandler(),
				$this->getOptionProvider());
		}

		return $this->profileConfigurationRepository;
	}

	/**
	 * @var ProfileRepository
	 */
	private $profileRepository = null;

	/**
	 * @return ProfileRepository
	 */
	public function getProfileRepository()
	{
		if ($this->profileRepository == null) {
			$this->profileRepository = new ProfileRepository(
				$this->getProfileConfigurationRepository(),
				$this->getBlogConfigurationRepository(),
				$this->getWordPressRepository(),
				$this->getOptionProvider());
		}

		return $this->profileRepository;

	}

	/**
	 * @var DefaultProfileRepository
	 */
	private $defaultProfileRepository = null;

	/**
	 * @return DefaultProfileRepository
	 */
	public function getDefaultProfileRepository()
	{
		if ($this->defaultProfileRepository == null) {
			$this->defaultProfileRepository = new DefaultProfileRepository();
		}

		return $this->defaultProfileRepository;
	}

	/**
	 * @var LdapAttributeRepository
	 */
	private $ldapAttributeRepository = null;

	/**
	 * @return LdapAttributeRepository
	 */
	public function getLdapAttributeRepository()
	{
		if ($this->ldapAttributeRepository == null) {
			$this->ldapAttributeRepository = new LdapAttributeRepository(
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->ldapAttributeRepository;
	}

	/**
	 * This is required for compatibility reasons with old Premium Extensions (e.g. nadiext-buddypress-simpleattributes).
	 *
	 * @return LdapAttributeRepository
	 */
	public function getAttributeRepository() {
		return $this->getLdapAttributeRepository();
	}

	/**
	 * @var LdapAttributeService
	 */
	private $ldapAttributeService = null;

	/**
	 * @return LdapAttributeService
	 */
	public function getLdapAttributeService()
	{
		if ($this->ldapAttributeService == null) {
			$this->ldapAttributeService = new LdapAttributeService(
				$this->getLdapConnection(),
				$this->getLdapAttributeRepository()
			);
		}

		return $this->ldapAttributeService;
	}

	/**
	 * @var Helper
	 */
	private $userHelper = null;

	/**
	 * @return Helper
	 */
	public function getUserHelper()
	{
		if ($this->userHelper == null) {
			$this->userHelper = new Helper(
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->userHelper;
	}

	/**
	 * @var Connection
	 */
	private $ldapConnection = null;

	/**
	 * @return Connection
	 */
	public function getLdapConnection()
	{
		if ($this->ldapConnection == null) {
			$this->ldapConnection = new Connection(
				$this->getMultisiteConfigurationService(),
				$this->getActiveDirectoryContext()
			);

			$this->ldapConnection->register();
		}

		return $this->ldapConnection;
	}

	/**
	 * @var RoleManager
	 */
	private $roleManager = null;

	/**
	 * @return RoleManager
	 */
	public function getRoleManager()
	{
		if ($this->roleManager == null) {
			$this->roleManager = new RoleManager(
				$this->getMultisiteConfigurationService(),
				$this->getLdapConnection()
			);
		}

		return $this->roleManager;
	}

	/**
	 * @var UserMetaRepository
	 */
	private $userMetaRepository;

	/**
	 * @return UserMetaRepository
	 */
	public function getUserMetaRepository()
	{
		if ($this->userMetaRepository == null) {
			$this->userMetaRepository = new UserMetaRepository();
		}

		return $this->userMetaRepository;
	}

	/**
	 * @var UserRepository
	 */
	private $userRepository;

	/**
	 * @return UserRepository
	 */
	public function getUserRepository()
	{
		if ($this->userRepository == null) {
			$this->userRepository = new UserRepository();
		}

		return $this->userRepository;
	}

	/**
	 * @var UserManager
	 */
	private $userManager = null;

	/**
	 * @return UserManager
	 */
	public function getUserManager()
	{
		if ($this->userManager == null) {
			$this->userManager = new UserManager(
				$this->getMultisiteConfigurationService(),
				$this->getLdapAttributeService(),
				$this->getUserHelper(),
				$this->getLdapAttributeRepository(),
				$this->getRoleManager(),
				$this->getUserMetaRepository(),
				$this->getUserRepository()
			);

			// register any hooks
			$this->userManager->register();
		}

		return $this->userManager;
	}

	/**
	 * @var TwigContainer
	 */
	private $twigContainer = null;

	/**
	 * @return TwigContainer
	 */
	public function getTwigContainer()
	{
		if ($this->twigContainer == null) {
			$this->twigContainer = new TwigContainer(
				$this->getBlogConfigurationRepository(),
				$this->getMultisiteConfigurationService(),
				$this->getProfileConfigurationRepository(),
				$this->getProfileRepository(),
				$this->getDefaultProfileRepository(),
				$this->getOptionProvider(),
				$this->getVerificationService()
			);
		}

		return $this->twigContainer;
	}

	/**
	 * @var ProvideDisableUserOption
	 */
	private $provideDisableUserOption = null;

	/**
	 * @return ProvideDisableUserOption
	 */
	public function getProvideDisableUserOption()
	{
		if ($this->provideDisableUserOption == null) {
			$this->provideDisableUserOption = new ProvideDisableUserOption(
				$this->getTwigContainer(),
				$this->getUserManager()
			);
		}

		return $this->provideDisableUserOption;
	}

	/**
	 * @var LoginService
	 */
	private $loginService = null;

	/**
	 * @return LoginService
	 */
	public function getLoginService()
	{
		if ($this->loginService == null) {
			$this->loginService = new LoginService(
				$this->getMultisiteConfigurationService(),
				$this->getLdapConnection(),
				$this->getUserManager(),
				$this->getLdapAttributeService(),
				$this->getLoginState(),
				$this->getLoginSucceededService()
			);
		}

		return $this->loginService;
	}

	/**
	 * @var PasswordValidationService
	 */
	private $passwordValidationService = null;

	/**
	 * @return PasswordValidationService
	 */
	public function getPasswordValidationService()
	{
		if ($this->passwordValidationService == null) {
			$this->passwordValidationService = new PasswordValidationService(
				$this->getLoginState(),
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->passwordValidationService;
	}

	/**
	 * @var BlogConfigurationController
	 */
	private $blogConfigurationController = null;

	/**
	 * @return BlogConfigurationController
	 */
	public function getBlogConfigurationController()
	{
		if ($this->blogConfigurationController == null) {
			$this->blogConfigurationController = new BlogConfigurationController(
				$this->getBlogConfigurationRepository(),
				$this->getOptionProvider()
			);
		}

		return $this->blogConfigurationController;
	}

	/**
	 * @var BlogProfileRelationshipController
	 */
	private $blogProfileRelationshipController = null;

	/**
	 * @return BlogProfileRelationshipController
	 */
	public function getBlogProfileRelationshipController()
	{
		if ($this->blogProfileRelationshipController == null) {
			$this->blogProfileRelationshipController = new BlogProfileRelationshipController(
				$this->getBlogConfigurationRepository(),
				$this->getProfileRepository(),
				$this->getDefaultProfileRepository()
			);
		}

		return $this->blogProfileRelationshipController;
	}

	/**
	 * @var ProfileController
	 */
	private $profileController = null;

	/**
	 * @return ProfileController
	 */
	public function getProfileController()
	{
		if ($this->profileController == null) {
			$this->profileController = new ProfileController(
				$this->getProfileRepository(),
				$this->getBlogConfigurationRepository(),
				$this->getDefaultProfileRepository()
			);
		}

		return $this->profileController;
	}

	/**
	 * @var ProfileConfigurationController
	 */
	private $profileConfigurationController = null;

	/**
	 * @return ProfileConfigurationController
	 */
	public function getProfileConfigurationController()
	{
		if ($this->profileConfigurationController == null) {
			$this->profileConfigurationController = new ProfileConfigurationController(
				$this->getProfileConfigurationRepository(),
				$this->getOptionProvider()
			);
		}

		return $this->profileConfigurationController;
	}

	/**
	 * @var ActiveDirectorySynchronizationService
	 */
	private $syncToActiveDirectory = null;

	/**
	 * @return ActiveDirectorySynchronizationService
	 */
	public function getSyncToActiveDirectory()
	{
		if ($this->syncToActiveDirectory == null) {
			$this->syncToActiveDirectory = new ActiveDirectorySynchronizationService(
				$this->getLdapAttributeService(),
				$this->getMultisiteConfigurationService(),
				$this->getLdapConnection()
			);
		}

		return $this->syncToActiveDirectory;
	}

	/**
	 * @var WordPressSynchronizationService
	 */
	private $syncToWordPress = null;

	/**
	 * @return WordPressSynchronizationService
	 */
	public function getSyncToWordPress()
	{
		if ($this->syncToWordPress == null) {
			$this->syncToWordPress = new WordPressSynchronizationService(
				$this->getUserManager(),
				$this->getUserHelper(),
				$this->getMultisiteConfigurationService(),
				$this->getLdapConnection(),
				$this->getLdapAttributeService(),
				$this->getRoleManager()
			);
		}

		return $this->syncToWordPress;
	}

	/**
	 * @var NadiSingleSiteConfigurationPage
	 */
	private $singleSiteConfigurationPage = null;

	/**
	 * @return NadiSingleSiteConfigurationPage
	 */
	public function getSingleSiteConfigurationPage()
	{
		if ($this->singleSiteConfigurationPage == null) {
			$this->singleSiteConfigurationPage = new NadiSingleSiteConfigurationPage(
				$this->getTwigContainer(),
				$this->getBlogConfigurationController()
			);

		}

		return $this->singleSiteConfigurationPage;
	}

	/**
	 * @var ConnectivityTestPage
	 */
	private $connectivityTestPage = null;

	/**
	 * @return ConnectivityTestPage
	 */
	public function getConnectivityTestPage()
	{
		if ($this->connectivityTestPage == null) {
			$this->connectivityTestPage = new ConnectivityTestPage(
				$this->getTwigContainer(),
				$this->getMultisiteConfigurationService(),
				$this->getLdapConnection(),
				$this->getLdapAttributeService(),
				$this->getUserManager(),
				$this->getRoleManager(),
				$this->getLoginSucceededService()
			);
		}

		return $this->connectivityTestPage;
	}

	/**
	 * @var SyncToActiveDirectoryPage
	 */
	private $syncToActiveDirectoryPage = null;

	/**
	 * @return SyncToActiveDirectoryPage
	 */
	public function getSyncToActiveDirectoryPage()
	{
		if ($this->syncToActiveDirectoryPage == null) {
			$this->syncToActiveDirectoryPage = new SyncToActiveDirectoryPage(
				$this->getTwigContainer(),
				$this->getSyncToActiveDirectory(),
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->syncToActiveDirectoryPage;
	}

	/**
	 * @var SyncToWordPressPage
	 */
	private $syncToWordPressPage = null;

	/**
	 * @return SyncToWordPressPage
	 */
	public function getSyncToWordPressPage()
	{
		if ($this->syncToWordPressPage == null) {
			$this->syncToWordPressPage = new SyncToWordPressPage(
				$this->getTwigContainer(),
				$this->getSyncToWordPress(),
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->syncToWordPressPage;
	}

	/**
	 * @var NadiMultisiteConfigurationPage
	 */
	private $multisiteConfigurationPage = null;

	/**
	 * @return NadiMultisiteConfigurationPage
	 */
	public function getMultisiteConfigurationPage()
	{
		if ($this->multisiteConfigurationPage == null) {
			$this->multisiteConfigurationPage = new NadiMultisiteConfigurationPage(
				$this->getTwigContainer(),
				$this->getBlogConfigurationController(),
				$this->getProfileConfigurationController(),
				$this->getProfileController(),
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->multisiteConfigurationPage;
	}

	/**
	 * @var BlogProfileRelationshipPage
	 */
	private $blogProfileRelationshipPage = null;

	/**
	 * @return BlogProfileRelationshipPage
	 */
	public function getBlogProfileRelationshipPage()
	{
		if ($this->blogProfileRelationshipPage == null) {
			$this->blogProfileRelationshipPage = new BlogProfileRelationshipPage(
				$this->getTwigContainer(),
				$this->getBlogProfileRelationshipController()
			);
		}

		return $this->blogProfileRelationshipPage;
	}

	/**
	 * @var Menu
	 */
	private $menu = null;

	/**
	 * @return Menu
	 */
	public function getMenu()
	{
		if ($this->menu == null) {
			$this->menu = new Menu(
				$this->getOptionProvider(),
				$this->getMultisiteConfigurationService(),
				$this->getSingleSiteConfigurationPage(),
				$this->getConnectivityTestPage(),
				$this->getSyncToWordPressPage(),
				$this->getSyncToActiveDirectoryPage()
			);
		}

		return $this->menu;
	}

	/**
	 * @var MultisiteMenu
	 */
	private $multisiteMenu = null;

	/**
	 * @return MultisiteMenu
	 */
	public function getMultisiteMenu()
	{
		if ($this->multisiteMenu == null) {
			$this->multisiteMenu = new MultisiteMenu(
				$this->getOptionProvider(),
				$this->getBlogProfileRelationshipPage(),
				$this->getMultisiteConfigurationPage()
			);
		}

		return $this->multisiteMenu;
	}

	/**
	 * @var UrlTrigger
	 */
	private $urlTrigger = null;

	/**
	 * @return UrlTrigger
	 */
	public function getUrlTrigger()
	{
		if ($this->urlTrigger == null) {
			$this->urlTrigger = new UrlTrigger(
				$this->getMultisiteConfigurationService(),
				$this->getSyncToActiveDirectory(),
				$this->getSyncToWordPress()
			);
		}

		return $this->urlTrigger;
	}

	/**
	 * @var ExtendUserList
	 */
	private $extendUserList = null;

	/**
	 * @return ExtendUserList
	 */
	public function getExtendUserList()
	{
		if ($this->extendUserList == null) {
			$this->extendUserList = new ExtendUserList(
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->extendUserList;
	}

	/**
	 * @var ShowLdapAttributes
	 */
	private $showLdapAttributes = null;

	/**
	 * @return ShowLdapAttributes
	 */
	public function getShowLdapAttributes()
	{
		if ($this->showLdapAttributes == null) {
			$this->showLdapAttributes = new ShowLdapAttributes(
				$this->getMultisiteConfigurationService(),
				$this->getTwigContainer(),
				$this->getLdapAttributeRepository(),
				$this->getSyncToActiveDirectory()
			);
		}

		return $this->showLdapAttributes;
	}

	/**
	 * @var PreventEmailChange
	 */
	private $preventEmailChange = null;

	/**
	 * @return PreventEmailChange
	 */
	public function getPreventEmailChange()
	{
		if ($this->preventEmailChange == null) {
			$this->preventEmailChange = new PreventEmailChange(
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->preventEmailChange;
	}

	/**
	 * @var TriggerActiveDirectorySynchronization
	 */
	private $triggerActiveDirectorySynchronization = null;

	/**
	 * @return TriggerActiveDirectorySynchronization
	 */
	public function getTriggerActiveDirectorySynchronization()
	{
		if ($this->triggerActiveDirectorySynchronization == null) {
			$this->triggerActiveDirectorySynchronization = new TriggerActiveDirectorySynchronization(
				$this->getMultisiteConfigurationService(),
				$this->getSyncToActiveDirectory(),
				$this->getLdapAttributeRepository()
			);
		}

		return $this->triggerActiveDirectorySynchronization;
	}

	/**
	 * @var PreventPasswordChange
	 */
	private $profilePreventPasswordChange = null;

	/**
	 * @return PreventPasswordChange
	 */
	public function getProfilePreventPasswordChange()
	{
		if ($this->profilePreventPasswordChange == null) {
			$this->profilePreventPasswordChange = new PreventPasswordChange(
				$this->getMultisiteConfigurationService(),
				$this->getUserManager()
			);
		}

		return $this->profilePreventPasswordChange;
	}

	/**
	 * @var Requirements
	 */
	private $requirements = null;

	/**
	 * @return Requirements
	 */
	public function getRequirements()
	{
		if ($this->requirements == null) {
			$this->requirements = new Requirements();
		}

		return $this->requirements;
	}


	/**
	 * @var ExtendSiteList
	 */
	private $extendSiteList = null;

	public function getExtendSiteList()
	{
		if ($this->extendSiteList == null) {
			// #183: we need to manually load the classes we are depending upon.
			require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
			require_once( ABSPATH . 'wp-admin/includes/class-wp-ms-sites-list-table.php');
				
			$this->extendSiteList = new ExtendSiteList(
				$this->getBlogConfigurationRepository(),
				$this->getProfileRepository()
			);
		}

		return $this->extendSiteList;
	}

	/**
	 * @var VerificationService
	 */
	private $verificationService = null;

	public function getVerificationService()
	{
		if ($this->verificationService == null) {
			$this->verificationService = new VerificationService(
				$this->getLdapConnection(), $this->getLdapAttributeRepository()
			);
		}

		return $this->verificationService;
	}

	/**
	 * @var SingleSignOnService
	 */
	private $ssoService = null;

	/**
	 * @return SingleSignOnService
	 */
	public function getSsoService()
	{
		if ($this->ssoService == null) {
			$this->ssoService = new SingleSignOnService(
				$this->getMultisiteConfigurationService(),
				$this->getLdapConnection(),
				$this->getUserManager(),
				$this->getLdapAttributeService(),
				$this->getSsoValidator(),
				$this->getLoginState(),
				$this->getLoginSucceededService(),
				$this->getSsoProfileLocator()
			);
		}

		return $this->ssoService;
	}

	/**
	 * @var ShowSingleSignOnLink
	 */
	private $ssoPage = null;

	/**
	 * @return ShowSingleSignOnLink
	 */
	public function getSsoPage()
	{
		if ($this->ssoPage == null) {
			// TODO SSO Error wp-login
			$this->ssoPage = new ShowSingleSignOnLink();
		}

		return $this->ssoPage;
	}

	/**
	 * @var Validator
	 */
	private $ssoValidator = null;

	/**
	 * @return Validator
	 */
	public function getSsoValidator()
	{
		if ($this->ssoValidator == null) {
			$this->ssoValidator = new Validator();
		}

		return $this->ssoValidator;
	}

	/**
	 * @var LoginState
	 */
	private $loginState = null;

	/**
	 * @return LoginState
	 */
	public function getLoginState()
	{
		if ($this->loginState == null) {
			$this->loginState = new LoginState();
		}

		return $this->loginState;
	}

	/**
	 * @var AuthorizationService
	 */
	private $authorizationService = null;

	/**
	 * @return AuthorizationService
	 */
	public function getAuthorizationService()
	{
		if ($this->authorizationService == null) {
			$this->authorizationService = new AuthorizationService(
				$this->getMultisiteConfigurationService(),
				$this->getUserManager(),
				$this->getRoleManager(),
				$this->getLoginState()
			);
		}

		return $this->authorizationService;
	}

	/**
	 * @var LoginSucceededService
	 */
	private $loginSucceededService = null;

	/**
	 * @return LoginSucceededService
	 */
	public function getLoginSucceededService()
	{
		if ($this->loginSucceededService == null) {
			$this->loginSucceededService = new LoginSucceededService(
				$this->getLoginState(),
				$this->getLdapAttributeService(),
				$this->getLdapConnection(),
				$this->getMultisiteConfigurationService(),
				$this->getUserManager(),
			);
		}

		return $this->loginSucceededService;
	}

	/**
	 * @var Context
	 */
	private $activeDirectoryContext;

	/**
	 * @return mixed|Context|void
	 */
	public function getActiveDirectoryContext()
	{
		if ($this->activeDirectoryContext == null) {
			// factory callback to create a new context
			add_filter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'create_dependency_active_directory_context', function ($instance, MultisiteConfigurationService $configuration) {
				if (empty($instance)) {
					$instance = new Context([$configuration->getOptionValue(Configuration\Options::DOMAIN_SID)]);
				}

				return $instance;
			}, 10, 2);

			$this->activeDirectoryContext = apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'create_dependency_active_directory_context', null, $this->getMultisiteConfigurationService());
		}

		return $this->activeDirectoryContext;
	}

	/**
	 * @var Locator
	 */
	private $ssoProfileLocator = null;

	/**
	 * @return Locator
	 * @since 2.0.0
	 */
	public function getSsoProfileLocator()
	{
		if ($this->ssoProfileLocator == null) {
			$this->ssoProfileLocator = new Locator(
				$this->getMultisiteConfigurationService()
			);
		}

		return $this->ssoProfileLocator;
	}
}