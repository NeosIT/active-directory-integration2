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
	const FAILED_SSO_UPN = 'failedSsoUpn';

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
	) {
		parent::__construct($failedLogin, $configuration, $ldapConnection, $userManager, $mailNotification,
			$userBlockedMessage, $attributeService, $roleManager);

		$this->validation = $validation;
		$this->logger = Logger::getLogger(__CLASS__);
	}

	/**
	 * Check if the user can be authenticated using user from the client machine.
	 *
	 * @param null   $user
	 * @param string $login
	 * @param string $password
	 *
	 * @return bool
	 */
	public function authenticate($user = null /* required for WordPress callback */, $login = '', $password = '')
	{
		$username = $this->findUsername();

		// if the user is already logged in, do not continue
		if (is_user_logged_in()) {
			return false;
		}

		// if no username was given, return false
		if (empty($username)) {
			$this->logger->warn('Cannot find username for SSO.');

			return false;
		}

		$credentials = self::createCredentials($username, '');
		$sessionHandler = $this->getSessionHandler();

		$this->clearAuthenticationState();

		try {
			$validation = $this->validation;
			$validation->validateUrl();
			$validation->validateAuthenticationState($credentials);
			$validation->validateLogoutState();

			// get the profile and check if it is valid
			$profile = $this->findCorrespondingConfiguration($credentials->getUpnSuffix());
			$validation->validateProfile($profile);
			$this->openLdapConnection($profile);

			// try to resolve the user using the sAMAccountName, if no suffix has been found
			if (null === $credentials->getUpnSuffix()) {
				$ldapAttributes = $this->getAttributeService()->findLdapAttributesOfUser($credentials, '');
				$upn = $ldapAttributes->getFilteredValue('userprincipalname');
				$credentials = self::createCredentials($upn, '');
			}

			// authenticate the given user and run the default procedure form the LoginService
			$user = parent::authenticate(null, $credentials->getUserPrincipalName());
			$validation->validateUser($user);

			// if our user is authenticated and we have a WordPress user, we
			$sessionHandler->clearValue(self::FAILED_SSO_UPN);
			$this->loginUser($user);
		} catch (NextADInt_Adi_Authentication_Exception $e) {
			$this->logger->error('User could not be authenticated using SSO.', $e);
			$sessionHandler->setValue(self::FAILED_SSO_UPN, $credentials->getUserPrincipalName());

			return false;
		}

		return true;
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
		$profile = $this->findCorrespondingConfiguration($suffix);

		if (null === $profile) {
			return array($suffix);
		}

		return NextADInt_Core_Util_StringUtil::split($profile[NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX], ';');
	}

	/**
	 * Use the given {@code $suffix} and resolve a valid profile for authentication.
	 *
	 * @param $suffix
	 *
	 * @return mixed
	 */
	public function findCorrespondingConfiguration($suffix)
	{
		// normalize our suffix, to prevent inconsistencies
		$suffix = $this->normalizeSuffix($suffix);
		$ssoEnabledProfiles = $this->findSsoEnabledProfiles();

		// find all profiles with our corresponding account suffix
		$profiles = $this->getProfilesForSuffix($suffix, $ssoEnabledProfiles);

		// if multiple profiles were found, log a warning and return the first result
		if (sizeof($profiles) > 1) {
			$this->logger->warn('Multiple profiles with the same account suffix and enabled SSO were found.');
		}

		// if no profile given suffix and sso enabled was found, search for profiles with SSO enabled and no suffixes
		if (sizeof($profiles) == 0) {
			$profiles = $this->getProfilesWithoutSuffixSet($ssoEnabledProfiles);
		}

		// return the first found profile or null
		return NextADInt_Core_Util_ArrayUtil::findFirstOrDefault($profiles, null);
	}

	/**
	 * Get all profiles for the corresponding suffix.
	 *
	 * @param $suffix
	 * @param $profiles
	 *
	 * @return array
	 */
	protected function getProfilesForSuffix($suffix, $profiles)
	{
		return NextADInt_Core_Util_ArrayUtil::filter(function($profile) use ($suffix) {
			$suffixes = NextADInt_Core_Util_StringUtil::split($profile[NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX], ';');

			return (NextADInt_Core_Util_ArrayUtil::containsIgnoreCase($suffix, $suffixes));
		}, $profiles);
	}

	/**
	 * Get all profiles that have no account suffix specified.
	 *
	 * @param $profiles
	 *
	 * @return array
	 */
	protected function getProfilesWithoutSuffixSet($profiles)
	{
		return NextADInt_Core_Util_ArrayUtil::filter(function($profile) {
			return NextADInt_Core_Util_StringUtil::isEmptyOrWhitespace($profile[NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX]);
		}, $profiles);
	}

	/**
	 * Try to authenticate the user against the Active Directory.
	 *
	 * @param string      $username
	 * @param null|string $accountSuffix
	 * @param string      $password
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
		));

		// get the current configuration and add it as first option
		array_unshift($profiles, $this->getConfiguration()->getAllOptions());

		// filter all profiles and get profiles with SSO enabled
		$profiles = NextADInt_Core_Util_ArrayUtil::filter(function($profile) {
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
		return NextADInt_Core_Util_ArrayUtil::map(function($profile) {
			// set the option_value as the real value
			return NextADInt_Core_Util_ArrayUtil::map(function($profileOption) {
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

		do_action('wp_login', $user->user_login, $user);
		wp_set_current_user($user->ID);
		wp_set_auth_cookie($user->ID);
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