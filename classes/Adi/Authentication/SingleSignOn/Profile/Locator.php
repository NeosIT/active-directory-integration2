<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_SingleSignOn_Profile_Locator')) {
	return;
}

/**
 * NextADInt_Adi_Authentication_SingleSignOn_Profile_Locator finds the correct NADI profile for a given principal.
 *
 * @author Christopher Klein <me[at]schakko[dot]de>
 *
 * @access
 * @since 2.0.0
 */
class NextADInt_Adi_Authentication_SingleSignOn_Profile_Locator
{
	/** @var Logger */
	private $logger;


	/* @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	/**
	 * NextADInt_Adi_Authentication_SingleSignOn_Profile_Locator constructor.
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 */
	public function __construct(
		NextADInt_Multisite_Configuration_Service $configuration
	)
	{
		$this->configuration = $configuration;
		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * @return NextADInt_Multisite_Configuration_Service
	 */
	public function getConfiguration()
	{
		return $this->configuration;
	}

	/**
	 * Based upon the credential parameters set, it tries either a lookup by using the NETBIOS name or its suffix (either UPN suffix or Kerberos realm)
	 *
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @return NextADInt_Adi_Authentication_SingleSignOn_Profile_Match|null
	 */
	public function locate(NextADInt_Adi_Authentication_Credentials $credentials)
	{
		$profileMatch = null;

		if (!empty($credentials->getNetbiosName())) {
			$profileMatch = $this->locateByNetbios($credentials);
		} else {
			$profileMatch = $this->locateBySuffix($credentials);
		}

		$this->logger->debug("Profile match: " . $profileMatch);
		return $profileMatch;
	}

	/**
	 * Locate a matching profile if UPN suffix has been set. At first, the Kerberos realm is tried, then a fallback to the userPrincipalName is done.
	 *
	 * @param $credentials
	 * @return NextADInt_Adi_Authentication_SingleSignOn_Profile_Match|null
	 */
	private function locateBySuffix($credentials)
	{
		$type = NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::UPN_SUFFIX;
		$profile = null;

		if (!empty($credentials->getUpnSuffix())) {
			$this->logger->debug("Looking up SSO profile by Kerberos realm for credential '" . $credentials . "'");
			$profile = $this->findBestConfigurationMatchForProfile(
				NextADInt_Adi_Configuration_Options::KERBEROS_REALM_MAPPINGS,
				$credentials->getUpnSuffix(),
				false /* we require (!) an exact Kerberos realm */);

			if ($profile) {
				$type = NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::KERBEROS_REALM;
			}
		}

		// if no Kerberos profile is matching, try to find an account_suffix profile
		if (!$profile) {
			$this->logger->debug("Looking up SSO profile by UPN suffix fallback for credential '" . $credentials . "'");
			$profile = $this->findBestConfigurationMatchForProfile(
				NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX,
				// normalizeSuffix is required; we can't change the already existing profile options in each WordPress instances
				$this->normalizeSuffix($credentials->getUpnSuffix()));
		}

		if ($profile) {
			return NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::create($profile, $type);
		}

		return NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::noMatch();
	}

	/**
	 * Locate a profile by the credential's NETBIOS name
	 *
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @return NextADInt_Adi_Authentication_SingleSignOn_Profile_Match|null
	 */
	private function locateByNetbios(NextADInt_Adi_Authentication_Credentials $credentials)
	{
		$this->logger->debug("Looking up SSO profile by NETBIOS name for credential '" . $credentials->getNetbiosName() . "'");
		$profileMatch = $this->findBestConfigurationMatchForProfile(NextADInt_Adi_Configuration_Options::NETBIOS_NAME, $credentials->getNetbiosName());

		if (!empty($profileMatch)) {
			return NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::create($profileMatch, NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::NETBIOS);
		}

		return NextADInt_Adi_Authentication_SingleSignOn_Profile_Match::noMatch();
	}

	/**
	 * Find the profile with SSO enabled and its configuration option contains the provided value.
	 *
	 * @param string $option name of profile option
	 * @param string $value value of given option to match
	 * @param bool $doSsoOnlyFallback if true: if no matching profile is found, it will return one of the SSO-enabled profiles
	 *
	 * @return mixed
	 */
	public function findBestConfigurationMatchForProfile($option, $value, $doSsoOnlyFallback = true)
	{
		$ssoEnabledProfiles = $this->findSsoEnabledProfiles();

		// find all profiles with given option value
		$profiles = $this->getProfilesWithOptionValue($option, $value, $ssoEnabledProfiles);

		// if multiple profiles were found, log a warning and return the first result
		if (sizeof($profiles) > 1) {
			$this->logger->warn('Multiple profiles with the same option "' . $option . '" and enabled SSO were found.');
		}

		if ($doSsoOnlyFallback) {
			// if no profile given suffix and SSO enabled was found, search for profiles with SSO enabled and no suffixes
			if (sizeof($profiles) == 0) {
				$profiles = $this->getProfilesWithoutOptionValue($option, $ssoEnabledProfiles);
			}
		}

		// return the first found profile or null
		return NextADInt_Core_Util_ArrayUtil::findFirstOrDefault($profiles, null);
	}

	/**
	 * Get all profiles with the given option value.
	 *
	 * @param string $option name of configuration option to search for
	 * @param string|integer $requiredValue
	 * @param array $profiles array of profiles to check into
	 *
	 * @return array
	 */
	protected function getProfilesWithOptionValue($option, $requiredValue, $profiles)
	{
		return NextADInt_Core_Util_ArrayUtil::filter(function ($profile) use ($option, $requiredValue) {
			$values = array();

			if (isset($profile[$option])) {
				$useValues = NextADInt_Core_Util_StringUtil::split($profile[$option], ';');

				foreach ($useValues as $useValue) {
					// support mapping like "key=value"
					$keyMapped = NextADInt_Core_Util_StringUtil::split($useValue, '=');

					if (sizeof($keyMapped) == 2) {
						$useValue = $keyMapped[0];
					}

					$useValue = trim($useValue);

					if(strlen($useValue) == 0) {
						// skip empty lines
						continue;
					}

					$values[] = $useValue;
				}
			}

			return (NextADInt_Core_Util_ArrayUtil::containsIgnoreCase($requiredValue, $values));
		}, $profiles);
	}

	/**
	 * Get all profiles which have no option specified.
	 *
	 * @param $option name of configuration option
	 * @param $profiles
	 *
	 * @return array
	 */
	protected function getProfilesWithoutOptionValue($option, $profiles)
	{
		return NextADInt_Core_Util_ArrayUtil::filter(function ($profile) use ($option) {
			$value = '';

			if (isset($profile[$option])) {
				$value = $profile[$option];
			}

			return NextADInt_Core_Util_StringUtil::isEmptyOrWhitespace($value);
		}, $profiles);
	}

	/**
	 * Return the suffix with an '@' prefix.
	 *
	 * @param $suffix
	 *
	 * @return string
	 */
	protected function normalizeSuffix($suffix)
	{
		if (!empty($suffix) && '@' !== $suffix[0]) {
			$suffix = '@' . $suffix;
		}

		return $suffix;
	}

	/**
	 * Find all profiles with the necessary roles.
	 *
	 * @return array
	 */
	protected function findSsoEnabledProfiles()
	{
		// find all profiles with the given options and add them to our $profiles array
		$profiles = $this->getConfiguration()->findAllProfiles(array(
			NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX,
			NextADInt_Adi_Configuration_Options::SSO_ENABLED,
			NextADInt_Adi_Configuration_Options::SSO_USER,
			NextADInt_Adi_Configuration_Options::SSO_PASSWORD,
			NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS,
			NextADInt_Adi_Configuration_Options::PORT,
			NextADInt_Adi_Configuration_Options::ENCRYPTION,
			NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT,
			NextADInt_Adi_Configuration_Options::BASE_DN,
			NextADInt_Adi_Configuration_Options::SSO_USER,
			NextADInt_Adi_Configuration_Options::SSO_PASSWORD,
			NextADInt_Adi_Configuration_Options::NETBIOS_NAME
		));

		// get the current configuration and add it as first option
		// this is required in a single site environment, as the profile will not be listed above
		array_unshift($profiles, $this->getConfiguration()->getAllOptions());

		// filter all profiles and get profiles with SSO enabled
		$profiles = NextADInt_Core_Util_ArrayUtil::filter(function ($profile) {
			if (!isset($profile[NextADInt_Adi_Configuration_Options::SSO_ENABLED]['option_value'])) {
				return false;
			}

			return $profile[NextADInt_Adi_Configuration_Options::SSO_ENABLED]['option_value'] === true;
		}, $profiles);

		return $this->normalizeProfiles($profiles);
	}

	/**
	 * Normalize the given profiles for further usage.
	 *
	 * @param $profiles
	 *
	 * @return array
	 */
	protected function normalizeProfiles($profiles)
	{
		// go through all found profiles and normalize the values
		return NextADInt_Core_Util_ArrayUtil::map(function ($profile) {
			// set the option_value as the real value
			return NextADInt_Core_Util_ArrayUtil::map(function ($profileOption) {
				return $profileOption['option_value'];
			}, $profile);
		}, $profiles);
	}
}