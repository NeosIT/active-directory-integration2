<?php

namespace Dreitier\Nadi\Authentication\SingleSignOn;

use Dreitier\Ldap\Connection;
use Dreitier\Ldap\ConnectionDetails;
use Dreitier\Nadi\Authentication\AuthenticationException;
use Dreitier\Nadi\Authentication\LoginService;
use Dreitier\Nadi\Authentication\LogoutException;
use Dreitier\Nadi\Authentication\SingleSignOn\Profile\Locator;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\LoginState;
use Dreitier\Nadi\User\LoginSucceededService;
use Dreitier\Nadi\User\Manager;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\ArrayUtil;
use Dreitier\Util\Session\SessionHandler;
use Dreitier\Nadi\Authentication\Credentials;

/**
 * Handles the login procedure for single sign on.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Service extends LoginService
{
	const FAILED_SSO_PRINCIPAL = "failedSsoPrincipal";

	const USER_LOGGED_OUT = 'userLoggedOut';

	/** @var Logger */
	private $logger;

	/** @var Validator */
	private $validation;

	/** @var LoginSucceededService $loginSucceededService */
	private $loginSucceededService;

	/**
	 * @since 2.0.0
	 * @var Locator
	 */
	private $ssoProfileLocator;

	public function __construct(
		\Dreitier\WordPress\Multisite\Configuration\Service $multisiteConfigurationService,
		Connection                                          $ldapConnection,
		Manager                                             $userManager,
		\Dreitier\Ldap\Attribute\Service                    $attributeService,
		Validator                                           $validation,
		LoginState                                          $loginState,
		LoginSucceededService                               $loginSucceededService,
		Locator                                             $ssoProfileLocator
	)
	{
		parent::__construct($multisiteConfigurationService, $ldapConnection, $userManager, $attributeService, $loginState, $loginSucceededService);

		$this->validation = $validation;
		$this->loginSucceededService = $loginSucceededService;
		$this->ssoProfileLocator = $ssoProfileLocator;
		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Register all hooks for our single sign on.
	 *
	 * @issue ADI-659 added optional $increaseLogoutPriority parameter
	 *
	 * @param $increaseLogoutExecutionPriority
	 */
	public function register($increaseLogoutExecutionPriority = false)
	{
		// ADI-659 enable earlier execution than default 10 to enable wOffice compatibility
		add_action('wp_logout', array($this, 'logout'), $increaseLogoutExecutionPriority ? 1 : 10);
		add_action('init', array($this, 'authenticate'));
		// #160: do a redirect after the login has succeeded
		add_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded_do_redirect', array($this, 'doRedirect'));

		// for SSO we have to re-register the user-disabled hook
		add_filter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded', array($this->loginSucceededService, 'checkUserEnabled'), 15, 1);
		// after login has succeeded, we want the current identified user to be automatically logged in
		add_filter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded', array($this, 'loginUser'), 19, 1);
		// #142: register an additional filter for checking if the username is excluded; please note that this differs from the parent's basic_login_requires_ad_authentication filter
		add_filter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_sso_login_requires_ad_authentication', array($this, 'requiresActiveDirectoryAuthentication'), 10, 1);
		// #160: register filter to create target redirect URI after login
		add_filter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded_create_redirect_uri', array($this, 'createRedirectUri'), 10, 1);
	}

	/**
	 * Check if the user can be authenticated using user from the client machine.
	 *
	 * @param null $user
	 * @param string $login
	 * @param string $password
	 *
	 * @return bool
	 * @throws \Exception
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
			$this->logger->warning('Cannot find username for SSO.');

			return false;
		}

		// check, if NADI is not responsible for this username, e.g. in case of logging in an admin account
		if (!apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'auth_sso_login_requires_ad_authentication', $username)) {
			return false;
		}

		$credentials = $this->buildCredentials($username, '');
		$sessionHandler = $this->getSessionHandler();

		$this->clearAuthenticationState();
		$validation = $this->validation;

		try {
			$validation->validateUrl();
			$validation->validateLogoutState();
		} catch (LogoutException $e) {
			$this->logger->info("Skipping further authentication because user is being logged out");
			return false;
		}

		try {
			$validation->validateAuthenticationState($credentials);

			// encapsulate the authentication process
			$credentials = $this->delegateAuth($credentials, $validation);

			// authenticate the given user and run the default procedure from the LoginService
			$authenticatedCredentials = $this->parentAuthenticate($credentials);

			if (!$authenticatedCredentials) {
				throw new AuthenticationException("Unable to authenticate user " . $credentials->getUserPrincipalName());
			}

			// as SSO runs during the "init" phase, we need to call the 'authorize' filter on our own
			apply_filters('authorize', $authenticatedCredentials);
			apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded', $authenticatedCredentials);

			// if our user is authenticated and we have a WordPress user, we
			$sessionHandler->clearValue(self::FAILED_SSO_PRINCIPAL);
		} catch (AuthenticationException $e) {
			$this->logger->error('User could not be authenticated using SSO. ' . $e->getMessage());
			$sessionHandler->setValue(self::FAILED_SSO_PRINCIPAL, $credentials->getLogin());

			return false;
		}

		return true;
	}

	/**
	 * Execute the authentication by looking up the username (sAMAccountName, userPrincipalName or Kerberos principal) inside the Active Directory.
	 *
	 * @param Credentials $credentials
	 * @param $validation
	 * @return Credentials
	 * @throws AuthenticationException
	 * @since 2.2.0
	 */
	function delegateAuth(Credentials $credentials, $validation)
	{
		// let our locator find a matching profile, based upon the given credentials
		$profileMatch = $this->ssoProfileLocator->locate($credentials);

		// #152, NADISUP-7: Critical WordPress error if a matching profile for SSO authentication can not be found
		if (!$profileMatch) {
			throw new AuthenticationException("Unable to locate a matching profile for '" . $credentials->getLogin() . "'");
		}

		// a valid profile is required for login
		$validation->validateProfile($profileMatch->getProfile());

		$this->logger->debug("Valid SSO profile for type '" . $profileMatch->getType() . "' found");
		// fire a hook to inform that one of the SSO profiles has been matched
		do_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'sso_profile_located', $credentials, $profileMatch);

		$this->openLdapConnection($profileMatch->getProfile());

		$ldapAttributes = $this->getAttributeService()->resolveLdapAttributes($credentials->toUserQuery());

		if ($ldapAttributes->getRaw() == false) {
			throw new AuthenticationException("User '" . $credentials->getLogin() . "' does not exist in Active Directory'");
		}

		// update the user's credentials, so we have valid a userPrincipalName, GUID and sAMAccountName based upon recent Active Directory data
		$this->updateCredentials($credentials, $ldapAttributes);

		return $credentials;
	}

	/**
	 * Delegate to parent authenticate method call.
	 *
	 * @param Credentials $credentials
	 *
	 * @return false|Credentials
	 * @throws \Exception
	 */
	public function parentAuthenticate($credentials)
	{
		return parent::authenticate(null, $credentials->getUserPrincipalName());
	}

	/**
	 * Clear the session values for failed sso or manual logout if the user wants to retry authentication over SSO.
	 */
	protected function clearAuthenticationState()
	{
		if ('sso' === ArrayUtil::get('reauth', $_GET, false)) {
			$this->getSessionHandler()->clearValue(self::FAILED_SSO_PRINCIPAL);
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
		$envVariable = $this->getMultisiteConfigurationService()->getOptionValue(Options::SSO_ENVIRONMENT_VARIABLE);
		$username = ArrayUtil::get($envVariable, $_SERVER);

		if (empty($username)) {
			$username = "";
		}

		// ADI-357 unescape already escaped username
		$unescape = stripslashes($username);

		$this->logger->debug('SSO provided username for environment variable "' . $envVariable . '" is "' . $username . "'");

		return $unescape;
	}

	/**
	 * Open the LDAP connection using the configuration from the profile.
	 *
	 * @param $profile
	 *
	 * @throws AuthenticationException if the connection could not be opened
	 */
	protected function openLdapConnection($profile)
	{
		$connectionDetails = $this->createConnectionDetailsFromProfile($profile);
		$this->getLdapConnection()->connect($connectionDetails);

		$this->validation->validateLdapConnection($this->getLdapConnection());
	}

	/**
	 * Since the web server already authenticated the user at this point we can simply return true.
	 * The LoginService.php post authentication will check if the user is authorized and the createAndUpdate method will
	 * return false if the given user could not be found. @CKL and @DME discussed several corner cases but we could not
	 * find a problem with this solution.
	 *
	 * @param Credentials $credentials
	 * @param array $suffixes
	 * @return bool|Credentials
	 * @throws \Exception
	 * @since 2.0.0
	 */
	public function tryAuthenticatableSuffixes(Credentials $credentials, $suffixes = array())
	{
		$this->logger->info("User has been authenticated through SSO, running post authentication");
		return $this->postAuthentication($credentials);
	}

	/**
	 * It returns the suffix itself as we have been already authenticated previously
	 *
	 * @param string $suffix
	 * @return array
	 */
	public function detectAuthenticatableSuffixes($suffix)
	{
		// there is no more logic required; we just want to make sure that the parent's tryAuthenticatableSuffixes method call is executed
		$this->logger->info("Authenticatable suffixes are ignored");
		return array($suffix);
	}

	/**
	 * Create new {@link Connection} using the given data from the profile.
	 *
	 * @param $profile
	 *
	 * @return ConnectionDetails
	 */
	protected function createConnectionDetailsFromProfile($profile)
	{
		$connection = new ConnectionDetails();
		$connection->setDomainControllers($profile[Options::DOMAIN_CONTROLLERS]);
		$connection->setPort($profile[Options::PORT]);
		$connection->setEncryption($profile[Options::ENCRYPTION]);
		$connection->setNetworkTimeout($profile[Options::NETWORK_TIMEOUT]);
		$connection->setBaseDn($profile[Options::BASE_DN]);
		$connection->setUsername($profile[Options::SSO_USER]);
		$connection->setPassword($profile[Options::SSO_PASSWORD]);

		return $connection;
	}

	/**
	 * Set a session flag that the user has logged out manually.
	 */
	public function logout()
	{
		$this->getSessionHandler()->setValue(self::USER_LOGGED_OUT, true);
	}

	/**
	 * If the user is not logged in, perform a login for the given user.
	 *
	 * @param WP_User $user
	 * @param boolean $exit
	 *
	 * @return WP_User
	 */
	public function loginUser($user, $exit = true)
	{
		if (!($user instanceof \WP_User)) {
			return $user;
		}

		$secure_cookie = is_ssl();
		wp_set_current_user($user->ID, $user->user_login);
		wp_set_auth_cookie($user->ID, true, $secure_cookie);

		do_action('wp_login', $user->user_login, $user);
		do_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded_do_redirect', $user, $exit);

		return $user;
	}

	/**
	 * Create a URI for redirection after a successful login.
	 *
	 * @issue #160
	 * @return mixed|string
	 */
	public function createRedirectUri($redirectTo = '')
	{
		$redirectTo = (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : "";

		/*
 		 * ADI-644
 		 * This check and redirect to the home url is required for the SSO Login to work.
 		 * When a users logs out of its WordPress account it will be redirected to the "/wp-login.php" with loggedout=true
		 * query parameter. If a user now tries to login via the SSO Login link, the REQUEST_URI will contain loggedout=true
		 * this will trigger the WordPress logout logic which will then logout the user instantly after an successful authentication.
		 * To prevent this we check for reauth and redirect the user to the home url. At the moment we think this is the best workaround
		 * due we did not find any corner cases, yet.
 		 */

		if (strpos($redirectTo, 'reauth=sso') !== false) {
			$redirectTo = home_url('/');
		}

		// default redirect if WordPress forces itself a login, e.g. when accessing /wp-admin
		$redirectTo = (!empty($_REQUEST['redirect_to'])) ? $_REQUEST['redirect_to'] : $redirectTo;
		// if not set, fall back to the home url
		$redirectTo = empty($redirectTo) ? home_url('/') : $redirectTo;

		return $redirectTo;
	}

	/**
	 * Execute the redirection after a successful login.
	 *
	 * @issue #160
	 * @param $user
	 * @param $exit
	 * @return void
	 */
	public function doRedirect($user, $exit = true)
	{
		$redirectTo = apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded_create_redirect_uri', '');
		$exitAfterRedirect = apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'login_succeeded_exit_after_redirect', $exit);

		if (!empty($redirectTo)) {
			wp_safe_redirect($redirectTo);
		}

		if ($exitAfterRedirect) {
			exit;
		}
	}

	/**
	 * Return the current session handler.
	 *
	 * @return SessionHandler
	 */
	protected function getSessionHandler()
	{
		return SessionHandler::getInstance();
	}
}