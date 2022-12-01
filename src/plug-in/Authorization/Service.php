<?php

namespace Dreitier\Nadi\Authorization;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\LoginState;
use Dreitier\Nadi\User\Manager;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Nadi\Authentication\Credentials;

/**
 * Service contains authorization procedures like checking for NADI authorization groups
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @since 2.1.8
 * @access
 */
class Service
{
	/** @var Logger */
	private $logger;

	/**
	 * @var \Dreitier\WordPress\Multisite\Configuration\Service
	 */
	private $multisiteConfigurationService;

	/**
	 * @var Manager
	 */
	private $userManager;

	/**
	 * @var \Dreitier\Nadi\Role\Manager
	 */
	private $roleManager;

	/**
	 * @var LoginState
	 */
	private $loginState;

	/**
	 * @param \Dreitier\WordPress\Multisite\Configuration\Service $multisiteConfigurationService
	 * @param Manager $userManager
	 * @param \Dreitier\Nadi\Role\Manager $roleManager
	 * @param LoginState $loginState
	 */
	public function __construct(
		\Dreitier\WordPress\Multisite\Configuration\Service $multisiteConfigurationService,
		Manager                                             $userManager,
		\Dreitier\Nadi\Role\Manager                         $roleManager,
		LoginState                                          $loginState
	)
	{
		$this->logger = NadiLog::getInstance();

		$this->multisiteConfigurationService = $multisiteConfigurationService;
		$this->userManager = $userManager;
		$this->roleManager = $roleManager;
		$this->loginState = $loginState;
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
		if (!($authenticatedCredentials instanceof Credentials)) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the given user is an AD user and has a
	 *
	 * @param null|Credentials|\WP_Error $authenticatedCredentials
	 *
	 * @return \WP_Error|Credentials
	 * @throws \Exception
	 */
	public function isUserInAuthorizationGroup($authenticatedCredentials)
	{
		if (!$this->checkAuthorizationRequired($authenticatedCredentials)) {
			// don't process further authorization group checks
			return $authenticatedCredentials;
		}

		$authorizeByGroup = $this->multisiteConfigurationService->getOptionValue(Options::AUTHORIZE_BY_GROUP);

		if (!$authorizeByGroup) {
			// authorization by group has not been enabled, so do not check further
			return $authenticatedCredentials;
		}

		// user should have been synchronized before
		$userGuid = $authenticatedCredentials->getObjectGuid();
		// user must have been authenticated at the Active Directory so we have an available AD/LDAP connection for fetching his roles
		$hasBeenAuthenticatedAtActiveDirectory = $this->loginState->isAuthenticated();

		if (!$userGuid || !$hasBeenAuthenticatedAtActiveDirectory) {
			$this->logger->warning("User has no GUID or has not been previously authenticated at the Active Directory; can't check role membership");
			// without having GUID and being authenticated we must assume that the user is a local user.
			// As a local user we can't check for AD group membership and so he is authorized.
			return $authenticatedCredentials;
		}

		// create role mapping with user's GUID
		$roleMapping = $this->roleManager->createRoleMapping($userGuid);

		if (!$this->roleManager->isInAuthorizationGroup($roleMapping)) {
			$this->loginState->setAuthorizationFailed();
			$this->logger->error("User with GUID: '$userGuid' is not in an authorization group.");

			return new \WP_Error('user_not_authorized', __('<strong>ERROR</strong>: You could not be authorized'));
		}

		$this->logger->info('User is in authorization group and has been authorized');

		return $authenticatedCredentials;
	}
}