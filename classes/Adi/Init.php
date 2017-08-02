<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Init')) {
	return;
}

/**
 * NextADInt_Adi_Init sets up all classes and their dependencies.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Christopher Klein <ckl@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_Init
{
	const NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED = "next_ad_int_plugin_has_been_enabled";

	/**
	 * @var NextADInt_Adi_Dependencies
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
		// add flag to WordPress cache for displaying the "plugin enabled" message
		set_transient(NextADInt_Adi_Init::NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED, true, 10);

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
				// ADI-393: the current user will be added to the excluded usernames.
				// At a later point we check for ID = 1 (local WordPress admin) but this user can be different from the current user (= another WordPress administrator).
				$currentUser = wp_get_current_user();
				$optionName = NextADInt_Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION;
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

		// show purchase support license information
		add_action('after_plugin_row_' . NEXT_AD_INT_PLUGIN_FILE, array($this, 'showLicensePurchaseInformation'), 99, 2);

		// do as few checks as possible
		if (($pagenow == 'plugins.php') && isset($_REQUEST['activate']) && ($_REQUEST['activate'] == 'true')) {
			// user views the 'plug-ins' page
			if (is_plugin_active(NEXT_AD_INT_PLUGIN_FILE)) {
				$optionsImporter = $this->dc()->getImportService();
				$optionsImporter->registerPostActivation();
			}
		}
	}

	/**
	 * Show purchase support license information if license has not been already set
	 *
	 * @since ADI-295
	 * @param string $file
	 * @param mixed $pluginData
	 */
	public function showLicensePurchaseInformation($file, $pluginData)
	{
		if (is_plugin_active(NEXT_AD_INT_PLUGIN_FILE)) {
			$configurationService = $this->dc()->getConfigurationService();
			$licenseKey = $configurationService->getOptionValue(NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY);

			if (empty($licenseKey)) {
                $link = '<a href="https://www.active-directory-wp.com/" style="color: #fff; text-decoration: underline">https://www.active-directory-wp.com/</a>';
                $text = esc_html__('Please purchase a valid Next Active Directory Integration support license from %s to support this plug-in.', 'next-active-directory-integration');
                $text = sprintf($text, $link);
				echo '<tr><td colspan="3" style="vertical-align: middle; background-color: #ef693e; color: #fff">' . $text . '</td>';
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
		require_once NEXT_AD_INT_PATH . '/uninstall.php';
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

		// load internationalization (i18n)
		load_plugin_textdomain('next-active-directory-integration', false, plugin_basename(NEXT_AD_INT_PATH) . '/languages');

		// ADI-354 (dme)
		$configurationService = $this->dc()->getConfiguration();
		$enableLogging = $configurationService->getOptionValue(NextADInt_Adi_Configuration_Options::LOGGER_ENABLE_LOGGING);
		$customPath = $configurationService->getOptionValue((NextADInt_Adi_Configuration_Options::LOGGER_CUSTOM_PATH));

		NextADInt_Core_Logger::initializeLogger($enableLogging, $customPath);

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

		// migration
		$this->registerMigrationHook();

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
		// do not unescape the $_POST because only numbers will be accessed
		if (NextADInt_Adi_Cron_UrlTrigger::getSyncMode($_POST) !== false) {
			$this->registerUrlTriggerHook();

			// further hooks must not be executed b/c the trigger is the only runnable method
			return false;
		}

		if ($this->isSsoEnabled()) {
			$this->registerSsoHooks();
		}

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
	 * Register hooks during WordPress load
	 */
	public function registerSsoHooks()
	{
		$isOnLoginPage = $this->isOnLoginPage();

		// register sso
		if ($isOnLoginPage) {
			$this->dc()->getSsoPage()->register();
		}

		$this->dc()->getSsoService()->register();
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
	 * @return NextADInt_Adi_Dependencies
	 */
	function dc()
	{
		if ($this->dependencyContainer == null) {
			$this->dependencyContainer = NextADInt_Adi_Dependencies::getInstance();;
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
		return NextADInt_Multisite_Util::isOnNetworkDashboard();
	}

	/**
	 * Return if ADI is active for the current blog
	 * @return bool
	 */
	function isActive()
	{
		return (bool)$this->dc()->getConfiguration()->getOptionValue(NextADInt_Adi_Configuration_Options::IS_ACTIVE);
	}

	/**
	 * Return if SSO is enabled for the current blog.
	 *
	 * @return bool
	 */
	function isSsoEnabled()
	{
		return (bool)$this->dc()->getConfiguration()->getOptionValue(NextADInt_Adi_Configuration_Options::SSO_ENABLED);
	}


	/**
	 * Return true if the user is currently on the login page or executes a log in.
	 * The method executes the next_adi_int_auth_enable_login_check filter to check if
	 * any other login plug-in wants to hook into the login process.
	 *
	 * @return bool
	 */
	public function isOnLoginPage()
	{
		$r = false;

		$page = $_SERVER['PHP_SELF'];
		$required = "wp-login.php";
		$isOnWpLogin = substr($page, -strlen($required)) == $required;
		$isOnXmlRpc = strpos($page, 'xmlrpc.php') !== false;

		if ($isOnWpLogin || $isOnXmlRpc) {
			$r = true;
		}

		$r = apply_filters(NEXT_AD_INT_PREFIX . 'auth_enable_login_check', $r);

		return $r;
	}
}