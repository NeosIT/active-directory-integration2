<?php

namespace Dreitier\Nadi;

/**
 * Contain the current state of authentication and authorization of a user login workflow.
 * This is used to decouple some classes and make testing easier.
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
class LoginState
{
	/**
	 * Indicate that the user has been logged in by NADI
	 * @var bool
	 */
	private $authenticated = false;

	/**
	 * null indicates that no authorization has been done
	 * @var bool|null
	 */
	private $authorized = null;

	/**
	 * Return if user has been authenticated by NADI
	 * @return bool
	 */
	public function isAuthenticated()
	{
		return $this->authenticated;
	}

	/**
	 * Return true if user has been authorized by NADI
	 * @return bool
	 */
	public function isAuthorized()
	{
		return $this->authorized;
	}

	/**
	 * Set the NADI authentication has succeeded
	 */
	public function setAuthenticationSucceeded()
	{
		$this->authenticated = true;
	}

	/**
	 * Set the NADI authorization has succeeded
	 */
	public function setAuthorizationSucceeded()
	{
		$this->authorized = true;
	}

	public function setAuthorizationFailed()
	{
		$this->authorized = false;
	}

	/**
	 * Return true if authentication is valid and authorization is valid or not done
	 */
	public function isAuthenticatedAndAuthorized()
	{
		return $this->isAuthenticated() && ($this->isAuthorized() !== false);
	}
}
