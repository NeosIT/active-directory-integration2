<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Role_Manager')) {
	return;
}

/**
 * Adi_Role_Manager creates and updates mappings between Active Directory security groups and WordPress roles.
 * This class acts as a bridge between both systems
 *
 * @author Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class Adi_Role_Manager
{
	/* @var Multisite_Configuration_Service */
	private $configuration;

	/* @var Ldap_Connection $ldapConnection */
	private $ldapConnection;

	/* @var Logger */
	private $logger;

	/* @var array */
	private $mapping;

	/**
	 * @param Multisite_Configuration_Service $configuration
	 * @param Ldap_Connection $ldapConnection
	 */
	public function __construct(Multisite_Configuration_Service $configuration,
								Ldap_Connection $ldapConnection
	)
	{
		$this->configuration = $configuration;
		$this->ldapConnection = $ldapConnection;

		$this->logger = Logger::getLogger(__CLASS__);
	}

	/**
	 * Create the role mapping for the given user. The belonging security groups of this user are automatically loaded
	 * from Active Directory
	 *
	 * @param string $username
	 * @return Adi_Role_Mapping
	 * @throws Exception
	 */
	public function createRoleMapping($username)
	{
		$roleMapping = new Adi_Role_Mapping($username);
		$securityGroups = $this->ldapConnection->getAdLdap()->user_groups($username);

		$roleMapping->setSecurityGroups($securityGroups);

		return $roleMapping;
	}

	/**
	 * Check if user is a member of at least one authorization group for the current blog
	 *
	 * @param Adi_Role_Mapping $roleMapping
	 * @return bool
	 * @throws Exception
	 */
	public function isInAuthorizationGroup(Adi_Role_Mapping $roleMapping)
	{
		$authorizationGroups = $this->configuration->getOptionValue(Adi_Configuration_Options::AUTHORIZATION_GROUP);
		$expectedGroups = Core_Util_StringUtil::split($authorizationGroups, ';');

		$intersect = $roleMapping->getMatchingGroups($expectedGroups);

		return sizeof($intersect) > 0;
	}

	/**
	 * Synchronizes the WP_User's roles with the provided role mapping. The WordPress roles are instantly loaded for the $roleMapping.
	 *
	 * There are four rules:
	 * <ul>
	 * <li>On user creation if Role Equivalent Groups exist and the user has no role he gets no role assigned</li>
	 * <li>On user creation if *no* Role Equivalent Groups exist the default role 'subscriber' is used</li>
	 * <li>On user update if Role Equivalent Groups exist and the user has no role *no* role is set</li>
	 * <li>On user update if *no* Role Equivalent Groups the user's existing roles will not be updated</li>
	 * </ul>
	 *
	 * @param WP_User|false $wpUser
	 * @param Adi_Role_Mapping $roleMapping
	 * @param bool $isUserPreviouslyCreated has the user been previously created
	 * @return bool false if $wpUser was no valid user
	 */
	public function synchronizeRoles($wpUser, Adi_Role_Mapping $roleMapping, $isUserPreviouslyCreated = false)
	{
		if (!$wpUser) {
			return false;
		}

		// load the users WordPress roles
		$this->loadWordPressRoles($roleMapping);

		$this->logger->info("Synchronizing roles of WordPress user with ID " . $wpUser->ID);

		$mappings = $this->getRoleEquivalentGroups();
		$hasRoleEquivalentGroups = sizeof($mappings) > 0;

		$wordPressRoles = $roleMapping->getWordPressRoles();
		$hasWordPressRoles = sizeof($wordPressRoles) > 0;

		$cleanExistingRoles = true;
		$roles = $wordPressRoles;

		if ($isUserPreviouslyCreated) {
			// ADI-141: On user creation if Role Equivalent Groups exist and the user has no role he gets no role assigned
			if ($hasRoleEquivalentGroups && !$hasWordPressRoles) {
				$roles = array();
			} // ADI-141: On user creation if *no* Role Equivalent Groups exist the default role 'subscriber' is used
			else if (!$hasRoleEquivalentGroups) {
				$this->logger->warn("No Role Equivalent Groups defined. User gets default WordPress role 'subscriber' assigned");
				$roles = array('subscriber');
			}
		} else /* updated user */ {
			// ADI-141: On user update if Role Equivalent Groups exist and the user has no role *no* role is set
			if ($hasRoleEquivalentGroups && !$hasWordPressRoles) {
				$roles = array();
			} // ADI-141: On user update if *no* Role Equivalent Groups the user's existing roles will not be updated
			else if (!$hasRoleEquivalentGroups) {
				$this->logger->warn("No Role Equivalent Groups defined. Previous assigned WordPress roles will stay untouched");
				$cleanExistingRoles = false;
				$roles = array();
			}
		}

		$this->logger->info("Security groups " . json_encode($roleMapping->getSecurityGroups()) . " are mapped to WordPress roles: " . json_encode($roles));
		$this->updateRoles($wpUser, $roles, $cleanExistingRoles);

		return true;
	}

	/**
	 * Update the role of the given user with his new role
	 *
	 * @param WP_User|false $wpUser
	 * @param array $roles WordPress roles to update. Only available roles can be assigned, all other roles are ignored
	 * @param bool $cleanExistingRoles If true all existing roles are removed before updating the new ones
	 * @return bool
	 */
	public function updateRoles($wpUser, $roles, $cleanExistingRoles = true)
	{
		if (!$wpUser) {
			return false;
		}

		if ($cleanExistingRoles) {
			$wpUser->set_role("");
		}

		// which roles are available?
		$availableRoles = new WP_Roles();

		foreach ($roles as $role) {
			if ($availableRoles->is_role($role)) {
				$wpUser->add_role($role);
			}
		}

		return true;
	}

	/**
	 * Return the assignment from Active Directory security group to its WordPress pendant
	 * @return map key => AD security group, value => WordPress role
	 */
	public function getRoleEquivalentGroups()
	{
		if (empty($this->mapping) || is_null($this->mapping)) {
			$this->mapping = array();

			$groups = $this->configuration->getOptionValue(Adi_Configuration_Options::ROLE_EQUIVALENT_GROUPS);
			$groups = Core_Util_StringUtil::split($groups, ';');

			foreach ($groups as $group) {
				$parts = Core_Util_StringUtil::split($group, '=');

				if (sizeof($parts) !== 2) {
					continue;
				}

				$securityGroup = trim($parts[0]);
				$wordPressRole = trim($parts[1]);

				$this->mapping[$securityGroup] = $wordPressRole;
			}
		}

		return $this->mapping;
	}

	/**
	 * Update the assigned WordPress roles of the user
	 *
	 * @param Adi_Role_Mapping $roleMapping
	 * @return Adi_Role_Mapping
	 */
	public function loadWordPressRoles(Adi_Role_Mapping $roleMapping)
	{
		$roles = $this->getMappedWordPressRoles($roleMapping);
		$roleMapping->setWordPressRoles($roles);

		return $roleMapping;
	}

	/**
	 * Get all WordPress roles of the user which have been mapped to one or multiple of his group membership in the Active Directory
	 *
	 * @param Adi_Role_Mapping $roleMapping
	 * @return array with mapped WordPress roles
	 */
	public function getMappedWordPressRoles(Adi_Role_Mapping $roleMapping)
	{
		$mappings = $this->getRoleEquivalentGroups();
		$roles = array();

		foreach ($mappings as $securityGroup => $wordPressRole) {
			if ($roleMapping->isInSecurityGroup($securityGroup)) {
				$roles[] = $wordPressRole;
			}
		}

		return $roles;
	}

}
