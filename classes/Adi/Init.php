<?php
if ( ! defined('ABSPATH')) {
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
				$optionName  = NextADInt_Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION;
				$optionValue = $currentUser->user_login;

				if (is_multisite()) {
					$this->dc()->getProfileConfigurationRepository()->persistSanitizedValue($profileId, $optionName,
						$optionValue);
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
			if (is_plugin_active(NEXT_AD_INT_PLUGIN_FILE)) {
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
		load_plugin_textdomain('next-active-directory-integration', false,
			plugin_basename(NEXT_AD_INT_PATH) . '/languages');

		// ADI-354 (dme)
		$configurationService = $this->dc()->getConfiguration();
		$enableLogging        = $configurationService->getOptionValue(NextADInt_Adi_Configuration_Options::LOGGER_ENABLE_LOGGING);
		$customPath           = $configurationService->getOptionValue((NextADInt_Adi_Configuration_Options::LOGGER_CUSTOM_PATH));

		NextADInt_Core_Logger::initializeLogger($enableLogging, $customPath);

		$this->initialized = true;
	}

	/**
	 * Is called after all plug-ins have been loaded (hook 'plugins_loaded') to gain access to WordPress functions.
	 * This method mostly delegates to the register* methods in this class. Only required hooks are registered to
	 * minimize the memory footprint and loading times.
	 *
	 * This method will not proceed if the user is currently viewing the Multisite network dashboard.
	 */
	public function run()
	{
	    $this->registerHooks();

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
                $this->finishRegistration();

				// the core has not been completely initialized so we do not have to proceed
				return;
			}
		}

		// the menu must be activated so that in a multisite setup the blog administrator can enable/disable ADI
		$this->registerAdministrationMenu();

		$this->finishRegistration();
	}

    /**
     * Register any hooks.
     * TODO: Replace current registration mechanism with WordPress' hooks architecture.
     */
	public function registerHooks()
    {
        add_action(NEXT_AD_INT_PREFIX . 'register_form_login_services', array($this, 'registerFormLoginServices'), 10, 0);
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

		// register all required authorization and authentication hooks
		if (!$this->registerAuthentication()) {
			// further hooks must not be executed if registerAuthentication returns false (should only happen if on login page)
			return false;
		}

		$currentUserId = wp_get_current_user()->ID; // Attribute ID will show 0 if there is no user.

		if ( ! $currentUserId) {
			// the current user is not logged in so further hooks must not be processed
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
     * Signal that NADI registration has been finished. It simply calls the WordPress action 'nadi_loaded'
     * @since 2.1.8
     * @see ADI-672
     */
	public function finishRegistration() {
	    do_action('next_ad_int_loaded');
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
		if ( ! $this->isOnNetworkDashboard()) {
			return;
		}

		$this->initialize();

		// migration
		$this->registerMigrationHook();

		// shared hooks
		$this->registerSharedAdministrationHooks();

		$this->dc()->getExtendSiteList()->register();
		$this->dc()->getMultisiteMenu()->register();
        $this->finishRegistration();
	}

	/**
	 * Register hooks used both in network and site administration
	 */
	public function registerSharedAdministrationHooks()
	{
		$this->dc()->getExtendPluginList()->register();
	}

	/**
	 * Register all required authentication and authorization hooks.
	 *
	 * @return bool
	 */
	public function registerAuthentication() {
		$isOnLoginPage = $this->isOnLoginPage();
		$isSsoEnabled = $this->isSsoEnabled();
		$isOnTestAuthenticationPage = $this->isOnTestAuthenticationPage();

		// register authorization (groups, user enabled, ...)
		$this->dc()->getAuthorizationService()->register();

		// ADI-665 register the hooks required during the test authentication process
		if ($isOnTestAuthenticationPage) {
			// further hooks must not be executed
			return true;
		}

		// NADI-673
		$this->dc()->getLoginSucceededService()->register();

		if ($isSsoEnabled) {
		    $isOnXmlRpcPage = $this->isOnXmlRpcPage();
		    $isSsoDisabledForXmlRpc = $this->isSsoDisabledForXmlRpc();
		    // by default, when we are in this branch, the SSO service will be registered
		    $registerSso = true;

		    // NADIS-92, ADI-679: add option to disable SSO when using XML-RPC
            // we need to skip the SSO registration
		    if ($isOnXmlRpcPage && $isSsoDisabledForXmlRpc) {
		        $registerSso = false;
            }

		    if ($registerSso) {
                // ADI-659 check if user has enabled custom login option
                // enabling this option will set the wp_logout action priority to 1
                $useCustomLoginPage = $this->dc()->getConfiguration()->getOptionValue(
                    NextADInt_Adi_Configuration_Options::CUSTOM_LOGIN_PAGE_ENABLED
                );

                $this->dc()->getSsoService()->register($useCustomLoginPage);
            }
		}

		if ($isOnLoginPage) {
		    do_action(NEXT_AD_INT_PREFIX . 'register_form_login_services');

			// further hooks must not be executed
			return false;
		}

		return true;
	}

    /**
     * Register the hooks in LoginService, PasswordValidationService and SSO link (if SSO is enabled).
     * Each of those classes checks for only one registration of hooks.
     */
	public function registerFormLoginServices()
    {
        // register authentication
        $this->dc()->getLoginService()->register();
        // register custom password validation
        $this->dc()->getPasswordValidationService()->register();

        if ($this->isSsoEnabled()) {
            $this->dc()->getSsoPage()->register();
        }
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
     * Return if SSO is disabled for XML-RPC access
     * @return bool
     */
	function isSsoDisabledForXmlRpc()
    {
        return (bool)$this->dc()->getConfiguration()->getOptionValue(NextADInt_Adi_Configuration_Options::SSO_DISABLE_FOR_XMLRPC);
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

		$page        = $_SERVER['PHP_SELF'];
		$required    = "wp-login.php";
		$isOnWpLogin = substr($page, -strlen($required)) == $required;
		$isOnXmlRpc  = $this->isOnXmlRpcPage();

		if ($isOnWpLogin || $isOnXmlRpc) {
			$r = true;
		}

		$customLoginPageEnabled = $this->dc()->getConfiguration()->getOptionValue(NextADInt_Adi_Configuration_Options::CUSTOM_LOGIN_PAGE_ENABLED);

		if ($customLoginPageEnabled) {
			if (isset($_SERVER["REQUEST_URI"]) && strpos($_SERVER["REQUEST_URI"], '/login') !== false) {
				$r = true;
			}
		}

		$r = apply_filters(NEXT_AD_INT_PREFIX . 'auth_enable_login_check', $r);

		return $r;
	}

    /**
     * Return if the current endpoint is xmlrpc.php
     *
     * @return bool
     */
	public function isOnXmlRpcPage() {
	    return strpos($_SERVER['PHP_SELF'], 'xmlrpc.php') !== false;
    }

	/**
	 * Return true if current page is the test authentication page.
	 */
	public function isOnTestAuthenticationPage()
	{
		return isset($_GET['page']) && $_GET['page'] === 'next_ad_int_test_connection';
	}
}