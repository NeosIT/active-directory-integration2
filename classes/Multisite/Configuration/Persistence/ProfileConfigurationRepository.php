<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository')) {
	return;
}

/**
 * NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository persists and finds profile options.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository implements NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository
{
	const PREFIX = 'po_';
	const PREFIX_VALUE = 'v_';
	const PREFIX_PERMISSION = 'p_';

	/* @var NextADInt_Multisite_Option_Sanitizer */
	private $sanitizer;

	/* @var NextADInt_Core_Encryption */
	private $encryptionHandler;

	/* @var NextADInt_Multisite_Option_Provider */
	private $optionProvider;

	/* @var Logger */
	private $logger;

	/**
	 * @param NextADInt_Multisite_Option_Sanitizer $sanitizer
	 * @param NextADInt_Core_Encryption $encryptionHandler
	 * @param NextADInt_Multisite_Option_Provider $optionProvider
	 */
	public function __construct(NextADInt_Multisite_Option_Sanitizer $sanitizer,
								NextADInt_Core_Encryption $encryptionHandler,
								NextADInt_Multisite_Option_Provider $optionProvider)
	{
		$this->sanitizer = $sanitizer;
		$this->encryptionHandler = $encryptionHandler;
		$this->optionProvider = $optionProvider;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Create the WordPress option name from the type and the username.
	 *
	 * @param mixed $optionValue
	 * @param int $profileId
	 * @param string $optionName
	 *
	 * @return string
	 */
	protected function createUniqueOptionName($optionValue, $profileId, $optionName)
	{
		$prefix = $optionValue ? self::PREFIX_VALUE : self::PREFIX_PERMISSION;

		return NEXT_AD_INT_PREFIX . self::PREFIX . $prefix . $profileId . '_' . $optionName;
	}

	/**
	 * Get the option $optionName for the profile $profileId.
	 *
	 * @param int    $profileSiteId
	 * @param string $optionName
	 *
	 * @return object
	 */
	public function findSanitizedValue($profileSiteId, $optionName)
	{
		$value = $this->findRawValue($profileSiteId, $optionName);
		$optionMetadata = $this->optionProvider->get($optionName);

		if (false === $value) {
			$optionValue = $this->getDefaultValue($profileSiteId, $optionName, $optionMetadata);
		}

		$type = NextADInt_Core_Util_ArrayUtil::get(NextADInt_Multisite_Option_Attribute::TYPE, $optionMetadata);

		if (NextADInt_Multisite_Option_Type::PASSWORD === $type) {
			$value = $this->encryptionHandler->decrypt($value);
		}

		if (isset($optionMetadata[NextADInt_Multisite_Option_Attribute::SANITIZER])) {
			$params = $optionMetadata[NextADInt_Multisite_Option_Attribute::SANITIZER];
			$value = $this->sanitizer->sanitize($value, $params, $optionMetadata);
		}

		return $value;
	}

	/**
	 * Get the default value for $optionName. If the optionMetadata flag DEFAULT_SANITIZER_VALUE exists, then
	 * the sanitizer will create a new value from the default value. This value will be persist, requested and returned.
	 *
	 * @param int $profileId
	 * @param string $optionName
	 * @param array $option
	 *
	 * @return bool|mixed|null|string
	 */
	public function getDefaultValue($profileId, $optionName, $option)
	{
		$optionValue = $option[NextADInt_Multisite_Option_Attribute::DEFAULT_VALUE];

		// generate with Sanitizer a new value, persist it and find it (again).
		if (NextADInt_Core_Util_ArrayUtil::get(NextADInt_Multisite_Option_Attribute::PERSIST_DEFAULT_VALUE, $option, false)) {
			$params = $option[NextADInt_Multisite_Option_Attribute::SANITIZER];
			$optionValue = $this->sanitizer->sanitize($optionValue, $params, $option, true);

			$this->persistValue($profileId, $optionName, $optionValue);
		}

		return $optionValue;
	}

	/**
	 * This method reads the raw value of the option $optionName for the profile $profileId.
	 *
	 * @param int $profileId
	 * @param string $optionName
	 *
	 * @return array|null|object|void
	 */
	public function findRawValue($profileId, $optionName)
	{
		$metadata = $this->optionProvider->get($optionName);
		$default = $metadata[NextADInt_Multisite_Option_Attribute::DEFAULT_VALUE];

		$name = $this->createUniqueOptionName(true, $profileId, $optionName);

		return get_site_option($name, $default);
	}

	/**
	 * Save the option value and option permission
	 *
	 * @param int    $profileSiteId
	 * @param string $optionName
	 * @param string $optionValue
	 *
	 * @return string $optionValue|null
	 */
	public function persistSanitizedValue($profileSiteId, $optionName, $optionValue)
	{
		//option meta data
		$optionElement = $this->optionProvider->get($optionName);

		//call sanitizer
		if (isset($optionElement[NextADInt_Multisite_Option_Attribute::SANITIZER])) {
			$params = $optionElement[NextADInt_Multisite_Option_Attribute::SANITIZER];
			$optionValue = $this->sanitizer->sanitize($optionValue, $params, $optionElement);
		}

		//encrypt if option is a password
		$type = NextADInt_Core_Util_ArrayUtil::get(NextADInt_Multisite_Option_Attribute::TYPE, $optionElement);
		if (NextADInt_Multisite_Option_Type::PASSWORD === $type) {
			$optionValue = $this->encryptionHandler->encrypt($optionValue);
		}

		//save option in database
		return $this->persistValue($profileSiteId, $optionName, $optionValue);
	}

	/**
	 * This method should not be called by the outside.
	 * Persist the option permission and option value for the profile and option name.
	 *
	 * @param int $profileId
	 * @param string $optionName
	 * @param mixed $optionValue
	 *
	 * @return string|null $optionValue
	 */
	protected function persistValue($profileId, $optionName, $optionValue)
	{
		$optionName = $this->createUniqueOptionName(true, $profileId, $optionName);

		return update_site_option($optionName, $optionValue);
	}

	/**
	 * Delete option value for profile.
	 *
	 * @param int $profileId
	 * @param string $optionName
	 *
	 * @return bool
	 */
	public function deleteValue($profileId, $optionName)
	{
		$optionName = $this->createUniqueOptionName(true, $profileId, $optionName);

		return delete_site_option($optionName);
	}

	/**
	 * Get the option permission for the profile and the option.
	 *
	 * @param int $profileId
	 * @param string $optionName
	 *
	 * @return array|bool|null|object|void
	 */
	public function findSanitizedPermission($profileId, $optionName)
	{
		$permissions = $this->findPermission($profileId, $optionName);

		if (!is_numeric($permissions) || $permissions < 0 || $permissions > 3) {
			return 3;
		}

		return $permissions;
	}

	/**
	 *
	 *
	 * @param int $profileId
	 * @param string $optionName
	 *
	 * @return bool|mixed
	 */
	protected function findPermission($profileId, $optionName)
	{
		$optionName = $this->createUniqueOptionName(false, $profileId, $optionName);

		return get_site_option($optionName, 3);
	}

	/**
	 * @param int $profileId
	 * @param string $optionName
	 * @param int $optionPermission between [0,3]
	 *
	 * @return bool
	 */
	public function persistSanitizedPermission($profileId, $optionName, $optionPermission)
	{
		$isValidPermission = is_numeric($optionPermission) && ($optionPermission >= 0 && $optionPermission <= 3);

		if ($isValidPermission) {
			return $this->persistPermission($profileId, $optionName, $optionPermission);
		}

		return false;
	}

	/**
	 * @param int $profileId
	 * @param string $optionName
	 * @param int $optionPermission
	 *
	 * @return bool,
	 */
	protected function persistPermission($profileId, $optionName, $optionPermission)
	{
		$optionName = $this->createUniqueOptionName(false, $profileId, $optionName);

		return update_site_option($optionName, $optionPermission);
	}

	/**
	 * Delete option permission for profile.
	 *
	 * @param int $profileId
	 * @param string $optionName
	 *
	 * @return bool
	 */
	public function deletePermission($profileId, $optionName)
	{
		$optionName = $this->createUniqueOptionName(false, $profileId, $optionName);

		return delete_site_option($optionName);

	}
}