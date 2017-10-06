<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Role_Mapping')) {
	return;
}

/**
 * NextADInt_Adi_Role_Mapping holds the mappings between Active Directory security groups and WordPress roles
 *
 * @author Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Role_Mapping
{
	/**
	 * @var array
	 */
	private $securityGroups = array();

	/**
	 * @var array
	 */
	private $wordPressRoles = array();

	/**
	 * @var string
	 */
	private $guidOrUsername;

	/**
	 * NextADInt_Adi_Role_Mapping constructor.
	 *
	 * @param string $guidOrUsername
	 */
	public function __construct($guidOrUsername)
	{
		$this->guidOrUsername = $guidOrUsername;
	}

	/**
	 * Set the Active Directory security groups
	 *
	 * @param array $securityGroups
	 */
	public function setSecurityGroups($securityGroups)
	{
		if (!is_array($securityGroups)) {
			$securityGroups = array();
		}

		$this->securityGroups = $securityGroups;
	}

	/**
	 * Get Active Directory security groups
	 * @return array
	 */
	public function getSecurityGroups()
	{
		return $this->securityGroups;
	}

	/**
	 * Set the user's WordPress roles
	 *
	 * @param array $wordPressRoles
	 */
	public function setWordPressRoles($wordPressRoles)
	{
		if (!is_array($wordPressRoles)) {
			$wordPressRoles = array();
		}

		$this->wordPressRoles = $wordPressRoles;
	}

	/**
	 * Get the user's WordPress roles
	 * @return array
	 */
	public function getWordPressRoles()
	{
		return $this->wordPressRoles;
	}


	/**
	 * Does the current mapping belong to the security group?
	 *
	 * @param string $securityGroup
	 *
	 * @return bool
	 */
	public function isInSecurityGroup($securityGroup)
	{
		return in_array($securityGroup, $this->securityGroups);
	}

	/**
	 * Get all security groups which are assigned
	 *
	 * @param $securityGroups
	 *
	 * @return array elements with matching groups
	 */
	public function getMatchingGroups($securityGroups)
	{
		$equalGroups = array_intersect($this->securityGroups, $securityGroups);

		return array_values($equalGroups);
	}

	/**
	 * Merge the given $roleMapping into this object.
	 * 
	 * @param NextADInt_Adi_Role_Mapping $roleMapping
	 */
	public function merge(NextADInt_Adi_Role_Mapping $roleMapping)
	{
		$this->securityGroups += $roleMapping->getSecurityGroups();
		$this->wordPressRoles += $roleMapping->getWordPressRoles();
	}

	public function __toString()
	{
		return "Mapping " . $this->guidOrUsername . "={ad_security_groups='" . implode(", ", $this->securityGroups)
		. "',wordpress_roles='" . implode(', ', $this->wordPressRoles) . "'}";
	}
}