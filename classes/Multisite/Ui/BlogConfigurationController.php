<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Ui_BlogConfigurationController')) {
	return;
}

/**
 * Multisite_Ui_BlogConfigurationController validates and persists option values entered
 * at the blog settings page.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class Multisite_Ui_BlogConfigurationController
{
	/* @var Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository */
	private $blogConfigurationRepository;

	/* @var Multisite_Option_Provider */
	private $optionProvider;

	/**
	 * @param Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository
	 * @param Multisite_Option_Provider                                       $optionProvider
	 */
	public function __construct(Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository,
		Multisite_Option_Provider $optionProvider
	) {
		$this->blogConfigurationRepository = $blogConfigurationRepository;
		$this->optionProvider = $optionProvider;
	}

	/**
	 * Convert the response from the frontend and save it in the database.
	 * This methods stores the new option values for blog options.
	 *
	 * @param array $options
	 *
	 * @return array
	 */
	public function saveBlogOptions($options)
	{
		try {
			$this->saveBlogOptionsInternal($options);
		} catch (Exception $e) {
			return Core_Message::error(__('An error occurred while saving the configuration.', NEXT_AD_INT_I18N))->toArray();
		}

		return Core_Message::success(__('The configuration has been saved successfully.', NEXT_AD_INT_I18N))->toArray();
	}

	/**
	 * Convert the response from the frontend and save it in the database.
	 * This methods stores the new option values for blog options.
	 *
	 * @param array $options
	 */
	protected function saveBlogOptionsInternal($options)
	{
		foreach ($options as $optionName => $option) {
			if (!$this->validateOption($optionName, $option)) {
				continue;
			}

			$this->persistOption($optionName, $option);
		}
	}

	/**
	 * Persist an single option value change from the $_POST response.
	 *
	 * @param string $optionName
	 * @param array  $option
	 *
	 * @return string
	 */
	public function persistOption($optionName, $option)
	{
		$r = $this->blogConfigurationRepository->persistSanitizedValue(get_current_blog_id(), $optionName, $option);

		return $r;
	}

	/**
	 * Validate option. The option name must exist and the option value must be defined.
	 *
	 * @param string $optionName
	 * @param array  $option
	 *
	 * @return bool
	 */
	public function validateOption($optionName, $option)
	{
		if (!isset($option)) {
			return false;
		}

		if (!$this->optionProvider->existOption($optionName)) {
			return false;
		}

		return true;
	}
}