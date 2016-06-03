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
	const LDAPS_PREFIX = 'ldaps://';

	/** @var Logger $logger */
	private $logger;

	/** @var Multisite_Configuration_Persistence_ProfileRepository $profileRepository */
	private $profileRepository;

	/** @var Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/** @var Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository */
	private $blogConfigurationRepository;

	public function __construct(Adi_Dependencies $dependencyContainer)
	{
		parent::__construct($dependencyContainer);

		$this->logger = Logger::getLogger('Migration_MigrateEncryption');
		$this->profileRepository = $dependencyContainer->getProfileRepository();
		$this->profileConfigurationRepository = $dependencyContainer->getProfileConfigurationRepository();
		$this->blogConfigurationRepository = $dependencyContainer->getBlogConfigurationRepository();
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
	 * Blog configuration migration.
	 */
	protected function migrateBlogs()
	{
		// so get all the blog ids from the blogs table
		$blogs = $this->findAllBlogIds();


		// migrate blog configurations
		foreach ($blogs AS $blog) {
			$blogId = $blog['blog_id'];

			$this->migrateConfig($this->blogConfigurationRepository, $blogId);
		}
	}

	/**
	 * Profile configuration migration.
	 */
	protected function migrateProfiles()
	{
		$profiles = $this->profileRepository->findAll();

		foreach ($profiles AS $profile) {
			$profileId = $profile['profileId'];

			$this->migrateConfig($this->profileConfigurationRepository, $profileId);
		}
	}

	/**
	 * Migrate the old data using the given {@code $configurationRepository}.
	 *
	 * @param Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository
	 * @param                                                             $id
	 */
	protected function migrateConfig(Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository,
									 $id
	) {
		// find data to migrate
		$domainControllers = $configurationRepository->findSanitizedValue($id,
			Adi_Configuration_Options::DOMAIN_CONTROLLERS);
		$useTls = $configurationRepository->findSanitizedValue($id, Adi_Configuration_Options::USE_TLS);
		// set initial encryption status by using the 'useTls' value
		$encryptionStatus = ($useTls) ? Multisite_Option_Encryption::STARTTLS : Multisite_Option_Encryption::NONE;

		// check if the connection uses LDAPS
		if (false !== stripos($domainControllers, self::LDAPS_PREFIX)) {
			// set our encryption status to LDAPS
			$encryptionStatus = Multisite_Option_Encryption::LDAPS;

			// remove the 'ldaps://' protocol from the URI
			$domainControllers = str_ireplace(self::LDAPS_PREFIX, '', $domainControllers);
			$configurationRepository->persistSanitizedValue($id, Adi_Configuration_Options::DOMAIN_CONTROLLERS,
				$domainControllers);
		}

		// now we can persist the new encryption status
		$configurationRepository->persistSanitizedValue($id, Adi_Configuration_Options::ENCRYPTION, $encryptionStatus);
	}

	/**
	 * Returns an array containing all blog ids
	 * 
	 * @return array
	 */
	protected function findAllBlogIds() {
		if (!is_multisite()) {
			return array(array('blog_id' => get_current_blog_id()));
		}
		
		global $wpdb;
		return $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
	}
}