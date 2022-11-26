<?php

namespace Dreitier\Nadi\Multisite\Site\Ui;

use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;

/**
 * Extends the "Sites" table for Multisite installations with a new column "ADI profile" which contains the assigned
 * ADI profile of every blog in the Multisite installation.
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
class ExtendSiteList
{
	const ADI_PROFILE_COLUMN = 'adi-profile';

	/**
	 * @var BlogConfigurationRepository
	 */
	private $blogConfigurationRepository;

	/**
	 * @var ProfileRepository
	 */
	private $profileRepository;

	/**
	 * @param BlogConfigurationRepository $blogConfigurationRepository
	 * @param ProfileRepository $profileRepository
	 */
	public function __construct(BlogConfigurationRepository $blogConfigurationRepository,
								ProfileRepository           $profileRepository
	)
	{
		$this->blogConfigurationRepository = $blogConfigurationRepository;
		$this->profileRepository = $profileRepository;
	}

	/**
	 * Add an 'user is disabled' indicator on the user management screen.
	 */
	public function register()
	{
		add_filter('wpmu_blogs_columns', array($this, 'addColumns'), 10, 1);
		add_action('manage_sites_custom_column', array($this, 'addContent'), 1, 2);
	}

	/**
	 * Add ADI profile column
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function addColumns($columns)
	{
		$columns[self::ADI_PROFILE_COLUMN] = __('Active NADI profile', 'next-active-directory-integration');

		return $columns;
	}

	/**
	 * Add the ADI profile for the current blog
	 *
	 * @param string $columnName
	 * @param int $blogId
	 *
	 * @return string
	 */
	public function addContent($columnName, $blogId)
	{
		if ($columnName == self::ADI_PROFILE_COLUMN) {
			$id = $this->blogConfigurationRepository->findProfileId($blogId);
			$isDefaultProfileUsed = $this->blogConfigurationRepository->isDefaultProfileUsed($blogId);

			if ($id) {
				$name = $this->profileRepository->findName($id, '');

				if ($isDefaultProfileUsed) {
					echo sprintf(__('%s (default profile)', 'next-active-directory-integration'), $name);

					return;
				}

				if ($name) {
					echo $name;

					return;
				}
			}

			echo "<em>" . __('None assigned', 'next-active-directory-integration') . '</em>';
		}
	}
}