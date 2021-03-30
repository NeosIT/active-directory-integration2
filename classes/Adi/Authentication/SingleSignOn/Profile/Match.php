<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_SingleSignOn_Profile_Match')) {
	return;
}

/**
 * Value object to describe a profile match
 *
 * @author Christopher Klein <me[at]schakko[dot]de>
 *
 * @access
 * @since 2.2.0
 */
class NextADInt_Adi_Authentication_SingleSignOn_Profile_Match
{
	/**
	 * a profile found by a NETBIOS realm
	 */
	public const NETBIOS = "netbios";

	/**
	 * a profile found by an account_suffix
	 */
	public const UPN_SUFFIX = "upn_suffix";

	/**
	 * a profile found by its Kerberos realm
	 */
	public const KERBEROS_REALM = "kerberos_realm";

	/**
	 * by default, we assume that the UPN suffix is used
	 * @var string
	 */
	private $type = self::UPN_SUFFIX;

	/**
	 * profile assigned
	 * @var array|null
	 */
	private $profile = null;

	/**
	 * NextADInt_Adi_Authentication_SingleSignOn_Profile_Match constructor.
	 * @param $profile
	 */
	public function __construct($profile)
	{
		$this->profile = $profile;
	}

	/**
	 * Sets the type of the match
	 *
	 * @param $type
	 * @return $this
	 * @throws Exception If the given type is non of NETBIOS, UPN_SUFFIX or KERBEROS_REALM
	 */
	public function setType($type)
	{
		if (!in_array($type, array(self::NETBIOS, self::UPN_SUFFIX, self::KERBEROS_REALM))) {
			throw new Exception("Unknown type");
		}

		$this->type = $type;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return array|null
	 */
	public function getProfile()
	{
		return $this->profile;
	}

	/**
	 * Wither which creates a new NextADInt_Adi_Authentication_SingleSignOn_Profile_Match with provided type
	 * @param $type
	 * @return $this
	 * @throws Exception
	 */
	public function withType($type)
	{
		$r = new NextADInt_Adi_Authentication_SingleSignOn_Profile_Match($this->profile);

		return $r->setType($type);
	}

	public function __toString() {
		return "Match={type='" . $this->type . "'}";
	}

	/**
	 * Factory methods, which delegates to #withType
	 *
	 * @param $profile
	 * @param $type
	 * @return static
	 * @throws Exception
	 */
	public static function create($profile, $type)
	{
		$r = new NextADInt_Adi_Authentication_SingleSignOn_Profile_Match($profile);
		return $r->withType($type);
	}

	/**
	 * Creates a null instance
	 * @return null
	 */
	public static function noMatch()
	{
		return null;
	}
}