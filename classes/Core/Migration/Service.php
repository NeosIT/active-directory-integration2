<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Migration_Service')) {
	return;
}

/**
 * NextADInt_Core_Migration_Service provides methods to execute migrations.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Core_Migration_Service
{
	/** @var NextADInt_Adi_Dependencies */
	private $dependencyContainer;

	/** @var NextADInt_Core_Migration_Persistence_MigrationRepository */
	private $migrationRepository;

	/** @var Logger */
	private $logger;

	/**
	 * NextADInt_Core_Migration_Service constructor.
	 *
	 * @param NextADInt_Adi_Dependencies                               $dependencyContainer
	 * @param NextADInt_Core_Migration_Persistence_MigrationRepository $migrationRepository
	 */
	public function __construct(NextADInt_Adi_Dependencies $dependencyContainer,
								NextADInt_Core_Migration_Persistence_MigrationRepository $migrationRepository
	) {
		$this->dependencyContainer = $dependencyContainer;
		$this->migrationRepository = $migrationRepository;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Register the migration start after our plugin initialization.
	 */
	public function register()
	{
		add_action('wp_loaded', array($this, 'startMigration'));
	}

	/**
	 * Get all migrations and execute migrations which have not been migrated yet.
	 */
	public function startMigration()
	{
		// find the latest migrated id
		$lastMigration = $this->migrationRepository->getLastMigration();
		$originalLastMigration = $lastMigration;

		// retrieve all migrations
		$migrations = $this->getOrderedMigrations();

		foreach ($migrations AS $id => $migrationClazz) {
			// check if this migration has already been migrated
			if (false === $id || $lastMigration >= $id) {
				continue;
			}

			$result = $this->executeMigration($migrationClazz);

			// if one of our migration failed, stop the migrations
			if (false === $result) {
				break;
			}

			$lastMigration = $id;
		}

		// if no migration was executed do not set the last migration
		if ($originalLastMigration == $lastMigration) {
			return;
		}

		// update the ID to our last migration.
		$this->migrationRepository->setLastMigration($lastMigration);
	}

	/**
	 * Execute the given migration.
	 *
	 * @param $migrationClazz
	 *
	 * @return bool|int
	 */
	protected function executeMigration($migrationClazz)
	{
		$id = $this->findId($migrationClazz);

		// if the migration has not been migrated yet, execute it and set the ID to $lastMigration
		try {
			/** @var NextADInt_Core_Migration_Abstract $migration */
			$migration = new $migrationClazz($this->dependencyContainer);
			$migration->execute();

			return $id;
		} catch (Exception $e) {
			// log the error
			$message = sprintf('An error occurred while executing the migration with id %d', $id);
			$this->logger->error($message . ". " . $e->getMessage());
		}

		return false;
	}

	/**
	 * Find the ID for the given {@code $migrationClazz}.
	 *
	 * @param $migrationClazz
	 *
	 * @return bool|integer
	 */
	protected function findId($migrationClazz)
	{
		if (!class_exists($migrationClazz)) {
			$this->logger->info(sprintf('Cannot find class "%s".', $migrationClazz));

			return false;
		}

		return call_user_func(array($migrationClazz, 'getId'));
	}

	/**
	 * Return a list of migrations ordered by their ID.
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	protected function getOrderedMigrations()
	{
		$migrations = $this->getMigrations();
		$result = array();

		foreach ($migrations AS $migrationClazz) {
			$id = $this->findId($migrationClazz);

			// no id found? skip the migration
			if (false === $id) {
				continue;
			}

			// duplicated migration id? throw an exception
			if (array_key_exists($id, $result)) {
				throw new Exception('The migration with ID "' . $id . '" has a duplicate.');
			}

			$result[$id] = $migrationClazz;
		}

		// sort our array by its key
		ksort($result);

		return $result;
	}

	/**
	 * Return a list with all migrations.
	 *
	 * @return NextADInt_Core_Migration_Abstract[]
	 */
	protected function getMigrations()
	{
		return array(
			'NextADInt_Migration_MigrateEncryption',
			'NextADInt_Migration_MigrateUseSamAccountNameForNewCreatedUsers',
			'NextADInt_Migration_MigratePasswordEncryption'
		);
	}
}