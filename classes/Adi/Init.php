<?php

/**
 * Adi_Init sets up all classes and their dependencies.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Christopher Klein <ckl@neos-it.de>
 *
 * @access public
 */
class Adi_Init
{
	/**
	 * @var Adi_Dependencies
	 */
	private $dependencyContainer;

	/**
	 * Has the plug-in been initialized
	 * @var bool
	 */
	private $initialized = false;

	// ---
	// WordPress plug-in lifecycle (activation/uninstall)
	// ---

	/**
	 * This function will be executed when the plugin is activated.
	 * The 'activation' hook is called by AJAX so you can not output anything. Use {#postActivation()} to register any UI hooks.
	 *
	 * @codeCoverageIgnore
	 */
	public function activation()
	{
		Core_Logger::displayAndLogMessages();
		Core_Logger::setLevel(LoggerLevel::getLevelError());

		$requirements = $this->dc()->getRequirements();

		if ($requirements->check(true, true)) {
			$optionsImporter = $this->dc()->getImportService();
			$optionsImporter->register();

			// the profile entry is added for network-wide and single site installations
			$profileId = $this->dc()->getProfileRepository()->insertDefaultProfile();
			$optionsImporter->autoImport();

			// ADI-255: Migrate the previous "adi_samaccountname" attribute of ADI 1.x on first run
			$userManager = $this->dc()->getUserManager();
			$userManager->migratePreviousVersion();

			if (is_numeric($profileId)) {
				$currentUser = wp_get_current_user();
				$optionName = Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION;
				$optionValue = $currentUser->user_login;

				if (is_multisite()) {
					$this->dc()->getProfileConfigurationRepository()->persistSanitizedValue($profileId, $optionName, $optionValue);
				} else {
					$this->dc()->getBlogConfigurationRepository()->persistSanitizedValue(0, $optionName, $optionValue);
				}
			}
		}
	}

	/**
	 * Register callbacks after this plug-in has been activated and the 'plugins' page has been reloaded.
	 */
	public function postActivation()
	{
		global $pagenow;

		// do as few checks as possible
		if (($pagenow == 'plugins.php') && isset($_REQUEST['activate']) && ($_REQUEST['activate'] == 'true')) {
			// user views the 'plug-ins' page
			if (is_plugin_active('active-directory-integration2/index.php')) {
				$optionsImporter = $this->dc()->getImportService();
				$optionsImporter->registerPostActivation();
			}
		}
	}

	/**
	 * This function will be executed when the plugin is deactivated.
	 *
	 * @codeCoverageIgnore
	 */
	public static function uninstall()
	{
		require_once ADI_PATH . DIRECTORY_SEPARATOR . 'uninstall.php';
	}

	// ---
	// main dependencies
	// ---

	/**
	 * Initialize required components like logging and i18n
	 * @access package
	 */
	function initialize()
	{
		if ($this->initialized) {
			return;
		}

		Core_Logger::logMessages();
		Core_Logger::setLevel(LoggerLevel::getLevelAll());

		// load internationalization (i18n)
		load_plugin_textdomain(ADI_I18N, false, ADI_PLUGIN_NAME . '/languages/');

		$this->initialized = true;
	}

	/**
	 * Is called after all plug-ins have been loaded (hook 'plugins_loaded') to gain access to WordPress functions.
	 * This method mostly delegates to the register* methods in this class. Only required hooks are registered to
	 * minimize the memory footprint and loading times.
	 *
	 * This method will not proceed if the user is currently viewing the Multisite network dashboard.
	 *
	 * @codeCoverageIgnore
	 */
	public function run()
	{
		// this method won't be executed when the Multisite network dashboard is shown
		if ($this->isOnNetworkDashboard()) {
			return;
		}

		$this->initialize();

		if ($this->isActive()) {
			// only with an active ADI profile the core has to be registered
			if (true !== $this->registerCore()) {
				// the core has not been completely initialized so we do not have to proceed
				return;
			}
		}

		// the menu must be activated so that in a multisite setup the blog administrator can enable/disable ADI
		$this->registerAdministrationMenu();
	}

	// ---
	// single site environment
	// ----

	/**
	 * Register core functionality of ADI.
	 *
	 * @access package
	 * @return bool true if core registration has succeeded
	 */
	function registerCore()
	{
		// if the current request should trigger a synchronization of Active Directory or WordPress
		if (Adi_Cron_UrlTrigger::getSyncMode($_POST) !== false) {
			$this->registerUrlTriggerHook();

			// further hooks must not be executed b/c the trigger is the only runnable method
			return false;
		}

		// TODO register SSO as soon as it is available
		if ($this->isOnLoginPage()) {
			$this->registerLoginHooks();

			// further hooks must not be executed
			return false;
		}

		$currentUserId = wp_get_current_user()->ID;

		if (!$currentUserId) {
			// the current user is not logged in so further hooks must not be processed
			return false;
		}

		// log out disabled user
		if ($this->dc()->getUserManager()->isDisabled($currentUserId)) {
			wp_logout();

			return false;
		}

		// migration
		$this->registerMigrationHook();

		// shared hooks
		$this->registerSharedAdministrationHooks();

		// register user profile hooks
		$this->registerUserProfileHooks();

		// register generic administration hooks
		$this->registerAdministrationHooks();

		return true;
	}

	/**
	 * Register hooks used for migrations
	 */
	protected function registerMigrationHook()
	{
		$this->dc()->getMigrationService()->register();
	}

	/**
	 * It registers the callbacks which are only required in a multisite setup and when viewing the network dashboard
	 */
	public function runMultisite()
	{
		// only network dashboard views are relevant
		if (!$this->isOnNetworkDashboard()) {
			return;
		}

		$this->initialize();

		// migration
		$this->registerMigrationHook();

		// shared hooks
		$this->registerSharedAdministrationHooks();

		$this->dc()->getExtendSiteList()->register();
		$this->dc()->getMultisiteMenu()->register();
	}

	/**
	 * Register hooks used both in network and site administration
	 */
	public function registerSharedAdministrationHooks()
	{
		$this->dc()->getExtendPluginList()->register();
	}

	/**
	 * Register hooks during the login procedure
	 */
	public function registerLoginHooks()
	{
		// register authentication
		$this->dc()->getLoginService()->register();
		// register custom password validation
		$this->dc()->getPasswordValidationService()->register();
	}

	/**
	 * Register hook for synchronization
	 */
	public function registerUrlTriggerHook()
	{
		// make URL listener for triggering synchronization available
		$this->dc()->getUrlTrigger()->register();
	}

	/**
	 * Register hooks during the view of the user's profile page
	 */
	public function registerUserProfileHooks()
	{
		// show LDAP attributes in users's profile
		$this->dc()->getShowLdapAttributes()->register();
		// prevent change of email address
		$this->dc()->getPreventEmailChange()->register();
		// prevent change of password
		$this->dc()->getProfilePreventPasswordChange()->register();
		// after persisting the profile the Active Directory synchronization should be performed
		$this->dc()->getTriggerActiveDirectorySynchronization()->register();
		// disable user profile
		$this->dc()->getProvideDisableUserOption()->register();
	}

	/**
	 * Register administration menu in single site mode
	 */
	public function registerAdministrationMenu()
	{
		// link menu
		$this->dc()->getMenu()->register();
	}

	/**
	 * Register generic administration hooks
	 */
	public function registerAdministrationHooks()
	{
		// extend the admin user list with custom columns
		$this->dc()->getExtendUserList()->register();
	}

	// ---
	// utility methods
	// ---

	/**
	 * Return the dependency container.
	 *
	 * @access private
	 * @return Adi_Dependencies
	 */
	function dc()
	{
		if ($this->dependencyContainer == null) {
			$this->dependencyContainer = new Adi_Dependencies();
		}

		return $this->dependencyContainer;
	}

	/**
	 * Return true if the current request is for the Multisite network view.
	 *
	 * @return bool
	 */
	function isOnNetworkDashboard()
	{
		return Multisite_Util::isOnNetworkDashboard();
	}

	/**
	 * Return if ADI is active for the current blog
	 * @return bool
	 */
	function isActive()
	{
		return (bool)$this->dc()->getConfiguration()->getOptionValue(Adi_Configuration_Options::IS_ACTIVE);
	}

	/**
	 * Return true if the user is currently on the login page or executes a log in
	 * @return bool
	 */
	public function isOnLoginPage()
	{
		$page = $_SERVER['PHP_SELF'];
		$required = "wp-login.php";

		return (substr($page, -strlen($required)) == $required);
	}

}