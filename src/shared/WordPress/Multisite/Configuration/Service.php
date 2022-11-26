<?php

namespace Dreitier\WordPress\Multisite\Configuration;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Vendor\Twig\Profiler\Profile;
use Dreitier\Util\ArrayUtil;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;

/**
 * Service returns the value for an option name.
 *
 * Service return the option value depending on the WordPress installation (single/multi side), profile settings and blog settings.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Service
{
	/**
	 * @var BlogConfigurationRepository
	 */
	private $blogConfigurationRepository;

	/**
	 * @var ProfileConfigurationRepository
	 */
	private $profileConfigurationRepository;

	/** @var ProfileRepository */
	private $profileRepository;

	/**
	 * The option is not shown/available in the frontend
	 */
	const UNAVAILABLE_FOR_BLOG_ADMIN = 0;

	/**
	 * The option is replaced in the frontend with a message that the admin can not edit the option.
	 */
	const REPLACE_OPTION_WITH_DEFAULT_TEXT = 1;

	/**
	 * The option is shown in the frontend but disabled
	 */
	const DISABLED_FOR_BLOG_ADMIN = 2;

	/**
	 * The option is shown in the frontend and editable by the admin
	 */
	const EDITABLE = 3;

	/* @var array */
	private $cache = array();

	/**
	 * @param BlogConfigurationRepository $blogConfigurationRepository
	 * @param ProfileConfigurationRepository $profileConfigurationRepository
	 * @param ProfileRepository $profileRepository
	 */
	public function __construct(BlogConfigurationRepository    $blogConfigurationRepository,
								ProfileConfigurationRepository $profileConfigurationRepository,
								ProfileRepository              $profileRepository
	)
	{
		$this->blogConfigurationRepository = $blogConfigurationRepository;
		$this->profileConfigurationRepository = $profileConfigurationRepository;
		$this->profileRepository = $profileRepository;
	}

	/**
	 * Return all profiles with the given options.
	 *
	 * @param array $optionNames if empty, search for all options
	 *
	 * @return array
	 */
	public function findAllProfiles(array $optionNames = array())
	{
		if (empty($optionNames)) {
			$optionNames = $this->blogConfigurationRepository->getAllOptionNames();
		}

		$profileIds = $this->profileRepository->findAllIds();
		$r = array();

		foreach ($profileIds as $profileId) {
			$r[$profileId] = $this->getProfileOptionsValues($profileId, $optionNames);
		}

		return $r;
	}

	/**
	 * Get the value for the option with name $optionName.
	 *
	 * @param string $optionName
	 * @param int|null $siteId if null, the current blog is used
	 *
	 * @return mixed
	 */
	public function getOptionValue($optionName, $siteId = null)
	{
		$option = $this->getOption($optionName, $siteId);

		return ArrayUtil::get('option_value', $option);
	}

	/**
	 * Get the option hashmap with keys 'option_value', 'option_name', 'option_permission' for the option $optionName of site $siteId.
	 *
	 * @param string $optionName name of option to lookup
	 * @param int|null $siteId if null, the current blog is used
	 *
	 * @return array
	 */
	public function getOption($optionName, $siteId = null)
	{
		if ($siteId === null) {
			$siteId = get_current_blog_id();
		}

		if (isset($this->cache[$siteId][$optionName]) && is_array($this->cache[$siteId][$optionName])) {
			return $this->cache[$siteId][$optionName];
		}

		$blogOptionValue = $this->blogConfigurationRepository->findSanitizedValue($siteId, $optionName);

		$profileId = $this->blogConfigurationRepository->findProfileId($siteId);

		$profileHasLinkedDomain = false;

		if ($profileId != null) {
			$profileDomainSid = $this->getProfileOptionValue(Options::DOMAIN_SID, $siteId);

			if (!empty($profileDomainSid)) {
				$profileHasLinkedDomain = true;
			}
		}

		$profileOptionValue = $this->getProfileOptionValue($optionName, $siteId);
		$permission = $this->getPermission($optionName, $profileId);

		// ADI-235: corner-case; if the profile has been already linked to an Active Directory domain the options from
		// the "Environment" tab can't be edited in child profiles. This prevents overwriting connections provided by the
		// network administrator
		if ($profileHasLinkedDomain && $this->isEnvironmentOption($optionName)) {
			$optionValue = $profileOptionValue;

			if ($permission == self::EDITABLE) {
				$permission = self::DISABLED_FOR_BLOG_ADMIN;
			}
		} else {
			$optionValue = $this->getValue($permission, $profileOptionValue, $blogOptionValue);
		}

		$optionArray = array(
			'option_name' => $optionName,
			'option_value' => $optionValue,
			'option_permission' => $permission,
		);

		$this->cache[$siteId][$optionName] = $optionArray;

		return $optionArray;
	}

	/**
	 * Return the value of the profile option for the requested blog. If the blog is not inside a Multsite installation, it returns null
	 *
	 * Do not call this method from the outside.
	 *
	 * @param int|null $siteId
	 * @param string $optionName
	 *
	 * @return null|array null if singlesite installation
	 */
	function getProfileOptionValue($optionName, $siteId = null)
	{
		if (!is_multisite()) {
			return null;
		}

		$profileId = $this->blogConfigurationRepository->findProfileId($siteId);
		$profileOption = $this->profileConfigurationRepository->findSanitizedValue($profileId, $optionName);

		return $profileOption;
	}

	/**
	 * Return the option value depending upon the WordPress installation type (singlesite or multisite).
	 *
	 * Do not call this method from the outside.
	 *
	 * @param int $permission
	 * @param mixed $profileOptionValue
	 * @param mixed $blogOptionValue
	 *
	 * @return mixed $blogOptionValue if $permission is EDITABLE, otherwise $profileOptionValue
	 */
	protected function getValue($permission, $profileOptionValue, $blogOptionValue)
	{
		if ($permission < self::EDITABLE) {
			return $profileOptionValue;
		}

		return $blogOptionValue;
	}

	/**
	 * In a MultiSide WordPress installation this method returns the option_permission from the profile option.
	 * In a normal WordPress installation this method returns always ’Dreitier\WordPress\Multisite\Configuration\Service::EDITABLE.
	 *
	 * Do not call this method from the outside.
	 *
	 * @param int|null $profileId
	 * @param string $optionName
	 *
	 * @return int
	 */
	protected function getPermission($optionName, $profileId = null)
	{
		if (is_multisite()) {
			return $this->profileConfigurationRepository->findSanitizedPermission($profileId, $optionName);
		}

		return self::EDITABLE;
	}

	/**
	 * Find all options for the current site.
	 * It merges the default profile options with its overriden settings fo the current site.
	 *
	 * @return array|mixed
	 */
	public function getAllOptions()
	{
		$siteId = get_current_blog_id();


		$allOptionNames = $this->blogConfigurationRepository->getAllOptionNames();
		$profileId = $this->blogConfigurationRepository->findProfileId($siteId);

		foreach ($allOptionNames as $name) {
			$buffer = $this->getOption($name, $siteId);

			if ($name == "additional_user_attributes") { //TODO find better solution
				$options[$name] = array(
					'option_value' => $buffer['option_value'],
					'option_permission' => $buffer['option_permission'],
				);
			} else {
				$options[$name] = array(
					'option_value' => $buffer['option_value'],
					'option_permission' => $buffer['option_permission'],
				);
			}
		}

		$options = $this->addProfileInformation($profileId, $options);

		return $options;
	}

	/**
	 * Find all option values and the corresponding permission.
	 *
	 * @param int $profileId
	 * @param array $optionNames
	 *
	 * @return array|mixed
	 */
	public function getProfileOptionsValues($profileId, $optionNames = array())
	{
		$allOptionNames = (empty($optionNames)) ? $this->blogConfigurationRepository->getAllOptionNames() : $optionNames;
		$options = array();

		foreach ($allOptionNames as $name) {
			$valueBuffer = $this->profileConfigurationRepository->findSanitizedValue($profileId, $name);
			$permissionBuffer = (string)$this->getPermission($name, $profileId);

			if ($name == "additional_user_attributes") { //TODO bessere Lösung überlegen
				$options[$name] = array(
					'option_value' => $valueBuffer,
					'option_permission' => $permissionBuffer,
				);
			} else {
				$options[$name] = array(
					'option_value' => $valueBuffer,
					'option_permission' => $permissionBuffer,
				);
			}
		}

		$options = $this->addProfileInformation($profileId, $options);

		return $options;
	}

	/**
	 * Find all option values, that are  part of the profile and not the profile configuration.
	 *
	 * @param number $profileId
	 * @param array $options
	 *
	 * @return mixed
	 */
	protected function addProfileInformation($profileId, $options)
	{
		$profileName = $this->profileRepository->findName($profileId);

		$options[Options::PROFILE_NAME] = array(
			'option_value' => $profileName,
			'option_permission' => self::DISABLED_FOR_BLOG_ADMIN,
		);

		return $options;
	}

	/**
	 * Return if the given option name is located on the "Environment" tab
	 *
	 * @param string $optionName
	 *
	 * @return bool
	 */
	public function isEnvironmentOption($optionName)
	{
		$arrEnvironmentOptions = array(Options::DOMAIN_CONTROLLERS,
			Options::PORT,
			Options::USE_TLS,
			Options::ALLOW_SELF_SIGNED,
			Options::NETWORK_TIMEOUT,
			Options::BASE_DN,
			Options::DOMAIN_SID,
			Options::VERIFICATION_USERNAME,
			Options::VERIFICATION_PASSWORD,
			Options::ENCRYPTION,
			Options::ADDITIONAL_DOMAIN_SIDS
		); //TODO move somewhere else

		// TODO better solution would be to get viewable configuration through Layout class. But this introduces new
		// dependencies to the front end package. Meh.

		if (in_array($optionName, $arrEnvironmentOptions)) {
			return true;
		}

		return false;
	}
}