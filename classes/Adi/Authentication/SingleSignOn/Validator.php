<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_SingleSignOn_Validator')) {
	return;
}

/**
 * Adi_Authentication_SingleSignOn_Validator provides validation methods. These validation methods will be used during
 * the single sign on procedure.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Adi_Authentication_SingleSignOn_Validator
{
	const FAILED_SSO_UPN = Adi_Authentication_SingleSignOn_Service::FAILED_SSO_UPN;

	const USER_LOGGED_OUT = Adi_Authentication_SingleSignOn_Service::USER_LOGGED_OUT;

	/**
	 * Check if the given {@link Ldap_Connection} is connected.
	 *
	 * @param $ldapConnection
	 *
	 * @throws Adi_Authentication_Exception
	 */
	public function validateLdapConnection(Ldap_Connection $ldapConnection)
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
	 * @throws Adi_Authentication_Exception
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
	 * @throws Adi_Authentication_Exception
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
	 * @param Adi_Authentication_Credentials $credentials
	 *
	 * @throws Adi_Authentication_Exception
	 */
	public function validateAuthenticationState(Adi_Authentication_Credentials $credentials)
	{
		$failedAuthenticateUsername = $this->getSessionHandler()->getValue(self::FAILED_SSO_UPN);

		if ($failedAuthenticateUsername === $credentials->getUserPrincipalName()) {
			$this->throwAuthenticationException('User has already failed to authenticate. Stop retrying.');
		}
	}

	/**
	 * Check if the user logged did not log out manually.
	 *
	 * @throws Adi_Authentication_Exception if the user logged out manually.
	 */
	public function validateLogoutState()
	{
		$userLoggedOut = $this->getSessionHandler()->getValue(self::USER_LOGGED_OUT, false);

		if ($userLoggedOut) {
			$this->throwAuthenticationException('User will not be logged in via SSO b/c he logged out manually.');
		}
	}

	/**
	 * Check if the user is on a valid page.
	 *
	 * @throws Adi_Authentication_Exception if the user is on the logout page
	 */
	public function validateUrl()
	{
		if ('logout' === Core_Util_ArrayUtil::get('action', $_GET, false)) {
			$this->throwAuthenticationException('User cannot be logged in on logout action.');
		}
	}

	/**
	 * Simple method for throwing a {@link Adi_Authentication_Exception}.
	 *
	 * @param $message
	 *
	 * @throws Adi_Authentication_Exception
	 */
	protected function throwAuthenticationException($message)
	{
		throw new Adi_Authentication_Exception($message);
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