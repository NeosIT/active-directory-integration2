<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ldap_ConnectionDetails')) {
	return;
}

/**
 * Ldap_ConnectionDetails contains all details for an LDAP connection to the Active Directory..
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Ldap_ConnectionDetails
{
	private $baseDn = null;
	private $domainControllers = null;
	private $port = null;
	private $useStartTls = null;
	private $networkTimeout = null;
	private $username = null;
	private $password = null;

	/**
	 * Get the custom base dn.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getCustomBaseDn()
	{
		return $this->baseDn;
	}

	/**
	 * Override the default base dn (set by the blog or site admin) with your own custom value.
	 * @param null $baseDn
	 */
	public function setCustomBaseDn($baseDn)
	{
		$this->baseDn = $baseDn;
	}

	/**
	 * Get the custom domain controllers.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getCustomDomainControllers()
	{
		return $this->domainControllers;
	}

	/**
	 * Override the default domain controllers (set by the blog or site admin) with your own custom value.
	 * @param string $domainControllers splitted with semicolon
	 */
	public function setCustomDomainControllers($domainControllers)
	{
		$this->domainControllers = $domainControllers;
	}

	/**
	 * Get the custom port.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getCustomPort()
	{
		return $this->port;
	}

	/**
	 * Override the default portset by the blog or site admin) with your own custom value.
	 * @param int $port
	 */
	public function setCustomPort($port)
	{
		$this->port = $port;
	}

	/**
	 * Get the custom usage of StartTLS.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getCustomUseStartTls()
	{
		return $this->useStartTls;
	}

	/**
	 * Override the default usage of StartTLS (set by the blog or site admin) with your own custom value.
	 * @param bool $useStartTls
	 */
	public function setCustomUseStartTls($useStartTls)
	{
		$this->useStartTls = $useStartTls;
	}

	/**
	 * Get the custom network timeout.
	 * If this value is null, the Connection.php will use the value set by the blog or network admin.
	 * @return null
	 */
	public function getCustomNetworkTimeout()
	{
		return $this->networkTimeout;
	}

	/**
	 * Override the default network timeout (set by the blog or site admin) with your own custom value.
	 * @param int $networkTimeout
	 */
	public function setCustomNetworkTimeout($networkTimeout)
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