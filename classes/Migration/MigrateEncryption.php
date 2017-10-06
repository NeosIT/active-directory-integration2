<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Migration_MigrateEncryption')) {
	return;
}

/**
 * NextADInt_Migration_MigrateEncryption migrates the encryption type from older versions to the new configuration.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Migration_MigrateEncryption extends NextADInt_Core_Migration_Configuration_Abstract
{
	const LDAPS_PREFIX = 'ldaps://';

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
		return 1;
	}

	/**
	 * Execute the migration.
	 * @return bool
	 * @throws Exception
	 */
	public function execute()
	{
		$this->migrateBlogs();
		$this->migrateProfiles();
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
		// find data to migrate
		$domainControllers = $configurationRepository->findSanitizedValue($id,
			NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS);
		$useTls = $configurationRepository->findSanitizedValue($id, NextADInt_Adi_Configuration_Options::USE_TLS);
		// set initial encryption status by using the 'useTls' value
		$encryptionStatus = ($useTls) ? NextADInt_Multisite_Option_Encryption::STARTTLS : NextADInt_Multisite_Option_Encryption::NONE;

		// check if the connection uses LDAPS
		if (false !== stripos($domainControllers, self::LDAPS_PREFIX)) {
			// set our encryption status to LDAPS
			$encryptionStatus = NextADInt_Multisite_Option_Encryption::LDAPS;

			// remove the 'ldaps://' protocol from the URI
			$domainControllers = str_ireplace(self::LDAPS_PREFIX, '', $domainControllers);
			$configurationRepository->persistSanitizedValue($id, NextADInt_Adi_Configuration_Options::PORT, 636);
			$configurationRepository->persistSanitizedValue($id, NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS,
				$domainControllers);
		}

		// now we can persist the new encryption status
		$configurationRepository->persistSanitizedValue($id, NextADInt_Adi_Configuration_Options::ENCRYPTION, $encryptionStatus);
	}
}