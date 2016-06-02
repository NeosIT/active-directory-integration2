<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Migration_Persistence_MigrationRepository')) {
	return;
}

/**
 * Core_Migration_Persistence_MigrationRepository provides access to the migration data to check if various migrations
 * have already been migrated.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Core_Migration_Persistence_MigrationRepository
{
	const MIGRATION = ADI_PREFIX . 'migration';

	/**
	 * Find the last migration that was executed.
	 *
	 * @return integer
	 */
	public function getLastMigration()
	{
		if (is_multisite()) {
			return get_site_option(self::MIGRATION, 0);
		}

		return get_option(self::MIGRATION, 0);
	}

	/**
	 * Persist the last migrated id.
	 *
	 * @param $lastMigration
	 *
	 * @return bool
	 */
	public function setLastMigration($lastMigration)
	{
		if (is_multisite()) {
			return update_site_option(self::MIGRATION, $lastMigration);
		}

		return update_option(self::MIGRATION, $lastMigration);
	}
}