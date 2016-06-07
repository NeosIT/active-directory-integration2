<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Dependencies')) {
	return;
}

/**
 * Contains all dependencies with lazy loading to reduce the amount of loaded classes during the plug-in runtime.
 * We don't use a proprietary DI container to keep compatibility with PHP 5.3.
 *
 * @author Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class Adi_Dependencies
{
	/**
	 * @var Core_Persistence_WordPressRepository
	 */
	private $wordPressRepository = null;

	/**
	 * @return Core_Persistence_WordPressRepository
	 */
	public function getWordPressRepository()
	{
		if ($this->wordPressRepository == null) {
			$this->wordPressRepository = new Core_Persistence_WordPressRepository();

		}

		return $this->wordPressRepository;
	}

	/**
	 * @var Multisite_Option_Sanitizer
	 */
	private $sanitizer = null;

	/**
	 * @return Multisite_Option_Sanitizer
	 */
	public function getSanitizer()
	{
		if ($this->sanitizer == null) {
			$this->sanitizer = new Multisite_Option_Sanitizer();
		}

		return $this->sanitizer;
	}

	/**
	 * @var Core_Encryption
	 */
	private $encryptionHandler = null;

	/**
	 * @return Core_Encryption
	 */
	public function getEncryptionHandler()
	{
		if ($this->encryptionHandler == null) {
			$this->encryptionHandler = new Core_Encryption();
		}

		return $this->encryptionHandler;
	}

	/**
	 * @var Multisite_Option_Provider
	 */
	private $optionProvider = null;

	/**
	 * @return Adi_Configuration_Options|Multisite_Option_Provider
	 */
	public function getOptionProvider()
	{
		if ($this->optionProvider == null) {
			$this->optionProvider = new Adi_Configuration_Options();
		}

		return $this->optionProvider;
	}

	/**
	 * @var Multisite_Configuration_Service
	 */
	private $configurationService;

	/**
	 * @return Multisite_Configuration_Service
	 */
	public function getConfigurationService()
	{
		if ($this->configurationService == null) {
			$this->configurationService = new Multisite_Configuration_Service(
				$this->blogConfigurationRepository,
				$this->profileConfigurationRepository,
				$this->profileRepository
			);
		}

		return $this->configurationService;
	}

	/**
	 * @var Multisite_Configuration_Persistence_BlogConfigurationRepository
	 */
	private $blogConfigurationRepository = null;

	/**
	 * @return Multisite_Configuration_Persistence_BlogConfigurationRepository
	 */
	public function getBlogConfigurationRepository()
	{
		if ($this->blogConfigurationRepository == null) {
			$this->blogConfigurationRepository = new Multisite_Configuration_Persistence_BlogConfigurationRepository(
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
	 * @var Multisite_Configuration_Persistence_ProfileConfigurationRepository
	 */
	private $profileConfigurationRepository = null;

	/**
	 * @return Multisite_Configuration_Persistence_ProfileConfigurationRepository
	 */
	public function getProfileConfigurationRepository()
	{
		if ($this->profileConfigurationRepository == null) {
			$this->profileConfigurationRepository = new Multisite_Configuration_Persistence_ProfileConfigurationRepository(
				$this->getSanitizer(),
				$this->getEncryptionHandler(),
				$this->getOptionProvider());
		}

		return $this->profileConfigurationRepository;
	}

	/**
	 * @var Multisite_Configuration_Service
	 */
	private $configuration = null;

	/**
	 * @return Multisite_Configuration_Service
	 */
	public function getConfiguration()
	{
		if ($this->configuration == null) {
			$this->configuration = new Multisite_Configuration_Service(
				$this->getBlogConfigurationRepository(),
				$this->getProfileConfigurationRepository(),
				$this->getProfileRepository()
			);
		}

		return $this->configuration;
	}

	/**
	 * @var Multisite_Configuration_Persistence_ProfileRepository
	 */
	private $profileRepository = null;

	/**
	 * @return Multisite_Configuration_Persistence_ProfileRepository
	 */
	public function getProfileRepository()
	{
		if ($this->profileRepository == null) {
			$this->profileRepository = new Multisite_Configuration_Persistence_ProfileRepository(
				$this->getProfileConfigurationRepository(),
				$this->getBlogConfigurationRepository(),
				$this->getWordPressRepository(),
				$this->getOptionProvider());
		}

		return $this->profileRepository;

	}

	/**
	 * @var Multisite_Configuration_Persistence_DefaultProfileRepository
	 */
	private $defaultProfileRepository = null;

	/**
	 * @return Multisite_Configuration_Persistence_DefaultProfileRepository
	 */
	public function getDefaultProfileRepository()
	{
		if ($this->defaultProfileRepository == null) {
			$this->defaultProfileRepository = new Multisite_Configuration_Persistence_DefaultProfileRepository();
		}

		return $this->defaultProfileRepository;
	}

	/**
	 * @return Adi_Authentication_Persistence_FailedLoginRepository
	 */
	private $failedLoginRepository = null;

	/**
	 * @return Adi_Authentication_Persistence_FailedLoginRepository|null
	 */
	public function getFailedLoginRepository()
	{
		if ($this->failedLoginRepository == null) {
			$this->failedLoginRepository = new Adi_Authentication_Persistence_FailedLoginRepository();
		}

		return $this->failedLoginRepository;
	}

	/**
	 * @var Ldap_Attribute_Repository
	 */
	private $attributeRepository = null;

	/**
	 * @return Ldap_Attribute_Repository
	 */
	public function getAttributeRepository()
	{
		if ($this->attributeRepository == null) {
			$this->attributeRepository = new Ldap_Attribute_Repository(
				$this->getConfiguration()
			);
		}

		return $this->attributeRepository;
	}

	/**
	 * @var Ldap_Attribute_Service
	 */
	private $attributeService = null;

	/**
	 * @return Ldap_Attribute_Service
	 */
	public function getAttributeService()
	{
		if ($this->attributeService == null) {
			$this->attributeService = new Ldap_Attribute_Service(
				$this->getLdapConnection(),
				$this->getAttributeRepository()
			);
		}

		return $this->attributeService;
	}

	/**
	 * @var Adi_User_Helper
	 */
	private $userHelper = null;

	/**
	 * @return Adi_User_Helper
	 */
	public function getUserHelper()
	{
		if ($this->userHelper == null) {
			$this->userHelper = new Adi_User_Helper(
				$this->getConfiguration()
			);
		}

		return $this->userHelper;
	}

	/**
	 * @var Ldap_Connection
	 */
	private $ldapConnection = null;

	/**
	 * @return Ldap_Connection
	 */
	public function getLdapConnection()
	{
		if ($this->ldapConnection == null) {
			$this->ldapConnection = new Ldap_Connection(
				$this->getConfiguration()
			);
		}

		return $this->ldapConnection;
	}

	/**
	 * @var Adi_Role_Manager
	 */
	private $roleManager = null;

	/**
	 * @return Adi_Role_Manager
	 */
	public function getRoleManager()
	{
		if ($this->roleManager == null) {
			$this->roleManager = new Adi_Role_Manager(
				$this->getConfiguration(),
				$this->getLdapConnection()
			);
		}

		return $this->roleManager;
	}

	/**
	 * @var Adi_User_Meta_Persistence_Repository
	 */
	private $userMetaRepository;

	/**
	 * @return Adi_User_Meta_Persistence_Repository
	 */
	public function getUserMetaRepository()
	{
		if ($this->userMetaRepository == null) {
			$this->userMetaRepository = new Adi_User_Meta_Persistence_Repository();
		}

		return $this->userMetaRepository;
	}

	/**
	 * @var Adi_User_Persistence_Repository
	 */
	private $userRepository;

	/**
	 * @return Adi_User_Persistence_Repository
	 */
	public function getUserRepository()
	{
		if ($this->userRepository == null) {
			$this->userRepository = new Adi_User_Persistence_Repository();
		}

		return $this->userRepository;
	}

	/**
	 * @var Adi_User_Manager
	 */
	private $userManager = null;

	/**
	 * @return Adi_User_Manager
	 */
	public function getUserManager()
	{
		if ($this->userManager == null) {
			$this->userManager = new Adi_User_Manager(
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
	 * @var Multisite_View_TwigContainer
	 */
	private $twigContainer = null;

	/**
	 * @return Multisite_View_TwigContainer
	 */
	public function getTwigContainer()
	{
		if ($this->twigContainer == null) {
			$this->twigContainer = new Multisite_View_TwigContainer(
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
	 * @var Adi_User_Profile_Ui_ProvideDisableUserOption
	 */
	private $provideDisableUserOption = null;

	/**
	 * @return Adi_User_Profile_Ui_ProvideDisableUserOption
	 */
	public function getProvideDisableUserOption()
	{
		if ($this->provideDisableUserOption == null) {
			$this->provideDisableUserOption = new Adi_User_Profile_Ui_ProvideDisableUserOption(
				$this->getTwigContainer(),
				$this->getUserManager()
			);
		}

		return $this->provideDisableUserOption;
	}

	/**
	 * @var Adi_Mail_Notification
	 */
	private $mailNotification = null;

	/**
	 * @return Adi_Mail_Notification
	 */
	public function getMailNotification()
	{
		if ($this->mailNotification == null) {
			$this->mailNotification = new Adi_Mail_Notification(
				$this->getConfiguration(),
				$this->getLdapConnection()
			);
		}

		return $this->mailNotification;
	}

	/**
	 * @var Adi_Authentication_Ui_ShowBlockedMessage
	 */
	private $showBlockedMessage = null;

	/**
	 * @return Adi_Authentication_Ui_ShowBlockedMessage
	 */
	public function getShowBlockedMessage()
	{
		if ($this->showBlockedMessage == null) {
			$this->showBlockedMessage = new Adi_Authentication_Ui_ShowBlockedMessage(
				$this->getConfiguration(),
				$this->getTwigContainer()
			);
		}

		return $this->showBlockedMessage;
	}

	/**
	 * @var Adi_Authentication_LoginService
	 */
	private $loginService = null;

	/**
	 * @return Adi_Authentication_LoginService
	 */
	public function getLoginService()
	{
		if ($this->loginService == null) {
			$this->loginService = new Adi_Authentication_LoginService(
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
	 * @var Adi_Authentication_PasswordValidationService
	 */
	private $passwordValidationService = null;

	/**
	 * @return Adi_Authentication_PasswordValidationService
	 */
	public function getPasswordValidationService()
	{
		if ($this->passwordValidationService == null) {
			$this->passwordValidationService = new Adi_Authentication_PasswordValidationService(
				$this->getLoginService(),
				$this->getConfiguration()
			);
		}

		return $this->passwordValidationService;
	}

	/**
	 * @var Multisite_Ui_BlogConfigurationController
	 */
	private $blogConfigurationController = null;

	/**
	 * @return Multisite_Ui_BlogConfigurationController
	 */
	public function getBlogConfigurationController()
	{
		if ($this->blogConfigurationController == null) {
			$this->blogConfigurationController = new Multisite_Ui_BlogConfigurationController(
				$this->getBlogConfigurationRepository(),
				$this->getOptionProvider()
			);
		}

		return $this->blogConfigurationController;
	}

	/**
	 * @var Multisite_Ui_BlogProfileRelationshipController
	 */
	private $blogProfileRelationshipController = null;

	/**
	 * @return Multisite_Ui_BlogProfileRelationshipController
	 */
	public function getBlogProfileRelationshipController()
	{
		if ($this->blogProfileRelationshipController == null) {
			$this->blogProfileRelationshipController = new Multisite_Ui_BlogProfileRelationshipController(
				$this->getBlogConfigurationRepository(),
				$this->getProfileRepository(),
				$this->getDefaultProfileRepository()
			);
		}

		return $this->blogProfileRelationshipController;
	}

	/**
	 * @var Multisite_Ui_ProfileController
	 */
	private $profileController = null;

	/**
	 * @return Multisite_Ui_ProfileController
	 */
	public function getProfileController()
	{
		if ($this->profileController == null) {
			$this->profileController = new Multisite_Ui_ProfileController(
				$this->getProfileRepository(),
				$this->getBlogConfigurationRepository(),
				$this->getDefaultProfileRepository()
			);
		}

		return $this->profileController;
	}

	/**
	 * @var Multisite_Ui_ProfileConfigurationController
	 */
	private $profileConfigurationController = null;

	/**
	 * @return Multisite_Ui_ProfileConfigurationController
	 */
	public function getProfileConfigurationController()
	{
		if ($this->profileConfigurationController == null) {
			$this->profileConfigurationController = new Multisite_Ui_ProfileConfigurationController(
				$this->getProfileConfigurationRepository(),
				$this->getOptionProvider()
			);
		}

		return $this->profileConfigurationController;
	}

	/**
	 * @var Adi_Synchronization_ActiveDirectory
	 */
	private $syncToActiveDirectory = null;

	/**
	 * @return Adi_Synchronization_ActiveDirectory
	 */
	public function getSyncToActiveDirectory()
	{
		if ($this->syncToActiveDirectory == null) {
			$this->syncToActiveDirectory = new Adi_Synchronization_ActiveDirectory(
				$this->getAttributeService(),
				$this->getConfiguration(),
				$this->getLdapConnection()
			);
		}

		return $this->syncToActiveDirectory;
	}

	/**
	 * @var Adi_Synchronization_WordPress
	 */
	private $syncToWordPress = null;

	/**
	 * @return Adi_Synchronization_WordPress
	 */
	public function getSyncToWordPress()
	{
		if ($this->syncToWordPress == null) {
			$this->syncToWordPress = new Adi_Synchronization_WordPress(
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
	 * @var Multisite_Ui_BlogConfigurationPage
	 */
	private $blogConfigurationPage = null;

	/**
	 * @return Multisite_Ui_BlogConfigurationPage
	 */
	public function getBlogConfigurationPage()
	{
		if ($this->blogConfigurationPage == null) {
			$this->blogConfigurationPage = new Multisite_Ui_BlogConfigurationPage(
				$this->getTwigContainer(),
				$this->getBlogConfigurationController()
			);

		}

		return $this->blogConfigurationPage;
	}

	/**
	 * @var Adi_Ui_ConnectivityTestPage
	 */
	private $connectivityTestPage = null;

	/**
	 * @return Adi_Ui_ConnectivityTestPage
	 */
	public function getConnectivityTestPage()
	{
		if ($this->connectivityTestPage == null) {
			$this->connectivityTestPage = new Adi_Ui_ConnectivityTestPage(
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
	 * @var Adi_Synchronization_Ui_SyncToActiveDirectoryPage
	 */
	private $syncToActiveDirectoryPage = null;

	/**
	 * @return Adi_Synchronization_Ui_SyncToActiveDirectoryPage
	 */
	public function getSyncToActiveDirectoryPage()
	{
		if ($this->syncToActiveDirectoryPage == null) {
			$this->syncToActiveDirectoryPage = new Adi_Synchronization_Ui_SyncToActiveDirectoryPage(
				$this->getTwigContainer(),
				$this->getSyncToActiveDirectory(),
				$this->getConfiguration()
			);
		}

		return $this->syncToActiveDirectoryPage;
	}

	/**
	 * @var Adi_Synchronization_Ui_SyncToWordPressPage
	 */
	private $syncToWordPressPage = null;

	/**
	 * @return Adi_Synchronization_Ui_SyncToWordPressPage
	 */
	public function getSyncToWordPressPage()
	{
		if ($this->syncToWordPressPage == null) {
			$this->syncToWordPressPage = new Adi_Synchronization_Ui_SyncToWordPressPage(
				$this->getTwigContainer(),
				$this->getSyncToWordPress(),
				$this->getConfiguration()
			);
		}

		return $this->syncToWordPressPage;
	}

	/**
	 * @var Multisite_Ui_ProfileConfigurationPage
	 */
	private $profileConfigurationPage = null;

	/**
	 * @return Multisite_Ui_ProfileConfigurationPage
	 */
	public function getProfileConfigurationPage()
	{
		if ($this->profileConfigurationPage == null) {
			$this->profileConfigurationPage = new Multisite_Ui_ProfileConfigurationPage(
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
	 * @var Multisite_Ui_BlogProfileRelationshipPage
	 */
	private $blogProfileRelationshipPage = null;

	/**
	 * @return Multisite_Ui_BlogProfileRelationshipPage
	 */
	public function getBlogProfileRelationshipPage()
	{
		if ($this->blogProfileRelationshipPage == null) {
			$this->blogProfileRelationshipPage = new Multisite_Ui_BlogProfileRelationshipPage(
				$this->getTwigContainer(),
				$this->getBlogProfileRelationshipController()
			);
		}

		return $this->blogProfileRelationshipPage;
	}

	/**
	 * @var Adi_Ui_Menu
	 */
	private $menu = null;

	/**
	 * @return Adi_Ui_Menu
	 */
	public function getMenu()
	{
		if ($this->menu == null) {
			$this->menu = new Adi_Ui_Menu(
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
	 * @var Adi_Multisite_Ui_Menu
	 */
	private $multisiteMenu = null;

	/**
	 * @return Adi_Multisite_Ui_Menu
	 */
	public function getMultisiteMenu()
	{
		if ($this->multisiteMenu == null) {
			$this->multisiteMenu = new Adi_Multisite_Ui_Menu(
				$this->getOptionProvider(),
				$this->getBlogProfileRelationshipPage(),
				$this->getProfileConfigurationPage()
			);
		}

		return $this->multisiteMenu;
	}

	/**
	 * @var Adi_Cron_UrlTrigger
	 */
	private $urlTrigger = null;

	/**
	 * @return Adi_Cron_UrlTrigger
	 */
	public function getUrlTrigger()
	{
		if ($this->urlTrigger == null) {
			$this->urlTrigger = new Adi_Cron_UrlTrigger(
				$this->getConfiguration(),
				$this->getSyncToActiveDirectory(),
				$this->getSyncToWordPress()
			);
		}

		return $this->urlTrigger;
	}

	/**
	 * @var Adi_User_Ui_ExtendUserList
	 */
	private $extendUserList = null;

	/**
	 * @return Adi_User_Ui_ExtendUserList
	 */
	public function getExtendUserList()
	{
		if ($this->extendUserList == null) {
			$this->extendUserList = new Adi_User_Ui_ExtendUserList(
				$this->getConfiguration()
			);
		}

		return $this->extendUserList;
	}

	/**
	 * @var Adi_User_Profile_Ui_ShowLdapAttributes
	 */
	private $showLdapAttributes = null;

	/**
	 * @return Adi_User_Profile_Ui_ShowLdapAttributes
	 */
	public function getShowLdapAttributes()
	{
		if ($this->showLdapAttributes == null) {
			$this->showLdapAttributes = new Adi_User_Profile_Ui_ShowLdapAttributes(
				$this->getConfiguration(),
				$this->getTwigContainer(),
				$this->getAttributeRepository(),
				$this->getSyncToActiveDirectory()
			);
		}

		return $this->showLdapAttributes;
	}

	/**
	 * @var Adi_User_Profile_Ui_PreventEmailChange
	 */
	private $preventEmailChange = null;

	/**
	 * @return Adi_User_Profile_Ui_PreventEmailChange
	 */
	public function getPreventEmailChange()
	{
		if ($this->preventEmailChange == null) {
			$this->preventEmailChange = new Adi_User_Profile_Ui_PreventEmailChange(
				$this->getConfiguration()
			);
		}

		return $this->preventEmailChange;
	}

	/**
	 * @var Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization
	 */
	private $triggerActiveDirectorySynchronization = null;

	/**
	 * @return Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization
	 */
	public function getTriggerActiveDirectorySynchronization()
	{
		if ($this->triggerActiveDirectorySynchronization == null) {
			$this->triggerActiveDirectorySynchronization = new Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization(
				$this->getConfiguration(),
				$this->getSyncToActiveDirectory(),
				$this->getAttributeRepository()
			);
		}

		return $this->triggerActiveDirectorySynchronization;
	}

	/**
	 * @var Adi_User_Profile_Ui_PreventPasswordChange
	 */
	private $profilePreventPasswordChange = null;

	/**
	 * @return Adi_User_Profile_Ui_PreventPasswordChange
	 */
	public function getProfilePreventPasswordChange()
	{
		if ($this->profilePreventPasswordChange == null) {
			$this->profilePreventPasswordChange = new Adi_User_Profile_Ui_PreventPasswordChange(
				$this->getConfiguration(),
				$this->getUserManager()
			);
		}

		return $this->profilePreventPasswordChange;
	}

	/**
	 * @var Adi_Requirements
	 */
	private $requirements = null;

	/**
	 * @return Adi_Requirements
	 */
	public function getRequirements()
	{
		if ($this->requirements == null) {
			$this->requirements = new Adi_Requirements();
		}

		return $this->requirements;
	}

	/**
	 * @var Adi_Configuration_ImportService
	 */
	private $importService = null;

	/**
	 * @return Adi_Configuration_ImportService
	 */
	public function getImportService()
	{
		if ($this->importService == null) {
			$this->importService = new Adi_Configuration_ImportService(
				$this->getBlogConfigurationRepository(),
				$this->getConfiguration(),
				$this->getOptionProvider()
			);
		}

		return $this->importService;
	}

	/**
	 * @var Adi_Multisite_Site_Ui_ExtendSiteList
	 */
	private $extendSiteList = null;

	public function getExtendSiteList()
	{
		if ($this->extendSiteList == null) {
			$this->extendSiteList = new Adi_Multisite_Site_Ui_ExtendSiteList(
				$this->getBlogConfigurationRepository(),
				$this->getProfileRepository()
			);
		}

		return $this->extendSiteList;
	}

	/**
	 * @var Adi_Configuration_Import_Ui_ExtendPluginList
	 */
	private $extendPluginList = null;

	public function getExtendPluginList()
	{
		if ($this->extendPluginList == null) {
			$this->extendPluginList = new Adi_Configuration_Import_Ui_ExtendPluginList(
				$this->getImportService()
			);
		}

		return $this->extendPluginList;
	}

	/**
	 * @var Core_Migration_Service
	 */
	private $migrationService = null;

	/**
	 * @return Core_Migration_Service
	 */
	public function getMigrationService()
	{
		if ($this->migrationService == null) {
			$this->migrationService = new Core_Migration_Service(
				$this,
				$this->getMigrationRepository()
			);
		}

		return $this->migrationService;
	}

	/**
	 * @var Core_Migration_Persistence_MigrationRepository
	 */
	private $migrationRepository;

	/**
	 * @return Core_Migration_Persistence_MigrationRepository
	 */
	public function getMigrationRepository()
	{
		if ($this->migrationRepository == null) {
			$this->migrationRepository = new Core_Migration_Persistence_MigrationRepository();
		}

		return $this->migrationRepository;
	}

	/**
	 * @var Adi_Authentication_VerificationService
	 */
	private $verificationService = null;

	public function getVerificationService()
	{
		if ($this->verificationService == null) {
			$this->verificationService = new Adi_Authentication_VerificationService(
				$this->getLdapConnection(), $this->getAttributeRepository()
			);
		}

		return $this->verificationService;
	}

	/**
	 * @var Adi_Authentication_SingleSignOn_Service
	 */
	private $ssoService = null;

	/**
	 * @return Adi_Authentication_SingleSignOn_Service
	 */
	public function getSsoService()
	{
		if ($this->ssoService == null) {
			$this->ssoService = new Adi_Authentication_SingleSignOn_Service(
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
	 * @var Adi_Authentication_Ui_SingleSignOn
	 */
	private $ssoPage = null;

	/**
	 * @return Adi_Authentication_Ui_SingleSignOn
	 */
	public function getSsoPage()
	{
		if ($this->ssoPage == null) {
			$this->ssoPage = new Adi_Authentication_Ui_SingleSignOn();
		}

		return $this->ssoPage;
	}

	/**
	 * @var Adi_Authentication_SingleSignOn_Validator
	 */
	private $ssoValidator = null;

	/**
	 * @return Adi_Authentication_SingleSignOn_Validator
	 */
	public function getSsoValidator()
	{
		if ($this->ssoValidator == null) {
			$this->ssoValidator = new Adi_Authentication_SingleSignOn_Validator();
		}

		return $this->ssoValidator;
	}
}