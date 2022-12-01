<?php

namespace Dreitier\Nadi\Authentication;

use Dreitier\Ldap\Attributes;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\ConnectionDetails;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\LoginState;
use Dreitier\Nadi\User\LoginSucceededService;
use Dreitier\Nadi\User\Manager;
use Dreitier\Nadi\User\User;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\Assert;
use Dreitier\Util\StringUtil;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\Nadi\Authentication\Credentials;

/**
 * Entrypoint for the authentication process of WordPress.
 *
 * This class registers the "authenticate" callback in WordPress and is responsible for the authentication process.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class LoginService
{
	/* @var Connection $ldapConnection */
	private $ldapConnection;

	/* @var Service $multisiteConfigurationService */
	private $multisiteConfigurationService;

	/* @var Manager $userManager */
	private $userManager;

	/** @var \Dreitier\Ldap\Attribute\Service $attributeService */
	private $attributeService;

	/* @var Logger $logger */
	private $logger;

	/**
	 * @var LoginState
	 */
	private $loginState;

	/** @var LoginSucceededService $loginSucceededService */
	private $loginSucceededService;

	/** @var boolean */
	private $isRegistered = false;

	/**
	 * @param Service $multisiteMultisiteConfigurationServiceService
	 * @param Connection $ldapConnection
	 * @param User $userManager
	 * @param \Dreitier\Ldap\Attribute\Service $attributeService
	 * @param LoginState $loginState
	 * @param LoginSucceededService $loginSucceededService
	 */
	public function __construct(
		Service                          $multisiteMultisiteConfigurationServiceService,
		Connection                       $ldapConnection,
		Manager                          $userManager,
		\Dreitier\Ldap\Attribute\Service $attributeService,
		LoginState                       $loginState,
		LoginSucceededService            $loginSucceededService
	)
	{
		$this->multisiteConfigurationService = $multisiteMultisiteConfigurationServiceService;
		$this->ldapConnection = $ldapConnection;
		$this->userManager = $userManager;
		$this->attributeService = $attributeService;
		$this->loginState = $loginState;
		$this->loginSucceededService = $loginSucceededService;

		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Callback handler for WordPress which adds this class to the "authenticate" callback.
	 */
	public function register()
	{
		// don't allow multiple registrations of the same LoginService instance
		if ($this->isRegistered) {
			return;
		}

		add_filter('authenticate', array($this, 'authenticate'), 10, 3);

		// disable 'lost password' feature
		$enableLostPasswordRecovery = $this->multisiteConfigurationService->getOptionValue(
			Options::ENABLE_LOST_PASSWORD_RECOVERY
		);

		if (!$enableLostPasswordRecovery) {
			add_filter('allow_password_reset', '__return_false');
			add_action('lost_password', array($this, 'disableLostPassword'));
		}

		// for normal login we have to check for disabled users by hooking into wp_authenticate_user
		add_filter('wp_authenticate_user', array($this->loginSucceededService, 'checkUserEnabled'), 10, 2);

		// #142: register an additional filter for checking if the username is excluded
		add_filter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_form_login_requires_ad_authentication', array($this, 'requiresActiveDirectoryAuthentication'), 10, 1);
	}

	/**
	 * Prevent WordPress' password recovery b/c password is managed by Active Directory.
	 */
	public function disableLostPassword()
	{
		$message = esc_html__(
			'Lost Password feature has been disabled by the "Next Active Directory Integration" plugin.', 'next-active-directory-integration'
		);

		wp_die($message);
	}

	/**
	 * Check if the user can be authenticated and update his local WordPress account based upon his Active Directory profile.
	 * ADI implicitly uses the authentication against the userPrincipalName by authenticating with the full UPN username.
	 * This method expects that $login and $password are escaped by WordPress.
	 *
	 * @param object|null $user not used
	 * @param string $login
	 * @param string $password
	 *
	 * @return false|Credentials
	 * @throws Exception
	 */
	public function authenticate($user = null /* required for WordPress callback */, $login = '', $password = '')
	{
		if (!$login) {
			return false;
		}

		$this->logger->info('A user tries to log in.');

		// ADI-367: check XML-RPC access
		$this->checkXmlRpcAccess();

		// unquote backlash from username
		// https://wordpress.org/support/topic/fatal-error-after-login-and-suffix-question/
		$login = stripcslashes($login);

		// EJN - 2017/11/16 - Allow users to log in with one of their email addresses specified in proxyAddresses
		// Check if this looks like a ProxyAddress and look up sAMAccountName if we are allowing ProxyAddresses as login.
		$allowProxyAddressLogin = $this->multisiteConfigurationService->getOptionValue(Options::ALLOW_PROXYADDRESS_LOGIN);
		if ($allowProxyAddressLogin && strpos($login, '@') !== false) {
			$login = $this->lookupFromProxyAddresses($login);
		}

		// check, if NADI is not responsible for this username, e.g. in case of logging in an admin account
		if (!apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_form_login_requires_ad_authentication', $login)) {
			return false;
		}

		// login should be case insensitive
		$password = stripslashes($password);

		$credentials = $this->buildCredentials($login, $password);
		$suffixes = $this->detectAuthenticatableSuffixes($credentials->getUpnSuffix());

		$r = $this->tryAuthenticatableSuffixes(
			$credentials,
			$suffixes
		);

		return $r;
	}

	/**
	 * Detect access to xmlrpc.php and disable it if configured
	 * @issue ADI-367
	 */
	public function checkXmlRpcAccess()
	{
		$xmlRpcEnabled = $this->multisiteConfigurationService->getOptionValue(Options::ALLOW_XMLRPC_LOGIN);
		$page = $_SERVER['PHP_SELF'];

		if (strpos($page, 'xmlrpc.php') !== false) {
			if ($xmlRpcEnabled) {
				$this->logger->warning("XML-RPC login detected! XML-RPC authentication is enabled. Continuing...");
				return;
			}

			$this->logger->warning("XML-RPC Login detected ! Preventing further authentication.");
			wp_die(__("Next ADI prevents XML RPC authentication!", 'next-active-directory-integration'));
		}
	}

	/**
	 * Lookup the user's sAMAccountName by their SMTP proxy addresses. If not found, just return the proxy address.
	 *
	 * EJN - 2017/11/16 - Allow users to log in with one of their email addresses specified in proxyAddresses
	 *
	 * @param String $proxyAddress The proxy address to try looking up.
	 *
	 * @return The associated sAMAccountName or $proxyAddress if not found.
	 */
	public function lookupFromProxyAddresses($proxyAddress)
	{

		// Use the Sync to WordPress username and password since anonymous bind can't search.
		$connectionDetails = new ConnectionDetails();
		$connectionDetails->setUsername($this->multisiteConfigurationService->getOptionValue(Options::SYNC_TO_WORDPRESS_USER));
		$connectionDetails->setPassword($this->multisiteConfigurationService->getOptionValue(Options::SYNC_TO_WORDPRESS_PASSWORD));

		// LDAP_Connection
		$this->ldapConnection->connect($connectionDetails);

		// check if domain controller is available
		$domainControllerIsAvailable = $this->ldapConnection->checkPorts();

		if ($domainControllerIsAvailable) {
			$samaccountname = $this->ldapConnection->findByProxyAddress($proxyAddress);

			// If this email address wasn't specified in anyone's proxyAddresses attributes, just return the original value.
			if ($samaccountname === false) {
				return $proxyAddress;
			}
		}

		$this->logger->info("Found sAMAccountName '" . $samaccountname . "' for proxy address '" . $proxyAddress . "'.");

		// Return the account we looked up.
		return $samaccountname;
	}

	/**
	 * Try every given suffix and authenticate with it against the Active Directory. The first authenticatable suffix is used.
	 *
	 * @param Credentials $credentials
	 * @param array $suffixes
	 *
	 * @return false|Credentials
	 * @throws Exception
	 */
	public function tryAuthenticatableSuffixes(Credentials $credentials, $suffixes = array())
	{
		Assert::notNull($credentials, "authentication must not be null");
		Assert::notNull($suffixes, "suffixes must not be null");

		$this->logger->debug("$credentials' with authenticatable suffixes: '" . implode(", ", $suffixes) . "'.");

		// authenticate at AD
		foreach ($suffixes as $suffix) {
			$success = $this->authenticateAtActiveDirectory(
				$credentials->getUpnUsername(),
				$suffix,
				$credentials->getPassword()
			);

			if ($success) {
				// ADI-204: After authentication the identified UPN suffix must be updated
				$credentials->setUpnSuffix($suffix);

				return $this->postAuthentication($credentials);
			}
		}

		$this->logger->warning('Login for ' . $credentials . ' failed: none of the suffixes succeeded');

		return false;
	}

	/**
	 * Create a new instance of Credentials
	 *
	 * @param $login
	 * @param $password
	 *
	 * @return Credentials
	 * @since 2.0.0
	 */
	public function buildCredentials($login, $password)
	{
		$r = PrincipalResolver::createCredentials($login, $password);

		/**
		 * @var Credentials
		 */
		$r = apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_configure_credentials', $r);

		return $r;
	}

	/**
	 * Check if the Active Directory authentication is required or not.
	 * If the username is empty or the user is WordPress first/admin account, an Active Directory authentication will *not* be executed.
	 *
	 * @param string $login Username or e-mail address
	 *
	 * @return bool
	 */
	public function requiresActiveDirectoryAuthentication($login)
	{
		// stop if username is empty
		if (empty($login)) {
			$this->logger->warning('Username is empty. Authentication failed.');

			return false;
		}

		// don't use Active Directory for WordPress' admin user (ID 1)
		$user = $this->getWordPressUser($login);

		if ($user) {
			// ID == 1 is the first user in WordPress and therefore an administrator
			if ($user->ID === 1) {
				$this->logger->debug('User with ID 1 will never be authenticated by this plugin.');

				return false;
			}
		}

		if ($this->isUsernameExcludedFromAuthentication($login)) {
			// ADI-393: Please note that by default the user who activated NADI must not be inevitably the first user/admin but can be another administrator
			// Therefore, the administrator who activated NADI has been added to the excluded usernames. He can be removed after configuring and testing the plug-in.
			$this->logger->debug("User '$login' is explicitly excluded from Active Directory authentication by configuration setting");

			return false;
		}

		// user must be authenticated by Active Directory
		return true;
	}

	/**
	 * Return whether the given username is excluded from authentication. This method is completely case-insensitive, so if the excluded usernames are
	 * "admin@test.ad;user@test.ad" it will return true for "admin@test.ad", "ADMIN@test.ad", "user@test.ad", "usER@test.AD" and so on.
	 *
	 * @access package
	 *
	 * @param string $username
	 *
	 * @return bool
	 */
	function isUsernameExcludedFromAuthentication($username)
	{
		$excludes = StringUtil::toLowerCase($this->multisiteConfigurationService->getOptionValue(Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION));
		$excludedUsernames = explode(';', $excludes);

		return in_array(StringUtil::toLowerCase($username), $excludedUsernames);
	}

	/**
	 * Get account suffix for given credentials
	 *
	 * @param string $suffix
	 *
	 * @return array
	 */
	function detectAuthenticatableSuffixes($suffix)
	{
		// get all account suffixes from the settings
		$suffixes = $this->multisiteConfigurationService->getOptionValue(Options::ACCOUNT_SUFFIX);
		$arrAuthenticatableSuffixes = StringUtil::trimmedExplode(';', $suffixes);

		// if $rawUsername does not contain no '@', than return the settings value for ACCOUNT_SUFFIX
		if (empty($suffix)) {
			return $arrAuthenticatableSuffixes;
		}

		if ($suffix[0] != '@') {
			$suffix = '@' . $suffix;
		}

		// return the new account suffix
		if (sizeof($arrAuthenticatableSuffixes) == 0) {
			// if no account suffix is set in the settings, then return the account suffix from $rawUsername
			$this->logger->debug("No account suffix set. Using user domain '{$suffix}' as account suffix.");

			return array($suffix);
		}

		if (($idx = array_search($suffix, $arrAuthenticatableSuffixes)) !== false) {
			// if the user uses one of the stored account suffixes, then return all stored $accountSuffixes
			$this->logger->debug("User domain '{$suffix}' is in list of account suffixes. Using this as first testable account suffix.");

			// ADI-716: only return the user's suffix if it is inside the list of authenticatable suffixes
			return array($suffix);
		}

		return $arrAuthenticatableSuffixes;
	}

	/**
	 * Execute the Active Directory authentication.
	 *
	 * The authentication fails if the user could not be found by his username, suffix and password or his account is blocked by brute-force attempts.
	 *
	 * @param string $username
	 * @param string|null $accountSuffix
	 * @param string $password
	 *
	 * @return false|string a string is returned if the authentication has been a success.
	 */
	public function authenticateAtActiveDirectory($username, $accountSuffix, $password)
	{
		// LDAP_Connection
		$this->ldapConnection->connect(new ConnectionDetails());

		// try to authenticate the user with $username $accountSuffix and $password
		$success = $this->ldapConnection->authenticate($username, $accountSuffix, $password);

		// stop if user could not be authenticated
		if (!$success) {
			$this->logger->error("User '$username' can not be authenticated.");

			return false;
		}

		return true;
	}

	/**
	 * @param Credentials $credentials
	 *
	 * @return bool|Credentials
	 * @throws Exception
	 */
	function postAuthentication(Credentials $credentials)
	{
		Assert::notNull($credentials, "credentials must not be null");

		// ADI-204: during login we have to use the authenticated user principal name
		$ldapAttributes = $this->attributeService->resolveLdapAttributes($credentials->toUserQuery());

		// ADI-395: wrong base DN leads to exception during Test Authentication
		// If the base DN is wrong then no LDAP attributes can be loaded and getRaw() is false
		if (false === $ldapAttributes->getRaw()) {
			$this->logger->error("Not creating/updating user because expected LDAP attributes could not be loaded.");
			return false;
		}

		// update the real sAMAccountName of the credentials. This could be totally different from the userPrincipalName user for login
		$this->updateCredentials($credentials, $ldapAttributes);

		// state: user is authenticated
		$this->loginState->setAuthenticationSucceeded();

		return $credentials;
	}

	/**
	 * Update the credential data (sAMAccountName, userPrincipalName, objectGUID) based upon the filtered LDAP attributes
	 *
	 * @param Credentials $credentials
	 * @param Attributes $ldapAttributes
	 * @pack
	 * @since 2.0.0
	 */
	function updateCredentials(Credentials $credentials, Attributes $ldapAttributes)
	{
		$credentials->setSAMAccountName($ldapAttributes->getFilteredValue('samaccountname'));
		$credentials->setObjectGuid($ldapAttributes->getFilteredValue('objectguid'));
		$credentials->setUserPrincipalName($ldapAttributes->getFilteredValue('userprincipalname'));
	}

	/**
	 * Get a WP_User instance for the user with login $login. Any user who is disabled will not be retrieved
	 *
	 * @param string $login Username or e-mail address
	 *
	 * @return false|WP_User
	 */
	public function getWordPressUser($login)
	{
		// get user id
		$userId = username_exists($login);

		if (!$userId) {
			$this->logger->debug("Local WordPress user '$login' could not be found");

			return false;
		}

		$this->logger->debug("User '$login' has local WordPress ID '$userId'.");

		return new \WP_User($userId);
	}

	/**
	 * @return Connection
	 */
	public function getLdapConnection()
	{
		return $this->ldapConnection;
	}

	/**
	 * @return Service
	 */
	public function getMultisiteConfigurationService()
	{
		return $this->multisiteConfigurationService;
	}

	/**
	 * @return Manager
	 */
	public function getUserManager()
	{
		return $this->userManager;
	}

	/**
	 * @return \Dreitier\Ldap\Attribute\Service
	 */
	public function getAttributeService()
	{
		return $this->attributeService;
	}

	/**
	 * @return Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}
}