<?php

namespace Dreitier\WordPress\Multisite\Configuration\Persistence;

/**
 * DefaultProfileRepository creates and updates the default profile.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class DefaultProfileRepository
{
	const SUFFIX_DEFAULT = 'default';

	/**
	 * Find the default profile from the configuration.
	 *
	 * @return int -1 if no profile was found.
	 */
	public function findProfileId()
	{
		$name = $this->getProfileOptionName();
		$profileId = get_site_option($name, false);

		return (false === $profileId) ? -1 : (int)$profileId;
	}

	/**
	 * Save the new $profileId as the default profile.
	 *
	 * @param $profileId
	 */
	public function saveProfileId($profileId)
	{
		$name = $this->getProfileOptionName();
		update_site_option($name, $profileId);
	}

	/**
	 * Return the configuration name for the default profile.
	 *
	 * @return string
	 */
	protected function getProfileOptionName()
	{
		return NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . ProfileRepository::PREFIX . self::SUFFIX_DEFAULT;
	}
}