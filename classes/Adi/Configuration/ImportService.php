<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Configuration_ImportService')) {
	return;
}

/**
 * NextADInt_Adi_Configuration_ImportService imports options the first ADI version and translate it to ADI 2 options.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_Configuration_ImportService
{
	const OLD_VERSION_KEY = "AD_Integration_version";

	/** @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository */
	private $blogConfigurationRepository;

	/** @var NextADInt_Multisite_Configuration_Service */
	private $configuration;

	/** @var NextADInt_Multisite_Option_Provider */
	private $optionProvider;

	/**
	 * Mapping between ADI 1.x and 2.x option names
	 * @var array
	 */
	private static $optionNameMapping = array(
		'display_name'               => 'name_pattern',
		'syncback'                   => NextADInt_Adi_Configuration_Options::SYNC_TO_AD_ENABLED,
		'syncback_use_global_user'   => NextADInt_Adi_Configuration_Options::SYNC_TO_AD_USE_GLOBAL_USER,
		'syncback_global_user'       => NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER,
		'syncback_global_pwd'        => NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD,
		'bulkimport_enabled'         => NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED,
		'bulkimport_authcode'        => NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE,
		'bulkimport_security_groups' => NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_SECURITY_GROUPS,
		'bulkimport_user'            => NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER,
		'bulkimport_pwd'             => NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD,
	);

	/**
	 * Converter constructor.
	 *
	 * @param NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository
	 * @param NextADInt_Multisite_Configuration_Service                                 $configuration
	 * @param NextADInt_Multisite_Option_Provider                                       $optionProvider
	 */
	public function __construct(NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository $blogConfigurationRepository,
		NextADInt_Multisite_Configuration_Service $configuration,
		NextADInt_Multisite_Option_Provider $optionProvider
	) {
		$this->blogConfigurationRepository = $blogConfigurationRepository;
		$this->configuration = $configuration;
		$this->optionProvider = $optionProvider;
	}

	public function register()
	{
	}

	/**
	 * Register hooks after post-activation
	 */
	public function registerPostActivation()
	{
        if (get_transient(NextADInt_Adi_Init::NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED)) {
            add_action('all_admin_notices', array($this, 'createMigrationNotices'));
            delete_transient(NextADInt_Adi_Init::NEXT_AD_INT_PLUGIN_HAS_BEEN_ENABLED);
        }
	}

	/**
	 * Create the output after activation. This method has <strong>no</strong> access to the previous run migration
	 * b/c WordPress handles the activation in two steps. The real activation is done by a previous AJAX request.
	 */
	public function createMigrationNotices()
	{
		$output = "";

		if (is_multisite()) {
			if (is_network_admin()) {
				// network activated plug-in
				$sites = NextADInt_Core_Util_Internal_WordPress::getSites();
				$sitesToUpgrade = 0;

				foreach ($sites as $site) {
					$siteId = $site['blog_id'];

					if ($this->getBlogVersion($siteId) !== false) {
						$sitesToUpgrade++;
					}
				}

				$output .= __('You are running inside a Multisite network installation. This requires you to add a new NADI profile or edit the default NADI profile.',
					'next-active-directory-integration');

				if ($sitesToUpgrade > 0) {
					$output .= sprintf(__('<strong>There are %d sites in your Multisite network which have a previous version of NADI(ADI) running. Make sure to disable all existing installations and create a new profile for all of them!</strong>',
						'next-active-directory-integration'), $sitesToUpgrade);
				}
			} else {
				// plug-in provided in network but not network-wide activated
				$output .= __('Migration of previous ADI options are not supported when running in Multisite installations. Please verify the NADI configuration',
					'next-active-directory-integration');
			}
		} else {
			// single site installation
			if ($this->getBlogVersion() !== false) {
				$output .= __('Options of a previous ADI installation have been migrated. You <strong>must</strong> re-enter the credentials of <em>Sync to WordPress/AD</em> service accounts.',
					'next-active-directory-integration');
			}
		}

		$html = "<div class='notice notice-warning'><h2>Next Active Directory Integration has been activated</h2><p>"
			. $output . "</p></div>";

		echo $html;
	}

	/**
	 * Start the import.
	 */
	public function autoImport()
	{
		if (!is_multisite()) {
			$isUpdated = $this->updateSite();

			return $isUpdated;
		}

		// multisite is not supported
		return false;
	}

	/**
	 * Check if the permission for updating the blog is set.
	 *
	 * @param null $siteId
	 *
	 * @return bool
	 */
	protected function updateSite($siteId = null)
	{
		// get version
		$version = $this->getBlogVersion($siteId);

		// version must be valid
		return $this->migratePreviousVersion($version, $siteId);
	}


	/**
	 * This method checks the version for the given site or the current blog. If the version is older than the current
	 * version, it will be updated.
	 *
	 * @param      $version
	 * @param null $siteId
	 *
	 * @return bool
	 */
	protected function migratePreviousVersion($version, $siteId = null)
	{
		if (self::isPreviousVersion($version)) {
			$this->importOptions($siteId, $version);

			return true;
		}

		return false;
	}

	/**
	 * Try resolving the version for either the given or the current site.
	 * In typical single WordPress installation the $siteId MUST be null.
	 *
	 * @param null|int $siteId
	 *
	 * @return mixed|void
	 */
	public function getBlogVersion($siteId = null)
	{
		if (null !== $siteId && is_multisite()) {
			// try resolving the value from the sitemeta table
			$version = $this->getPreviousNetworkVersion();

			// if the global value has not been found, resolve the blog option
			if (false === $version) {
				$version = $this->getPreviousSiteVersion($siteId);
			}

			return $version;
		}


		return $this->getPreviousBlogVersion();
	}

	/**
	 * Return true if the provided version is an old one
	 *
	 * @param $version
	 *
	 * @return bool true if version is prior 2.0
	 */
	public static function isPreviousVersion($version)
	{
		// the installed version is newer than the current version?
		if ($version) {
			return NextADInt_Core_Util::native()->compare($version, NEXT_AD_INT_PLUGIN_VERSION, '<');
		}

		return false;
	}

	/**
	 * Return the previous ADI version which is installed in the network and network wide activated.
	 * This is relevant for ADI >= 1.1.6 and < 2.x.
	 * @return string|false false if version could not be found
	 */
	public function getPreviousNetworkVersion()
	{
		return get_site_option(self::OLD_VERSION_KEY, false);
	}

	/**
	 * Return the previous ADI version for a specific site inside a network installation.
	 * This is relevant for ADI =< 1.1.5.
	 *
	 * @param int $siteId
	 *
	 * @return string|false false if version could not be found
	 */
	public function getPreviousSiteVersion($siteId)
	{
		$version = false;

		if (is_multisite()) {
			$version = get_blog_option($siteId, self::OLD_VERSION_KEY, false);
		}

		return $version;
	}

	/**
	 * Return the previous ADI version in a single site environment
	 * @return string|false false if version could not be found
	 */
	public function getPreviousBlogVersion()
	{
		return get_option(self::OLD_VERSION_KEY, false);
	}

	/**
	 * Iterate through all ADI options and translate them for the new version.
	 *
	 * @param int          $siteId
	 * @param string|false $previousVersion
	 */
	protected function importOptions($siteId, $previousVersion)
	{
		// get all option names
		$configuration = $this->getPreviousConfiguration($siteId, $previousVersion);

		foreach ($configuration as $index => $optionDefinition) {
			$this->blogConfigurationRepository->persistSanitizedValue($siteId, $optionDefinition['option_new'],
				$optionDefinition['value']);
		}

		$this->persistConvertedAttributeMapping($siteId, $previousVersion);
	}

	/**
	 * Persist the the converted attribute mapping
	 *
	 * @param int         $siteId
	 * @param string|null $previousVersion
	 */
	public function persistConvertedAttributeMapping($siteId, $previousVersion)
	{
		$overwriteEmpty = (boolean)$this->getOption($siteId, 'usermeta_empty_overwrite', $previousVersion);
		// option show_attributes is not required b/c attributes_to_show already implies which attributes has to be shown
		$attributesToShow = $this->getOption($siteId, 'attributes_to_show', $previousVersion);
		$customAttributes = $this->getOption($siteId, 'additional_user_attributes', $previousVersion);

		$data = $this->convertAttributeMapping($customAttributes, $attributesToShow, $overwriteEmpty);
		$configurationString = '';

		while (list($attribute, $setting) = each($data)) {
			$subsettings = array($attribute,
				$setting['type'],
				$setting['wordpress_attribute'],
				$setting['description'],
				$setting['view_in_userprofile'] ? 1 : 0,
				$setting['sync_to_ad'] ? 1 : 0,
				$setting['overwrite'] ? 1 : 0,
			);

			$configurationString .= implode(":", $subsettings) . ";";
		}

		$this->blogConfigurationRepository->persistSanitizedValue($siteId,
			NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES, $configurationString);
	}

	/**
	 * Convert 1.x attribute mapping configuration string to 2.x
	 *
	 * @param $customAttributes configuration string with attributes to load from Active Directory
	 * @param $attributesToShow configuration string with attributes to show in user profile
	 * @param $overwriteEmpty overwrite with empty values
	 *
	 * @return array
	 */
	public function convertAttributeMapping($customAttributes, $attributesToShow, $overwriteEmpty)
	{
		$r = array();

		$customAttributes = explode("\n", str_replace("\r", '', $customAttributes));
		$attributesToShow = explode("\n", str_replace("\r", '', $attributesToShow));

		// collect previous custom attributes
		foreach ($customAttributes as $line) {
			$settings = explode(":", $line);
			$adAttribute = NextADInt_Core_Util_StringUtil::toLowerCase(trim($settings[0]));
			$type = sizeof($settings) >= 2 ? $settings[1] : 'string';
			$wordpressAttribute = sizeof($settings) >= 3 ? $settings[2] : $adAttribute;

			$r[$adAttribute] = array(
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE                 => $type,
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE  => $wordpressAttribute,
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_OVERWRITE_EMPTY      => $overwriteEmpty,
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_DESCRIPTION          => '',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_SYNC_TO_AD           => false,
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE => false,
			);
		}

		// overwrite any attributes to show configuration
		foreach ($attributesToShow as $line) {
			$settings = explode(":", $line);
			$adAttribute = NextADInt_Core_Util_StringUtil::toLowerCase(trim($settings[0]));

			if (!isset($r[$adAttribute])) {
				continue;
			}

			$r[$adAttribute]['description'] = sizeof($settings) >= 2 ? $settings[1] : '';
			$r[$adAttribute]['sync_to_ad'] = sizeof($settings) >= 3 ? true : false;
			$r[$adAttribute]['view_in_userprofile'] = true;
		}

		return $r;
	}

	/**
	 * Get the previous configuration and its mapping to the new configuration
	 *
	 * @param int          $siteId
	 * @param string|false $previousVersion
	 *
	 * @return array of arrays [['option_old' => 'display_name', 'option_new' => 'name_pattern', 'value' => 'samaccountname], ['option_old' => 'dc', 'option_new' => 'dc', ...]]
	 */
	public function getPreviousConfiguration($siteId, $previousVersion)
	{
		$r = array();

		$options = $this->getMergedOptions();

		foreach ($options as $option) {
			// get old values and store them
			$oldAdiOptionValue = $this->getOption($siteId, $option, $previousVersion);
			$newOptionName = self::convertOptionName($option);

			$r[] = array(
				'option_old' => $option,
				'option_new' => $newOptionName,
				'value'      => $oldAdiOptionValue,
			);
		}

		return $r;
	}

	/**
	 * Get all option settings of ADI v2 and v1 in this order
	 * @return array [#v2# 'name_pattern', ... , #v1# 'display_name', ...]
	 */
	public function getMergedOptions()
	{
		// get all option names
		$v2Options = array_keys($this->optionProvider->getNonTransient());
		$v1Options = array_keys(self::$optionNameMapping);

		$options = array_merge($v2Options, $v1Options);

		return $options;
	}

	/**
	 * Try resolving the correct option for the given blog, name and version.
	 *
	 * @param $siteId
	 * @param $optionName
	 * @param $previousVersion
	 *
	 * @return mixed|void
	 */
	protected function getOption($siteId, $optionName, $previousVersion)
	{
		$prefix = 'AD_Integration_';
		$optionKey = $prefix . $optionName;

		// for non multi-site installations
		if (!is_multisite() || null === $siteId) {
			return get_option($optionKey);
		}


		// for old multi-site installations
		if (NextADInt_Core_Util::native()->compare($previousVersion, '1.1.5', '<=')) {
			// for 1.1.5 and older
			return get_blog_option($siteId, $optionKey);
		}

		// for new multi-site installations (version > 1.5)
		return get_site_option($optionKey);
	}

	/**
	 * Convert option name from previous 1.x to 2.x
	 *
	 * @param string $optionName
	 *
	 * @return string mixed
	 */
	public static function convertOptionName($optionName)
	{
		if (isset(self::$optionNameMapping[$optionName])) {
			return self::$optionNameMapping[$optionName];
		}

		return $optionName;
	}
}