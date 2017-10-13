<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_SingleSignOn_Service')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_SingleSignOn_Service handles the login procedure for single sign on.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Adi_Authentication_SingleSignOn_Service extends NextADInt_Adi_Authentication_LoginService
{
	/**
	 * If Kerberos or certificate authentication failed
	 */
	const FAILED_SSO_UPN = 'failedSsoUpn';

	/**
	 * If NTLM authentication failed
	 */
	const FAILED_SSO_NETBIOS_NAME = 'failedSsoNetbios';

	const USER_LOGGED_OUT = 'userLoggedOut';

	/** @var Logger */
	private $logger;

	/** @var NextADInt_Adi_Authentication_SingleSignOn_Validator */
	private $validation;

	public function __construct(NextADInt_Adi_Authentication_Persistence_FailedLoginRepository $failedLogin = null,
								NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Ldap_Connection $ldapConnection,
								NextADInt_Adi_User_Manager $userManager,
								NextADInt_Adi_Mail_Notification $mailNotification = null,
								NextADInt_Adi_Authentication_Ui_ShowBlockedMessage $userBlockedMessage = null,
								NextADInt_Ldap_Attribute_Service $attributeService,
								NextADInt_Adi_Role_Manager $roleManager,
								NextADInt_Adi_Authentication_SingleSignOn_Validator $validation
	)
	{
		parent::__construct($failedLogin, $configuration, $ldapConnection, $userManager, $mailNotification,
			$userBlockedMessage, $attributeService, $roleManager);

		$this->validation = $validation;
		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Check if the user can be authenticated using user from the client machine.
	 *
	 * @param null $user
	 * @param string $login
	 * @param string $password
	 *
	 * @return bool
	 */
	public function authenticate($user = null /* required for WordPress callback */, $login = '', $password = '')
	{
		// if the user is already logged in, do not continue
		$isUserLoggedIn = is_user_logged_in();

		if ($isUserLoggedIn) {
			return false;
		}

		$username = $this->findUsername();

		// if no username was given, return false
		if (empty($username)) {
			$this->logger->warn('Cannot find username for SSO.');

			return false;
		}

		$credentials = self::createCredentials($username, '');
		$sessionHandler = $this->getSessionHandler();

		$this->clearAuthenticationState();
		$validation = $this->validation;

		try {
			$validation->validateUrl();
			$validation->validateLogoutState();
		}
		catch (NextADInt_Adi_Authentication_LogoutException $e) {
			$this->logger->info("Skipping further authentication because user is being logged out");
			return false;
		}

		try {
			$validation->validateAuthenticationState($credentials);

			$netbiosName = $credentials->getNetbiosName();

			if (isset($netbiosName)) {
				$credentials = $this->ntlmAuth($credentials, $validation);
			} else {
				// $username or $username@$upnSuffix
				$credentials = $this->kerberosAuth($credentials, $validation);
			}

			// authenticate the given user and run the default procedure form the LoginService
			$user = parent::authenticate(null, $credentials->getUserPrincipalName());

			$validation->validateUser($user);

			// if our user is authenticated and we have a WordPress user, we
			$sessionHandler->clearValue(self::FAILED_SSO_UPN);
			$this->loginUser($user);
		} catch (NextADInt_Adi_Authentication_Exception $e) {
			$this->logger->error('User could not be authenticated using SSO. ' . $e->getMessage());
			$sessionHandler->setValue(self::FAILED_SSO_UPN, $credentials->getUserPrincipalName());

			return false;
		}

		return true;
	}


	/**
	 * Create new credentials based upon sAMAccountName or userPrincipalName
	 *
	 * @throws NextADInt_Adi_Authentication_Exception If the user's attribute could not be found
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @return NextADInt_Adi_Authentication_Credentials
	 */
	function createUpnCredentials(NextADInt_Adi_Authentication_Credentials $credentials)
	{
		// findLdapAttributesOfUser tries both sAMAccountName and userPrincipalName
		$ldapAttributes = $this->getAttributeService()->findLdapAttributesOfUser($credentials, '');

		if ($ldapAttributes->getRaw() == false) {
			throw new NextADInt_Adi_Authentication_Exception("User '"  . $credentials->getLogin() . "' does not exist in Active Directory'");
		}

		$upn = $ldapAttributes->getFilteredValue('userprincipalname');
		$credentials = self::createCredentials($upn, '');

		return $credentials;
	}

	/**
	 * Authenticate given credentials by using an internal lookup of the provided NETBIOS name.
	 *
	 * @throws NextADInt_Adi_Authentication_Exception if the profile could not be found
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @param NextADInt_Adi_Authentication_SingleSignOn_Validator $validation
	 * @return NextADInt_Adi_Authentication_Credentials
	 */
	function ntlmAuth(NextADInt_Adi_Authentication_Credentials $credentials, NextADInt_Adi_Authentication_SingleSignOn_Validator $validation)
	{
		$this->logger->info('SSO authentication triggered using NTLM for user ' . $credentials->getLogin());
		$profile = null;

		// find assigned profile by previously detected nETBIOSName
		try {
			$profile = $this->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::NETBIOS_NAME, $credentials->getNetbiosName());

			$validation->validateProfile($profile);
		}
		catch (NextADInt_Adi_Authentication_Exception $e) {
			$this->logger->error("Validation of profile for NETBIOS name '" . $credentials->getNetbiosName() . "' failed: " . $e->getMessage());

			throw new NextADInt_Adi_Authentication_Exception("Unable to find matching NADI profile for NETBIOS name '" . $credentials->getNetbiosName() . "'. Is NADI connected to a valid Active Directory domain?");
		}

		$this->openLdapConnection($profile);

		// create required credentials with userPrincipalName and upnSuffix. At the moment we got only nETBIOSName and sAMAccountName
		$credentials = $this->createUpnCredentials($credentials);

		return $credentials;
	}

	/**
	 * Authenticate given credentials by using an internal lookup of the provided UPN suffix.
	 *
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @param NextADInt_Adi_Authentication_SingleSignOn_Validator $validation
	 * @return NextADInt_Adi_Authentication_Credentials
	 */
	function kerberosAuth(NextADInt_Adi_Authentication_Credentials $credentials, NextADInt_Adi_Authentication_SingleSignOn_Validator $validation)
	{
		$this->logger->info('SSO authentication triggered using Kerberos for user ' . $credentials->getLogin());

		// normalize our suffix, to prevent inconsistencies
		$suffix = $this->normalizeSuffix($credentials->getUpnSuffix());

		// get the profile and check if it is valid
		$profile = $this->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix);
		$validation->validateProfile($profile);
		$this->openLdapConnection($profile);

		// try to resolve the user using the sAMAccountName, if no suffix has been found
		if (null === $credentials->getUpnSuffix()) {
			$credentials = $this->createUpnCredentials($credentials);
		}

		return $credentials;
	}

	/**
	 * Clear the session values for failed sso or manual logout if the user wants to retry authentication over SSO.
	 */
	protected function clearAuthenticationState()
	{
		if ('sso' === NextADInt_Core_Util_ArrayUtil::get('reauth', $_GET, false)) {
			$this->getSessionHandler()->clearValue(self::FAILED_SSO_UPN);
			$this->getSessionHandler()->clearValue(self::USER_LOGGED_OUT);
		}
	}

	/**
	 * Get the environment variable
	 *
	 * @return mixed
	 */
	protected function findUsername()
	{
		$envVariable = $this->getConfiguration()->getOptionValue(NextADInt_Adi_Configuration_Options::SSO_ENVIRONMENT_VARIABLE);
		$username = NextADInt_Core_Util_ArrayUtil::get($envVariable, $_SERVER);

		// ADI-357 unescape already escaped username
		$unescape = stripslashes($username);

		$this->logger->debug('SSO provided username for environment variable "'.  $envVariable . '" is "' . $username . "'");

		return $unescape;
	}


	/**
	 * Open the LDAP connection using the configuration from the profile.
	 *
	 * @param $profile
	 *
	 * @throws NextADInt_Adi_Authentication_Exception if the connection could not be opened
	 */
	protected function openLdapConnection($profile)
	{
		$connectionDetails = $this->createConnectionDetailsFromProfile($profile);
		$this->getLdapConnection()->connect($connectionDetails);

		$this->validation->validateLdapConnection($this->getLdapConnection());
	}

	/**
	 * Get account suffix for given credentials
	 *
	 * @param string $suffix
	 *
	 * @return array
	 */
	public function detectAuthenticatableSuffixes($suffix)
	{
		$profile = $this->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX, $suffix);

		if (null === $profile) {
			return array($suffix);
		}

		return NextADInt_Core_Util_StringUtil::split($profile[NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX], ';');
	}

	/**
	 * Find the profile with SSO enabled and its configuration option contains the provided value.
	 *
	 * @param $option name of profile option
	 * @param $value value of given option to match
	 *
	 * @return mixed
	 */
	public function findBestConfigurationMatchForProfile($option, $value)
	{
		$ssoEnabledProfiles = $this->findSsoEnabledProfiles();

		// find all profiles with given option value
		$profiles = $this->getProfilesWithOptionValue($option, $value, $ssoEnabledProfiles);

		// if multiple profiles were found, log a warning and return the first result
		if (sizeof($profiles) > 1) {
			$this->logger->warn('Multiple profiles with the same option "' . $option .  '" and enabled SSO were found.');
		}

		// if no profile given suffix and sso enabled was found, search for profiles with SSO enabled and no suffixes
		if (sizeof($profiles) == 0) {
			$profiles = $this->getProfilesWithoutOptionValue($option, $ssoEnabledProfiles);
		}

		// return the first found profile or null
		return NextADInt_Core_Util_ArrayUtil::findFirstOrDefault($profiles, null);
	}

	/**
	 * Get all profiles with the given option value.
	 *
	 * @param $option name of configuration option to search for
	 * @param $requiredValue
	 * @param $profiles
	 *
	 * @return array
	 */
	protected function getProfilesWithOptionValue($option, $requiredValue, $profiles)
	{
		return NextADInt_Core_Util_ArrayUtil::filter(function ($profile) use ($option, $requiredValue) {
			$values = array();

			if (isset($profile[$option])) {
				$values = NextADInt_Core_Util_StringUtil::split($profile[$option], ';');
			}

			return (NextADInt_Core_Util_ArrayUtil::containsIgnoreCase($requiredValue, $values));
		}, $profiles);
	}

	/**
	 * Get all profiles which have no option specified.
	 *
	 * @param $option name of configuration option
	 * @param $profiles
	 *
	 * @return array
	 */
	protected function getProfilesWithoutOptionValue($option, $profiles)
	{
		return NextADInt_Core_Util_ArrayUtil::filter(function ($profile) use ($option) {
			$value = '';

			if (isset($profile[$option])) {
				$value = $profile[$option];
			}

			return NextADInt_Core_Util_StringUtil::isEmptyOrWhitespace($value);
		}, $profiles);
	}

	/**
	 * Try to authenticate the user against the Active Directory.
	 *
	 * @param string $username
	 * @param null|string $accountSuffix
	 * @param string $password
	 *
	 * @return bool
	 */
	public function authenticateAtActiveDirectory($username, $accountSuffix, $password)
	{
		return $this->isUserAuthorized($username, $accountSuffix);
	}

	/**
	 * Create new {@link NextADInt_Ldap_Connection} using the given data from the profile.
	 *
	 * @param $profile
	 *
	 * @return NextADInt_Ldap_ConnectionDetails
	 */
	protected function createConnectionDetailsFromProfile($profile)
	{
		$connection = new NextADInt_Ldap_ConnectionDetails();
		$connection->setDomainControllers($profile[NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS]);
		$connection->setPort($profile[NextADInt_Adi_Configuration_Options::PORT]);
		$connection->setEncryption($profile[NextADInt_Adi_Configuration_Options::ENCRYPTION]);
		$connection->setNetworkTimeout($profile[NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT]);
		$connection->setBaseDn($profile[NextADInt_Adi_Configuration_Options::BASE_DN]);
		$connection->setUsername($profile[NextADInt_Adi_Configuration_Options::SSO_USER]);
		$connection->setPassword($profile[NextADInt_Adi_Configuration_Options::SSO_PASSWORD]);

		return $connection;
	}

	/**
	 * Return the suffix with an '@' prefix.
	 *
	 * @param $suffix
	 *
	 * @return string
	 */
	protected function normalizeSuffix($suffix)
	{
		if (!empty($suffix) && '@' !== $suffix[0]) {
			$suffix = '@' . $suffix;
		}

		return $suffix;
	}

	/**
	 * Find all profiles with the necessary roles.
	 *
	 * @return array
	 */
	protected function findSsoEnabledProfiles()
	{
		// find all profiles with the given options and add them to our $profiles array
		$profiles = $this->getConfiguration()->findAllProfiles(array(
			NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX,
			NextADInt_Adi_Configuration_Options::SSO_ENABLED,
			NextADInt_Adi_Configuration_Options::SSO_USER,
			NextADInt_Adi_Configuration_Options::SSO_PASSWORD,
			NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS,
			NextADInt_Adi_Configuration_Options::PORT,
			NextADInt_Adi_Configuration_Options::ENCRYPTION,
			NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT,
			NextADInt_Adi_Configuration_Options::BASE_DN,
			NextADInt_Adi_Configuration_Options::SSO_USER,
			NextADInt_Adi_Configuration_Options::SSO_PASSWORD,
			NextADInt_Adi_Configuration_Options::NETBIOS_NAME
		));

		// get the current configuration and add it as first option
		// this is required in a single site environment, as the profile will not be listed above
		array_unshift($profiles, $this->getConfiguration()->getAllOptions());

		// filter all profiles and get profiles with SSO enabled
		$profiles = NextADInt_Core_Util_ArrayUtil::filter(function ($profile) {
			if (!isset($profile[NextADInt_Adi_Configuration_Options::SSO_ENABLED]['option_value'])) {
				return false;
			}

			return $profile[NextADInt_Adi_Configuration_Options::SSO_ENABLED]['option_value'] === true;
		}, $profiles);

		return $this->normalizeProfiles($profiles);
	}

	/**
	 * Normalize the given profiles for further usage.
	 *
	 * @param $profiles
	 *
	 * @return array
	 */
	protected function normalizeProfiles($profiles)
	{
		// go through all found profiles and normalize the values
		return NextADInt_Core_Util_ArrayUtil::map(function ($profile) {
			// set the option_value as the real value
			return NextADInt_Core_Util_ArrayUtil::map(function ($profileOption) {
				return $profileOption['option_value'];
			}, $profile);
		}, $profiles);
	}

	/**
	 * Set a session flag that the user has logged out manually.
	 */
	public function logout()
	{
		$this->getSessionHandler()->setValue(self::USER_LOGGED_OUT, true);
	}

	/**
	 * Register all hooks for our single sign on.
	 */
	public function register()
	{
		add_action('wp_logout', array($this, 'logout'));
		add_action('init', array($this, 'authenticate'));
	}

	/**
	 * If the user is not logged in, perform a login for the given user.
	 *
	 * @param      $user
	 * @param bool $exit
	 */
	protected function loginUser($user, $exit = true)
	{
		// ADI-418: Accessing un-protected URLs directly with SSO enabled redirect does not work
		$redirectTo = (isset($_SERVER['REDIRECT_URL']) && !empty($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : null;
		// default redirect if WordPress forces itself a login, e.g. when accessing /wp-admin
		$redirectTo = (!empty($_REQUEST['redirect_to'])) ? $_REQUEST['redirect_to'] : $redirectTo;
		// if not set, fall back to the home url
		$redirectTo = empty($redirectTo) ? home_url('/') : $redirectTo;

		$secure_cookie = is_ssl();
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID, true, $secure_cookie);

		do_action('wp_login', $user->user_login, $user);
		wp_safe_redirect($redirectTo);

		if ($exit) {
			exit;
		}
	}

	/**
	 * Return the current session handler.
	 *
	 * @return NextADInt_Core_Session_Handler
	 */
	protected function getSessionHandler()
	{
		return NextADInt_Core_Session_Handler::getInstance();
	}
}