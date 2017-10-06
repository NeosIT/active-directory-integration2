<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Migration_Persistence_MigrationRepository')) {
	return;
}

/**
 * NextADInt_Core_Migration_Persistence_MigrationRepository provides access to the migration data to check if various migrations
 * have already been migrated.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Core_Migration_Persistence_MigrationRepository
{
	const MIGRATION = 'migration';

	/**
	 * Return the full option name for migrations
	 * @return string
	 */
	public static function getMigrationOption() {
		return NEXT_AD_INT_PREFIX . 'migration';
	}

	/**
	 * Find the last migration that was executed.
	 *
	 * @return integer
	 */
	public function getLastMigration()
	{
		if (is_multisite()) {
			return get_site_option(self::getMigrationOption(), 0);
		}

		return get_option(self::getMigrationOption(), 0);
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
			return update_site_option(self::getMigrationOption(), $lastMigration);
		}

		return update_option(self::getMigrationOption(), $lastMigration);
	}
}