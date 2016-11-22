<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Dependencies')) {
	return;
}

/**
 * Contains all dependencies with lazy loading to reduce the amount of loaded classes during the plug-in runtime.
 * We don't use a proprietary DI container to keep compatibility with PHP 5.3.
 *
 * @author Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Dependencies
{
	/**
	 * @var NextADInt_Adi_Dependencies
	 */
	private static $_instance = null;

	/**
	 * Provide Singleton
	 *
	 * @return NextADInt_Adi_Dependencies
	 */
	public static function getInstance() {
		if (self::$_instance === null) {
			self::$_instance = new NextADInt_Adi_Dependencies();
		}

		return self::$_instance;
	}


	/**
	 * @var NextADInt_Core_Persistence_WordPressRepository
	 */
	private $wordPressRepository = null;

	/**
	 * @return NextADInt_Core_Persistence_WordPressRepository
	 */
	public function getWordPressRepository()
	{
		if ($this->wordPressRepository == null) {
			$this->wordPressRepository = new NextADInt_Core_Persistence_WordPressRepository();

		}

		return $this->wordPressRepository;
	}

	/**
	 * @var NextADInt_Multisite_Option_Sanitizer
	 */
	private $sanitizer = null;

	/**
	 * @return NextADInt_Multisite_Option_Sanitizer
	 */
	public function getSanitizer()
	{
		if ($this->sanitizer == null) {
			$this->sanitizer = new NextADInt_Multisite_Option_Sanitizer();
		}

		return $this->sanitizer;
	}

	/**
	 * @var NextADInt_Core_Encryption
	 */
	private $encryptionHandler = null;

	/**
	 * @return NextADInt_Core_Encryption
	 */
	public function getEncryptionHandler()
	{
		if ($this->encryptionHandler == null) {
			$this->encryptionHandler = new NextADInt_Core_Encryption();
		}

		return $this->encryptionHandler;
	}

	/**
	 * @var NextADInt_Multisite_Option_Provider
	 */
	private $optionProvider = null;

	/**
	 * @return NextADInt_Adi_Configuration_Options|NextADInt_Multisite_Option_Provider
	 */
	public function getOptionProvider()
	{
		if ($this->optionProvider == null) {
			$this->optionProvider = new NextADInt_Adi_Configuration_Options();
		}

		return $this->optionProvider;
	}

	/**
	 * @var NextADInt_Multisite_Configuration_Service
	 */
	private $configurationService;

	/**
	 * @return NextADInt_Multisite_Configuration_Service
	 */
	public function getConfigurationService()
	{
		if ($this->configurationService == null) {
			$this->configurationService = new NextADInt_Multisite_Configuration_Service(
				$this->blogConfigurationRepository,
				$this->profileConfigurationRepository,
				$this->profileRepository
			);
		}

		return $this->configurationService;
	}

	/**
	 * @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository
	 */
	private $blogConfigurationRepository = null;

	/**
	 * @return NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository
	 */
	public function getBlogConfigurationRepository()
	{
		if ($this->blogConfigurationRepository == null) {
			$this->blogConfigurationRepository = new NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository(
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
	 * @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository
	 */
	private $profileConfigurationRepository = null;

	/**
	 * @return NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository
	 */
	public function getProfileConfigurationRepository()
	{
		if ($this->profileConfigurationRepository == null) {
			$this->profileConfigurationRepository = new NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository(
				$this->getSanitizer(),
				$this->getEncryptionHandler(),
				$this->getOptionProvider());
		}

		return $this->profileConfigurationRepository;
	}

	/**
	 * @var NextADInt_Multisite_Configuration_Service
	 */
	private $configuration = null;

	/**
	 * @return NextADInt_Multisite_Configuration_Service
	 */
	public function getConfiguration()
	{
		if ($this->configuration == null) {
			$this->configuration = new NextADInt_Multisite_Configuration_Service(
				$this->getBlogConfigurationRepository(),
				$this->getProfileConfigurationRepository(),
				$this->getProfileRepository()
			);
		}

		return $this->configuration;
	}

	/**
	 * @var NextADInt_Multisite_Configuration_Persistence_ProfileRepository
	 */
	private $profileRepository = null;

	/**
	 * @return NextADInt_Multisite_Configuration_Persistence_ProfileRepository
	 */
	public function getProfileRepository()
	{
		if ($this->profileRepository == null) {
			$this->profileRepository = new NextADInt_Multisite_Configuration_Persistence_ProfileRepository(
				$this->getProfileConfigurationRepository(),
				$this->getBlogConfigurationRepository(),
				$this->getWordPressRepository(),
				$this->getOptionProvider());
		}

		return $this->profileRepository;

	}

	/**
	 * @var NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository
	 */
	private $defaultProfileRepository = null;

	/**
	 * @return NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository
	 */
	public function getDefaultProfileRepository()
	{
		if ($this->defaultProfileRepository == null) {
			$this->defaultProfileRepository = new NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository();
		}

		return $this->defaultProfileRepository;
	}

	/**
	 * @return NextADInt_Adi_Authentication_Persistence_FailedLoginRepository
	 */
	private $failedLoginRepository = null;

	/**
	 * @return NextADInt_Adi_Authentication_Persistence_FailedLoginRepository|null
	 */
	public function getFailedLoginRepository()
	{
		if ($this->failedLoginRepository == null) {
			$this->failedLoginRepository = new NextADInt_Adi_Authentication_Persistence_FailedLoginRepository();
		}

		return $this->failedLoginRepository;
	}

	/**
	 * @var NextADInt_Ldap_Attribute_Repository
	 */
	private $attributeRepository = null;

	/**
	 * @return NextADInt_Ldap_Attribute_Repository
	 */
	public function getAttributeRepository()
	{
		if ($this->attributeRepository == null) {
			$this->attributeRepository = new NextADInt_Ldap_Attribute_Repository(
				$this->getConfiguration()
			);
		}

		return $this->attributeRepository;
	}

	/**
	 * @var NextADInt_Ldap_Attribute_Service
	 */
	private $attributeService = null;

	/**
	 * @return NextADInt_Ldap_Attribute_Service
	 */
	public function getAttributeService()
	{
		if ($this->attributeService == null) {
			$this->attributeService = new NextADInt_Ldap_Attribute_Service(
				$this->getLdapConnection(),
				$this->getAttributeRepository()
			);
		}

		return $this->attributeService;
	}

	/**
	 * @var NextADInt_Adi_User_Helper
	 */
	private $userHelper = null;

	/**
	 * @return NextADInt_Adi_User_Helper
	 */
	public function getUserHelper()
	{
		if ($this->userHelper == null) {
			$this->userHelper = new NextADInt_Adi_User_Helper(
				$this->getConfiguration()
			);
		}

		return $this->userHelper;
	}

	/**
	 * @var NextADInt_Ldap_Connection
	 */
	private $ldapConnection = null;

	/**
	 * @return NextADInt_Ldap_Connection
	 */
	public function getLdapConnection()
	{
		if ($this->ldapConnection == null) {
			$this->ldapConnection = new NextADInt_Ldap_Connection(
				$this->getConfiguration()
			);
		}

		return $this->ldapConnection;
	}

	/**
	 * @var NextADInt_Adi_Role_Manager
	 */
	private $roleManager = null;

	/**
	 * @return NextADInt_Adi_Role_Manager
	 */
	public function getRoleManager()
	{
		if ($this->roleManager == null) {
			$this->roleManager = new NextADInt_Adi_Role_Manager(
				$this->getConfiguration(),
				$this->getLdapConnection()
			);
		}

		return $this->roleManager;
	}

	/**
	 * @var NextADInt_Adi_User_Meta_Persistence_Repository
	 */
	private $userMetaRepository;

	/**
	 * @return NextADInt_Adi_User_Meta_Persistence_Repository
	 */
	public function getUserMetaRepository()
	{
		if ($this->userMetaRepository == null) {
			$this->userMetaRepository = new NextADInt_Adi_User_Meta_Persistence_Repository();
		}

		return $this->userMetaRepository;
	}

	/**
	 * @var NextADInt_Adi_User_Persistence_Repository
	 */
	private $userRepository;

	/**
	 * @return NextADInt_Adi_User_Persistence_Repository
	 */
	public function getUserRepository()
	{
		if ($this->userRepository == null) {
			$this->userRepository = new NextADInt_Adi_User_Persistence_Repository();
		}

		return $this->userRepository;
	}

	/**
	 * @var NextADInt_Adi_User_Manager
	 */
	private $userManager = null;

	/**
	 * @return NextADInt_Adi_User_Manager
	 */
	public function getUserManager()
	{
		if ($this->userManager == null) {
			$this->userManager = new NextADInt_Adi_User_Manager(
				$this->getConfiguration(),
				$this->getAttributeService(),
				$this->getUserHelper(),
				$this->getAttributeRepository(),
				$this->getRoleManager(),
				$this->getUserMetaRepository(),
				$this->getUserRepository()
			);
		}

		return $this->userManager;
	}

	/**
	 * @var NextADInt_Multisite_View_TwigContainer
	 */
	private $twigContainer = null;

	/**
	 * @return NextADInt_Multisite_View_TwigContainer
	 */
	public function getTwigContainer()
	{
		if ($this->twigContainer == null) {
			$this->twigContainer = new NextADInt_Multisite_View_TwigContainer(
				$this->getBlogConfigurationRepository(),
				$this->getConfiguration(),
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
	 * @var NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption
	 */
	private $provideDisableUserOption = null;

	/**
	 * @return NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption
	 */
	public function getProvideDisableUserOption()
	{
		if ($this->provideDisableUserOption == null) {
			$this->provideDisableUserOption = new NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption(
				$this->getTwigContainer(),
				$this->getUserManager()
			);
		}

		return $this->provideDisableUserOption;
	}

	/**
	 * @var NextADInt_Adi_Mail_Notification
	 */
	private $mailNotification = null;

	/**
	 * @return NextADInt_Adi_Mail_Notification
	 */
	public function getMailNotification()
	{
		if ($this->mailNotification == null) {
			$this->mailNotification = new NextADInt_Adi_Mail_Notification(
				$this->getConfiguration(),
				$this->getLdapConnection()
			);
		}

		return $this->mailNotification;
	}

	/**
	 * @var NextADInt_Adi_Authentication_Ui_ShowBlockedMessage
	 */
	private $showBlockedMessage = null;

	/**
	 * @return NextADInt_Adi_Authentication_Ui_ShowBlockedMessage
	 */
	public function getShowBlockedMessage()
	{
		if ($this->showBlockedMessage == null) {
			$this->showBlockedMessage = new NextADInt_Adi_Authentication_Ui_ShowBlockedMessage(
				$this->getConfiguration(),
				$this->getTwigContainer()
			);
		}

		return $this->showBlockedMessage;
	}

	/**
	 * @var NextADInt_Adi_Authentication_LoginService
	 */
	private $loginService = null;

	/**
	 * @return NextADInt_Adi_Authentication_LoginService
	 */
	public function getLoginService()
	{
		if ($this->loginService == null) {
			$this->loginService = new NextADInt_Adi_Authentication_LoginService(
				$this->getFailedLoginRepository(),
				$this->getConfiguration(),
				$this->getLdapConnection(),
				$this->getUserManager(),
				$this->getMailNotification(),
				$this->getShowBlockedMessage(),
				$this->getAttributeService(),
				$this->getRoleManager()
			);
		}

		return $this->loginService;
	}

	/**
	 * @var NextADInt_Adi_Authentication_PasswordValidationService
	 */
	private $passwordValidationService = null;

	/**
	 * @return NextADInt_Adi_Authentication_PasswordValidationService
	 */
	public function getPasswordValidationService()
	{
		if ($this->passwordValidationService == null) {
			$this->passwordValidationService = new NextADInt_Adi_Authentication_PasswordValidationService(
				$this->getLoginService(),
				$this->getConfiguration()
			);
		}

		return $this->passwordValidationService;
	}

	/**
	 * @var NextADInt_Multisite_Ui_BlogConfigurationController
	 */
	private $blogConfigurationController = null;

	/**
	 * @return NextADInt_Multisite_Ui_BlogConfigurationController
	 */
	public function getBlogConfigurationController()
	{
		if ($this->blogConfigurationController == null) {
			$this->blogConfigurationController = new NextADInt_Multisite_Ui_BlogConfigurationController(
				$this->getBlogConfigurationRepository(),
				$this->getOptionProvider()
			);
		}

		return $this->blogConfigurationController;
	}

	/**
	 * @var NextADInt_Multisite_Ui_BlogProfileRelationshipController
	 */
	private $blogProfileRelationshipController = null;

	/**
	 * @return NextADInt_Multisite_Ui_BlogProfileRelationshipController
	 */
	public function getBlogProfileRelationshipController()
	{
		if ($this->blogProfileRelationshipController == null) {
			$this->blogProfileRelationshipController = new NextADInt_Multisite_Ui_BlogProfileRelationshipController(
				$this->getBlogConfigurationRepository(),
				$this->getProfileRepository(),
				$this->getDefaultProfileRepository()
			);
		}

		return $this->blogProfileRelationshipController;
	}

	/**
	 * @var NextADInt_Multisite_Ui_ProfileController
	 */
	private $profileController = null;

	/**
	 * @return NextADInt_Multisite_Ui_ProfileController
	 */
	public function getProfileController()
	{
		if ($this->profileController == null) {
			$this->profileController = new NextADInt_Multisite_Ui_ProfileController(
				$this->getProfileRepository(),
				$this->getBlogConfigurationRepository(),
				$this->getDefaultProfileRepository()
			);
		}

		return $this->profileController;
	}

	/**
	 * @var NextADInt_Multisite_Ui_ProfileConfigurationController
	 */
	private $profileConfigurationController = null;

	/**
	 * @return NextADInt_Multisite_Ui_ProfileConfigurationController
	 */
	public function getProfileConfigurationController()
	{
		if ($this->profileConfigurationController == null) {
			$this->profileConfigurationController = new NextADInt_Multisite_Ui_ProfileConfigurationController(
				$this->getProfileConfigurationRepository(),
				$this->getOptionProvider()
			);
		}

		return $this->profileConfigurationController;
	}

	/**
	 * @var NextADInt_Adi_Synchronization_ActiveDirectory
	 */
	private $syncToActiveDirectory = null;

	/**
	 * @return NextADInt_Adi_Synchronization_ActiveDirectory
	 */
	public function getSyncToActiveDirectory()
	{
		if ($this->syncToActiveDirectory == null) {
			$this->syncToActiveDirectory = new NextADInt_Adi_Synchronization_ActiveDirectory(
				$this->getAttributeService(),
				$this->getConfiguration(),
				$this->getLdapConnection()
			);
		}

		return $this->syncToActiveDirectory;
	}

	/**
	 * @var NextADInt_Adi_Synchronization_WordPress
	 */
	private $syncToWordPress = null;

	/**
	 * @return NextADInt_Adi_Synchronization_WordPress
	 */
	public function getSyncToWordPress()
	{
		if ($this->syncToWordPress == null) {
			$this->syncToWordPress = new NextADInt_Adi_Synchronization_WordPress(
				$this->getUserManager(),
				$this->getUserHelper(),
				$this->getConfiguration(),
				$this->getLdapConnection(),
				$this->getAttributeService(),
				$this->getRoleManager()
			);
		}

		return $this->syncToWordPress;
	}

	/**
	 * @var NextADInt_Multisite_Ui_BlogConfigurationPage
	 */
	private $blogConfigurationPage = null;

	/**
	 * @return NextADInt_Multisite_Ui_BlogConfigurationPage
	 */
	public function getBlogConfigurationPage()
	{
		if ($this->blogConfigurationPage == null) {
			$this->blogConfigurationPage = new NextADInt_Multisite_Ui_BlogConfigurationPage(
				$this->getTwigContainer(),
				$this->getBlogConfigurationController()
			);

		}

		return $this->blogConfigurationPage;
	}

	/**
	 * @var NextADInt_Adi_Ui_ConnectivityTestPage
	 */
	private $connectivityTestPage = null;

	/**
	 * @return NextADInt_Adi_Ui_ConnectivityTestPage
	 */
	public function getConnectivityTestPage()
	{
		if ($this->connectivityTestPage == null) {
			$this->connectivityTestPage = new NextADInt_Adi_Ui_ConnectivityTestPage(
				$this->getTwigContainer(),
				$this->getConfiguration(),
				$this->getLdapConnection(),
				$this->getAttributeService(),
				$this->getUserManager(),
				$this->getRoleManager()
			);
		}

		return $this->connectivityTestPage;
	}

	/**
	 * @var NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage
	 */
	private $syncToActiveDirectoryPage = null;

	/**
	 * @return NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage
	 */
	public function getSyncToActiveDirectoryPage()
	{
		if ($this->syncToActiveDirectoryPage == null) {
			$this->syncToActiveDirectoryPage = new NextADInt_Adi_Synchronization_Ui_SyncToActiveDirectoryPage(
				$this->getTwigContainer(),
				$this->getSyncToActiveDirectory(),
				$this->getConfiguration()
			);
		}

		return $this->syncToActiveDirectoryPage;
	}

	/**
	 * @var NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage
	 */
	private $syncToWordPressPage = null;

	/**
	 * @return NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage
	 */
	public function getSyncToWordPressPage()
	{
		if ($this->syncToWordPressPage == null) {
			$this->syncToWordPressPage = new NextADInt_Adi_Synchronization_Ui_SyncToWordPressPage(
				$this->getTwigContainer(),
				$this->getSyncToWordPress(),
				$this->getConfiguration()
			);
		}

		return $this->syncToWordPressPage;
	}

	/**
	 * @var NextADInt_Multisite_Ui_ProfileConfigurationPage
	 */
	private $profileConfigurationPage = null;

	/**
	 * @return NextADInt_Multisite_Ui_ProfileConfigurationPage
	 */
	public function getProfileConfigurationPage()
	{
		if ($this->profileConfigurationPage == null) {
			$this->profileConfigurationPage = new NextADInt_Multisite_Ui_ProfileConfigurationPage(
				$this->getTwigContainer(),
				$this->getBlogConfigurationController(),
				$this->getProfileConfigurationController(),
				$this->getProfileController(),
				$this->getConfiguration()
			);
		}

		return $this->profileConfigurationPage;
	}

	/**
	 * @var NextADInt_Multisite_Ui_BlogProfileRelationshipPage
	 */
	private $blogProfileRelationshipPage = null;

	/**
	 * @return NextADInt_Multisite_Ui_BlogProfileRelationshipPage
	 */
	public function getBlogProfileRelationshipPage()
	{
		if ($this->blogProfileRelationshipPage == null) {
			$this->blogProfileRelationshipPage = new NextADInt_Multisite_Ui_BlogProfileRelationshipPage(
				$this->getTwigContainer(),
				$this->getBlogProfileRelationshipController()
			);
		}

		return $this->blogProfileRelationshipPage;
	}

	/**
	 * @var NextADInt_Adi_Ui_Menu
	 */
	private $menu = null;

	/**
	 * @return NextADInt_Adi_Ui_Menu
	 */
	public function getMenu()
	{
		if ($this->menu == null) {
			$this->menu = new NextADInt_Adi_Ui_Menu(
				$this->getOptionProvider(),
				$this->getConfiguration(),
				$this->getBlogConfigurationPage(),
				$this->getConnectivityTestPage(),
				$this->getSyncToWordPressPage(),
				$this->getSyncToActiveDirectoryPage()
			);
		}

		return $this->menu;
	}

	/**
	 * @var NextADInt_Adi_Multisite_Ui_Menu
	 */
	private $multisiteMenu = null;

	/**
	 * @return NextADInt_Adi_Multisite_Ui_Menu
	 */
	public function getMultisiteMenu()
	{
		if ($this->multisiteMenu == null) {
			$this->multisiteMenu = new NextADInt_Adi_Multisite_Ui_Menu(
				$this->getOptionProvider(),
				$this->getBlogProfileRelationshipPage(),
				$this->getProfileConfigurationPage()
			);
		}

		return $this->multisiteMenu;
	}

	/**
	 * @var NextADInt_Adi_Cron_UrlTrigger
	 */
	private $urlTrigger = null;

	/**
	 * @return NextADInt_Adi_Cron_UrlTrigger
	 */
	public function getUrlTrigger()
	{
		if ($this->urlTrigger == null) {
			$this->urlTrigger = new NextADInt_Adi_Cron_UrlTrigger(
				$this->getConfiguration(),
				$this->getSyncToActiveDirectory(),
				$this->getSyncToWordPress()
			);
		}

		return $this->urlTrigger;
	}

	/**
	 * @var NextADInt_Adi_User_Ui_ExtendUserList
	 */
	private $extendUserList = null;

	/**
	 * @return NextADInt_Adi_User_Ui_ExtendUserList
	 */
	public function getExtendUserList()
	{
		if ($this->extendUserList == null) {
			$this->extendUserList = new NextADInt_Adi_User_Ui_ExtendUserList(
				$this->getConfiguration()
			);
		}

		return $this->extendUserList;
	}

	/**
	 * @var NextADInt_Adi_User_Profile_Ui_ShowLdapAttributes
	 */
	private $showLdapAttributes = null;

	/**
	 * @return NextADInt_Adi_User_Profile_Ui_ShowLdapAttributes
	 */
	public function getShowLdapAttributes()
	{
		if ($this->showLdapAttributes == null) {
			$this->showLdapAttributes = new NextADInt_Adi_User_Profile_Ui_ShowLdapAttributes(
				$this->getConfiguration(),
				$this->getTwigContainer(),
				$this->getAttributeRepository(),
				$this->getSyncToActiveDirectory()
			);
		}

		return $this->showLdapAttributes;
	}

	/**
	 * @var NextADInt_Adi_User_Profile_Ui_PreventEmailChange
	 */
	private $preventEmailChange = null;

	/**
	 * @return NextADInt_Adi_User_Profile_Ui_PreventEmailChange
	 */
	public function getPreventEmailChange()
	{
		if ($this->preventEmailChange == null) {
			$this->preventEmailChange = new NextADInt_Adi_User_Profile_Ui_PreventEmailChange(
				$this->getConfiguration()
			);
		}

		return $this->preventEmailChange;
	}

	/**
	 * @var NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization
	 */
	private $triggerActiveDirectorySynchronization = null;

	/**
	 * @return NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization
	 */
	public function getTriggerActiveDirectorySynchronization()
	{
		if ($this->triggerActiveDirectorySynchronization == null) {
			$this->triggerActiveDirectorySynchronization = new NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization(
				$this->getConfiguration(),
				$this->getSyncToActiveDirectory(),
				$this->getAttributeRepository()
			);
		}

		return $this->triggerActiveDirectorySynchronization;
	}

	/**
	 * @var NextADInt_Adi_User_Profile_Ui_PreventPasswordChange
	 */
	private $profilePreventPasswordChange = null;

	/**
	 * @return NextADInt_Adi_User_Profile_Ui_PreventPasswordChange
	 */
	public function getProfilePreventPasswordChange()
	{
		if ($this->profilePreventPasswordChange == null) {
			$this->profilePreventPasswordChange = new NextADInt_Adi_User_Profile_Ui_PreventPasswordChange(
				$this->getConfiguration(),
				$this->getUserManager()
			);
		}

		return $this->profilePreventPasswordChange;
	}

	/**
	 * @var NextADInt_Adi_Requirements
	 */
	private $requirements = null;

	/**
	 * @return NextADInt_Adi_Requirements
	 */
	public function getRequirements()
	{
		if ($this->requirements == null) {
			$this->requirements = new NextADInt_Adi_Requirements();
		}

		return $this->requirements;
	}

	/**
	 * @var NextADInt_Adi_Configuration_ImportService
	 */
	private $importService = null;

	/**
	 * @return NextADInt_Adi_Configuration_ImportService
	 */
	public function getImportService()
	{
		if ($this->importService == null) {
			$this->importService = new NextADInt_Adi_Configuration_ImportService(
				$this->getBlogConfigurationRepository(),
				$this->getConfiguration(),
				$this->getOptionProvider()
			);
		}

		return $this->importService;
	}

	/**
	 * @var NextADInt_Adi_Multisite_Site_Ui_ExtendSiteList
	 */
	private $extendSiteList = null;

	public function getExtendSiteList()
	{
		if ($this->extendSiteList == null) {
			$this->extendSiteList = new NextADInt_Adi_Multisite_Site_Ui_ExtendSiteList(
				$this->getBlogConfigurationRepository(),
				$this->getProfileRepository()
			);
		}

		return $this->extendSiteList;
	}

	/**
	 * @var NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList
	 */
	private $extendPluginList = null;

	public function getExtendPluginList()
	{
		if ($this->extendPluginList == null) {
			$this->extendPluginList = new NextADInt_Adi_Configuration_Import_Ui_ExtendPluginList(
				$this->getImportService()
			);
		}

		return $this->extendPluginList;
	}

	/**
	 * @var NextADInt_Core_Migration_Service
	 */
	private $migrationService = null;

	/**
	 * @return NextADInt_Core_Migration_Service
	 */
	public function getMigrationService()
	{
		if ($this->migrationService == null) {
			$this->migrationService = new NextADInt_Core_Migration_Service(
				$this,
				$this->getMigrationRepository()
			);
		}

		return $this->migrationService;
	}

	/**
	 * @var NextADInt_Core_Migration_Persistence_MigrationRepository
	 */
	private $migrationRepository;

	/**
	 * @return NextADInt_Core_Migration_Persistence_MigrationRepository
	 */
	public function getMigrationRepository()
	{
		if ($this->migrationRepository == null) {
			$this->migrationRepository = new NextADInt_Core_Migration_Persistence_MigrationRepository();
		}

		return $this->migrationRepository;
	}

	/**
	 * @var NextADInt_Adi_Authentication_VerificationService
	 */
	private $verificationService = null;

	public function getVerificationService()
	{
		if ($this->verificationService == null) {
			$this->verificationService = new NextADInt_Adi_Authentication_VerificationService(
				$this->getLdapConnection(), $this->getAttributeRepository()
			);
		}

		return $this->verificationService;
	}

	/**
	 * @var NextADInt_Adi_Authentication_SingleSignOn_Service
	 */
	private $ssoService = null;

	/**
	 * @return NextADInt_Adi_Authentication_SingleSignOn_Service
	 */
	public function getSsoService()
	{
		if ($this->ssoService == null) {
			$this->ssoService = new NextADInt_Adi_Authentication_SingleSignOn_Service(
				$this->getFailedLoginRepository(),
				$this->getConfiguration(),
				$this->getLdapConnection(),
				$this->getUserManager(),
				$this->getMailNotification(),
				$this->getShowBlockedMessage(),
				$this->getAttributeService(),
				$this->getRoleManager(),
				$this->getSsoValidator()
			);
		}

		return $this->ssoService;
	}

	/**
	 * @var NextADInt_Adi_Authentication_Ui_SingleSignOn
	 */
	private $ssoPage = null;

	/**
	 * @return NextADInt_Adi_Authentication_Ui_SingleSignOn
	 */
	public function getSsoPage()
	{
		if ($this->ssoPage == null) {
		    // TODO SSO Error wp-login
			$this->ssoPage = new NextADInt_Adi_Authentication_Ui_SingleSignOn();
		}

		return $this->ssoPage;
	}

	/**
	 * @var NextADInt_Adi_Authentication_SingleSignOn_Validator
	 */
	private $ssoValidator = null;

	/**
	 * @return NextADInt_Adi_Authentication_SingleSignOn_Validator
	 */
	public function getSsoValidator()
	{
		if ($this->ssoValidator == null) {
			$this->ssoValidator = new NextADInt_Adi_Authentication_SingleSignOn_Validator();
		}

		return $this->ssoValidator;
	}
}