<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_Credentials')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_Credentials encapsulates login credentials.
 * This class is mutable so parts of the credentials can be updated due to AD/LDAP lookups.
 *
 * @author  Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Authentication_Credentials
{
	/** @var string */
	private $login;

	/** @var string */
	private $netbiosName;

	/** @var  string */
	private $sAMAccountName;

	/** @var  string */
	private $upnUsername;

	/** @var string suffix */
	private $upnSuffix;

	/** @var string */
	private $password;

	/** @var NextADInt_Core_Logger */
	private $logger;

	/**
	 * NextADInt_Adi_Authentication_Credentials constructor.
	 *
	 * @param string $login Login in form 'username' (sAMAccountName), 'username@domain' (userPrincipalName) or 'NETBIOS\sAMAccountName'
	 * @param string $password
	 *
	 * @throws Exception
	 */
	public function __construct($login = '', $password = '')
	{
		$this->logger = NextADInt_Core_Logger::getLogger();
		$this->setLogin($login);
		$this->setPassword($password);
	}

	/**
	 * @param $login
	 */
	public function setLogin($login) {
		$this->login = $login;
	}

	/**
	 * @return string
	 */
	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * Get suffix without any '@' character
	 * @return string
	 */
	public function getUpnSuffix()
	{
		return $this->upnSuffix;
	}

	/**
	 * Set UPN suffix
	 *
	 * @param $upnSuffix
	 */
	public function setUpnSuffix($upnSuffix)
	{
		if (0 === strpos($upnSuffix, '@')) {
			$upnSuffix = substr($upnSuffix, 1);
		}

		$this->upnSuffix = $upnSuffix;
	}

	/**
	 * Get UPN
	 * @return string
	 */
	public function getUserPrincipalName()
	{
		$r = $this->upnUsername;

		if (!empty($this->upnSuffix)) {
			$r .= '@' . $this->upnSuffix;
		}

		return $r;
	}

	/**
	 * Set the user principal name
	 * @param $userPrincipalName
	 */
	public function setUserPrincipalName($userPrincipalName) {
		$parts = explode("@", $userPrincipalName);

		if ($parts >= 2) {
			$this->upnUsername = $parts[0];
			$this->upnSuffix = $parts[1];
		}
	}

	/**
	 * Update password
	 *
	 * @param $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

	/**
	 * @return string
	 */
	public function getPassword()
	{
		return $this->password;
	}


	/**
	 * @param string|null
	 */
	public function setNetbiosName($netbiosName)
	{
		$this->netbiosName = $netbiosName;
	}

	/**
	 * @return string|null if NETBIOS name is available it is returned in upper case
	 */
	public function getNetbiosName()
	{
		return $this->netbiosName;
	}

	/**
	 * @return string
	 */
	public function getSAMAccountName()
	{
		return $this->sAMAccountName;
	}

	/**
	 * If the string contains a slash ('\') it uses the part after the slash as sAMAccountName
	 * @param string $sAMAccountName
	 */
	public function setSAMAccountName($sAMAccountName)
	{
		$this->sAMAccountName = $sAMAccountName;
	}

	/**
	 * @return string
	 */
	public function getUpnUsername()
	{
		return $this->upnUsername;
	}

	/**
	 * @param string $upnUsername
	 */
	public function setUpnUsername($upnUsername)
	{
		$this->upnUsername = $upnUsername;
	}

	public function __toString()
	{
		return "Credentials={login='" . $this->login . "',sAMAccountName='" . $this->sAMAccountName
			. "',userPrincipalName='" . $this->getUserPrincipalName() . "',netbios='" . $this->netbiosName . "'}";
	}
}