<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Ui_ProfileConfigurationController')) {
	return;
}

/**
 * NextADInt_Multisite_Ui_ProfileConfigurationController validates and persists the option values and
 * permissions for a profile.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class NextADInt_Multisite_Ui_ProfileConfigurationController
{
	/* @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/* @var NextADInt_Multisite_Option_Provider */
	private $optionProvider;

	/**
	 * @param NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository
	 * @param NextADInt_Multisite_Option_Provider                                          $optionProvider
	 */
	public function __construct(NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository,
		NextADInt_Multisite_Option_Provider $optionProvider
	) {
		$this->profileConfigurationRepository = $profileConfigurationRepository;
		$this->optionProvider = $optionProvider;
	}

	/**
	 * This method converts the data from the frontend and save the new profile options in the database.
	 *
	 * @param $options
	 * @param $profile
	 *
	 * @return array
	 */
	public function saveProfileOptions($options, $profile)
	{
		try {
			$this->saveProfileOptionsInternal($options, $profile);
		} catch (Exception $e) {
			return NextADInt_Core_Message::error(__('An error occurred while saving the configuration.', 'next-active-directory-integration'))->toArray();
		}

		return NextADInt_Core_Message::success(__('The configuration was saved successfully.', 'next-active-directory-integration'))->toArray();
	}

	/**
	 * This method saves the profile data in the database.
	 *
	 * @param $options
	 * @param $profile
	 */
	protected function saveProfileOptionsInternal($options, $profile)
	{
		foreach ($options as $optionName => $option) {
			if (!$this->validateOption($optionName, $option)) {
				continue;
			}

			$this->persistOption($optionName, $option, $profile);
		}
	}

	/**
	 * Persists the value and the permission for a single option for
	 * the profile $profile.
	 *
	 * @param $optionName
	 * @param $option
	 * @param $profile
	 */
	function persistOption($optionName, $option, $profile)
	{
		$optionPermission = $option['option_permission'];
		$optionValue = $option['option_value'];
		$this->profileConfigurationRepository->persistSanitizedValue($profile, $optionName, $optionValue);
		$this->profileConfigurationRepository->persistSanitizedPermission($profile, $optionName, $optionPermission);
	}

	/**
	 * Validate the option.
	 *
	 * @param $optionName
	 * @param $option
	 *
	 * @return bool
	 */
	public function validateOption($optionName, $option)
	{
		if (!$this->optionProvider->existOption($optionName)) {
			return false;
		}

		if (!isset($option['option_permission'])) {
			return false;
		}

		if (!isset($option['option_value'])) {
			return false;
		}

		return true;
	}
}