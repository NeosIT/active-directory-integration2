<?php

namespace Dreitier\Ldap;

use Dreitier\Nadi\Authentication\Credentials;

/**
 * Value object for doing a user search
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 * @since 2.0.0
 */
class UserQuery
{
	/**
	 * @var string sAMAccountName, userPrincipalName, netbiosName
	 */
	private $principal;

	/**
	 * @var boolean
	 */
	private $isGuid = false;

	/**
	 * @var Credentials
	 */
	private $credentials = null;

	private function __construct(?Credentials $credentials = null, ?string $principal = null)
	{
		$this->principal = $principal;
		$this->credentials = $credentials;
	}

	/**
	 * Mark that the principal is a guid
	 * @return $this
	 */
	public function setGuid()
	{
		$this->isGuid = true;
		return $this;
	}

	/**
	 * Return if this query is for a guid
	 * @return bool
	 */
	public function isGuid()
	{
		return $this->isGuid;
	}

	/**
	 * @return mixed|string|null
	 */
	public function getPrincipal()
	{
		return $this->principal;
	}

	/**
	 * @return Credentials|null
	 */
	public function getCredentials()
	{
		return $this->credentials;
	}

	/**
	 * Creates a new UserQuery instance with the given principal but the same credentials;
	 * @param string $principal
	 * @return UserQuery
	 */
	public function withPrincipal(string $principal)
	{
		return new UserQuery($this->credentials, $principal);
	}

	public function withGuid(string $guid)
	{
		$r = new UserQuery($this->credentials, $guid);
		return $r->setGuid();
	}

	/**
	 * Factory method to create a new UserQuery based upon a principal
	 *
	 * @param string $principal
	 * @param Credentials|null $credentials
	 * @return UserQuery
	 */
	public static function forPrincipal($principal, ?Credentials $credentials = null)
	{
		return new UserQuery($credentials, $principal);
	}

	/**
	 * Factory method to create a new UserQuery solely based upon a Credentials instance
	 * @param Credentials $credentials
	 * @return UserQuery
	 */
	public static function forCredentials(Credentials $credentials)
	{
		return new UserQuery($credentials);
	}

	/**
	 * Factory method to create a new UserQuery based upon an objectGuid
	 * @param string $guid
	 * @param Credentials|null $credentials
	 * @return mixed
	 */
	public static function forGuid($guid, ?Credentials $credentials = null)
	{
		$r = new UserQuery($credentials, $guid);
		return $r->setGuid();
	}

	public function __toString()
	{
		return "UserQuery={principal='" . $this->principal . "',isGuid='" . $this->isGuid() . "'}";
	}
}