<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Migration_Configuration_Abstract')) {
	return;
}

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 */
abstract class NextADInt_Core_Migration_Configuration_Abstract extends NextADInt_Core_Migration_Abstract
{
	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileRepository $profileRepository */
	protected $profileRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository */
	protected $profileConfigurationRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository */
	protected $blogConfigurationRepository;

	public function __construct(NextADInt_Adi_Dependencies $dependencyContainer)
	{
		parent::__construct($dependencyContainer);

		$this->profileRepository = $dependencyContainer->getProfileRepository();
		$this->profileConfigurationRepository = $dependencyContainer->getProfileConfigurationRepository();
		$this->blogConfigurationRepository = $dependencyContainer->getBlogConfigurationRepository();
	}

	/**
	 * Execute the migration.
	 *
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

	abstract protected function migrateConfig(NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository,
											  $blogId
	);

	/**
	 * Returns an array containing all blog ids
	 *
	 * @return array
	 */
	protected function findAllBlogIds()
	{
		if (!is_multisite()) {
			return array(array('blog_id' => get_current_blog_id()));
		}

		global $wpdb;

		return $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A);
	}
}