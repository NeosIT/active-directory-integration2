<?php

namespace Dreitier\WordPress\Multisite\Ui;

use Dreitier\Util\Exception;
use Dreitier\Util\Message\Message;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileConfigurationRepository;
use Dreitier\WordPress\Multisite\Option\Provider;

/**
 * ProfileConfigurationController validates and persists the option values and
 * permissions for a profile.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class ProfileConfigurationController
{
	/* @var ProfileConfigurationRepository $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/* @var Provider */
	private $optionProvider;

	/**
	 * @param ProfileConfigurationRepository $profileConfigurationRepository
	 * @param Provider $optionProvider
	 */
	public function __construct(ProfileConfigurationRepository $profileConfigurationRepository,
								Provider                       $optionProvider
	)
	{
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
		} catch (\Exception $e) {
			return Message::error(__('An error occurred while saving the configuration.', 'next-active-directory-integration'))->toArray();
		}

		return Message::success(__('The configuration was saved successfully.', 'next-active-directory-integration'))->toArray();
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