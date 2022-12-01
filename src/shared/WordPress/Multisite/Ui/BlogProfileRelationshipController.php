<?php

namespace Dreitier\WordPress\Multisite\Ui;


use Dreitier\Nadi\Vendor\Twig\Profiler\Profile;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\DefaultProfileRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;
use Dreitier\WordPress\Multisite\Ui\Table\ProfileAssignment;
use Dreitier\WordPress\WordPressSiteRepository;

/**
 * BlogProfileRelationshipController validates and persists blog-profile-changes.
 * After the network admin stores the change the profile of a blog then this class will persist the change.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class BlogProfileRelationshipController
{
	/* @var BlogConfigurationRepository $blogConfigurationRepository */
	private $blogConfigurationRepository;

	/* @var ProfileRepository $profileRepository */
	private $profileRepository;

	/** @var DefaultProfileRepository $defaultProfileRepository */
	private $defaultProfileRepository;

	/**
	 * @param BlogConfigurationRepository $blogConfigurationRepository
	 * @param ProfileRepository $profileRepository
	 * @param DefaultProfileRepository $defaultProfileRepository
	 */
	public function __construct(BlogConfigurationRepository $blogConfigurationRepository,
								ProfileRepository           $profileRepository,
								DefaultProfileRepository    $defaultProfileRepository
	)
	{
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
			return WordPressSiteRepository::getSites(array('limit' => 9999));
		}

		return array();
	}

	/**
	 * $blogId must exist.
	 *
	 * @param int $blogId
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
	 * @param int $profileId
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
	 * @return ProfileAssignment
	 */
	public function buildSiteTable()
	{
		$wpListTable = new ProfileAssignment(array(
			'screen' =>NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'blog_profile_relationship',
		));

		$wpListTable->register();
		$wpListTable->prepare_items();

		return $wpListTable;
	}
}