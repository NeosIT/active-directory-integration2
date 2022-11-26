<?php

namespace Dreitier\Nadi\User;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\WordPress\Multisite\Configuration\Service;

/**
 * Helper class for displaying name and email from the userAttributeValues. Also creates unique email addresses
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Helper
{
	/**
	 * @var Service
	 */
	private $multisiteConfigurationService;

	/** @var Logger */
	private $logger;

	/**
	 * @param Service $configuration
	 */
	public function __construct(Service $multisiteConfigurationService)
	{
		$this->multisiteConfigurationService = $multisiteConfigurationService;

		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Return the enriched $userData for WordPress, using the values from the Active Directory.
	 *
	 * @param User $user
	 *
	 * @return array
	 */
	public function getEnrichedUserData(User $user)
	{
		$ldapAttributes = $user->getLdapAttributes()->getFiltered();

		$userData = array(
			'ID' => $user->getId(),
			'first_name' => $ldapAttributes['givenname'],
			'last_name' => $ldapAttributes['sn'],
		);

		$autoUpdateDescription = $this->multisiteConfigurationService->getOptionValue(Options::AUTO_UPDATE_DESCRIPTION);

		if ($autoUpdateDescription) {
			$userData['description'] = $ldapAttributes['description'];
		}

		$displayName = $this->getDisplayName($user->getCredentials()->getSAMAccountName(), $ldapAttributes);

		if ($displayName) {
			$userData['display_name'] = $displayName;
		}

		return $userData;
	}

	/**
	 * Get the right account suffix from the Active Directory values ($userAttributeValues)
	 *
	 * @param array $ldapAttributes
	 *
	 * @return string
	 */
	public function getAccountSuffix($ldapAttributes)
	{
		// get UPN suffix
		$userPrincipalName = $ldapAttributes['userprincipalname'];

		// ensure that we received a result from adLDAP
		if (is_array($userPrincipalName)) {
			$userPrincipalName = $userPrincipalName[0];
		}

		$parts = explode('@', $userPrincipalName);

		if (isset($parts[1])) {
			return '@' . $parts[1];
		}

		return '';
	}

	/**
	 * Get the password for a new user using the {@see Helper}.
	 *
	 * @param string $password
	 * @param boolean $syncToWordPress
	 *
	 * @return string
	 */
	public function getPassword($password, $syncToWordPress)
	{
		$randomGeneratePassword = $this->isRandomGeneratePassword($syncToWordPress);

		return $this->getRandomPassword($randomGeneratePassword, $password);
	}

	/**
	 * Check if a random password should be generated.
	 *
	 * @param boolean $syncToWordPress
	 *
	 * @return bool
	 */
	protected function isRandomGeneratePassword($syncToWordPress)
	{
		$noRandomPassword = $this->multisiteConfigurationService->getOptionValue(Options::NO_RANDOM_PASSWORD);

		return (!$noRandomPassword || $syncToWordPress);
	}

	/**
	 * Generate either a new password or return the given $defaultPassword.
	 *
	 * @param boolean $generateRandomPassword
	 * @param string $defaultPassword
	 *
	 * @return string
	 */
	protected function getRandomPassword($generateRandomPassword, $defaultPassword)
	{
		if ($generateRandomPassword) {
			$this->logger->debug('Setting random password.');

			return wp_generate_password(64, true, true);
		}

		$this->logger->debug('Setting local password to the used for this login.');

		return $defaultPassword;
	}

	/**
	 * Get e-mail address from the attributes array
	 *
	 * @param string $username
	 * @param array $ldapAttributes
	 *
	 * @return string
	 */
	public function getEmailAddress($username, $ldapAttributes)
	{
		// if mail is not empty, then return it
		if (!empty($ldapAttributes['mail'])) {
			return $ldapAttributes['mail'];
		}

		// if a defaultEmailDomain is set, then return the username with the defaultEmailDomain as suffix
		$defaultEmailDomain = $this->multisiteConfigurationService->getOptionValue(Options::DEFAULT_EMAIL_DOMAIN);

		if ($defaultEmailDomain) {
			return $username . '@' . $defaultEmailDomain;
		}

		// if $username contains a suffix/domain, then use the username as email (because ad-user + @ + ad-domain = e-mail-address
		if (strpos($username, '@') !== false) {
			return $username;
		}

		//empty string as fall back
		return '';
	}

	/**
	 * Get the display name from the userAttributeValues array
	 *
	 * @param string $username
	 * @param array $ldapAttributes
	 *
	 * @return string
	 */
	public function getDisplayName($username, $ldapAttributes)
	{
		$namePattern = $this->multisiteConfigurationService->getOptionValue(Options::NAME_PATTERN);

		// if name pattern is empty or 'samaccountName', then do nothing
		if (!$namePattern || $namePattern === 'samaccountname') {
			return $username;
		}

		// process $userInfo for generating the wanted displayName
		$displayName = '';

		if ($namePattern === 'givenname sn') {
			if (isset($ldapAttributes['givenname']) && isset($ldapAttributes['sn'])) {
				$displayName = $ldapAttributes['givenname'] . ' ' . $ldapAttributes['sn'];
			}
		} else {
			if (isset($ldapAttributes[$namePattern])) {
				$displayName = $ldapAttributes[$namePattern];
			}
		}

		// return the unchanged username if displayName is empty
		if (!$displayName) {
			$displayName = $username;
		}

		// return display name
		return $displayName;
	}

	/**
	 * Based upon the provided $email a unique email address is created which exists only once in the WordPress installation.
	 *
	 * @param string $email
	 *
	 * @return string
	 */
	public function createUniqueEmailAddress($email)
	{
		//check if $email has got a '@'
		$parts = explode('@', $email);
		if (sizeof($parts) !== 2) {
			return $email;
		}

		//split into local and domain part
		$localPart = $parts[0];
		$domainPart = $parts[1];

		//change the $localPart until the new email-address $email does not exists (in the WordPress user table)
		for ($i = 0; email_exists($email); $i++) {
			$email = $localPart . $i . '@' . $domainPart;
		}

		//return new email address
		return $email;
	}
}