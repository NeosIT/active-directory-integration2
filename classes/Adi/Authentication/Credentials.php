<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_Credentials')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_Credentials encapsulates login credentials
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
	 * @param string $login Login in form 'username' (sAMAccountName) or 'username@domain' (userPrincipalName)
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
	 * Set login credential, extract sAMAccountName, NETBIOS name, userPrincipalName and UPN suffix
	 *
	 * @param string $login
	 *
	 * @throws Exception if login is empty
	 */
	public function setLogin($login)
	{
		$login = NextADInt_Core_Util_StringUtil::toLowerCase(trim($login));
		$this->login = $login;

		$this->setUserPrincipalName($login);
		$this->setNetbiosName($login);
		$this->setSAMAccountName($this->getUpnUsername());
	}

	/**
	 * Update the NETBIOS name of the $login name. If available, the NETBIOS name is converted to upper case.
	 *
	 * @param $login should contain '\' to separate the NETBIOS name from the sAMAccountName
	 */
	public function setNetbiosName($login)
	{
		$parts = explode("\\", $login);
		if (sizeof($parts) >= 2) {

			// ADI-564 | Github Issue#44 check if the username has claims prefixed, then the REMOTE_USER looks like this 0#.w|domain\username
			$parts_claims = explode("|", $parts[0]);
			if (sizeof($parts_claims) >= 2) {
				$this->logger->info("Claim detected. Removing claim from netBiosName.");
				$this->netbiosName = strtoupper($parts_claims[1]);
				$this->logger->info("NetBiosName is now set to '" . $this->netbiosName . "'.");
			} else {
				$this->netbiosName = strtoupper($parts[0]);
			}
		}
	}

	/**
	 * Set the user principal name.
	 *
	 * @param $userPrincipalName If this string contains an '@' character the first part is set as userPrincipalName, the second as upnSuffix.
	 * @throws Exception
	 */
	public function setUserPrincipalName($userPrincipalName)
	{
		NextADInt_Core_Assert::notEmpty($userPrincipalName, "userPrincipalName must not be empty");
		$userPrincipalName = NextADInt_Core_Util_StringUtil::toLowerCase(trim($userPrincipalName));

		$parts = explode('@', $userPrincipalName);

		if (sizeof($parts) >= 2) {
			$this->upnUsername = $parts[0];
			$this->upnSuffix = $parts[1];

			return;
		}

		$this->upnUsername = $userPrincipalName;
		$parts = explode("\\", $userPrincipalName);

		if (sizeof($parts) >= 2) {
			$this->upnUsername = $parts[1];
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
		$r = $this->upnUsername;

		if (!empty($this->upnSuffix)) {
			$r .= '@' . $this->upnSuffix;
		}

		return $r;
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
			. "',userPrincipalName='" . $this->getUserPrincipalName() . "',netbios='" . $this->netbiosName . "'}";
	}
}