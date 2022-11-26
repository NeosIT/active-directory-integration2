<?php

namespace Dreitier\Nadi\Authentication;


use Dreitier\Ldap\UserQuery;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Vendor\Monolog\Logger;

/**
 * Credentials encapsulates login credentials.
 * This class is mutable so parts of the credentials can be updated due to AD/LDAP lookups.
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
class Credentials
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

	/** @var Logger */
	private $logger;

	/** @var string|null $objectGuid */
	private $objectGuid;

	/** @var integer|null $wordPressUserId
	 */
	private $wordPressUserId;

	/**
	 * a specific Kerberos realm
	 * @since 2.2.0
	 * @var string
	 */
	private $kerberosRealm;

	/**
	 * options for this context
	 * @since 2.2.0
	 * @var array
	 */
	private $options = array();

	/**
	 * @param string $login Login in form 'username' (sAMAccountName), 'username@domain' (userPrincipalName) 'sAMAccountName@REALM' (Kerberors) or 'NETBIOS\sAMAccountName'
	 * @param string $password
	 */
	public function __construct($login = '', $password = '')
	{
		$this->logger = NadiLog::getInstance();
		$this->setLogin($login);
		$this->setPassword($password);
	}

	/**
	 * @param $login
	 */
	public function setLogin($login)
	{
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
	 * Add an additional option to the credential's context
	 *
	 * @param $key
	 * @param $value
	 * @since 2.2.0
	 */
	public function setOption($key, $value)
	{
		$this->options[$key] = $value;
	}

	/**
	 * Return an option's value. it returns null if the option has not been set.
	 *
	 * @param $key
	 * @return mixed|null
	 * @since 2.2.0
	 */
	public function getOption($key)
	{
		if (isset($this->options[$key])) {
			return $this->options[$key];
		}

		return NULL;
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
		if (empty($upnSuffix)) {
			$upnSuffix = "";
		}

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
	 *
	 * @param $userPrincipalName
	 */
	public function setUserPrincipalName($userPrincipalName)
	{
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
	 *
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

	/**
	 * @return string|null
	 */
	public function getObjectGuid()
	{
		return $this->objectGuid;
	}

	/**
	 * @param string|null $objectGuid
	 */
	public function setObjectGuid($objectGuid)
	{
		$this->objectGuid = $objectGuid;
	}

	/**
	 * @return int|null
	 */
	public function getWordPressUserId()
	{
		return $this->wordPressUserId;
	}

	/**
	 * @param int|null $wordPressUserId
	 */
	public function setWordPressUserId($wordPressUserId)
	{
		$this->wordPressUserId = $wordPressUserId;
	}

	/**
	 * Get the user's Kerberos realm
	 * @return string
	 * @since 2.2.0
	 */
	public function getKerberosRealm()
	{
		return $this->kerberosRealm;
	}

	/**
	 * Set the user's Kerberos realm
	 * @param $kerberosRealm
	 * @since 2.2.0
	 */
	public function setKerberosRealm($kerberosRealm)
	{
		$this->kerberosRealm = $kerberosRealm;
	}

	/**
	 * Based upon this credential, a new LDAP query will be created
	 *
	 * @return UserQuery
	 * @since 2.2.0
	 */
	public function toUserQuery()
	{
		return UserQuery::forPrincipal($this->login, $this);
	}

	public function __toString()
	{
		return "Credentials={login='" . $this->login . "',sAMAccountName='" . $this->sAMAccountName
			. "',userPrincipalName='" . $this->getUserPrincipalName() . "',netbios='" . $this->netbiosName
			. "',objectGuid='" . $this->objectGuid . "',wordPressUserId='" . $this->wordPressUserId
			. "',kerberosRealm='" . $this->kerberosRealm . "'}";
	}
}