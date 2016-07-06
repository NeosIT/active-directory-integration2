<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_Credentials')) {
	return;
}

/**
 * Adi_Authentication_Credentials encapsulates login credentials
 *
 * @author  Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class Adi_Authentication_Credentials
{
	/** @var string */
	private $login;

	/** @var  string */
	private $sAMAccountName;

	/** @var  string */
	private $upnUsername;

	/** @var string suffix */
	private $upnSuffix;

	/** @var string */
	private $password;

	/**
	 * Adi_Authentication_Credentials constructor.
	 *
	 * @param string $login Login in form 'username' (sAMAccountName) or 'username@domain' (userPrincipalName)
	 * @param string $password
	 *
	 * @throws Exception
	 */
	public function __construct($login = '', $password = '')
	{
		$this->setLogin($login);
		$this->setPassword($password);
	}

	/**
	 * Set login, extract username and suffix
	 *
	 * @param string $login
	 *
	 * @throws Exception if login is empty
	 */
	public function setLogin($login)
	{
		$login = strtolower(trim($login));
		$this->login = $login;

		$this->setUserPrincipalName($login);
		$this->setSAMAccountName($this->getUpnUsername());
	}

	public function setUserPrincipalName($userPrincipalName)
	{
		Core_Assert::notEmpty($userPrincipalName, "userPrincipalName must not be empty");
		$userPrincipalName = strtolower(trim($userPrincipalName));

		$parts = explode('@', $userPrincipalName);

		if (sizeof($parts) >= 2) {
			$this->upnUsername = $parts[0];
			$this->upnSuffix = $parts[1];
		} else {
			$this->upnUsername = $userPrincipalName;
		}
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
		return $this->upnUsername . '@' . $this->upnSuffix;
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
	 * @return string
	 */
	public function getSAMAccountName()
	{
		return $this->sAMAccountName;
	}

	/**
	 * @param string $sAMAccountName
	 */
	public function setSAMAccountName($sAMAccountName)
	{
		$this->sAMAccountName = $sAMAccountName;

		// split the sAMAcountName from the logon name
		$parts = explode('\\', $sAMAccountName);

		if ($parts >= 2) {
			$this->sAMAccountName = array_pop($parts);
		}
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
		. "',userPrincipalName='" . $this->getUserPrincipalName() . "'}";
	}
}