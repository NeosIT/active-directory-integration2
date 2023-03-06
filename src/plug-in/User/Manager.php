<?php

namespace Dreitier\Nadi\User;

use Dreitier\Ldap\Attribute\Attribute;
use Dreitier\Ldap\Attribute\Converter;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Ldap\Attributes;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Role\Mapping;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\ArrayUtil;
use Dreitier\Util\Assert;
use Dreitier\Util\StringUtil;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\Nadi\Authentication\Credentials;
use Dreitier\WordPress\WordPressErrorException;

/**
 * Creates and updates user. It also provides information about the users and enables/disables them.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Manager
{
	/* @var Service */
	private $multisiteConfigurationService;

	/* @var \Dreitier\Ldap\Attribute\Service */
	private $ldapAttributeService;

	/* @var Helper */
	private $userHelper;

	/* @var Repository */
	private $attributeRepository;

	/* @var \Dreitier\Nadi\Role\Manager */
	private $roleManager;

	/* @var Logger */
	private $logger;

	/** @var \Dreitier\Nadi\User\Persistence\Repository */
	private $userRepository;

	/** @var \Dreitier\Nadi\User\Meta\Persistence\Repository */
	private $metaRepository;

	/**
	 * @param Service $multisiteConfigurationService
	 * @param \Dreitier\Ldap\Attribute\Service $attributeService
	 * @param Helper $userHelper
	 * @param Repository $attributeRepository
	 * @param \Dreitier\Nadi\Role\Manager $roleManager
	 * @param \Dreitier\Nadi\User\Meta\Persistence\Repository $metaRepository
	 * @param \Dreitier\Nadi\User\Persistence\Repository $userRepository
	 */
	public function __construct(Service                                         $multisiteConfigurationService,
								\Dreitier\Ldap\Attribute\Service                $attributeService,
								Helper                                          $userHelper,
								Repository                                      $attributeRepository,
								\Dreitier\Nadi\Role\Manager                     $roleManager,
								\Dreitier\Nadi\User\Meta\Persistence\Repository $metaRepository,
								\Dreitier\Nadi\User\Persistence\Repository      $userRepository
	)
	{
		$this->multisiteConfigurationService = $multisiteConfigurationService;
		$this->ldapAttributeService = $attributeService;
		$this->userHelper = $userHelper;
		$this->attributeRepository = $attributeRepository;
		$this->roleManager = $roleManager;
		$this->metaRepository = $metaRepository;
		$this->userRepository = $userRepository;

		$this->logger = NadiLog::getInstance();
	}

	public function register()
	{
		// ADI-691: Register callback to handle creation of new email addresses
		add_filter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_create_email', array($this, 'createNewEmailForExistingAddress'), 10, 2);
	}

	/**
	 * Find the WordPress user by its internal WordPress id.
	 *
	 * @param integer $userId
	 *
	 * @return \WP_User|false
	 * @throws \Exception
	 */
	public function findById($userId)
	{
		Assert::validId($userId);

		return $this->userRepository->findById($userId);
	}

	/**
	 * Lookup the given usernames (sAMAccountName and userPrincipalName) in the WordPress database.
	 * They are looked up in the following order
	 * <ul>
	 * <li>ADI meta attribute sAMAccountName = $sAMAccountName</li>
	 * <li>WordPress user_login = $userPrincipalName</li>
	 * <li>WordPress user_login = $sAMAccountName</li>
	 * </ul>
	 *
	 * @param string $sAMAccountName not empty
	 * @param string|null $userPrincipalName not empty
	 *
	 * @return \WP_User|false
	 * @throws \Exception
	 */
	public function findByActiveDirectoryUsername($sAMAccountName, $userPrincipalName)
	{
		Assert::notEmpty($sAMAccountName, "sAMAccountName must not be empty");
		Assert::notEmpty($userPrincipalName, "userPrincipalName must not be empty");

		// the wp_user_meta.samaccountname has the highest priority: user has been already added by ADI
		$wpUser = $this->userRepository->findBySAMAccountName($sAMAccountName);

		if (!$wpUser) {
			// do a full lookup by userPrincipalName to satisfy "append suffix to new users" setting
			$wpUser = $this->userRepository->findByUsername($userPrincipalName);

			if (!$wpUser) {
				// append suffix to new users has been (probably) set to false
				$wpUser = $this->userRepository->findByUsername($sAMAccountName);
			}
		}

		if (!$wpUser) {
			$this->logger->warning(
				"Local WordPress user with wp_user_meta.samaccountname='" . $sAMAccountName . "', user_login='"
				. $userPrincipalName . "'"
				. " or user_login='" . $sAMAccountName . "'"
				. ' could not be found');
		}

		return $wpUser;
	}

	/**
	 * Check if user $userId is disabled
	 *
	 * @param integer $userId
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function isDisabled($userId)
	{
		Assert::validId($userId);

		return $this->metaRepository->isUserDisabled($userId);
	}

	/**
	 * Create a new User instance based upon the given Credentials object
	 * The role mappings and LDAP attributes of the user will be automatically populated.
	 *
	 * @param Credentials $credentials not null
	 * @param Attributes $ldapAttributes
	 *
	 * @return User
	 * @throws \Exception
	 */
	public function createAdiUser(Credentials $credentials, Attributes $ldapAttributes)
	{
		Assert::notNull($credentials, "credentials must not be null");
		Assert::notNull($ldapAttributes, "ldapAttributes must not be null");

		// ADI-428: Create role mapping based upon the user's objectGUID and not on his sAMAccountName
		$userGuid = $ldapAttributes->getFilteredValue('objectguid');
		$roleMapping = $this->roleManager->createRoleMapping($userGuid);

		// NADIS-98/ADI-688: Use objectGuid as primary attribute to identify the user
		$wpUser = $this->userRepository->findByObjectGuid($userGuid);

		// if user could not be found (= not synchronized yet to WordPress), fall back to sAMAccountName
		if (!$wpUser) {
			// NADIS-1: Changed findUserByGuid to findUserBySamAccountName to be able to detect the right user if no guid is available
			$wpUser = $this->userRepository->findBySAMAccountName($credentials->getSAMAccountName());
		}

		// if sAMAccountName is also not registered, fall back to UPN
		if (!$wpUser) {
			$wpUser = $this->findByActiveDirectoryUsername($credentials->getSAMAccountName(),
				$credentials->getUserPrincipalName());
		}

		$r = new User($credentials, $ldapAttributes);

		$r->setRoleMapping($roleMapping);

		if ($wpUser) {
			$r->setUserLogin($wpUser->user_login);
			$r->setId($wpUser->ID);
			$r->setNewUser(false);
		}

		$this->logger->debug("Created new instance of " . $r);

		return $r;
	}

	/**
	 * Create a new {@see \WP_User} and persist it.
	 *
	 * @param User $user
	 * @param bool $syncToWordPress
	 * @param bool $writeUserMeta
	 *
	 * @return \WP_User|\WP_Error WP_User if creation has been a success
	 * @throws \Exception
	 */
	public function create(User $user, $syncToWordPress = false, $writeUserMeta = true)
	{
		Assert::notNull($user, "user must not be null");

		try {
			$credentials = $user->getCredentials();

			// update the password if it should be randomly generated
			$password = $this->userHelper->getPassword($credentials->getPassword(), $syncToWordPress);
			$credentials->setPassword($password);

			$wpUserLogin = $credentials->getUserPrincipalName();

			// get the correct username and assign it to our user, so we can persist him
			if ($this->useSamAccountNameForNewUsers()) {
				$this->logger->info(
					"Using the samAccountName '" . $credentials->getSAMAccountName()
					. "' for newly created user instead of userPrincipalName."
				);

				$wpUserLogin = $credentials->getSAMAccountName();
			}

			$user->setUserLogin($wpUserLogin);

			$email = $this->userHelper->getEmailAddress($wpUserLogin, $user->getLdapAttributes()->getFiltered());
			$email = $this->handleEmailAddressOfUser(null, $email);

			// create a new user and assign the id to the user object
			$userId = $this->userRepository->create($user, $email);
			WordPressErrorException::processWordPressError($userId);
			$user->setId($userId);

			// ADI-145: provide API
			do_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_after_create', $user, $syncToWordPress, $writeUserMeta);

			// call updateUser to sync attributes but don't update the user's email address as it has been already updated before
			return $this->update($user, $syncToWordPress, $writeUserMeta, false);
		} catch (WordPressErrorException $e) {
			return $e->getWordPressError();
		}
	}

	/**
	 * Return if setting "Append suffix to new users" is enabled.
	 *
	 * @return bool
	 */
	function useSamAccountNameForNewUsers()
	{
		// Use samAccountName for new created users ?
		$useSamAccountNameForNewUsers = $this->multisiteConfigurationService->getOptionValue(Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS);

		return (bool)$useSamAccountNameForNewUsers;
	}

	/**
	 * Update user information of an existing user
	 *
	 * @param User $user
	 * @param boolean $syncToWordPress false by default
	 * @param boolean $writeUserMeta true by default
	 * @param bool $updateEmail update the user's email. true by default
	 *
	 * @return \WP_User|\WP_Error Updated WordPress user or WP_Error if updating failed
	 * @throws \Exception
	 */
	public function update(User $user, $syncToWordPress = false, $writeUserMeta = true, $updateEmail = true)
	{
		Assert::notNull($user, "user must not be null");

		try {
			// ADI-145: provide API
			do_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_before_update', $user, $syncToWordPress, $writeUserMeta);

			$credentials = $user->getCredentials();

			/* Since WP 4.3 we have to disable email on password and email change */
			$this->disableEmailNotification();

			// check if the user is existing
			$this->assertUserExisting($user);

			// update user's WordPress account like first_name, last_name, roles etc.
			$this->updateWordPressAccount($user);

			// update custom user_metadata
			if ($writeUserMeta) {
				$this->updateUserMetaDataFromActiveDirectory($user->getId(), $user->getLdapAttributes()->getFiltered());
			}

			// update the user account suffix
			$this->metaRepository->update($user->getId(),NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'account_suffix',
				'@' . $credentials->getUpnSuffix());

			// update users email; this should be only skipped if a user has been previously created.
			// Otherwise, it's generated email address will be overridden
			if ($updateEmail) {
				$email = $this->userHelper->getEmailAddress($credentials->getSAMAccountName(),
					$user->getLdapAttributes()->getFiltered());

				$this->updateEmail($user, $email);
			}

			$wpUser = $this->findById($user->getId());

			// ADI-145: provide API
			do_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_after_update', $user, $wpUser, $syncToWordPress, $writeUserMeta);

			return $wpUser;
		} catch (WordPressErrorException $e) {
			return $e->getWordPressError();
		}
	}

	/**
	 * Check if the given {@see User} has an ID. If not an exception will be thrown.
	 *
	 * @param User $user
	 *
	 * @throws WordPressErrorException
	 */
	protected function assertUserExisting(User $user)
	{
		Assert::notNull($user, "user must not be null");

		if (!$user->getId()) {
			$error = new \WP_Error('error', "WordPress User '{
				$user}' does not exist.");

			WordPressErrorException::processWordPressError($error);
		}
	}

	/**
	 * Check if the password should be updated and update it.
	 *
	 * @param User $user
	 * @throws \Exception
	 */
	public function updatePassword($user)
	{
		Assert::notNull($user, "userId must be a valid id");

		// ADI-648 Register WordPress Filter to suppress "Your password was changed" to users
		$this->disableEmailNotification();

		$userId = $user->getId();
		$password = $user->getCredentials()->getPassword();

		$this->logger->debug('Setting local password to the one used for this login.');
		$this->userRepository->updatePassword($userId, $password);
	}

	/**
	 * Disable the notification for email and password change.
	 */
	protected function disableEmailNotification()
	{
		add_filter('send_password_change_email', '__return_false');
		add_filter('send_email_change_email', '__return_false');
	}

	/**
	 * Update the user, sAMAccountName and roles with by using the {@see User::getAttributeValues()) and
	 * {@see User::getRoleMapping()).
	 *
	 * @param User $user
	 * @throws \Exception
	 */
	protected function updateWordPressAccount(User $user)
	{
		Assert::notNull($user, "user must not be null");

		$userData = $this->userHelper->getEnrichedUserData($user);

		$this->logger->debug(
			"Update " . $user . " with this values: " .
			json_encode($userData)
		);

		// update user data
		$this->userRepository->update($user, $userData);

		// update the login username of ADI
		$this->updateSAMAccountName($user->getId(), $user->getCredentials()->getSAMAccountName());

		// update roles
		$this->updateUserRoles($user->getId(), $user->getRoleMapping(), $user->isNewUser());
	}

	/**
	 * Update the sAMAccountName of given user
	 *
	 * @param $userId
	 * @param $sAMAccountName
	 * @throws \Exception
	 */
	public function updateSAMAccountName($userId, $sAMAccountName)
	{
		Assert::validId($userId, "userId must be valid id");
		Assert::notEmpty($sAMAccountName, "sAAMccountName must not be empty");

		$this->logger->info("Updating sAMAccountName of user '$userId' to '$sAMAccountName'");

		$this->userRepository->updateSAMAccountName($userId, $sAMAccountName);
	}


	/**
	 * Update the roles for the given $userId.
	 *
	 * @param integer $userId
	 * @param Mapping $roleMapping
	 * @param bool $isNewUser
	 * @throws \Exception
	 */
	public function updateUserRoles($userId, Mapping $roleMapping, $isNewUser = false)
	{
		Assert::validId($userId, "userId is not valid: $userId");
		Assert::notNull($roleMapping, "roleMapping must not be null");

		$this->logger->info("Updating user roles for $userId : " . $roleMapping);

		$wpUser = $this->userRepository->findById($userId);

		// update any role the user has
		$this->roleManager->synchronizeRoles($wpUser, $roleMapping, $isNewUser);
	}

	/**
	 * Update the user meta by the data from the Active Directory ($ldapAttributes)
	 *
	 * @param integer $userId
	 * @param array $ldapAttributes
	 * @throws \Exception
	 */
	protected function updateUserMetaDataFromActiveDirectory($userId, $ldapAttributes)
	{
		Assert::validId($userId, 'userId must be a valid id');
		Assert::notNull($ldapAttributes, "ldapAttributes must not be null");

		$attributeWhiteList = $this->attributeRepository->getWhitelistedAttributes();

		$filteredAttributes = $this->filterDisallowedAttributes($ldapAttributes, $attributeWhiteList);
		$filteredAttributes = $this->filterEmptyAttributes(
			$filteredAttributes,
			$attributeWhiteList
		);

		// iterate over all userAttributeValues
		foreach ($filteredAttributes as $name => $value) {

			if ($name === "samaccountname" || $name === "userprincipalname") {
				$value = strtolower($value);
			}

			// get type and metaKey
			/* @var $attribute Attributes */
			$attribute = ArrayUtil::get($name, $attributeWhiteList, false);

			// conversion/formatting
			$value = Converter::formatAttributeValue($attribute->getType(), $value);

			$message = "Set AD attribute '$name' (ADI " . $attribute . ") to " . StringUtil::firstChars($value);
			$this->logger->debug($message);

			$this->metaRepository->update($userId, $attribute->getMetakey(), $value);
		}
	}

	/**
	 * Filter empty or disallowed attributes from the given $attributeValues.
	 *
	 * @param array $ldapAttributes
	 * @param array $whitelist
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function filterDisallowedAttributes($ldapAttributes, $whitelist)
	{
		Assert::notNull($ldapAttributes, "ldapAttributes must not be null");
		Assert::notNull($whitelist, "whitelist must not be null");

		// workaround: $this in closures are only allowed as of PHP 5.4
		$host = &$this;

		return ArrayUtil::filter(
			function ($value, $name) use ($whitelist, $host) {
				/* @var $attribute Attribute */
				$attribute = ArrayUtil::get($name, $whitelist, false);

				if (!$attribute) {
					$message = "$name is empty. Local value left unchanged.";
					$host->getLogger()->debug($message);

					return false;
				}

				return true;
			}, $ldapAttributes, true
		);
	}

	/**
	 * Filter attributes from the given $attributeValues if their value is empty and $userMetaEmptyOverride is false.
	 *
	 * @param array $ldapAttributes
	 * @param array $whitelist
	 *
	 * @return array
	 */
	protected function filterEmptyAttributes($ldapAttributes, $whitelist)
	{
		// workaround: $this in closures are only allowed as of PHP 5.4
		$host = &$this;

		return ArrayUtil::filter(
			function ($value, $name) use ($whitelist, $host) {
				/* @var $attribute Attribute */
				$attribute = ArrayUtil::get($name, $whitelist, false);

				// conversion/formatting
				$value = Converter::formatAttributeValue($attribute->getType(), $value);
				$value = trim($value);

				if (empty($value) && !$attribute->isOverwriteWithEmpty()) {
					$message = "AD attribute '$name'' is empty. Local value '" . $attribute . "' left unchanged.";
					$host->getLogger()->debug($message);

					return false;
				}

				return true;
			}, $ldapAttributes, true
		);
	}

	/**
	 * Update email address for user $userId.
	 *
	 * @param User $user
	 * @param string $email
	 * @throws WordPressErrorException
	 */
	protected function updateEmail(User $user, $email)
	{
		// exit if $email is not an email address
		if (!is_email($email)) {
			return;
		}

		// get userdata
		$userId = $user->getId();
		$wpUser = $this->userRepository->findById($userId);

		if ($email !== ($userEmail = $this->handleEmailAddressOfUser($wpUser, $email))) {
			// current email and new email differs
			$this->userRepository->updateEmail($userId, $userEmail);
		}
	}

	/**
	 * Handle the user's email setting:
	 * <ul>
	 * <li>If the email does not exist yet, it will be used</li>
	 * <li>If <em>Duplicate Email prevention</em> is Allow/UNSAFE, the preferred email is also used. This is a hacky way and can be disabled in future WordPress releases</li>
	 * <li>If email does already exist (for a new or existing user) and <em>Duplicate Email prevention</em> is CREATE, the hook next_ad_int_user_create_email is called</li>
	 * <li>If email belongs to another user and <em>Duplicate Email prevention</em> is PREVENT, this method will throw an exception</li>
	 * </ul>
	 *
	 * @issue ADI-691
	 * @param \WP_User $wpUser |null
	 * @param $preferredEmail
	 * @return string email address to use
	 * @throws WordPressErrorException If duplicate email is set to PREVENT but email does already exist or any other state is matched
	 */
	public function handleEmailAddressOfUser($wpUser, $preferredEmail)
	{
		// Check if the given emails is already in use. If not, return the current $email.
		if (!$this->userRepository->isEmailExisting($preferredEmail)) {
			return $preferredEmail;
		}

		// ---
		// At this point, the email does already exist.
		// ---

		$ownerOfPreferredEmail = $this->userRepository->findByEmail($preferredEmail);
		// if the owner of the email it the user to change, the email update will succeed
		if ($wpUser != null && ($wpUser->ID == $ownerOfPreferredEmail->ID)) {
			return $preferredEmail;
		}

		// ---
		// The email address does already exist but its ownership is not for the current user.
		// We have to check for *Duplicate email prevention* setting.
		// ---

		// ADI-691: Introduce hook for duplicate e-mail handling
		$duplicateEmailPrevention = $this->multisiteConfigurationService->getOptionValue(
			Options::DUPLICATE_EMAIL_PREVENTION
		);

		// Check if duplicate emails are allowed. If duplicate emails are allowed set WP_IMPORTING to TRUE
		// to force WordPress to take a duplicated email.
		if (DuplicateEmailPrevention::ALLOW == $duplicateEmailPrevention) {
			if (!defined('WP_IMPORTING')) {
				define('WP_IMPORTING', true); // This is a dirty hack. See wp-includes/registration.php
			}

			return $preferredEmail;
		}

		// ---
		// Email address belongs to another user. How to handle this conflict?
		// ---

		// With PREVENT we will throw an exception.
		if (DuplicateEmailPrevention::PREVENT == $duplicateEmailPrevention) {
			$error = new \WP_Error('duplicateEmailPrevention', "Can not use email address '$preferredEmail' for user as it does already exist inside WordPress.");
			WordPressErrorException::processWordPressError($error);

			// return only required for unit tests
			return false;
		}

		// With CREATE, we wil initiate the creation of a new email address
		if (DuplicateEmailPrevention::CREATE == $duplicateEmailPrevention) {
			// ADI-691: Add hook for creating new emails
			return apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_create_email', $wpUser, $preferredEmail);
		}

		WordPressErrorException::processWordPressError(new \WP_Error('invalidDuplicateEmailPreventionState', "Unknown state how to handle email address '$preferredEmail'"));

		// return only required for unit tests
		return false;
	}

	/**
	 * This hook is called in case of <em>Duplicate email prevention</em> is set to <em>CREATE</em>.
	 * It creates a unique email if
	 * <ul>
	 * <li>the user to change ($wpUserToChange) is null (= new user)</li>
	 * <li>or the email of user to change inside the WordPress' database is not set</li>
	 * </ul>
	 *
	 * In any other case the preferred email is returned
	 *
	 * @param \WP_User|null $wpUserToChange null, if user has not been created yet
	 * @param $preferredEmail
	 * @return string
	 * @since 2.1.9
	 * @issue ADI-691
	 */
	public function createNewEmailForExistingAddress($wpUserToChange, $preferredEmail)
	{
		$r = $preferredEmail;

		if (!$wpUserToChange || !$wpUserToChange->user_email) {
			$r = $this->userHelper->createUniqueEmailAddress($preferredEmail);
			$this->logger->debug("Duplicate email address prevention: email changed from '$preferredEmail' to '$r'.");
		}

		return $r;
	}

	/**
	 * Check if the given WP_User or user ID has a connected Active Directory account to its WordPress account
	 *
	 * @param int|string|\WP_User $wpUserOrId User to check. This can be the internal ID (int), the login name (string) or the WP_User instance
	 *
	 * @return boolean true if samaccountname is set and is not empty. false if there is no sAMAccountName or the given
	 * userId is not valid.
	 */
	public function hasActiveDirectoryAccount($wpUserOrId)
	{
		$userId = $wpUserOrId;

		// accept username
		if (is_string($wpUserOrId)) {
			$wpUserOrId = $this->userRepository->findByUsername($wpUserOrId);
		}

		// accept WP_User
		if (is_object($wpUserOrId)) {
			$userId = $wpUserOrId->ID;
		}

		// fail if non-numeric user ID has been provided
		if (!is_int($userId)) {
			return false;
		}

		$result = $this->metaRepository->find($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true);

		return (!empty($result));
	}

	/**
	 * Enable the user for this plugin.
	 *
	 * @param integer $userId
	 * @throws \Exception
	 */
	public function enable($userId)
	{
		Assert::validId($userId);

		// It is very likely that the email is already restored (e.g. by the user update/creation in SyncToWordpress).
		// But if the AD has no email for the user then the old email will be restored.
		$email = $this->metaRepository->find($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_disabled_email', true);
		$wpUser = $this->userRepository->findById($userId);

		$this->metaRepository->enableUser($wpUser);
		$isRestored = false;

		// ADI-384: Changed from updateEmail with empty string to updateEmail with user_email + -DISABLED to prevent exception due WordPress email address persist validation
		if ($email && strpos($wpUser->user_email, '-DISABLED') !== false) {
			$this->logger->info(
				"Restore email of enabled user '$wpUser->user_login' ($userId). The current email '$wpUser->user_email' will be overridden."
			);
			$this->userRepository->updateEmail($userId, $email);
			$isRestored = true;
		}

		// ADI-145: provide API
		do_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_after_enable', $wpUser, $isRestored);
	}

	/**
	 * Disable the user for this plugin.
	 *
	 * @param integer $userId
	 * @param string $reason
	 */
	public function disable($userId, $reason)
	{
		$isUserAlreadyDisabled = $this->metaRepository->isUserDisabled($userId);
		$wpUser = $this->userRepository->findById($userId);

		// ADI-699: Add hook user_before_disable
		do_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_before_disable', $wpUser, $isUserAlreadyDisabled);

		if ($isUserAlreadyDisabled) {
			$this->logger->debug("User with id '{$userId}' has been already disabled");
			return;
		}

		$this->metaRepository->disableUser($wpUser, $reason);

		// Change e-mail of user to be disabled to prevent him from restoring his password.
		$suffix = '-DISABLED';
		$cleanMail = str_replace($suffix, '', $wpUser->user_email);
		$this->userRepository->updateEmail($userId, $cleanMail . $suffix);
		$this->logger->warning('Disabled user with user id ' . $userId . ' with reason: ' . $reason);

		// ADI-145: provide API
		do_action(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_after_disable', $wpUser);
	}

	/**
	 * Migrate the adi_samaccountname user meta field to next_ad_int_samaccountname.
	 * The samaccountname is only migrated if there is no next_ad_int_samaccountname field, otherwise any previous assignment of ADI would be overwritten.
	 *
	 * @return int    number of migrated users
	 */
	public function migratePreviousVersion()
	{
		$oldSamAccountNameProperty = 'adi_samaccountname';
		$newSamAccountNameProperty =NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . \Dreitier\Nadi\User\Persistence\Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME;
		$wpUsers = $this->userRepository->findByMetaKey($oldSamAccountNameProperty);

		$migrated = 0;

		foreach ($wpUsers as $wpUser) {
			$userMeta = $this->userRepository->findUserMeta($wpUser->ID);

			$hasOldSamAccountName = isset($userMeta[$oldSamAccountNameProperty]) && (sizeof($userMeta[$oldSamAccountNameProperty]) > 0);
			$hasNewSamAccountName = isset($userMeta[$newSamAccountNameProperty]) && (sizeof($userMeta[$newSamAccountNameProperty]) > 0);

			if ($hasOldSamAccountName && !$hasNewSamAccountName) {
				$sAMAccountName = $userMeta[$oldSamAccountNameProperty][0];

				$this->userRepository->updateSAMAccountName($wpUser->ID, $sAMAccountName);
				$migrated++;
			}
		}

		return $migrated;
	}

	/**
	 * Check if given user is a NADI user.
	 * This method checks if the user id is associated with a samaccountname or userprincipalname.
	 *
	 * @param $wpUser
	 * @return bool
	 */
	function isNadiUser($wpUser)
	{
		$userID = $wpUser->ID;
		$samAccountName = get_user_meta($userID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true);
		$userPrincipalName = get_user_meta($userID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'userprincipalname', true);

		if ($samAccountName || $userPrincipalName) {
			return true;
		}

		return false;
	}

	/**
	 * @return Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}
}