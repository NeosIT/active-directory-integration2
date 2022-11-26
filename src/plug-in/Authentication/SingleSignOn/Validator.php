<?php

namespace Dreitier\Nadi\Authentication\SingleSignOn;

use Dreitier\Ldap\Connection;
use Dreitier\Nadi\Authentication\AuthenticationException;
use Dreitier\Nadi\Authentication\LogoutException;
use Dreitier\Util\ArrayUtil;
use Dreitier\Util\Session\SessionHandler;
use Dreitier\Nadi\Authentication\Credentials;

/**
 * Validator provides validation methods. These validation methods will be used during
 * the single sign on procedure.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Validator
{
	/**
	 * Check if the given {@link Connection} is connected.
	 *
	 * @param $ldapConnection
	 *
	 * @throws AuthenticationException
	 */
	public function validateLdapConnection(Connection $ldapConnection)
	{
		if (!$ldapConnection->isConnected()) {
			$this->throwAuthenticationException('Cannot connect to ldap. Check the connection.');
		}
	}

	/**
	 * Check if the given user is valid.
	 *
	 * @param $user
	 *
	 * @throws AuthenticationException
	 */
	public function validateUser($user)
	{
		if (false === $user || !is_a($user, 'WP_User')) {
			$this->throwAuthenticationException('The given user is invalid.');
		}
	}

	/**
	 * Check if the given profile is valid.
	 *
	 * @param $profile
	 *
	 * @throws AuthenticationException
	 */
	public function validateProfile($profile)
	{
		if (null === $profile) {
			$this->throwAuthenticationException('No profile found for authentication.');
		}
	}

	/**
	 * Check if the authentication has not failed.
	 *
	 * @param Credentials $credentials
	 *
	 * @throws AuthenticationException
	 */
	public function validateAuthenticationState(Credentials $credentials)
	{
		$failedAuthenticateUsername = $this->getSessionHandler()->getValue(Service::FAILED_SSO_PRINCIPAL);

		if (!empty($failedAuthenticateUsername) && ($failedAuthenticateUsername === $credentials->getLogin())) {
			$this->throwAuthenticationException('User has already failed to authenticate. Stop retrying.');
		}
	}

	/**
	 * Check if the user logged did not log out manually.
	 *
	 * @throws LogoutException if the user logged out manually.
	 */
	public function validateLogoutState()
	{
		$userLoggedOut = $this->getSessionHandler()->getValue(Service::USER_LOGGED_OUT, false);

		if ($userLoggedOut) {
			throw new LogoutException('User will not be logged in via SSO b/c he logged out manually.');
		}
	}

	/**
	 * Check if the user is on a valid page.
	 *
	 * @throws LogoutException if the user is on the logout page
	 */
	public function validateUrl()
	{
		if ('logout' === ArrayUtil::get('action', $_GET, false)) {
			throw new LogoutException('User cannot be logged in on logout action.');
		}
	}

	/**
	 * Simple method for throwing a {@link AuthenticationException}.
	 *
	 * @param $message
	 *
	 * @throws AuthenticationException
	 */
	protected function throwAuthenticationException($message)
	{
		throw new AuthenticationException($message);
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