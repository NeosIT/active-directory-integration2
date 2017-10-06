<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_ConnectionDetails')) {
	return;
}

/**
 * NextADInt_Ldap_Connection contains all details for an LDAP connection to the Active Directory.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Ldap_ConnectionDetails
{
	private $baseDn = null;
	private $domainControllers = null;
	private $port = null;
	private $useStartTls = null;
	private $encryption = null;
	private $networkTimeout = null;
	private $username = null;
	private $password = null;

	/**
	 * Get the custom base dn.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getBaseDn()
	{
		return $this->baseDn;
	}

	/**
	 * Override the default base dn (set by the blog or site admin) with your own custom value.
	 * @param null $baseDn
	 */
	public function setBaseDn($baseDn)
	{
		$this->baseDn = $baseDn;
	}

	/**
	 * Get the custom domain controllers.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getDomainControllers()
	{
		return $this->domainControllers;
	}

	/**
	 * Override the default domain controllers (set by the blog or site admin) with your own custom value.
	 * @param string $domainControllers splitted with semicolon
	 */
	public function setDomainControllers($domainControllers)
	{
		$this->domainControllers = $domainControllers;
	}

	/**
	 * Get the custom port.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getPort()
	{
		return $this->port;
	}

	/**
	 * Override the default portset by the blog or site admin) with your own custom value.
	 * @param int $port
	 */
	public function setPort($port)
	{
		$this->port = $port;
	}

	/**
	 * Return the encryption type. This can be "none", "starttls" or "ldaps"
	 * @return string
	 */
	public function getEncryption()
	{
		return $this->encryption;
	}

	/**
	 * @param string $encryption "none", "starttls" or "ldaps"
	 */
	public function setEncryption($encryption)
	{
		if (!isset($encryption)) {
			$encryption = 'none';
		}

        NextADInt_Core_Assert::condition(
			in_array(NextADInt_Core_Util_StringUtil::toLowerCase($encryption), NextADInt_Multisite_Option_Encryption::getValues()),
			'Encryption type must be one of none, starttls or ldaps');

		$this->encryption = $encryption;
	}

	/**
	 * Get the custom network timeout.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getNetworkTimeout()
	{
		return $this->networkTimeout;
	}

	/**
	 * Override the default network timeout (set by the blog or site admin) with your own custom value.
	 * @param int $networkTimeout
	 */
	public function setNetworkTimeout($networkTimeout)
	{
		$this->networkTimeout = $networkTimeout;
	}

	/**
	 * Get the username for connection to the Active Directory.
	 * @return null
	 */
	public function getUsername()
	{
		return $this->username;
	}

	/**
	 * Set the username for connection to the Active Directory.
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}

	/**
	 * Get the passwort for connection to the Active Directory.
	 * @return null
	 */
	public function getPassword()
	{
		return $this->password;
	}

	/**
	 * Set the password for connection to the Active Directory.
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}
}