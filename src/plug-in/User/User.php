<?php

namespace Dreitier\Nadi\User;

use Dreitier\Ldap\Attributes;
use Dreitier\Nadi\Role\Mapping;
use Dreitier\Util\Assert;
use Dreitier\Nadi\Authentication\Credentials;

/**
 * User encapsulates a WordPress user and extends it with information provided by Active Directory.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @access public
 */
class User
{
	/**
	 * @var Credentials
	 */
	private $credentials = null;

	/** @var integer */
	private $id;

	/** @var string WordPress user_login field */
	private $user_login;

	/** @var Attributes */
	private $ldapAttributes;

	/** @var Mapping */
	private $roleMapping;

	/**
	 * @var bool
	 */
	private $newUser = true;

	/**
	 * @param Credentials $credentials
	 * @param Attributes $ldapAttributes
	 */
	public function __construct(Credentials $credentials, Attributes $ldapAttributes)
	{
		$this->setCredentials($credentials);
		$this->setLdapAttributes($ldapAttributes);
	}

	/**
	 * @return Credentials never be empty
	 */
	public function getCredentials()
	{
		return $this->credentials;
	}

	/**
	 * @param Credentials $credentials
	 * @throws \Exception
	 */
	public function setCredentials(Credentials $credentials)
	{
		Assert::notNull($credentials, "credentials must not be null");
		Assert::notEmpty($credentials->getUserPrincipalName(), "userPrincipalName must not be empty");
		Assert::notEmpty($credentials->getSAMAccountName(), "sAMAccountName must not be empty");

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
	 * @param Attributes $ldapAttributes
	 */
	public function setLdapAttributes(Attributes $ldapAttributes)
	{
		$this->ldapAttributes = $ldapAttributes;
	}

	/**
	 * @return Attributes never null; if ldapAttributes is not set a new instance will be returned
	 */
	public function getLdapAttributes()
	{
		return $this->ldapAttributes ? $this->ldapAttributes : new Attributes();
	}

	/**
	 * @return Mapping
	 */
	public function getRoleMapping()
	{
		return $this->roleMapping;
	}

	/**
	 * @param Mapping $roleMapping
	 */
	public function setRoleMapping($roleMapping)
	{
		$this->roleMapping = $roleMapping;
	}

	/**
	 * @return string
	 * @throws \Exception if the login has not been previously set
	 */
	public function getUserLogin()
	{
		if (!$this->user_login) {
			throw new \Exception("User login has not been set");
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
		return "User " . ($this->user_login ? $this->user_login : '<no_wp_user_account>') . "={id='" . $this->id . "', credentials='" . $this->credentials . "'}";
	}
}