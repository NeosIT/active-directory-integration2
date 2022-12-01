<?php

namespace Dreitier\Nadi\Authentication\SingleSignOn\Profile;

use Dreitier\Nadi\Authentication\SingleSignOn\Profile\Matcher;
use Dreitier\Nadi\Authentication\Credentials;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\ArrayUtil;
use Dreitier\Util\StringUtil;
use Dreitier\WordPress\Multisite\Configuration\Service;

/**
 * Locator finds the correct NADI profile for a given principal.
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 *
 * @access
 * @since 2.0.0
 */
class Locator
{
	/** @var Logger */
	private $logger;


	/* @var Service $multisiteConfigurationService */
	private $multisiteConfigurationService;

	/**
	 * @param Service $multisiteConfigurationService
	 */
	public function __construct(
		Service $multisiteConfigurationService
	)
	{
		$this->multisiteConfigurationService = $multisiteConfigurationService;
		$this->logger = NadiLog::getInstance();
	}

	/**
	 * @return Service
	 */
	public function getMultisiteConfigurationService()
	{
		return $this->multisiteConfigurationService;
	}

	/**
	 * Based upon the credential parameters set, it tries either a lookup by using the NETBIOS name or its suffix (either UPN suffix or Kerberos realm)
	 *
	 * @param Credentials $credentials
	 * @return Matcher|null
	 */
	public function locate(Credentials $credentials)
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
	 * @return Matcher|null
	 */
	private function locateBySuffix($credentials)
	{
		$type = Matcher::UPN_SUFFIX;
		$profile = null;

		if (!empty($credentials->getUpnSuffix())) {
			$this->logger->debug("Looking up SSO profile by Kerberos realm for credential '" . $credentials . "'");
			$profile = $this->findBestConfigurationMatchForProfile(
				Options::KERBEROS_REALM_MAPPINGS,
				$credentials->getUpnSuffix(),
				false /* we require (!) an exact Kerberos realm */);

			if ($profile) {
				$type = Matcher::KERBEROS_REALM;
			}
		}

		// if no Kerberos profile is matching, try to find an account_suffix profile
		if (!$profile) {
			$this->logger->debug("Looking up SSO profile by UPN suffix fallback for credential '" . $credentials . "'");
			$profile = $this->findBestConfigurationMatchForProfile(
				Options::ACCOUNT_SUFFIX,
				// normalizeSuffix is required; we can't change the already existing profile options in each WordPress instances
				$this->normalizeSuffix($credentials->getUpnSuffix()));
		}

		if ($profile) {
			return Matcher::create($profile, $type);
		}

		return Matcher::noMatch();
	}

	/**
	 * Locate a profile by the credential's NETBIOS name
	 *
	 * @param Credentials $credentials
	 * @return Matcher|null
	 */
	private function locateByNetbios(Credentials $credentials)
	{
		$this->logger->debug("Looking up SSO profile by NETBIOS name for credential '" . $credentials->getNetbiosName() . "'");
		$profileMatch = $this->findBestConfigurationMatchForProfile(Options::NETBIOS_NAME, $credentials->getNetbiosName());

		if (!empty($profileMatch)) {
			return Matcher::create($profileMatch, Matcher::NETBIOS);
		}

		return Matcher::noMatch();
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
			$this->logger->warning('Multiple profiles with the same option "' . $option . '" and enabled SSO were found.');
		}

		if ($doSsoOnlyFallback) {
			// if no profile given suffix and SSO enabled was found, search for profiles with SSO enabled and no suffixes
			if (sizeof($profiles) == 0) {
				$profiles = $this->getProfilesWithoutOptionValue($option, $ssoEnabledProfiles);
			}
		}

		// return the first found profile or null
		return ArrayUtil::findFirstOrDefault($profiles, null);
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
		return ArrayUtil::filter(function ($profile) use ($option, $requiredValue) {
			$values = array();

			if (isset($profile[$option])) {
				$useValues = StringUtil::split($profile[$option], ';');

				foreach ($useValues as $useValue) {
					// support mapping like "key=value"
					$keyMapped = StringUtil::split($useValue, '=');

					if (sizeof($keyMapped) == 2) {
						$useValue = $keyMapped[0];
					}

					$useValue = trim($useValue);

					if (strlen($useValue) == 0) {
						// skip empty lines
						continue;
					}

					$values[] = $useValue;
				}
			}

			return (ArrayUtil::containsIgnoreCase($requiredValue, $values));
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
		return ArrayUtil::filter(function ($profile) use ($option) {
			$value = '';

			if (isset($profile[$option])) {
				$value = $profile[$option];
			}

			return StringUtil::isEmptyOrWhitespace($value);
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
		$profiles = $this->getMultisiteConfigurationService()->findAllProfiles(array(
			Options::ACCOUNT_SUFFIX,
			Options::SSO_ENABLED,
			Options::SSO_USER,
			Options::SSO_PASSWORD,
			Options::DOMAIN_CONTROLLERS,
			Options::PORT,
			Options::ENCRYPTION,
			Options::NETWORK_TIMEOUT,
			Options::BASE_DN,
			Options::SSO_USER,
			Options::SSO_PASSWORD,
			Options::NETBIOS_NAME
		));

		// get the current configuration and add it as first option
		// this is required in a single site environment, as the profile will not be listed above
		array_unshift($profiles, $this->getMultisiteConfigurationService()->getAllOptions());

		// filter all profiles and get profiles with SSO enabled
		$profiles = ArrayUtil::filter(function ($profile) {
			if (!isset($profile[Options::SSO_ENABLED]['option_value'])) {
				return false;
			}

			return $profile[Options::SSO_ENABLED]['option_value'] === true;
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
		return ArrayUtil::map(function ($profile) {
			// set the option_value as the real value
			return ArrayUtil::map(function ($profileOption) {
				return $profileOption['option_value'];
			}, $profile);
		}, $profiles);
	}
}