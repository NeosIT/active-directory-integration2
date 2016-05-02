<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Configuration_Persistence_BlogConfigurationRepository')) {
	return;
}

/**
 * Multisite_Configuration_Persistence_BlogConfigurationRepository finds or insert option values for a normal WordPress installation or for each sites of an
 * network WordPress installation.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class Multisite_Configuration_Persistence_BlogConfigurationRepository
{
	const PROFILE_ID = 'profile_id';
	const PREFIX = 'bo_v_';

	/* @var Multisite_Option_Sanitizer $sanitizer */
	private $sanitizer;

	/* @var Core_Encryption $encryptionHandler */
	private $encryptionHandler;

	/* @var Logger $logger */
	private $logger;

	/** @var Multisite_Option_Provider $optionProvider */
	private $optionProvider;

	/** @var Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/** @var Multisite_Configuration_Persistence_ProfileRepository $profileRepository */
	private $profileRepository;

	/** @var Multisite_Configuration_Persistence_DefaultProfileRepository $defaultProfileRepository */
	private $defaultProfileRepository;

	/**
	 * @param Multisite_Option_Sanitizer                                         $sanitizer
	 * @param Core_Encryption                                                    $encryptionHandler
	 * @param Multisite_Option_Provider                                          $optionProvider
	 * @param Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository
	 * @param Multisite_Configuration_Persistence_DefaultProfileRepository       $defaultProfileRepository
	 */
	public function __construct(Multisite_Option_Sanitizer $sanitizer,
								Core_Encryption $encryptionHandler,
								Multisite_Option_Provider $optionProvider,
		Multisite_Configuration_Persistence_ProfileConfigurationRepository $profileConfigurationRepository,
		Multisite_Configuration_Persistence_DefaultProfileRepository $defaultProfileRepository
	) {
		$this->sanitizer = $sanitizer;
		$this->encryptionHandler = $encryptionHandler;
		$this->optionProvider = $optionProvider;
		$this->profileConfigurationRepository = $profileConfigurationRepository;
		$this->defaultProfileRepository = $defaultProfileRepository;

		$this->logger = Logger::getLogger(__CLASS__);
	}

	/**
	 * Generate the WordPress option name from the internal option name
	 *
	 * @param string $optionName
	 *
	 * @return string
	 */
	protected function getOptionName($optionName)
	{
		return ADI_PREFIX . self::PREFIX . $optionName;
	}

	/**
	 * Get all option names that can be persisted.
	 *
	 * @return array
	 */
	public function getAllOptionNames()
	{
		return array_keys($this->optionProvider->getNonTransient());
	}

	/**
	 * Get all options as an array for the blog $blogid.
	 *
	 * @param int $siteId
	 *
	 * @return array
	 */
	public function findAllSanitized($siteId)
	{
		$options = array();
		$optionNames = array_keys($this->optionProvider->getNonTransient());
		foreach ($optionNames as $optionName) {
			$options[$optionName] = $this->findSanitized($siteId, $optionName);
		}

		return $options;
	}

	/**
	 * Get the value for the option $optionName and for the blog $blogId.
	 * Moreover this method sanitize, decrypt etc. the value.
	 *
	 * @param int    $siteId
	 * @param string $optionName
	 *
	 * @return null|string
	 */
	public function findSanitized($siteId, $optionName)
	{
		//prevent change of associated profile
		if (self::PROFILE_ID === $optionName) {
			return null;
		}

		if ($this->isOptionHandledByProfile($siteId, $optionName)) {
			$profileId = $this->findProfileId($siteId);

			return $this->profileConfigurationRepository->findValueSanitized($profileId, $optionName);
		}

		$optionValue = $this->find($siteId, $optionName);
		$optionMetadata = $this->optionProvider->get($optionName);

		if (false === $optionValue) {
			$optionValue = $this->getDefaultValue($siteId, $optionName, $optionMetadata);
		}

		$type = Core_Util_ArrayUtil::get(Multisite_Option_Attribute::TYPE, $optionMetadata);

		if ($type === Multisite_Option_Type::PASSWORD) {
			$optionValue = $this->encryptionHandler->decrypt($optionValue);
		}

		if (isset($optionMetadata[Multisite_Option_Attribute::SANITIZER])) {
			$params = $optionMetadata[Multisite_Option_Attribute::SANITIZER];
			$optionValue = $this->sanitizer->sanitize($optionValue, $params, $optionMetadata, false);
		}

		return $optionValue;
	}

	/**
	 * Check if the current option is handled by a profile.
	 *
	 * @param int    $siteId
	 * @param string $optionName
	 *
	 * @return bool
	 */
	protected function isOptionHandledByProfile($siteId, $optionName)
	{
		$profileId = $this->findProfileId($siteId);
		$permission = $this->profileConfigurationRepository->findPermissionSanitized($profileId, $optionName);

		if (Multisite_Configuration_Service::EDITABLE > $permission) {
			return true;
		}

		return false;
	}

	/**
	 * Get the default value for $optionName. If the optionMetadata flag DEFAULT_SANITIZER_VALUE exists, then
	 * the sanitizer will create a new value from the default value. This value will be persist, requested and returned.
	 *
	 * @param int    $siteId
	 * @param string $optionName
	 * @param array $option
	 *
	 * @return bool|mixed|null|string
	 */
	public function getDefaultValue($siteId, $optionName, $option)
	{
		$optionValue = $option[Multisite_Option_Attribute::DEFAULT_VALUE];

		// generate with Sanitizer a new value, persist it and find it (again).
		if (Core_Util_ArrayUtil::get(Multisite_Option_Attribute::PERSIST_DEFAULT_VALUE, $option, false)) {
			$params = $option[Multisite_Option_Attribute::SANITIZER];
			$optionValue = $this->sanitizer->sanitize($optionValue, $params, $option, true);

			$this->persistSanitized($siteId, $optionName, $optionValue);
		}

		return $optionValue;
	}

	/**
	 * This method should not be called by the outside.
	 * Read the value for option $optionName and site/blog $blogId.
	 *
	 * @param int    $siteId
	 * @param string $optionName
	 *
	 * @return string|null
	 */
	protected function find($siteId, $optionName)
	{
		$name = $this->getOptionName($optionName);

		if (is_multisite()) {
			return get_blog_option($siteId, $name, false);
		}

		return get_option($name, false);
	}

	/**
	 * Save an option for the blog $blogid.
	 * Moreover this method sanitize, encrypt etc. the value.
	 *
	 * @param int    $siteId
	 * @param string $optionName
	 * @param string $optionValue
	 *
	 * @return string $optionValue return the sanitized value
	 */
	public function persistSanitized($siteId, $optionName, $optionValue)
	{
		if (self::PROFILE_ID === $optionName) {
			return null;
		}

		$optionMetadata = $this->optionProvider->get($optionName);

		if (isset($optionMetadata[Multisite_Option_Attribute::SANITIZER])) {
			$params = $optionMetadata[Multisite_Option_Attribute::SANITIZER];
			$optionValue = $this->sanitizer->sanitize($optionValue, $params, $optionMetadata, true);
		}

		$type = Core_Util_ArrayUtil::get(Multisite_Option_Attribute::TYPE, $optionMetadata);

		if (Multisite_Option_Type::PASSWORD === $type) {
			$optionValue = $this->encryptionHandler->encrypt($optionValue);
		}

		return $this->persist($siteId, $optionName, $optionValue);
	}

	/**
	 * This method should not be called by the outside.
	 * Write the value $optionValue for option $optionName and blog/site $blogId.
	 *
	 * @param int    $siteId
	 * @param string $optionName
	 * @param mixed $optionValue
	 *
	 * @return String|null $optionValue or (when it fails) null
	 */
	protected function persist($siteId, $optionName, $optionValue)
	{
		$optionName = $this->getOptionName($optionName);

		if (is_multisite()) {
			$success = update_blog_option($siteId, $optionName, $optionValue);
		} else {
			$success = update_option($optionName, $optionValue, false);
		}

		if (false === $success) {
			return false;
		}

		$this->logger->info("Successfully updated blog option: ($siteId,$optionName,$optionValue)");


		return $optionValue;
	}

	/**
	 * Check if the profile for the given $blog is the default profile.
	 *
	 * @param int $siteId
	 *
	 * @return bool
	 */
	public function isDefaultProfileUsed($siteId)
	{
		$profileId = $this->find($siteId, self::PROFILE_ID);
		$defaultProfileId = $this->defaultProfileRepository->findProfileId();

		return (false === $profileId && (-1 != $defaultProfileId && false !== $defaultProfileId));
	}

	/**
	 * Get id of the associated profile of this blog $blogId.
	 *
	 * @param int $siteId
	 *
	 * @return null|string
	 */
	public function findProfileId($siteId)
	{
		$profileId = $this->find($siteId, self::PROFILE_ID);

		if (false === $profileId) {
			$profileId = $this->defaultProfileRepository->findProfileId();
	}

		return $profileId;
	}

	/**
	 * Set id of the associated profile of this blog $blogId.
	 *
	 * @param int    $siteId
	 * @param string $profileId
	 *
	 * @return string return stored profile id
	 */
	public function updateProfileId($siteId, $profileId)
	{
		return $this->persist($siteId, self::PROFILE_ID, $profileId);
	}

	/**
	 * Delete the associated of the profile with a blog $siteId.
	 *
	 * @param int    $siteId
	 * @param string $profileId
	 *
	 * @return string return stored profile id
	 */
	public function deleteProfileId($siteId, $profileId)
	{
		return $this->delete($siteId, self::PROFILE_ID);
	}


	/**
	 * Delete an option value.
	 *
	 * @param int	$siteId
	 * @param string $optionName
	 *
	 * @return bool
	 */
	protected function delete($siteId, $optionName)
	{
		$optionName = $this->getOptionName($optionName);

		if (is_multisite()) {
			return delete_blog_option($siteId, $optionName);
		}

		return delete_option($optionName);
	}

	/**
	 * This method returns all sites for that are associated with the given profile.
	 *
	 * @param int $profileId
	 *
	 * @return array
	 */
	public function findProfileAssociations($profileId)
	{
		$sites = $this->getSites();
		$r = array();

		foreach ($sites as $site) {
			$blogId = $site['blog_id'];
			$optionValue = $this->find($blogId, self::PROFILE_ID);

			if ($profileId === $optionValue) {
				$r[] = $site;
			}
		}

		return $r;
	}

	/**
	 * This method should not be called by the outside.
	 * Delete all blog associations for a specific profile.
	 * This method cleans up the blog options database after profile deletion.
	 *
	 * @param int $profileId
	 *
	 * @return bool
	 */
	public function deleteProfileAssociations($profileId)
	{
		$sites = $this->findProfileAssociations($profileId);

		foreach ($sites as $site) {
			$blogId = $site['blog_id'];
				$this->delete($blogId, self::PROFILE_ID);
			}
		}

	/**
	 * Get all sites singe site safe.
	 *
	 * @return array
	 */
	public function getSites()
	{
		if (is_multisite()) {
			return wp_get_sites();
		}

		return array(
			array(
				'blog_id' => 0,
			),
		);
	}
}
