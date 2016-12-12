<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_SingleSignOn_Validator')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_SingleSignOn_Validator provides validation methods. These validation methods will be used during
 * the single sign on procedure.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Adi_Authentication_SingleSignOn_Validator
{
	const FAILED_SSO_UPN = NextADInt_Adi_Authentication_SingleSignOn_Service::FAILED_SSO_UPN;

	const USER_LOGGED_OUT = NextADInt_Adi_Authentication_SingleSignOn_Service::USER_LOGGED_OUT;

	/**
	 * Check if the given {@link NextADInt_Ldap_Connection} is connected.
	 *
	 * @param $ldapConnection
	 *
	 * @throws NextADInt_Adi_Authentication_Exception
	 */
	public function validateLdapConnection(NextADInt_Ldap_Connection $ldapConnection)
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
	 * @throws NextADInt_Adi_Authentication_Exception
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
	 * @throws NextADInt_Adi_Authentication_Exception
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
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 *
	 * @throws NextADInt_Adi_Authentication_Exception
	 */
	public function validateAuthenticationState(NextADInt_Adi_Authentication_Credentials $credentials)
	{
		$failedAuthenticateUsername = $this->getSessionHandler()->getValue(self::FAILED_SSO_UPN);

		if ($failedAuthenticateUsername === $credentials->getUserPrincipalName()) {
			$this->throwAuthenticationException('User has already failed to authenticate. Stop retrying.');
		}
	}

	/**
	 * Check if the user logged did not log out manually.
	 *
	 * @throws NextADInt_Adi_Authentication_Exception if the user logged out manually.
	 */
	public function validateLogoutState()
	{
		$userLoggedOut = $this->getSessionHandler()->getValue(self::USER_LOGGED_OUT, false);

		if ($userLoggedOut) {
			throw new NextADInt_Adi_Authentication_LogoutException('User will not be logged in via SSO b/c he logged out manually.');
		}
	}

	/**
	 * Check if the user is on a valid page.
	 *
	 * @throws NextADInt_Adi_Authentication_Exception if the user is on the logout page
	 */
	public function validateUrl()
	{
		if ('logout' === NextADInt_Core_Util_ArrayUtil::get('action', $_GET, false)) {
			throw new NextADInt_Adi_Authentication_LogoutException('User cannot be logged in on logout action.');
		}
	}

	/**
	 * Simple method for throwing a {@link NextADInt_Adi_Authentication_Exception}.
	 *
	 * @param $message
	 *
	 * @throws NextADInt_Adi_Authentication_Exception
	 */
	protected function throwAuthenticationException($message)
	{
		throw new NextADInt_Adi_Authentication_Exception($message);
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