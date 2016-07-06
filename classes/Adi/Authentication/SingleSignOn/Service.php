<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_SingleSignOn_Service')) {
	return;
}

/**
 * Adi_Authentication_SingleSignOn_Service handles the login procedure for single sign on.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Adi_Authentication_SingleSignOn_Service extends Adi_Authentication_LoginService
{
	const FAILED_SSO_UPN = 'failedSsoUpn';

	const USER_LOGGED_OUT = 'userLoggedOut';

	/** @var Logger */
	private $logger;

	/** @var Adi_Authentication_SingleSignOn_Validator */
	private $validation;

	public function __construct(Adi_Authentication_Persistence_FailedLoginRepository $failedLogin = null,
								Multisite_Configuration_Service $configuration,
								Ldap_Connection $ldapConnection,
								Adi_User_Manager $userManager,
								Adi_Mail_Notification $mailNotification = null,
								Adi_Authentication_Ui_ShowBlockedMessage $userBlockedMessage = null,
								Ldap_Attribute_Service $attributeService,
								Adi_Role_Manager $roleManager,
								Adi_Authentication_SingleSignOn_Validator $validation
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
		} catch (Adi_Authentication_Exception $e) {
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
		if ('sso' === Core_Util_ArrayUtil::get('reauth', $_GET, false)) {
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
		$envVariable = $this->getConfiguration()->getOptionValue(Adi_Configuration_Options::SSO_ENVIRONMENT_VARIABLE);

		return Core_Util_ArrayUtil::get($envVariable, $_SERVER);
	}


	/**
	 * Open the LDAP connection using the configuration from the profile.
	 *
	 * @param $profile
	 *
	 * @throws Adi_Authentication_Exception if the connection could not be opened
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

		return Core_Util_StringUtil::split($profile[Adi_Configuration_Options::ACCOUNT_SUFFIX], ';');
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
		return Core_Util_ArrayUtil::findFirstOrDefault($profiles, null);
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
		return Core_Util_ArrayUtil::filter(function($profile) use ($suffix) {
			$suffixes = Core_Util_StringUtil::split($profile[Adi_Configuration_Options::ACCOUNT_SUFFIX], ';');

			return (Core_Util_ArrayUtil::containsIgnoreCase($suffix, $suffixes));
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
		return Core_Util_ArrayUtil::filter(function($profile) {
			return Core_Util_StringUtil::isEmptyOrWhitespace($profile[Adi_Configuration_Options::ACCOUNT_SUFFIX]);
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
	 * Create new {@link Ldap_ConnectionDetails} using the given data from the profile.
	 *
	 * @param $profile
	 *
	 * @return Ldap_ConnectionDetails
	 */
	protected function createConnectionDetailsFromProfile($profile)
	{
		$connection = new Ldap_ConnectionDetails();
		$connection->setDomainControllers($profile[Adi_Configuration_Options::DOMAIN_CONTROLLERS]);
		$connection->setPort($profile[Adi_Configuration_Options::PORT]);
		$connection->setEncryption($profile[Adi_Configuration_Options::ENCRYPTION]);
		$connection->setNetworkTimeout($profile[Adi_Configuration_Options::NETWORK_TIMEOUT]);
		$connection->setBaseDn($profile[Adi_Configuration_Options::BASE_DN]);
		$connection->setUsername($profile[Adi_Configuration_Options::SSO_USER]);
		$connection->setPassword($profile[Adi_Configuration_Options::SSO_PASSWORD]);

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
			Adi_Configuration_Options::ACCOUNT_SUFFIX,
			Adi_Configuration_Options::SSO_ENABLED,
			Adi_Configuration_Options::SSO_USER,
			Adi_Configuration_Options::SSO_PASSWORD,
			Adi_Configuration_Options::DOMAIN_CONTROLLERS,
			Adi_Configuration_Options::PORT,
			Adi_Configuration_Options::ENCRYPTION,
			Adi_Configuration_Options::NETWORK_TIMEOUT,
			Adi_Configuration_Options::BASE_DN,
			Adi_Configuration_Options::SSO_USER,
			Adi_Configuration_Options::SSO_PASSWORD,
		));

		// get the current configuration and add it as first option
		array_unshift($profiles, $this->getConfiguration()->getAllOptions());

		// filter all profiles and get profiles with SSO enabled
		$profiles = Core_Util_ArrayUtil::filter(function($profile) {
			if (!isset($profile[Adi_Configuration_Options::SSO_ENABLED]['option_value'])) {
				return false;
			}

			return $profile[Adi_Configuration_Options::SSO_ENABLED]['option_value'] === true;
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
		return Core_Util_ArrayUtil::map(function($profile) {
			// set the option_value as the real value
			return Core_Util_ArrayUtil::map(function($profileOption) {
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
		$redirectTo = (!empty($_REQUEST['redirect_to'])) ? $_REQUEST['redirect_to'] : home_url('/');
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
	 * @return Core_Session_Handler
	 */
	protected function getSessionHandler()
	{
		return Core_Session_Handler::getInstance();
	}
}