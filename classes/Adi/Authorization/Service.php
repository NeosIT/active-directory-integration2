<?php
if ( ! defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authorization_Service')) {
	return;
}

/**
 * NextADInt_Adi_Authorization_Service contains Authorization procedures like checking for NADI authorization groups
 *
 * @author  Christopher Klein <ckl@neos-it.de>
 * @since 2.1.8
 * @access
 */
class NextADInt_Adi_Authorization_Service
{
	/** @var Logger */
	private $logger;

	/**
	 * @var NextADInt_Multisite_Configuration_Service
	 */
	private $configuration;

	/**
	 * @var NextADInt_Adi_User_Manager
	 */
	private $userManager;

	/**
	 * @var NextADInt_Adi_Role_Manager
	 */
	private $roleManager;

	/**
	 * @var NextADInt_Adi_LoginState
	 */
	private $loginState;

	/**
	 * NextADInt_Adi_Authorization_Service constructor.
	 *
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Adi_User_Manager $userManager
	 * @param NextADInt_Adi_Role_Manager $roleManager
	 * @param NextADInt_Adi_LoginState $loginState
	 */
	public function __construct(
		NextADInt_Multisite_Configuration_Service $configuration,
		NextADInt_Adi_User_Manager $userManager,
		NextADInt_Adi_Role_Manager $roleManager,
		NextADInt_Adi_LoginState $loginState
	) {
		$this->logger = NextADInt_Core_Logger::getLogger();

		$this->configuration = $configuration;
		$this->userManager   = $userManager;
		$this->roleManager   = $roleManager;
		$this->loginState    = $loginState;
	}

	public function register()
	{
		// we register authorizeAfterAuthentication as last filter in the authentication chain so we can do post-authentication things aka authorization
		add_filter('authenticate', array($this, 'authorizeAfterAuthentication'), 15, 3);

		// we need our own authorize filter so we can trigger authorization from SSO and connectivity test page
		add_filter('authorize', array($this, 'isUserInAuthorizationGroup'), 10, 1);
	}

	/**
	 * Authorization callback will be run as last 'authenticate' filter in chain
	 *
	 * @param $wpUser
	 * @param $username
	 * @param null $password
	 *
	 * @return mixed
	 */
	public function authorizeAfterAuthentication($authenticatedCredentials, $username, $password = null)
	{
		return apply_filters('authorize', $authenticatedCredentials);
	}

	/**
	 * Check if authorization is required.
	 * Authorization is *not* required if
	 * <ul>
	 * <li>a user could has not been previously authenticated</li>
	 * <li>or the user i the first user of a WordPress instance: the administrator</li>
	 * </ul>
	 *
	 * @param $authenticatedCredentials
	 *
	 * @return boolean
	 */
	function checkAuthorizationRequired($authenticatedCredentials)
	{
		// if any error has previously occurred or we could not find any valid user, we don't have to proceed
		if ( ! ($authenticatedCredentials instanceof NextADInt_Adi_Authentication_Credentials)) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the given user is an AD user and has a
	 *
	 * @param null|NextADInt_Adi_Authentication_Credentials|WP_Error $authenticatedCredentials
	 *
	 * @return WP_Error|NextADInt_Adi_Authentication_Credentials
	 * @throws Exception
	 */
	public function isUserInAuthorizationGroup($authenticatedCredentials)
	{
		if (!$this->checkAuthorizationRequired($authenticatedCredentials)) {
			// don't process further authorization group checks
			return $authenticatedCredentials;
		}

		$authorizeByGroup = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::AUTHORIZE_BY_GROUP);

		if (!$authorizeByGroup) {
			// authorization by group has not been enabled, so do not check further
			return $authenticatedCredentials;
		}

		// user should have been synchronized before
		$userGuid = $authenticatedCredentials->getObjectGuid();
		// user must have been authenticated at the Active Directory so we have an available AD/LDAP connection for fetching his roles
		$hasBeenAuthenticatedAtActiveDirectory = $this->loginState->isAuthenticated();

		if (!$userGuid || ! $hasBeenAuthenticatedAtActiveDirectory) {
			$this->logger->warn("User has no GUID or has not been previously authenticated at the Active Directory; can't check role membership");
			// without having GUID and being authenticated we must assume that the user is a local user.
			// As a local user we can't check for AD group membership and so he is authorized.
			return $authenticatedCredentials;
		}

		// create role mapping with user's GUID
		$roleMapping = $this->roleManager->createRoleMapping($userGuid);

		if ( ! $this->roleManager->isInAuthorizationGroup($roleMapping)) {
			$this->loginState->setAuthorizationFailed();
			$this->logger->error("User with GUID: '$userGuid' is not in an authorization group.");

			return new WP_Error('user_not_authorized', __('<strong>ERROR</strong>: You could not be authorized'));
		}

		$this->logger->info('User is in authorization group and has been authorized');

		return $authenticatedCredentials;
	}
}