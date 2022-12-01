<?php

namespace Dreitier\WordPress\Multisite\Configuration\Persistence;

use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Util\ArrayUtil;
use Dreitier\Util\Encryption;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Option\Type;
use Dreitier\WordPress\Multisite\Option\Attribute;
use Dreitier\WordPress\Multisite\Option\Provider;
use Dreitier\WordPress\Multisite\Option\Sanitizer;
use Dreitier\WordPress\WordPressSiteRepository;

/**
 * finds or insert option values for a normal WordPress installation or for each sites of an
 * network WordPress installation.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class BlogConfigurationRepository implements ConfigurationRepository
{
	const PROFILE_ID = 'profile_id';
	const PREFIX = 'bo_v_';

	/* @var Sanitizer $sanitizer */
	private $sanitizer;

	/* @var Encryption $encryptionHandler */
	private $encryptionHandler;

	/* @var \Dreitier\Nadi\Vendor\Monolog\Logger $logger */
	private $logger;

	/** @var Provider $optionProvider */
	private $optionProvider;

	/** @var ProfileConfigurationRepository $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/** @var ProfileRepository $profileRepository */
	private $profileRepository;

	/** @var DefaultProfileRepository $defaultProfileRepository */
	private $defaultProfileRepository;

	private $siteToProfileCache = array();

	/**
	 * @param Sanitizer $sanitizer
	 * @param Encryption $encryptionHandler
	 * @param Provider $optionProvider
	 * @param ProfileConfigurationRepository $profileConfigurationRepository
	 * @param DefaultProfileRepository $defaultProfileRepository
	 */
	public function __construct(Sanitizer                      $sanitizer,
								Encryption                     $encryptionHandler,
								Provider                       $optionProvider,
								ProfileConfigurationRepository $profileConfigurationRepository,
								DefaultProfileRepository       $defaultProfileRepository
	)
	{
		$this->sanitizer = $sanitizer;
		$this->encryptionHandler = $encryptionHandler;
		$this->optionProvider = $optionProvider;
		$this->profileConfigurationRepository = $profileConfigurationRepository;
		$this->defaultProfileRepository = $defaultProfileRepository;

		$this->logger = NadiLog::getInstance();
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
		return NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . self::PREFIX . $optionName;
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
			$options[$optionName] = $this->findSanitizedValue($siteId, $optionName);
		}

		return $options;
	}

	/**
	 * Get the value for the option $optionName and for the blog $blogId.
	 * Moreover this method sanitize, decrypt etc. the value.
	 *
	 * @param int $siteId
	 * @param string $optionName
	 *
	 * @return null|string
	 */
	public function findSanitizedValue($siteId, $optionName)
	{
		// prevent change of associated profile
		if (self::PROFILE_ID === $optionName) {
			return null;
		}

		$optionValue = $this->findRawValue($siteId, $optionName);
		// #140: findRawValue returns NULL if the option is missing but "" (empty string) if the option is just unset.
		// checking for $optionValue == null results in TRUE if $optionValue is "" (an empty string).
		$noOptionValueInSite = $optionValue === null;
		$profileId = $this->findProfileId($siteId);

		// #124: when a profile is connected and no value has been yet inside the blog, we have to return the profile's value
		// #140: missing profiles have the value -1 and *not* NULL or 0
		if ($profileId >= 1) {
			// and either no value for this option has been defined or it's handled by the profile
			if ($noOptionValueInSite || $this->isOptionHandledByProfile($siteId, $optionName)) {
				$r = $this->profileConfigurationRepository->findSanitizedValue($profileId, $optionName);
				return $r;
			}
		}

		$optionMetadata = $this->optionProvider->get($optionName);

		if (null === $optionValue) {
			$optionValue = $this->getDefaultValue($siteId, $optionName, $optionMetadata);
		}

		$type = ArrayUtil::get(Attribute::TYPE, $optionMetadata);

		if ($type === Type::PASSWORD) {
			$optionValue = $this->encryptionHandler->decrypt($optionValue);
		}

		if (isset($optionMetadata[Attribute::SANITIZER])) {
			$params = $optionMetadata[Attribute::SANITIZER];
			$optionValue = $this->sanitizer->sanitize($optionValue, $params, $optionMetadata, false);
		}

		return $optionValue;
	}

	/**
	 * Check if the current option is handled by a profile.
	 *
	 * @param int $siteId
	 * @param string $optionName
	 *
	 * @return bool
	 */
	protected function isOptionHandledByProfile($siteId, $optionName)
	{
		$profileId = $this->findProfileId($siteId);
		$forcePermission = $this->profileConfigurationRepository->findSanitizedPermission($profileId, $optionName);

		if (Service::EDITABLE > $forcePermission) {
			return true;
		}

		return false;
	}

	/**
	 * Get the default value for $optionName. If the optionMetadata flag DEFAULT_SANITIZER_VALUE exists, then
	 * the sanitizer will create a new value from the default value. This value will be persist, requested and returned.
	 *
	 * @param int $siteId
	 * @param string $optionName
	 * @param array $option
	 *
	 * @return bool|mixed|null|string
	 */
	function getDefaultValue($siteId, $optionName, $option)
	{
		// #127: PHP 7.4 compatibility; warning if $option is not an array but null
		if (!is_array($option)) {
			$this->logger->warning("Option '" . $optionName . "' in site with ID '" . $siteId . "' has no option configuration");
			return null;
		}

		$optionValue = $option[Attribute::DEFAULT_VALUE];

		// generate with Sanitizer a new value, persist it and find it (again).
		if (ArrayUtil::get(Attribute::PERSIST_DEFAULT_VALUE, $option, false)) {
			$params = $option[Attribute::SANITIZER];
			$optionValue = $this->sanitizer->sanitize($optionValue, $params, $option, true);

			$this->persistSanitizedValue($siteId, $optionName, $optionValue);
		}

		return $optionValue;
	}

	/**
	 * This method should not be called by the outside (expect for the migration of the encrypted passwords).
	 * Read the value for option $optionName and site $siteId.
	 *
	 * @param int $siteId
	 * @param string $optionName
	 *
	 * @return string|null
	 */
	public function findRawValue($siteId, $optionName)
	{
		$name = $this->getOptionName($optionName);

		if (is_multisite()) {
			$r = get_blog_option($siteId, $name, null);
			return $r;
		}

		return get_option($name, null);
	}

	/**
	 * Save an option for the blog $blogid.
	 * Moreover this method sanitize, encrypt etc. the value.
	 *
	 * @param int $siteId
	 * @param string $optionName
	 * @param string $optionValue
	 *
	 * @return string $optionValue return the sanitized value
	 */
	public function persistSanitizedValue($siteId, $optionName, $optionValue)
	{
		if (self::PROFILE_ID === $optionName) {
			return null;
		}

		$optionMetadata = $this->optionProvider->get($optionName);

		if (isset($optionMetadata[Attribute::SANITIZER])) {
			$params = $optionMetadata[Attribute::SANITIZER];
			$optionValue = $this->sanitizer->sanitize($optionValue, $params, $optionMetadata, true);
		}

		$type = ArrayUtil::get(Attribute::TYPE, $optionMetadata);

		if (Type::PASSWORD === $type) {
			$optionValue = $this->encryptionHandler->encrypt($optionValue);
		}

		return $this->persist($siteId, $optionName, $optionValue);
	}

	/**
	 * This method should not be called by the outside.
	 * Write the value $optionValue for option $optionName and blog/site $blogId.
	 *
	 * @param int $siteId
	 * @param string $optionName
	 * @param mixed $optionValue
	 *
	 * @return String|null $optionValue or (when it fails) null
	 */
	protected function persist($siteId, $optionName, $optionValue)
	{
		$optionName = $this->getOptionName($optionName);
		$isMultisite = is_multisite();


		// Multisite
		if ($isMultisite) {
			$optionExists = $this->doesOptionExist($optionName, $siteId);

			if ($optionExists) {
				$success = $this->updateOption($optionName, $optionValue, $siteId);
			} else {
				$success = $this->createOption($optionName, $optionValue, $siteId);
			}

			// Singlesite
		} else {
			$optionExists = $this->doesOptionExist($optionName);

			if ($optionExists) {
				$success = $this->updateOption($optionName, $optionValue);
			} else {
				$success = $this->createOption($optionName, $optionValue);
			}

		}

		if (false === $success) {
			return false;
		}

		$this->logger->info("Persistance of blog option: $optionName successful.");

		return $optionValue;
	}

	/**
	 * Create new NADI option
	 *
	 * @param $optionName
	 * @param $optionValue
	 * @param integer $siteId
	 * @return bool
	 */
	protected function createOption($optionName, $optionValue, $siteId = null)
	{

		// Create Multi Site option
		if ($siteId) {
			$success = add_blog_option($siteId, $optionName, $optionValue);
		} // Create Single Site option
		else {
			$success = add_option($optionName, $optionValue, false);
		}

		if (false === $success) {
			$this->logger->warning("Failed creating blog option: ($siteId,$optionName,$optionValue)");
			return false;
		}

		$this->logger->info("Successfully created blog option: ($siteId,$optionName,$optionValue)");

		return $success;
	}

	/**
	 * Update existing NADI option
	 *
	 * @param $optionName
	 * @param $optionValue
	 * @param integer $siteId
	 * @return bool
	 */
	protected function updateOption($optionName, $optionValue, $siteId = null)
	{

		// Update Multi Site option
		if ($siteId) {
			$success = update_blog_option($siteId, $optionName, $optionValue);
		} // Update Single Site option
		else {
			$success = update_option($optionName, $optionValue, false);
		}

		// TODO add check before update if $old_value equals $new_value to determine if update failed or skipped ( because $old_value == $new_value )
		if (false === $success) {
			return false;
		}

		$this->logger->info("Successfully updated blog option: ($siteId,$optionName,$optionValue)");

		return $success;
	}

	/**
	 * Method to determine if the option we are trying to persist exists in the database to prevent WordPress update problem if the value to persist equals false
	 *
	 * @param $optionName
	 * @param integer $siteId
	 * @return bool
	 */
	protected function doesOptionExist($optionName, $siteId = null)
	{

		if ($siteId) {
			$optionExists = get_blog_option($siteId, $optionName);
		} else {
			$optionExists = get_option($optionName);
		}

		if ($optionExists !== false) {
			return true;
		}

		return false;
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
		$profileId = $this->findRawValue($siteId, self::PROFILE_ID);
		$defaultProfileId = $this->defaultProfileRepository->findProfileId();

		return (false === $profileId && (-1 != $defaultProfileId && false !== $defaultProfileId));
	}

	/**
	 * Get id of the associated profile of this site's $siteId.
	 * To make it more performant, the linked profile is cached.
	 *
	 * @param int $siteId
	 *
	 * @return null|string
	 */
	public function findProfileId($siteId)
	{
		$key = "K-" . $siteId;

		if (!isset($this->siteToProfileCache[$key])) {
			$profileId = $this->findRawValue($siteId, self::PROFILE_ID);

			// no profile for this site has been specified, so find the default profile
			if (!is_numeric($profileId)) {
				$profileId = $this->defaultProfileRepository->findProfileId();
			}

			$this->siteToProfileCache[$key] = (int)$profileId;
		}

		return $this->siteToProfileCache[$key];
	}

	/**
	 * Set id of the associated profile of this blog $blogId.
	 *
	 * @param int $siteId
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
	 * @param int $siteId
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
	 * @param int $siteId
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
			$optionValue = $this->findRawValue($blogId, self::PROFILE_ID);

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
			return WordPressSiteRepository::getSites();
		}

		return array(
			array(
				'blog_id' => 0,
			),
		);
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
		return false;
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
		return false;
	}

}
