<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Ui_BlogProfileRelationshipController')) {
	return;
}

/**
 * NextADInt_Multisite_Ui_BlogProfileRelationshipController validates and persists blog-profile-changes.
 * After the network admin stores the change the profile of a blog then this class will persist the change.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class NextADInt_Multisite_Ui_BlogProfileRelationshipController
{
	/* @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository */
	private $blogConfigurationRepository;

	/* @var NextADInt_Multisite_Configuration_Persistence_ProfileRepository $profileRepository */
	private $profileRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository $defaultProfileRepository */
	private $defaultProfileRepository;

	/**
	 * @param NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository
	 * @param NextADInt_Multisite_Configuration_Persistence_ProfileRepository           $profileRepository
	 * @param NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository    $defaultProfileRepository
	 */
	public function __construct(NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository,
		NextADInt_Multisite_Configuration_Persistence_ProfileRepository $profileRepository,
		NextADInt_Multisite_Configuration_Persistence_DefaultProfileRepository $defaultProfileRepository
	) {
		$this->blogConfigurationRepository = $blogConfigurationRepository;
		$this->profileRepository = $profileRepository;
		$this->defaultProfileRepository = $defaultProfileRepository;
	}

	/**
	 * This methods stores default profile.
	 *
	 * @param $profileId
	 */
	public function saveDefaultProfile($profileId)
	{
		$profiles = $this->profileRepository->findAllIds();

		// If the given profile is not valid, we prevent the save
		if (!$this->validateProfile($profileId, $profiles)) {
			return;
		}

		$this->defaultProfileRepository->saveProfileId($profileId);
	}

	/**
	 * This methods persists the changed relation between from given blogs to a profile.
	 *
	 * @param $profileId
	 * @param $blogIds
	 */
	public function saveBlogProfileAssociations($profileId, $blogIds)
	{
		$profiles = $this->profileRepository->findAllIds();
		$sites = $this->getSites();

		if (!$this->validateProfile($profileId, $profiles)) {
			return;
		}

		foreach ($blogIds as $blogId) {
			if (!$this->validateBlog($blogId, $sites)) {
				continue;
			}

			$this->persist($blogId, $profileId);
		}
	}

	/**
	 * Returns an array with all network sites. In a normal installation this method will return an empty string.
	 *
	 * @return array
	 */
	public function getSites()
	{
		if (is_multisite()) {
			return NextADInt_Core_Util_Internal_WordPress::getSites(array('limit' => 9999));
		}

		return array();
	}

	/**
	 * $blogId must exist.
	 *
	 * @param int   $blogId
	 * @param array $sites
	 *
	 * @return bool
	 */
	public function validateBlog($blogId, $sites)
	{
		foreach ($sites as $site) {
			if ($site['blog_id'] == $blogId) {
				return true;
			}
		}

		return false;
	}

	/**
	 * $profileId must exist.
	 *
	 * @param int   $profileId
	 * @param array $profiles
	 *
	 * @return bool
	 */
	public function validateProfile($profileId, $profiles)
	{
		if ('-1' === $profileId) {
			return true;
		}

		foreach ($profiles as $profile) {
			if ($profile == $profileId) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Changed the associated profile of a blog.
	 *
	 * @param int $blogId
	 * @param int $profileId
	 *
	 * @return string
	 */
	public function persist($blogId, $profileId)
	{
		if ('-1' === $profileId) {
			return $this->blogConfigurationRepository->deleteProfileId($blogId, $profileId);
		}

		return $this->blogConfigurationRepository->updateProfileId($blogId, $profileId);
	}

	/**
	 * Create a new {@see Multisite_Ui_Table_SiteTable}, load the items and return the new instance.
	 *
	 * @return NextADInt_Multisite_Ui_Table_ProfileAssignment
	 */
	public function buildSiteTable()
	{
		$wpListTable = new NextADInt_Multisite_Ui_Table_ProfileAssignment(array(
			'screen' => NEXT_AD_INT_PREFIX . 'blog_profile_relationship',
		));

		$wpListTable->register();
		$wpListTable->prepare_items();

		return $wpListTable;
	}
}