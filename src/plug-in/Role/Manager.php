<?php

namespace Dreitier\Nadi\Role;


use Dreitier\Ldap\Connection;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\StringUtil;
use Dreitier\Util\Util;
use Dreitier\WordPress\Multisite\Configuration\Service;

/**
 * Manager creates and updates mappings between Active Directory security groups and WordPress roles.
 * This class acts as a bridge between both systems
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
class Manager
{
	const ROLE_SUPER_ADMIN = 'super admin';

	/* @var Service */
	private $multisiteConfigurationService;

	/* @var Connection $ldapConnection */
	private $ldapConnection;

	/* @var Logger */
	private $logger;

	/* @var array */
	private $mapping;

	/**
	 * @param Service $multisiteConfigurationService
	 * @param Connection $ldapConnection
	 */
	public function __construct(Service    $multisiteConfigurationService,
								Connection $ldapConnection
	)
	{
		$this->multisiteConfigurationService = $multisiteConfigurationService;
		$this->ldapConnection = $ldapConnection;

		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Create the role mapping for the given user. The belonging security groups of this user are automatically loaded
	 * from Active Directory
	 *
	 * @param string userGuid
	 *
	 * @return Mapping
	 * @throws \Exception
	 */
	public function createRoleMapping($userGuid)
	{
		$roleMapping = new Mapping($userGuid);
		$securityGroups = $this->ldapConnection->getAdLdap()->user_groups($userGuid, null, true);

		$roleMapping->setSecurityGroups($securityGroups);

		return $roleMapping;
	}

	/**
	 * Check if user is a member of at least one authorization group for the current blog
	 *
	 * @param Mapping $roleMapping
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function isInAuthorizationGroup(Mapping $roleMapping)
	{
		$authorizationGroups = $this->multisiteConfigurationService->getOptionValue(Options::AUTHORIZATION_GROUP);
		$expectedGroups = StringUtil::splitNonEmpty($authorizationGroups, ';');

		// ADI-248: if no authorization group has been defined, the login is always possible and there has not to be
		// matching group
		if (sizeof($expectedGroups) == 0) {
			return true;
		}

		$intersect = $roleMapping->getMatchingGroups($expectedGroups);

		// is the user inside of at least one authorization group?
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
	 * @param \WP_User|false $wpUser
	 * @param Mapping $roleMapping
	 * @param bool $isUserPreviouslyCreated has the user been previously created
	 *
	 * @return bool false if $wpUser was no valid user
	 */
	public function synchronizeRoles($wpUser, Mapping $roleMapping, $isUserPreviouslyCreated = false)
	{
		if (!$wpUser) {
			return false;
		}

		// load the users WordPress roles
		$this->loadWordPressRoles($roleMapping);

		$this->logger->info("Synchronizing roles of WordPress user with ID " . $wpUser->ID);

		$mappings = $this->getRoleEquivalentGroups();
		$isMemberOfRoleEquivalentGroup = $this->isMemberOfRoleEquivalentGroups($roleMapping, $mappings);

		$wordPressRoles = $roleMapping->getWordPressRoles();
		$hasWordPressRoles = sizeof($wordPressRoles) > 0;

		$cleanExistingRoles = $this->multisiteConfigurationService->getOptionValue(Options::CLEAN_EXISTING_ROLES);

		// User create specific logic
		if ($isUserPreviouslyCreated) {
			// If user part of a mapped security group on create, set cleanExistingRoles true to remove WordPress default subscriber role.
			if ($isMemberOfRoleEquivalentGroup && $hasWordPressRoles) {
				$this->logger->info("Role Equivalent Groups for user " . $wpUser->user_login . " defined. Removing default Role Subscriber");
				$cleanExistingRoles = true;
			} // ADI-141: On user creation if *no* Role Equivalent Groups exist the default role 'subscriber' is used
			if (!$isMemberOfRoleEquivalentGroup) {
				$this->logger->warning("No Role Equivalent Groups defined. User gets default WordPress role 'subscriber' assigned");
				$wordPressRoles = array('subscriber');
				$cleanExistingRoles = false;
			}
		}

		$cleanExistingRoles = apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'sync_ad2wp_clean_existing_roles', $cleanExistingRoles, $wordPressRoles, $wpUser, $roleMapping);
		$wordPressRoles = apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'sync_ad2wp_filter_roles', $wordPressRoles, $cleanExistingRoles, $wpUser, $roleMapping);

		$this->logger->info("Security groups " . json_encode($roleMapping->getSecurityGroups())
			. " are mapped to WordPress roles: " . json_encode($wordPressRoles));
		$this->updateRoles($wpUser, $wordPressRoles, $cleanExistingRoles);

		return true;
	}

	/**
	 * Grant the role 'super admin' to a specific {@link WP_User}.
	 *
	 * @param \WP_User $wpUser
	 */
	protected function grantSuperAdminRole(\WP_User $wpUser)
	{
		$this->loadMultisiteFunctions();
		grant_super_admin($wpUser->ID);
	}

	/**
	 * Update the role of the given user with his new role
	 *
	 * @param WP_User|false $wpUser
	 * @param array $roles WordPress roles to update. Only available roles can be assigned, all other roles are ignored
	 * @param bool $cleanExistingRoles If true all existing roles are removed before updating the new ones
	 *
	 * @return bool
	 */
	public function updateRoles($wpUser, $roles, $cleanExistingRoles = true)
	{
		if (!$wpUser) {
			return false;
		}

		if ($cleanExistingRoles) {
			$wpUser->set_role("");
			$this->logger->warning("Cleaning existing roles true for user '" . $wpUser->user_login . "' existing roles will be deleted.");
		} else {
			$this->logger->warning("Cleaning existing roles false for user '" . $wpUser->user_login . "' existing roles will stay untouched.");
		}

		// which roles are available?
		$availableRoles = new \WP_Roles();

		foreach ($roles as $role) {
			if ($role == self::ROLE_SUPER_ADMIN) {
				$this->grantSuperAdminRole($wpUser);
				continue;
			}

			if ($availableRoles->is_role($role)) {
				$wpUser->add_role($role);
			} else {
				$this->logger->warning("Can not add role '$role' to '" . $wpUser->user_login . "' because the role does NOT exist.");
			}
		}

		return true;
	}

	/**
	 * Load the ms.php file to provide the multisite functions on login.
	 */
	protected function loadMultisiteFunctions()
	{
		$multiSiteFilePath = ABSPATH . 'wp-admin/includes/ms.php';
		$coreUtil = Util::native();

		// check if the necessary function is already available
		if ($coreUtil->isFunctionAvailable('grant_super_admin')) {
			return;
		}

		// check if the necessary file is existing
		if (!$coreUtil->isFileAvailable($multiSiteFilePath)) {
			return;
		}

		// at login the 'wp-admin/includes/ms.php' is not loaded
		$coreUtil->includeOnce($multiSiteFilePath);
	}

	/**
	 * Return the assignment from Active Directory security group to its WordPress pendant
	 * @return map key => AD security group, value => WordPress role
	 */
	public function getRoleEquivalentGroups()
	{
		if (empty($this->mapping) || is_null($this->mapping)) {
			$this->mapping = array();

			$groups = $this->multisiteConfigurationService->getOptionValue(Options::ROLE_EQUIVALENT_GROUPS);
			$groups = StringUtil::split($groups, ';');

			foreach ($groups as $group) {
				$parts = StringUtil::split($group, '=');

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
	 * @param Mapping $roleMapping
	 *
	 * @return Mapping
	 */
	public function loadWordPressRoles(Mapping $roleMapping)
	{
		$roles = $this->getMappedWordPressRoles($roleMapping);
		$roleMapping->setWordPressRoles($roles);

		return $roleMapping;
	}

	/**
	 * Get all WordPress roles of the user which have been mapped to one or multiple of his group membership in the Active Directory
	 *
	 * @param Mapping $roleMapping
	 *
	 * @return array with mapped WordPress roles
	 */
	public function getMappedWordPressRoles(Mapping $roleMapping)
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

	/**
	 * Return an array with role name as key and the real value as value.
	 *
	 * @return array
	 */
	public static function getRoles()
	{
		$result = array(
			'super admin' => self::ROLE_SUPER_ADMIN,
		);

		$wpRoles = new \WP_Roles();

		foreach ($wpRoles->roles as $id => $object) {
			$result[$id] = $id;
		}

		// in a single site WordPress installation remove the super admin, b/c it does not exist
		if (!is_multisite()) {
			unset($result['super admin']);
		}

		return $result;
	}

	/**
	 * @param Mapping $roleMappings
	 * @param $roleEquivalentGroups
	 * @return bool
	 */
	public function isMemberOfRoleEquivalentGroups(Mapping $roleMappings, $roleEquivalentGroups)
	{

		foreach ($roleMappings->getSecurityGroups() as $key => $securityGroup) {
			if (isset($roleEquivalentGroups[$securityGroup])) {
				return true;
			}
		}

		return false;
	}
}
