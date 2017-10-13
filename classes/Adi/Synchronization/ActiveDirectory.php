<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Synchronization_ActiveDirectory')) {
	return;
}

/**
 * Synchronize the profile of WordPress users from the WordPress database back to the Active Directory server.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Synchronization_ActiveDirectory extends NextADInt_Adi_Synchronization_Abstract
{
	/* @var Logger */
	private $logger;

	/**
	 * NextADInt_Adi_Synchronization_ActiveDirectory constructor.
	 *
	 * @param NextADInt_Ldap_Attribute_Service $attributeService
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Ldap_Connection $connection
	 */
	public function __construct(NextADInt_Ldap_Attribute_Service $attributeService,
								NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Ldap_Connection $connection)
	{
		parent::__construct($configuration, $connection, $attributeService);

		$this->logger = NextADInt_Core_Logger::getLogger();
	}


	/**
	 * Get all attribute values of WordPress users and synchronize them with the corresponding Active Directory users.

	 * @param int|null $userId if provided only the user with the given ID is synchronized
	 * @param string|null $username username
	 * @param string|null $password password
	 * @return bool
	 */
	public function synchronize($userId = null, $username = null, $password = null)
	{
		if (!$this->prepareForSync($username, $password)) {
			return false;
		}

		$attributes = $this->attributeService->getRepository()->getSyncableAttributes();

		$this->logger->info("Available attributes for synchronization: " . NextADInt_Core_Logger::toString($attributes)); // TODO Revisit and add to new logger
		$users = $this->getUsers($userId);

		if (!is_array($users) || empty($users)) {
			$this->logger->error("User array is empty !");

			return false;
		}

		// ADI-145: provide API
		$users = apply_filters(NEXT_AD_INT_PREFIX . 'sync_wp2ad_filter_synchronizable_users', $users);

		$updatedUsers = 0;

		foreach ($users as $user) {
			$status = $this->synchronizeUser($user, $attributes);

			if ($status) {
				$updatedUsers++;
			}
		}

		$this->finishSynchronization($updatedUsers);

		return true;
	}

	/**
	 * Prepare for user synchronization.
	 *
	 * @param string|null $username if provided use this username for the LDAP connection
	 * @param string|null $password if provided use this password for the LDAP connection
	 * @return bool false if synchronization is disabled
	 */
	protected function prepareForSync($username = null, $password = null)
	{

		if (!$this->isEnabled()) {
			$this->logger->info('Sync to AD is disabled.');

			return false;
		}

		$this->logger->info('Start of Sync to AD');
		$this->startTimer();

		// either use custom username and password or fall back to service account
		$username = isset($username) ? $username : $this->getServiceAccountUsername();
		$password = isset($password) ? $password : $this->getServiceAccountPassword();

		if (empty($username) && empty($password)) {
			$this->logger->error('Missing username and/or password for synchronization to Active Directory.');
			return false;
		}

		if (!$this->connectToAdLdap($username, $password)) {
			return false;
		}

		if (!$this->isUsernameInDomain($username)) {
			return false;
		}

		$this->increaseExecutionTime();
		
		return true;
	}

	/**
	 * Return whether the given user ID has a valid corresponding Active Directory account
	 *
	 * @param int $userId
	 * @return bool
	 */
	public function isSynchronizable($userId) {
		$users = $this->findActiveDirectoryUsers($userId);

		return sizeof($users) > 0;
	}

	/**
	 * Return whether the WordPress' permission for editing the Active Directory attributes of the current profile is available or not
	 *
	 * @param bool $isOwnProfile
	 * @return bool true if its the user's profile or the user is admin/superadmin
	 */
	public function hasActiveDirectoryAttributeEditPermission($isOwnProfile) {
		if ($isOwnProfile) {
			return true;
		}

		if (current_user_can('edit_users')) {
			return true;
		}

		return false;
	}

	/**
	 * Return if the profile of $userId can be edited by the current user based upon on his permissions and if yes is the synchronization to Active Directory available.
	 * If the synchronization is unavailable, this method returns false.
	 *
	 * @param int $userId
	 * @param bool $isOwnProfile
	 * @return bool
	 */
	public function isEditable($userId, $isOwnProfile) {
		if ($this->hasActiveDirectoryAttributeEditPermission($isOwnProfile)) {
			try {
				return $this->assertSynchronizationAvailable($userId, $isOwnProfile);
			}
			catch (Exception $e) {
				// discard
			}
		}

		return false;
	}

	/**
	 * Return if this option has been enabled for this blog
	 * @return bool
	 */
	public function isEnabled() {
		return $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_ENABLED);
	}

	/**
	 * Return if the service account has been enabled for Sync To Active Directory
	 * @return mixed
	 */
	public function isServiceAccountEnabled() {
		return $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_USE_GLOBAL_USER);
	}

	/**
	 * Get the service account username for the current blog
	 * @return mixed
	 */
	public function getServiceAccountUsername() {
		return $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER);
	}

	/**
	 * Get the service account password for the current blog
	 * @return mixed
	 */
	public function getServiceAccountPassword() {
		return $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD);
	}

	/**
	 * Assert that the synchronization to Active Directory is available for the given profile.
	 *
	 * @param int $userId
	 * @param bool $isOwnProfile true, if the user wants to edit his own profile
	 * @return bool true if synchronization is available. In every other case an Exception is thrown
	 * @throws Exception If Sync To AD has not been enabled
	 * @throws Exception If the $userId has no corresponding Active Directory account
	 * @throws Exception If the current user tries to edit another user and no service account is used
	 */
	public function assertSynchronizationAvailable($userId, $isOwnProfile) {
		if (!$this->isEnabled()) {
			throw new Exception(__("Synchronization is not enabled", 'next-active-directory-integration'));
		}

		$isUserSynchronizable = $this->isSynchronizable($userId);

		if (!$isUserSynchronizable) {
			throw new Exception(__("This user does not have a corresponding Active Directory account", 'next-active-directory-integration'));
		}

		if (!$isOwnProfile && !$this->isServiceAccountEnabled()) {
			throw new Exception(__("This user is not editable because there is no Sync To AD service account available", 'next-active-directory-integration'));
		}

		return true;
	}


	/**
	 * Return all synchronizable users.
	 *
	 * @param null|int $userId if specified only the user with the given WordPress user id is returned
	 * @return array a list with synchronizable users which can be null
	 */
	protected function getUsers($userId = null) {
		$users = $this->findActiveDirectoryUsers($userId);

		if (!$users) {
			$this->logger->warn('No possible users for synchronization back to Active Directory found.');

			return array();
		}

		return $users;
	}

	/**
	 * Get all user meta values and sync them with the corresponding user in the active directory
	 *
	 * @param WP_User $wpUser
	 * @param array $allowedAttributes
	 * @return bool
	 */
	protected function synchronizeUser($wpUser, $allowedAttributes)
	{
		$this->logger->info("WordPress Login (username (ID)): " . $wpUser->user_login . " (" . $wpUser->ID . ")");
		$attributesToSync = $this->findAttributesOfUser($wpUser->ID, $allowedAttributes);

		foreach ($attributesToSync as $attributeName => $value)
		{
			if ($this->isAttributeValueEmpty($attributesToSync, $attributeName)) {
				$attributesToSync[$attributeName] = array();
			}
		}

		// ADI-145: provide API
		$attributesToSync = apply_filters(NEXT_AD_INT_PREFIX . 'sync_wp2ad_filter_synchronizable_attributes', $attributesToSync, $wpUser, $allowedAttributes);

		$status = $this->connection->modifyUserWithoutSchema($wpUser, $attributesToSync);

		// ADI-145: provide API
		do_action(NEXT_AD_INT_PREFIX . 'sync_wp2ad_after_user_synchronize', $status, $wpUser, $attributesToSync, $allowedAttributes);

		return $status;
	}

	/**
	 * Find the user meta data by his user id
	 *
	 * @param int $userId ID of user whose meta data should be fetch
	 * @param array $attributes
	 * @return array
	 */
	protected function findAttributesOfUser($userId, $attributes) {
		$userMeta = get_user_meta($userId);
		$r = array();

		/* @var NextADInt_Ldap_Attribute $attribute */
		foreach ($attributes as $attributeName => $attribute) {
			$metaKey = $attribute->getMetakey();

			if (!isset($userMeta[$metaKey])) {
				continue;
			}

			$r[$attributeName] = $userMeta[$metaKey];
		}

		return $r;
	}

	/**
	 * Log elapsed time.
	 *
	 * @param int $updatedUsers amount of updated users
	 */
	protected function finishSynchronization($updatedUsers) {
		$elapsedTime = $this->getElapsedTime();
		$this->logger->info("$updatedUsers users updated in $elapsedTime seconds.");
		$this->logger->info('End of Sync to AD');
	}
}
