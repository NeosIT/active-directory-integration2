<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Migration_MigrateEncryption')) {
	return;
}

/**
 * Migration_MigrateEncryption migrates the encryption type from older versions to the new configuration.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Migration_MigrateEncryption extends Core_Migration_Abstract
{
	/** @var Logger */
	private $logger;

	public function __construct(Adi_Dependencies $dependencyContainer)
	{
		parent::__construct($dependencyContainer);

		$this->logger = Logger::getLogger('Migration_MigrateEncryption');
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
	 *
	 * @return boolean
	 */
	public function execute()
	{
		$this->logger->info('Start executing migration for new encryption value.');
	}
}