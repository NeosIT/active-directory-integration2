<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Configuration_Service')) {
	return;
}

/**
 * Multisite_Configuraiton_Service returns the value for an option name.
 *
 * Multisite_Configuraiton_Service return the option value depending on the WordPress installation (single/multi side), profile settings and blog settings.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Multisite_Configuration_Service
{
	/**
	 * @var Multisite_Configuration_Persistence_BlogConfigurationRepository
	 */
	private $blogConfigurationRepository;

	/**
	 * @var Multisite_Configuration_Persistence_ProfileConfigurationRepository
	 */
	private $profileConfigurationRepository;

	/** @var Multisite_Configuration_Persistence_ProfileRepository */
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
	 * @param Multisite_Configuration_Persistence_BlogConfigurationRepository    $blogConfigurationRepository
	 * @param Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository
	 * @param Multisite_Configuration_Persistence_ProfileRepository              $profileRepository
	 */
	public function __construct(Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository,
		Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository,
		Multisite_Configuration_Persistence_ProfileRepository $profileRepository
	) {
		$this->blogConfigurationRepository = $blogConfigurationRepository;
		$this->profileConfigurationRepository = $profileConfigurationRepository;
		$this->profileRepository = $profileRepository;
	}

	/**
	 * Get the value for the option with name $optionName.
	 *
	 * @param string   $optionName
	 * @param int|null $blogId if null, the current blog is used
	 *
	 * @return mixed
	 */
	public function getOptionValue($optionName, $blogId = null)
	{
		$option = $this->getOption($optionName, $blogId);

		return Core_Util_ArrayUtil::get('option_value', $option);
	}

	/**
	 * Get the option hashmap with keys 'option_value', 'option_name', 'option_permission' for the option $optionName of blog $blogId.
	 * If $blogId is null, then the current blog will be used.
	 *
	 * @param string   $optionName name of option to lookup
	 * @param int|null $blogId if null, the current blog is used
	 *
	 * @return array
	 */
	public function getOption($optionName, $blogId = null)
	{
		if ($blogId === null) {
			$blogId = get_current_blog_id();
		}

		if (isset($this->cache[$blogId][$optionName]) && is_array($this->cache[$blogId][$optionName])) {
			return $this->cache[$blogId][$optionName];
		}

		$blogOptionValue = $this->blogConfigurationRepository->findSanitized($blogId, $optionName);
		$profileId = $this->blogConfigurationRepository->findProfileId($blogId);
		$profileOptionValue = $this->getProfileOptionValue($optionName, $blogId);

		$permission = $this->getPermission($optionName, $profileId);
		$optionValue = $this->getValue($permission, $profileOptionValue, $blogOptionValue);

		$optionArray = array(
			'option_name'       => $optionName,
			'option_value'      => $optionValue,
			'option_permission' => $permission,
		);

		$this->cache[$blogId][$optionName] = $optionArray;

		return $optionArray;
	}

	/**
	 * Return the value of the profile option for the requested blog. If the blog is not inside a Multsite installation, it returns null
	 *
	 * Do not call this method from the outside.
	 *
	 * @param int|null $blogId
	 * @param string   $optionName
	 *
	 * @return null|array null if singlesite installation
	 */
	public function getProfileOptionValue($optionName, $blogId = null)
	{
		if (!is_multisite()) {
			return null;
		}

		$profileId = $this->blogConfigurationRepository->findProfileId($blogId);
		$profileOption = $this->profileConfigurationRepository->findValueSanitized($profileId, $optionName);

		return $profileOption;
	}

	/**
	 * Return the option value depending upon the WordPress installation type (singlesite or multisite).
	 *
	 * Do not call this method from the outside.
	 *
	 * @param int   $permission
	 * @param mixed $profileOptionValue
	 * @param mixed $blogOptionValue
	 *
	 * @return mixed $blogOpotionValue if $permission is EDITABLE, otherwise $profileOptionValue
	 */
	protected function getValue($permission, $profileOptionValue, $blogOptionValue)
	{
		if ($permission < Multisite_Configuration_Service::EDITABLE) {
			return $profileOptionValue;
		}

		return $blogOptionValue;
	}

	/**
	 * In a MultiSide WordPress installation this method returns the option_permission from the profile option.
	 * In a normal WordPress installation this method returns always Multisite_Configuration_Service::EDITABLE.
	 *
	 * Do not call this method from the outside.
	 *
	 * @param int|null $profileId
	 * @param string   $optionName
	 *
	 * @return int
	 */
	protected function getPermission($optionName, $profileId = null)
	{
		if (is_multisite()) {
			return $this->profileConfigurationRepository->findPermissionSanitized($profileId, $optionName);
		}

		return Multisite_Configuration_Service::EDITABLE;
	}

	public function getAllOptions()
	{
		$allOptionNames = $this->blogConfigurationRepository->getAllOptionNames();
		$profileId = $this->blogConfigurationRepository->findProfileId(get_current_blog_id());

		$options = array();

		foreach ($allOptionNames as $name) {
			$buffer = $this->getOption($name);

			if ($name == "additional_user_attributes") { //TODO bessere Lösung überlegen
				$options[$name] = array(
					'option_value'      => $buffer['option_value'],
					'option_permission' => $buffer['option_permission'],
				);
			} else {
				$options[$name] = array(
					'option_value'      => $buffer['option_value'],
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
	 * @param $profileId
	 *
	 * @return array|mixed
	 */
	public function getAllProfileOptionsValues($profileId)
	{
		$allOptionNames = $this->blogConfigurationRepository->getAllOptionNames();
		$options = array();

		foreach ($allOptionNames as $name) {
			$valueBuffer = $this->profileConfigurationRepository->findValueSanitized($profileId, $name);
			$permissionBuffer = (string)$this->getPermission($name, $profileId);

			if ($name == "additional_user_attributes") { //TODO bessere Lösung überlegen
				$options[$name] = array(
					'option_value'      => $valueBuffer,
					'option_permission' => $permissionBuffer,
				);
			} else {
				$options[$name] = array(
					'option_value'      => $valueBuffer,
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
	 * @param $profileId
	 * @param $options
	 *
	 * @return mixed
	 */
	protected function addProfileInformation($profileId, $options)
	{
		$profileName = $this->profileRepository->findName($profileId);

		$options[Adi_Configuration_Options::PROFILE_NAME] = array(
			'option_value'      => $profileName,
			'option_permission' => Multisite_Configuration_Service::DISABLED_FOR_BLOG_ADMIN,
		);

		return $options;
	}
}
