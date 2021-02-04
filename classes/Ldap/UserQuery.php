<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_UserQuery')) {
	return;
}

/**
 * Value object for doing a user search
 *
 * @author Christopher Klein <me[at]schakko[dot]de>
 * @access public
 * @since 2.0.0
 */
class NextADInt_Ldap_UserQuery
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
	 * @var NextADInt_Adi_Authentication_Credentials
	 */
	private $credentials = null;

	private function __construct(NextADInt_Adi_Authentication_Credentials $credentials = null, $principal = null)
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
	 * @return NextADInt_Adi_Authentication_Credentials|null
	 */
	public function getCredentials()
	{
		return $this->credentials;
	}

	/**
	 * Creates a new NextADInt_Ldap_UserQuery instance with the given principal but the same credentials;
	 * @param string $principal
	 * @return NextADInt_Ldap_UserQuery
	 */
	public function withPrincipal(string $principal)
	{
		return new NextADInt_Ldap_UserQuery($this->credentials, $principal);
	}

	public function withGuid(string $guid)
	{
		$r = new NextADInt_Ldap_UserQuery($this->credentials, $guid);
		return $r->setGuid();
	}

	/**
	 * Factory method to create a new UserQuery based upon a principal
	 *
	 * @param string $principal
	 * @param NextADInt_Adi_Authentication_Credentials|null $credentials
	 * @return NextADInt_Ldap_UserQuery
	 */
	public static function forPrincipal($principal, NextADInt_Adi_Authentication_Credentials $credentials = null)
	{
		return new NextADInt_Ldap_UserQuery($credentials, $principal);
	}

	/**
	 * Factory method to create a new UserQuery solely based upon a Credentials instance
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @return NextADInt_Ldap_UserQuery
	 */
	public static function forCredentials(NextADInt_Adi_Authentication_Credentials $credentials)
	{
		return new NextADInt_Ldap_UserQuery($credentials);
	}

	/**
	 * Factory method to create a new UserQuery based upon an objectGuid
	 * @param string $guid
	 * @param NextADInt_Adi_Authentication_Credentials|null $credentials
	 * @return mixed
	 */
	public static function forGuid($guid, NextADInt_Adi_Authentication_Credentials $credentials = null)
	{
		$r = new NextADInt_Ldap_UserQuery($credentials, $guid);
		return $r->setGuid();
	}

	public function __toString()
	{
		return "UserQuery={principal='" . $this->principal . "',isGuid='" . $this->isGuid() . "'}";
	}
}