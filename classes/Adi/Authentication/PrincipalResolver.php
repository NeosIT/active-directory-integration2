<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_PrincipalResolver')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_PrincipalResolver provides information about a given $principal
 *
 * @author  Christopher Klein <ckl@neos-it.de>
 * @access public
 * @since ADI-620
 */
class NextADInt_Adi_Authentication_PrincipalResolver
{
	/** @var string */
	private $principal = null;

	/** @var string */
	private $netbiosName;

	/** @var  string */
	private $sAMAccountName;

	/** @var  string */
	private $upnUsername;

	/** @var string suffix */
	private $upnSuffix;

	/**
	 * PrincipalResolver constructor.
	 * @param $principal
	 */
	public function __construct($principal)
	{
		$this->principal = NextADInt_Core_Util_StringUtil::toLowerCase(trim($principal));
		$this->resolve();
	}

	/**
	 * Resolve the parts of the credentials
	 */
	private function resolve()
	{
		$this->netbiosName = self::detectNetbiosName($this->principal);

		// by default, sAMAccountName and userPrincipalName are equal.
		$samAccountName = self::suggestSamaccountName($this->principal);
		$this->sAMAccountName = $samAccountName;
		$this->upnUsername = $samAccountName;

		// if user has definitely a userPrincipalName (${userPrincipalName}@${upnSuffix}) we overwrite the upnUsername
		$upn = self::detectUserPrincipalParts($this->principal);

		if (is_array($upn)) {
			$this->upnUsername = $upn[0];
			$this->upnSuffix = $upn[1];
		}
	}

	/**
	 * Orginal principal
	 * @return mixed|string
	 */
	public function getPrincipal()
	{
		return $this->principal;
	}

	/**
	 * @return string|null Extracted NETBIOS name or null if it could not be extracted
	 */
	public function getNetbiosName()
	{
		return $this->netbiosName;
	}

	/**
	 * @return string sAMAccountName
	 */
	public function getSAMAccountName()
	{
		return $this->sAMAccountName;
	}

	/**
	 * @return string either the extract userPrincipalName or the sAMAccountName if the principal is not provided in UPN format
	 */
	public function getUpnUsername()
	{
		return $this->upnUsername;
	}

	/**
	 * @return string|null UPN suffix
	 */
	public function getUpnSuffix()
	{
		return $this->upnSuffix;
	}

	/**
	 * Detect the NETBIOS name of the $principal name. If available, the NETBIOS name is converted to upper case.
	 *
	 * @param $principal should contain '\' to separate the NETBIOS name from the sAMAccountName
	 * @return string|null either the detected NETBIOS name or null if it could not extracted from the given principal
	 */
	public static function detectNetbiosName($principal)
	{
		$parts = explode("\\", $principal);

		if (sizeof($parts) >= 2) {
			// ADI-564 | Github Issue#44 check if the username has claims prefixed, then the REMOTE_USER looks like this 0#.w|domain\username
			$parts_claims = explode("|", $parts[0]);
			if (sizeof($parts_claims) >= 2) {
				$r = strtoupper($parts_claims[1]);
			} else {
				$r = strtoupper($parts[0]);
			}

			return $r;
		}

		return null;
	}

	/**
	 * Detect the userPrincipalName and upnSuffix from the given princiapl
	 * @param $principal a string with format ${userPrincipalName}@${upnSuffix}
	 * @return array|null an array with two elements ([userPrincipalName, upnSuffix]) or null
	 */
	public static function detectUserPrincipalParts($principal)
	{
		NextADInt_Core_Assert::notEmpty($principal, "$principal must not be empty");

		$parts = explode('@', $principal);

		if (sizeof($parts) >= 2) {
			return array($parts[0] /* upn username */, $parts[1] /* suffix */);
		}

		return null;
	}

	/**
	 * Suggest the possible sAMAccountName from the given principal. The sAMAccountName is suggested as the userPrincipalName part
	 * of the UPN format does not have to be the sAMAccountName.
	 *
	 * @param $principal
	 * @return mixed
	 */
	public static function suggestSamaccountName($principal)
	{
		// format: ${NETBIOSNAME}\${samaccountname}
		$parts = explode("\\", $principal);

		if (sizeof($parts) >= 2) {
			// return last part
			return array_pop($parts);
		}

		// format: ${userprincipalname}@${upnSuffix}
		// please note that we are aware that the userPrincipalName does not explicitly equal to the samAccountName.
		// this code assumes that the customer's AD has userPrincipalName = sAMAccountName
		$parts = explode("@", $principal);

		if (sizeof($parts) > 0) {
			return $parts[0];
		}

		return $principal;
	}

	/**
	 * Create a new NextADInt_Adi_Authentication_Credentials object based on the given login and password
	 *
	 * @param string $login
	 * @param $password
	 * @return NextADInt_Adi_Authentication_Credentials
	 */
	public static function createCredentials($login, $password = '')
	{
		$resolver = new NextADInt_Adi_Authentication_PrincipalResolver($login);
		$r = new NextADInt_Adi_Authentication_Credentials($resolver->getPrincipal(), $password);
		$r->setSAMAccountName($resolver->getSAMAccountName());
		$r->setNetbiosName($resolver->getNetbiosName());
		$r->setUpnSuffix($resolver->getUpnSuffix());
		$r->setUpnUsername($resolver->getUpnUsername());

		return $r;
	}
}
