<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_User_Manager')) {
	return;
}

/**
 * Adi_User_Manager creates and updates user. It also provides information about the users and enables/disables them.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Adi_User_Manager
{
	/* @var Multisite_Configuration_Service */
	private $configuration;

	/* @var Ldap_Attribute_Service */
	private $attributeService;

	/* @var Adi_User_Helper */
	private $userHelper;

	/* @var Ldap_Attribute_Repository */
	private $attributeRepository;

	/* @var Adi_Role_Manager */
	private $roleManager;

	/* @var Logger */
	private $logger;

	/** @var Adi_User_Persistence_Repository */
	private $userRepository;

	/** @var Adi_User_Meta_Persistence_Repository */
	private $metaRepository;

	/**
	 * @param Multisite_Configuration_Service $configuration
	 * @param Ldap_Attribute_Service $attributeService
	 * @param Adi_User_Helper $userHelper
	 * @param Ldap_Attribute_Repository $attributeRepository
	 * @param Adi_Role_Manager $roleManager
	 * @param Adi_User_Meta_Persistence_Repository $metaRepository
	 * @param Adi_User_Persistence_Repository $userRepository
	 */
	public function __construct(Multisite_Configuration_Service $configuration,
								Ldap_Attribute_Service $attributeService,
								Adi_User_Helper $userHelper,
								Ldap_Attribute_Repository $attributeRepository,
								Adi_Role_Manager $roleManager,
								Adi_User_Meta_Persistence_Repository $metaRepository,
								Adi_User_Persistence_Repository $userRepository
	)
	{
		$this->configuration = $configuration;
		$this->attributeService = $attributeService;
		$this->userHelper = $userHelper;
		$this->attributeRepository = $attributeRepository;
		$this->roleManager = $roleManager;
		$this->metaRepository = $metaRepository;
		$this->userRepository = $userRepository;

		$this->logger = Logger::getLogger(__CLASS__);
	}

	/**
	 * Find the WordPress user by its internal WordPress id.
	 *
	 * @param integer $userId
	 *
	 * @return WP_User|false
	 */
	public function findById($userId)
	{
		Core_Assert::validId($userId);

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
	 * @return WP_User
	 */
	public function findByActiveDirectoryUsername($sAMAccountName, $userPrincipalName)
	{
		Core_Assert::notEmpty($sAMAccountName, "sAMAccountName must not be empty");
		Core_Assert::notEmpty($userPrincipalName, "userPrincipalName must not be empty");

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
			$this->logger->warn(
				"WordPress user with wp_user_meta.samaccountname='" . $sAMAccountName . "', user_login='"
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
	 */
	public function isDisabled($userId)
	{
		Core_Assert::validId($userId);

		return $this->metaRepository->isUserDisabled($userId);
	}

	/**
	 * Create a new Adi_User instance based upon the given Adi_Authentication_Credentials object
	 * The role mappings and LDAP attributes of the user will be automatically populated.
	 *
	 * @param Adi_Authentication_Credentials $credentials not null
	 * @param Ldap_Attributes $ldapAttributes
	 *
	 * @return Adi_User
	 */
	public function createAdiUser(Adi_Authentication_Credentials $credentials, Ldap_Attributes $ldapAttributes)
	{
		Core_Assert::notNull($credentials, "credentials must not be null");
		Core_Assert::notNull($ldapAttributes, "ldapAttributes must not be null");

		$guid = $ldapAttributes->getFilteredValue(Adi_User_Persistence_Repository::META_KEY_OBJECT_GUID);
		$wpUser = $this->userRepository->findByObjectGuid($guid);

		if (!$wpUser) {
			$wpUser = $this->findByActiveDirectoryUsername($credentials->getSAMAccountName(),
				$credentials->getUserPrincipalName());
		}

		$roleMapping = $this->roleManager->createRoleMapping($credentials->getSAMAccountName());

		$r = new Adi_User($credentials, $ldapAttributes);

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
	 * Create a new {@see WP_User} and persist it.
	 *
	 * @param Adi_User $user
	 * @param bool $syncToWordPress
	 * @param bool $writeUserMeta
	 *
	 * @return WP_User|WP_Error WP_User if creation has been a success
	 */
	public function create(Adi_User $user, $syncToWordPress = false, $writeUserMeta = true)
	{
		Core_Assert::notNull($user, "user must not be null");

		try {
			$credentials = $user->getCredentials();

			// update the password if it should be randomly generated
			$password = $this->userHelper->getPassword($credentials->getPassword(), $syncToWordPress);
			$credentials->setPassword($password);

			$wpUserLogin = $credentials->getSAMAccountName();

			// get the correct username and assign it to our user, so we can persist him
			if ($this->appendSuffixToNewUser()) {
				$this->logger->debug(
					"Using the userPrincipalName '" . $credentials->getUserPrincipalName()
					. " for newly created user instead of sAMAccountName."
				);

				$wpUserLogin = $credentials->getUserPrincipalName();
			}

			$user->setUserLogin($wpUserLogin);

			// do not create a user if DUPLICATE_EMAIL_PREVENTION is 'prevent' and the email address is already in use
			$email = $this->userHelper->getEmailAddress($wpUserLogin, $user->getLdapAttributes()->getFiltered());
			$this->checkDuplicateEmail($wpUserLogin, $email);

			// create a new user and assign the id to the user object
			$userId = $this->userRepository->create($user);
			Core_Util_ExceptionUtil::handleWordPressErrorAsException($userId);
			$user->setId($userId);

			// call updateUser to sync attributes
			return $this->update($user, $syncToWordPress, $writeUserMeta);
		} catch (Core_Exception_WordPressErrorException $e) {
			return $e->getWordPressError();
		}
	}

	/**
	 * Return if setting "Append suffix to new users" is enabled.
	 *
	 * @return bool
	 */
	function appendSuffixToNewUser()
	{
		// get stored value for APPEND_SUFFIX_TO_NEW_USER
		$appendSuffixToNewUser = $this->configuration->getOptionValue(Adi_Configuration_Options::APPEND_SUFFIX_TO_NEW_USERS);

		return (bool)$appendSuffixToNewUser;
	}


	/**
	 * Check if the given {@see $email} is already existing for another user. If prevention is active and the
	 * {@see $email} is already existing, an exception is thrown.
	 *
	 * @param string $username
	 * @param string $email
	 *
	 * @throws Core_Exception_WordPressErrorException
	 */
	protected function checkDuplicateEmail($username, $email)
	{
		// get the duplicate email prevention and check if the email is already existing
		$prevention = $this->configuration->getOptionValue(Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION);
		$emailAlreadyExists = $this->userRepository->isEmailExisting($email);

		if (Adi_User_DuplicateEmailPrevention::PREVENT == $prevention && $emailAlreadyExists) {
			// Duplicate emails should be prevented and the email is already existing. So we throw an exception with
			// our WP_Error
			$error = new WP_Error('duplicateEmailPrevention', "Can not create user '$username' because he uses an existing email address.");
			Core_Util_ExceptionUtil::handleWordPressErrorAsException($error);
		}
	}

	/**
	 * Update user information of an existing user
	 *
	 * @param Adi_User $user
	 * @param boolean $syncToWordPress
	 * @param boolean $writeUserMeta
	 *
	 * @return WP_User|WP_Error Updated WordPress user or WP_Error if updating failed
	 */
	public function update(Adi_User $user, $syncToWordPress = false, $writeUserMeta = true)
	{
		Core_Assert::notNull($user, "user must not be null");

		try {
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
			$this->metaRepository->update($user->getId(), ADI_PREFIX . 'account_suffix',
				'@' . $credentials->getUpnSuffix());

			// update users email
			$email = $this->userHelper->getEmailAddress($credentials->getSAMAccountName(),
				$user->getLdapAttributes()->getFiltered());
			$this->updateEmail($user, $email);

			// update users password
			$this->updatePassword($user->getId(), $credentials->getPassword(), $syncToWordPress);

			return $this->findById($user->getId());
		} catch (Core_Exception_WordPressErrorException $e) {
			return $e->getWordPressError();
		}
	}

	/**
	 * Check if the given {@see Adi_User} has an ID. If not an exception will be thrown.
	 *
	 * @param Adi_User $user
	 *
	 * @throws Core_Exception_WordPressErrorException
	 */
	protected function assertUserExisting(Adi_User $user)
	{
		Core_Assert::notNull($user, "user must not be null");

		if (!$user->getId()) {
			$error = new WP_Error("WordPress User '{
				$user}' does not exist.");

			Core_Util_ExceptionUtil::handleWordPressErrorAsException($error);
		}
	}

	/**
	 * Check if the password should be updated and update it.
	 *
	 * @param int $userId
	 * @param string $password
	 * @param boolean $syncToWordPress
	 */
	protected function updatePassword($userId, $password, $syncToWordPress)
	{
		Core_Assert::notNull($userId, "userId must be a valid id");

		// update users password
		$autoUpdatePassword = $this->configuration->getOptionValue(Adi_Configuration_Options::AUTO_UPDATE_PASSWORD);

		if ($autoUpdatePassword && !$syncToWordPress) {
			$this->logger->debug('Setting local password to the one used for this login.');
			$this->userRepository->updatePassword($userId, $password);
		}
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
	 * Update the user, sAMAccountName and roles with by using the {@see Adi_User::getAttributeValues()) and
	 * {@see Adi_User::getRoleMapping()).
	 *
	 * @param Adi_User $user
	 */
	protected function updateWordPressAccount(Adi_User $user)
	{
		Core_Assert::notNull($user, "user must not be null");

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
	 */
	public function updateSAMAccountName($userId, $sAMAccountName)
	{
		Core_Assert::validId($userId, "userId must be valid id");
		Core_Assert::notEmpty($sAMAccountName, "sAAMccountName must not be empty");

		$this->logger->info("Updating sAMAccountName of user '$userId' to '$sAMAccountName'");

		$this->userRepository->updateSAMAccountName($userId, $sAMAccountName);
	}


	/**
	 * Update the roles for the given $userId.
	 *
	 * @param integer $userId
	 * @param Adi_Role_Mapping $roleMapping
	 * @param bool $isNewUser
	 */
	public function updateUserRoles($userId, Adi_Role_Mapping $roleMapping, $isNewUser = false)
	{
		Core_Assert::validId($userId, "userId is not valid: $userId");
		Core_Assert::notNull($roleMapping, "roleMapping must not be null");

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
	 */
	protected function updateUserMetaDataFromActiveDirectory($userId, $ldapAttributes)
	{
		Core_Assert::validId($userId, 'userId must be a valid id');
		Core_Assert::notNull($ldapAttributes, "ldapAttributes must not be null");

		// should empty user metas be override
		$userMetaEmptyOverwrite = $this->configuration->getOptionValue(
			Adi_Configuration_Options::USERMETA_EMPTY_OVERWRITE
		);
		$attributeWhiteList = $this->attributeRepository->getWhitelistedAttributes();

		$filteredAttributes = $this->filterDisallowedAttributes($ldapAttributes, $attributeWhiteList);
		$filteredAttributes = $this->filterEmptyAttributes(
			$filteredAttributes,
			$attributeWhiteList,
			$userMetaEmptyOverwrite
		);

		// iterate over all userAttributeValues
		foreach ($filteredAttributes as $name => $value) {
			// get type and metaKey
			/* @var $attribute Ldap_Attribute */
			$attribute = Core_Util_ArrayUtil::get($name, $attributeWhiteList, false);

			// conversion/formatting
			$value = Ldap_Attribute_Converter::formatAttributeValue($attribute->getType(), $value);

			// set value if $value is not empty or $userMetaEmptyOverwrite is true
			$message = "Set AD attribute '$name' (ADI " . $attribute . ") to " . print_r($value, true);
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
	 */
	protected function filterDisallowedAttributes($ldapAttributes, $whitelist)
	{
		Core_Assert::notNull($ldapAttributes, "ldapAttributes must not be null");
		Core_Assert::notNull($whitelist, "whitelist must not be null");

		// workaround: $this in closures are only allowed as of PHP 5.4
		$host = &$this;

		return Core_Util_ArrayUtil::filter(
			function ($value, $name) use ($whitelist, $host) {
				/* @var $attribute Ldap_Attribute */
				$attribute = Core_Util_ArrayUtil::get($name, $whitelist, false);

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
	 * @param boolean $userMetaEmptyOverwrite
	 *
	 * @return array
	 */
	protected function filterEmptyAttributes($ldapAttributes, $whitelist, $userMetaEmptyOverwrite)
	{
		// workaround: $this in closures are only allowed as of PHP 5.4
		$host = &$this;

		return Core_Util_ArrayUtil::filter(
			function ($value, $name) use ($whitelist, $userMetaEmptyOverwrite, $host) {
				/* @var $attribute Ldap_Attribute */
				$attribute = Core_Util_ArrayUtil::get($name, $whitelist, false);

				// conversion/formatting
				$value = Ldap_Attribute_Converter::formatAttributeValue($attribute->getType(), $value);
				$value = trim($value);

				if (empty($value) && !$userMetaEmptyOverwrite) {
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
	 * @param Adi_User $user
	 * @param string $email
	 */
	protected function updateEmail(Adi_User $user, $email)
	{
		// exit if $email is not an email address
		if (!is_email($email)) {
			return;
		}

		// get userdata
		$userId = $user->getId();
		$userData = $this->userRepository->findById($userId);

		if (false !== ($userEmail = $this->getEmailForUpdate($userData, $email))) {
			$this->userRepository->updateEmail($userId, $userEmail);

			return;
		}

		$this->logger->debug(
			"Duplicate email address prevention: Existing email '$userData->user_email' left unchanged."
		);
	}

	/**
	 * Check the settings to get the correct email for the user.
	 *
	 * @param WP_User $userData
	 * @param string $email
	 *
	 * @return bool|string
	 */
	protected function getEmailForUpdate(WP_User $userData, $email)
	{
		$duplicateEmailPrevention = $this->configuration->getOptionValue(
			Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION
		);

		// Check if duplicate emails are allowed. If duplicate emails are allowed set WP_IMPORTING to TRUE
		// to force WordPress to take a duplicated email.
		if (Adi_User_DuplicateEmailPrevention::ALLOW == $duplicateEmailPrevention) {
			if (!defined('WP_IMPORTING')) {
				define('WP_IMPORTING', true); // This is a dirty hack. See wp-includes/registration.php
			}

			return $email;
		}

		// Check if the given emails is already in use. If not, return the current $email.
		if (!$this->userRepository->isEmailExisting($email)) {
			return $email;
		}

		// Check if the user has no email and an email should be created
		if (Adi_User_DuplicateEmailPrevention::CREATE == $duplicateEmailPrevention && !$userData->user_email) {
			$newEmail = $this->userHelper->createUniqueEmailAddress($email);
			$this->logger->debug("Duplicate email address prevention: email changed from '$email' to '$newEmail'.");

			return $newEmail;
		}

		return false;
	}

	/**
	 * Check if the given WP_User or user ID has a connected Active Directory account to its WordPress account
	 *
	 * @param int|string|WP_User $wpUserOrId User to check. This can be the internal ID (int), the login name (string) or the WP_User instance
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

		$result = $this->metaRepository->find($userId, ADI_PREFIX . 'samaccountname', true);

		return (!empty($result));
	}

	/**
	 * Enable the user for this plugin.
	 *
	 * @param integer $userId
	 */
	public function enable($userId)
	{
		Core_Assert::validId($userId);

		// It is very likely that the email is already restored (e.g. by the user update/creation in SyncToWordpress).
		// But if the AD has no email for the user then the old email will be restored.
		$email = $this->metaRepository->find($userId, ADI_PREFIX . 'user_disabled_email', true);
		$userData = $this->userRepository->findById($userId);

		$this->metaRepository->enableUser($userData);

		if ($email && !$userData->user_email) {
			$this->logger->info(
				"Restore email of enabled user '$userData->user_login' ($userId). The current email '$userData->user_email' will be overridden."
			);
			$this->userRepository->updateEmail($userId, $email);
		}
	}

	/**
	 * Disable the user for this plugin.
	 *
	 * @param integer $userId
	 * @param string $reason
	 */
	public function disable($userId, $reason)
	{
		$userData = $this->userRepository->findById($userId);
		$this->metaRepository->disableUser($userData, $reason);

		// delete e-mail from user
		$this->userRepository->updateEmail($userId, '');
	}

	/**
	 * Migrate the adi_samaccountname user meta field to adi2_samaccountname.
	 * The samaccountname is only migrated if there is no adi2_samaccountname field, otherwise any previous assignment of ADI would be overwritten.
	 *
	 * @return int    number of migrated users
	 */
	public function migratePreviousVersion()
	{
		$oldSamAccountNameProperty = 'adi_samaccountname';
		$newSamAccountNameProperty = ADI_PREFIX . Adi_User_Persistence_Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME;
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
	 * @return Logger
	 */
	public function getLogger()
	{
		return $this->logger;
	}
}
