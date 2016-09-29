<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Configuration_Service')) {
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
class NextADInt_Multisite_Configuration_Service
{
	/**
	 * @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository
	 */
	private $blogConfigurationRepository;

	/**
	 * @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository
	 */
	private $profileConfigurationRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileRepository */
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
	 * @param NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository    $blogConfigurationRepository
	 * @param NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository
	 * @param NextADInt_Multisite_Configuration_Persistence_ProfileRepository              $profileRepository
	 */
	public function __construct(NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository,
								NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository,
								NextADInt_Multisite_Configuration_Persistence_ProfileRepository $profileRepository
	) {
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
	 * @param string   $optionName
	 * @param int|null $blogId if null, the current blog is used
	 *
	 * @return mixed
	 */
	public function getOptionValue($optionName, $blogId = null)
	{
		$option = $this->getOption($optionName, $blogId);

		return NextADInt_Core_Util_ArrayUtil::get('option_value', $option);
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

		$blogOptionValue = $this->blogConfigurationRepository->findSanitizedValue($blogId, $optionName);
		$profileId = $this->blogConfigurationRepository->findProfileId($blogId);
		$profileHasLinkedDomain = false;

		if ($profileId != null) {
			$profileDomainSid = $this->getProfileOptionValue(NextADInt_Adi_Configuration_Options::DOMAIN_SID, $blogId);

			if (!empty($profileDomainSid)) {
				$profileHasLinkedDomain = true;
			}
		}

		$profileOptionValue = $this->getProfileOptionValue($optionName, $blogId);
		$permission = $this->getPermission($optionName, $profileId);

		// ADI-235: corner-case; if the profile has been already linked to an Active Directory domain the options from
		// the "Environment" tab can't be edited in child profiles. This prevents overwriting connections provided by the
		// network administrator
		if ($profileHasLinkedDomain && $this->isEnvironmentOption($optionName)) {
			$optionValue = $profileOptionValue;

			if ($permission == NextADInt_Multisite_Configuration_Service::EDITABLE) {
				$permission = NextADInt_Multisite_Configuration_Service::DISABLED_FOR_BLOG_ADMIN;
			}
		}
		else {
			$optionValue = $this->getValue($permission, $profileOptionValue, $blogOptionValue);
		}

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
		$profileOption = $this->profileConfigurationRepository->findSanitizedValue($profileId, $optionName);

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
		if ($permission < NextADInt_Multisite_Configuration_Service::EDITABLE) {
			return $profileOptionValue;
		}

		return $blogOptionValue;
	}

	/**
	 * In a MultiSide WordPress installation this method returns the option_permission from the profile option.
	 * In a normal WordPress installation this method returns always NextADInt_Multisite_Configuration_Service::EDITABLE.
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
			return $this->profileConfigurationRepository->findSanitizedPermission($profileId, $optionName);
		}

		return NextADInt_Multisite_Configuration_Service::EDITABLE;
	}

	/**
	 * Find all options for the current site
	 * @return array|mixed
	 */
	public function getAllOptions()
	{
		$allOptionNames = $this->blogConfigurationRepository->getAllOptionNames();
		$profileId = $this->blogConfigurationRepository->findProfileId(get_current_blog_id());

		$options = array();

		foreach ($allOptionNames as $name) {
			$buffer = $this->getOption($name);

			if ($name == "additional_user_attributes") { //TODO find better solution
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
	 * @param int	$profileId
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
					'option_value'      => $valueBuffer,
					'option_permission' => $permissionBuffer,
				);
			}
			else {
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
	 * @param number $profileId
	 * @param array   $options
	 *
	 * @return mixed
	 */
	protected function addProfileInformation($profileId, $options)
	{
		$profileName = $this->profileRepository->findName($profileId);

		$options[NextADInt_Adi_Configuration_Options::PROFILE_NAME] = array(
			'option_value'      => $profileName,
			'option_permission' => NextADInt_Multisite_Configuration_Service::DISABLED_FOR_BLOG_ADMIN,
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
		$arrEnvironmentOptions = array(NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS,
			NextADInt_Adi_Configuration_Options::PORT,
			NextADInt_Adi_Configuration_Options::USE_TLS,
			NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT,
			NextADInt_Adi_Configuration_Options::BASE_DN,
			NextADInt_Adi_Configuration_Options::DOMAIN_SID,
			NextADInt_Adi_Configuration_Options::VERIFICATION_USERNAME,
			NextADInt_Adi_Configuration_Options::VERIFICATION_PASSWORD,
			NextADInt_Adi_Configuration_Options::ENCRYPTION
		); //TODO move somewhere else

		// TODO better solution would be to get viewable configuration through Layout class. But this introduces new
		// dependencies to the front end package. Meh.

		if (in_array($optionName, $arrEnvironmentOptions)) {
			return true;
		}

		return false;
	}
}
