<?php
namespace Dreitier\WordPress\Multisite\Configuration\Persistence;

/**
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 */
interface ConfigurationRepository
{
	/**
	 * Find the value of the given option
	 *
	 * @param int    $id
	 * @param string $optionName
	 *
	 * @return mixed
	 */
	public function findSanitizedValue($id, $optionName);

	/**
	 * Find the raw value of the given option.
	 * This method is necessary for the migration of the encrypted passwords.
	 *
	 *
	 * @param $siteId
	 * @param $optionName
	 *
	 * @return mixed
	 */
	public function findRawValue($siteId, $optionName);

	/**
	 * Persist the given configuration option
	 *
	 * @param int    $siteId
	 * @param string $optionName
	 * @param mixed  $optionValue
	 */
	public function persistSanitizedValue($siteId, $optionName, $optionValue);

	/**
	 * Get the option permission for the profile and the option.
	 *
	 * @param int    $profileId
	 * @param string $optionName
	 *
	 * @return array|bool|null|object|void
	 */
	public function findSanitizedPermission($profileId, $optionName);

	/**
	 * @param int    $profileId
	 * @param string $optionName
	 * @param int    $optionPermission between [0,3]
	 *
	 * @return bool
	 */
	public function persistSanitizedPermission($profileId, $optionName, $optionPermission);
}