<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Requirements')) {
	return;
}

/**
 * Custom exception class to mark non-matching requirements
 */
class RequirementException extends Exception
{
}

/**
 * Checks if necessary preconditions like PHP version, PHP modules etc. are fulfilled.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Requirements
{
	/* @var Logger $logger */
	private $logger;

	const WORDPRESS_VERSION = '4.0';
	const MODULE_LDAP = 'ldap';
	const MODULE_MBSTRING = 'mbstring';
    const MODULE_OPENSSL = 'openssl';
	const DEPRECATED_ADI_PLUGIN_NAME = 'active-directory-integration/ad-integration.php';

	public function __construct()
	{
		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Check if all required dependencies are met and return true if it so.
	 * If the check fails NADI is automatically deactivated to prevent any issues.
	 *
	 * @param bool|true  $showErrors display admin notifications
	 * @param bool|false $includeActivationCheck include checks which are only executed during activation
	 *
	 * @return bool
	 */
	public function check($showErrors = true, $includeActivationCheck = false)
	{
		try {
			$this->requireWordPressVersion($showErrors);
			$this->requireLdap($showErrors);
			$this->requireMbstring($showErrors);
            $this->requireOpenSSL($showErrors);

			// check if this WordPress instance has more than 10,000 blogs/sites
			if (is_multisite()) {
				$this->preventTooManySites();

				// must be only executed during activation and *not* during execution
				if ($includeActivationCheck) {
					$this->preventSiteActivation();
				}
			}

			// deactivate deprecated ADI version if requirements are met
			$this->deactivateDeprecatedVersion();
		} catch (Exception $e) {
			// at this moment the plugin.php is not loaded
			NextADInt_Core_Util::native()->includeOnce(ABSPATH . 'wp-admin/includes/plugin.php');

			// ensure that the plug-in has not been enabled
			deactivate_plugins(NEXT_AD_INT_PLUGIN_FILE);

			return false;
		}

		return true;
	}

	/**
	 * WordPress 4.0 is required
	 *
	 * @param bool $showErrors
	 *
	 * @throws RequirementException
	 */
	public function requireWordPressVersion($showErrors = true)
	{
		// check wp version
		global $wp_version;

		if (NextADInt_Core_Util::native()->compare($wp_version, self::WORDPRESS_VERSION, '<')) {
			if ($showErrors) {
				add_action(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
					$this, 'wrongWordPressVersion',
				));
			}

			throw new RequirementException();
		}
	}

	/**
	 * Display the error message for a wrong WordPress version
	 */
	public function wrongWordPressVersion()
	{
		global $wp_version;
		$necessary = self::WORDPRESS_VERSION;

		echo "
        <div class=\"error\">
			<p>The 'Next Active Directory Integration' plugin requires WordPress $necessary to work properly.</p>
			<p>You are currently using WordPress $wp_version. Please upgrade your WordPress installation.<p>
        </p></div>";
	}

	/**
	 * LDAP module must be loaded
	 *
	 * @param bool $showErrors
	 *
	 * @throws RequirementException
	 */
	public function requireLdap($showErrors = true)
	{
		// check php module
		if (!NextADInt_Core_Util::native()->isLoaded(self::MODULE_LDAP)) {
			if ($showErrors) {
				add_action(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
					$this, 'missingLdapModule',
				));
			}

			throw new RequirementException();
		}
	}

	/**
	 * Display the error message for the missing LDAP extension.
	 */
	public function missingLdapModule()
	{
		echo "
        <div class=\"error\">
			<p>The 'Next Active Directory Integration' plugin requires the PHP module 'ldap' for communicating with the AD server. Please enable it.</p>
			<p>For further information please visit <a href=\"https://secure.php.net/ldap\">https://secure.php.net/ldap</a>.</p>
        </div>";
	}

	/**
	 * mbstring module must be loaded
	 *
	 * @param bool $showErrors
	 *
	 * @throws RequirementException
	 */
	public function requireMbstring($showErrors = true)
	{
		// mb_strings php module
		if (!NextADInt_Core_Util::native()->isLoaded(self::MODULE_MBSTRING)) {
			if ($showErrors) {
				add_action(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
					$this, 'missingMbstring',
				));
			}

			throw new RequirementException();
		}
	}

	/**
	 * Display the error message for the missing mb_string extension.
	 */
	public function missingMbstring()
	{
		echo "
        <div class=\"error\">
			<p>The 'Next Active Directory Integration' plugin requires the PHP module 'mbstring' for working with encrypted strings. You have to enable it.</p>
			<p>For further information please visit <a href=\"https://secure.php.net/manual/en/mbstring.installation.php\">https://secure.php.net/manual/en/mbstring.installation.php</a>.</p>
        </div>";
	}

    /**
     * mbstring module must be loaded
     *
     * @param bool $showErrors
     *
     * @throws RequirementException
     */
    public function requireOpenSSL($showErrors = true)
    {
        // openssl php module
        if (!NextADInt_Core_Util::native()->isLoaded(self::MODULE_OPENSSL)) {
            if ($showErrors) {
                add_action(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
                    $this, 'missingOpenSSL',
                ));
            }

            throw new RequirementException();
        }
    }

    /**
     * Display the error message for the missing openssl extension.
     */
    public function missingOpenSSL()
    {
        echo "
        <div class=\"error\">
			<p>The 'Next Active Directory Integration' plugin requires the PHP module 'openssl' for encrypting passwords and establishing starttls/ldaps connections. You have to enable it.</p>
			<p>For further information please visit <a href=\"https://secure.php.net/manual/de/openssl.setup.php\">https://secure.php.net/manual/de/openssl.setup.php</a>.</p>
        </div>";
    }

	/**
	 * Large networks are not supported
	 *
	 * @param bool $showErrors
	 *
	 * @throws RequirementException
	 */
	public function preventTooManySites($showErrors = true)
	{
		if (wp_is_large_network('sites')) {
			if ($showErrors) {
				add_action(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
					$this, 'tooManySites',
				));
			}

			throw new RequirementException();
		}
	}

	/**
	 * Display the error message for too many sites.
	 */
	public function tooManySites()
	{
		echo "
        <div class=\"error\">
        <p>The 'Next Active Directory Integration' plugin does not support more than 10,000 sites.</p>
			<p>Please delete some unused sites or contact the developer for a feature request.</p>
        </div>";
	}

	/**
	 * Activation of ADI in a network environment for a specific site is not supported
	 *
	 * @param bool $showErrors
	 *
	 * @throws RequirementException
	 */
	public function preventSiteActivation($showErrors = true)
	{
		// ADI-188: do not allow activation when *not* network-wide activated
		if (!is_network_admin()) {
			if ($showErrors) {
				add_action(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
					$this, 'networkSiteActivationNotAllowed',
				));
			}

			throw new RequirementException();
		}
	}

	/**
	 * Activation of ADI inside a network site is not allowed. It must be network-wide activated
	 */
	public function networkSiteActivationNotAllowed()
	{
		echo "
		<div class=\"error\">
			<p>Your blog is member of a WordPress network. Active Directory Integration can only be activated for your whole network. Please contact the administrator of your WordPress installation to set-up an ADI profile for your blog.</p>
		</div>";
	}

	public function registerPostActivation()
	{
		if ($this->isPluginInstalled(self::DEPRECATED_ADI_PLUGIN_NAME)) {
			// after activation of ADI 2.x we want to show the information that any previous version has been deactivated
			add_action(NextADInt_Adi_Ui_Actions::ADI_REQUIREMENTS_ALL_ADMIN_NOTICES, array(
				$this, 'deactivatedDeprecatedAdiVersionMessage'));
		}
	}

	/**
	 * On execution, ADI 1.x is always disabled if the deprecated version is active.
	 * @return bool
	 */
	public function deactivateDeprecatedVersion()
	{
		// at this moment the plugin.php is not loaded
		NextADInt_Core_Util::native()->includeOnce(ABSPATH . 'wp-admin/includes/plugin.php');

		if (is_plugin_active(self::DEPRECATED_ADI_PLUGIN_NAME)) {
			deactivate_plugins(self::DEPRECATED_ADI_PLUGIN_NAME);
			$this->logger->debug("Disabled deprecated version of ADI.");

			return true;
		}

		return false;
	}

	/**
	 * Display an error message to inform the user that an older ADI version has been disabled.
	 * Hides original WordPress message saying "Plugin activated." (for ADI 1.x) to prevent confusion.
	 */
	public function deactivatedDeprecatedAdiVersionMessage()
	{
		echo "<script>jQuery( document ).ready(function() {
    	jQuery(\"#message\").hide();
    	});
    	</script>
		<div class=\"notice notice-warning\">
		<p>ADI 1.x and NADI can not run in parallel. Any previous version of ADI 1.x has been deactivated to prevent issues.</p>
		</div>
		";
	}

	/**
	 * Return if the given plugin name has been installed.
	 * This method does <strong>not check</strong> the activate/not active status but uses the file system for lookup
	 *
	 * @param string $pluginName
	 *
	 * @return bool
	 */
	public function isPluginInstalled($pluginName)
	{
		$plugins = get_plugins();

		if (isset($plugins[$pluginName])) {
			return true;
		}

		return false;
	}
}
