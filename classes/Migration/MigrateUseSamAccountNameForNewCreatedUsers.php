<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Migration_MigrateUseSamAccountNameForNewCreatedUsers')) {
	return;
}

/**
 * NextADInt_Migration_MigrateUseSamAccountNameForNewCreatedUsers migrates the "Use sAMAccountname for new created users" option value from older versions to the new configuration by negating it.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Migration_MigrateUseSamAccountNameForNewCreatedUsers extends NextADInt_Core_Migration_Configuration_Abstract
{
	const APPEND_SUFFIX_TO_NEW_USERS = 'append_suffix_to_new_users';

	public function __construct(NextADInt_Adi_Dependencies $dependencyContainer)
	{
		parent::__construct($dependencyContainer);
	}

	/**
	 * Get the position for this migration.
	 *
	 * @return integer
	 */
	public static function getId()
	{
		return 2;
	}

	/**
	 * Migrate the old data using the given {@code $configurationRepository}.
	 *
	 * @param NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository
	 * @param                                                             $id
	 */
	protected function migrateConfig(NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository,
									 $id
	) {
		$this->migrateValue($configurationRepository, $id);
		$this->migratePermission($configurationRepository, $id);
	}

	/**
	 * Migrate the value from the old "append_suffix_to_new_users" settings.
	 *
	 * @param NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository
	 * @param                                                             $id
	 */
	protected function migrateValue(NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository,
									$id
	) {
		// find data to migrate
		$useSamAccountNameForNewUsers = $configurationRepository->findSanitizedValue(
			$id,
			self::APPEND_SUFFIX_TO_NEW_USERS
		);

		// negate it
		if (empty($useSamAccountNameForNewUsers)) {
			$useSamAccountNameForNewUsers = 1;
		} else {
			$useSamAccountNameForNewUsers = '';
		}

		// now we can persist the new value
		$configurationRepository->persistSanitizedValue(
			$id, NextADInt_Adi_Configuration_Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS, $useSamAccountNameForNewUsers
		);
	}

	/**
	 * Migrate the permission from the old "append_suffix_to_new_users" settings.
	 *
	 * @param NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository
	 * @param                                                             $id
	 */
	protected function migratePermission(NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository,
										 $id
	) {
		// find data to migrate
		$useSamAccountNameForNewUsersPermission = $configurationRepository->findSanitizedPermission(
			$id,
			self::APPEND_SUFFIX_TO_NEW_USERS
		);

		// blog configurations do not have a permission and return false instead
		if (false === $useSamAccountNameForNewUsersPermission) {
			return;
		}

		// now we can persist the new value
		$configurationRepository->persistSanitizedPermission(
			$id, NextADInt_Adi_Configuration_Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS, $useSamAccountNameForNewUsersPermission
		);
	}
}