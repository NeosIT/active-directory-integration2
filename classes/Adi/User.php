<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_User')) {
	return;
}

/**
 * NextADInt_Adi_User encapsulates a WordPress user and extends it with information provided by Active Directory.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @access public
 */
class NextADInt_Adi_User
{
	/**
	 * @var NextADInt_Adi_Authentication_Credentials
	 */
	private $credentials = null;

	/** @var integer */
	private $id;

	/** @var string WordPress user_login field */
	private $user_login;

	/** @var NextADInt_Ldap_Attributes */
	private $ldapAttributes;

	/** @var NextADInt_Adi_Role_Mapping */
	private $roleMapping;

	/**
	 * @var bool
	 */
	private $newUser = true;


	/**
	 * NextADInt_Adi_User constructor.
	 *
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @param NextADInt_Ldap_Attributes $ldapAttributes
	 */
	public function  __construct(NextADInt_Adi_Authentication_Credentials $credentials, NextADInt_Ldap_Attributes $ldapAttributes)
	{
		$this->setCredentials($credentials);
		$this->setLdapAttributes($ldapAttributes);
	}

	/**
	 * @return NextADInt_Adi_Authentication_Credentials never be empty
	 */
	public function getCredentials()
	{
		return $this->credentials;
	}

	/**
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @throws Exception
	 */
	public function setCredentials(NextADInt_Adi_Authentication_Credentials $credentials)
	{
		NextADInt_Core_Assert::notNull($credentials, "credentials must not be null");
		NextADInt_Core_Assert::notEmpty($credentials->getUserPrincipalName(), "userPrincipalName must not be empty");
		NextADInt_Core_Assert::notEmpty($credentials->getSAMAccountName(), "sAMAccountName must not be empty");

		$this->credentials = $credentials;
	}

	/**
	 * @return int
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @param int $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}

	/**
	 * Set LDAP attributes
	 * @param NextADInt_Ldap_Attributes $ldapAttributes
	 */
	public function setLdapAttributes(NextADInt_Ldap_Attributes $ldapAttributes)
	{
		$this->ldapAttributes = $ldapAttributes;
	}

	/**
	 * @return NextADInt_Ldap_Attributes never null; if ldapAttributes is not set a new instance will be returned
	 */
	public function getLdapAttributes() {
		return $this->ldapAttributes ? $this->ldapAttributes : new NextADInt_Ldap_Attributes();
	}

	/**
	 * @return NextADInt_Adi_Role_Mapping
	 */
	public function getRoleMapping()
	{
		return $this->roleMapping;
	}

	/**
	 * @param NextADInt_Adi_Role_Mapping $roleMapping
	 */
	public function setRoleMapping($roleMapping)
	{
		$this->roleMapping = $roleMapping;
	}

	/**
	 * @return string
	 * @throws Exception if the login has not been previously set
	 */
	public function getUserLogin()
	{
		if (!$this->user_login) {
			throw new Exception("User login has not been set");
		}

		return $this->user_login;
	}

	/**
	 * @param null $user_login
	 */
	public function setUserLogin($user_login)
	{
		$this->user_login = $user_login;
	}

	/**
	 * @return boolean returns true by default
	 */
	public function isNewUser()
	{
		return $this->newUser;
	}

	/**
	 * @param boolean $newUser
	 */
	public function setNewUser($newUser)
	{
		$this->newUser = $newUser;
	}

	public function __toString()
	{
		return "User " . ($this->user_login ? $this->user_login : '<no_wp_user_account>') . "={id='" . $this->id . "', credentials='" . $this->credentials ."'}";
	}
}