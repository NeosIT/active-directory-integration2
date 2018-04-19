<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Synchronization_WordPress')) {
	return;
}

/**
 * NextADInt_Adi_Synchronization_WordPress sync the Active Directory users and their attribute values with WordPress.
 *
 * NextADInt_Adi_Synchronization_WordPress get all users from the Active Directory and WordPress. Then each user will be updated
 * or created with the attribute values supplied by the Active Directory
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Synchronization_WordPress extends NextADInt_Adi_Synchronization_Abstract
{
	// userAccountControl Flags
	const UF_ACCOUNT_DISABLE = 2; // 0010
	const UF_NORMAL_ACCOUNT = 512; // 0010 0000 0000
	const UF_INTERDOMAIN_TRUST_ACCOUNT = 2048; // 1000 0000 0000
	const UF_WORKSTATION_TRUST_ACCOUNT = 4096; // 0001 0000 0000 0000
	const UF_SERVER_TRUST_ACCOUNT = 8192; // 0010 0000 0000 0000
	const UF_MNS_LOGON_ACCOUNT = 131072; // 0010 0000 0000 0000 0000
	const UF_SMARTCARD_REQUIRED = 262144; // 0100 0000 0000 0000 0000
	const UF_PARTIAL_SECRETS_ACCOUNT = 67108864; // 0100 0000 0000 0000 0000 0000 0000

	// = UF_INTERDOMAIN_TRUST_ACCOUNT + UF_WORKSTATION_TRUST_ACCOUNT + UF_SERVER_TRUST_ACCOUNT + UF_MNS_LOGON_ACCOUNT + UF_PARTIAL_SECRETS_ACCOUNT
	// This flags should never be set for a normal user account.
	const NO_UF_NORMAL_ACCOUNT = 67254272; // 0100 0000 0010 0011 1000 0000 0000

	/* @var NextADInt_Adi_User_Manager */
	private $userManager;

	/* @var NextADInt_Adi_Role_Manager */
	private $roleManager;

	/* @var NextADInt_Adi_User_Helper */
	private $userHelper;

	/* @var Logger $logger */
	private $logger;

	/* @var int */
	private $ldapRequestTimeCounter;

	/* @var int */
	private $wordpressDbTimeCounter;

	/* @var boolean */
	private $loggingEnabled;

	/* @var string */
	private $customPath;

	/**
	 * @param NextADInt_Adi_User_Manager                $userManager
	 * @param NextADInt_Adi_User_Helper                 $userHelper
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Ldap_Connection                 $connection
	 * @param NextADInt_Ldap_Attribute_Service          $attributeService
	 * @param NextADInt_Adi_Role_Manager                $roleManager
	 */
	public function __construct(NextADInt_Adi_User_Manager $userManager,
								NextADInt_Adi_User_Helper $userHelper,
								NextADInt_Multisite_Configuration_Service $configuration,
								NextADInt_Ldap_Connection $connection,
								NextADInt_Ldap_Attribute_Service $attributeService,
								NextADInt_Adi_Role_Manager $roleManager
	) {
		parent::__construct($configuration, $connection, $attributeService);

		$this->userManager = $userManager;
		$this->userHelper = $userHelper;
		$this->roleManager = $roleManager;

		$this->loggingEnabled = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::LOGGER_ENABLE_LOGGING);
		$this->customPath = $this->configuration->getOptionValue((NextADInt_Adi_Configuration_Options::LOGGER_CUSTOM_PATH));

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Get all users from certain Active Directory groups and import them as WordPress user into the WordPress database.
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function synchronize()
	{
		if (!$this->prepareForSync()) {
			return false;
		}

		$startTime = time();
		$this->logger->debug('START: findSynchronizableUsers(): ' . $startTime);
		$users = $this->findSynchronizableUsers();
		$totalTimeNeeded = time() - $startTime;
		$this->logger->debug('END: findSynchronizableUsers(): Duration:  ' . $totalTimeNeeded . ' seconds');

		// ADI-145: provide API
		$users = apply_filters(NEXT_AD_INT_PREFIX . 'sync_ad2wp_filter_synchronizable_users', $users);

		if (is_array($users) && !empty($users)) {
			$this->logNumberOfUsers($users);

			$addedUsers = 0;
			$updatedUsers = 0;
			$failedSync = 0;

			foreach ($users as $guid => $sAMAccountName) {
				$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials($sAMAccountName);
				$status = -1;

				try {
					$status = $this->synchronizeUser($credentials, $guid);
				} catch (Exception $ex) {
					$this->logger->error('Failed to synchronize ' . $credentials . ". " . $ex->getMessage());
				}

				switch ($status) {
					case 0:
						$addedUsers++;
						break;
					case 1:
						$updatedUsers++;
						break;
					default:
						$failedSync++;
				}
			}


			$this->finishSynchronization($addedUsers, $updatedUsers, $failedSync);

			return true;
		}

		$this->logger->error("No possible users for Sync to Wordpress were found.");

		return false;
	}

	/**
	 *
	 * @return bool
	 */
	protected function prepareForSync()
	{

		$enabled = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED);
		if (!$enabled) {
			$this->logger->info('Sync to WordPress is disabled.');

			return false;
		}

		$this->logger->info('Start of Sync to WordPress');
		$this->startTimer();

		$username = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER);
		$password = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD);

		if (empty($username) && empty($password)) {
			$this->logger->error('Sync to WordPress service account user or password not set.');
			return false;
		}

		if (!$this->connectToAdLdap($username, $password)) {
			return false;
		}

		if (!$this->isUsernameInDomain($username)) {
			return false;
		}

		$this->increaseExecutionTime();

		return true;
	}

	/**
	 * Combines all GUIDs from WordPress and from Active Directory
	 *
	 * @return array|hashmap key is Active Directory objectGUID, value is WordPress username
	 */
	protected function findSynchronizableUsers()
	{
		$groups = trim(
			$this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_SECURITY_GROUPS)
		);

		// find security group membership
		$activeDirectoryUsers = $this->connection->findAllMembersOfGroups($groups);
		$convertedActiveDirectoryUsers = $this->convertActiveDirectoryUsers($activeDirectoryUsers);

		$this->logger->info("After removing duplicate users security/primary groups contain '" . sizeof($convertedActiveDirectoryUsers) . "' in total users");

		// find already existing local WordPress users with Active Directory membership
		$wordPressUsers = $this->findActiveDirectoryUsernames();

		$this->logger->info("Local WordPress instance contains " . sizeof($wordPressUsers) . " users which are connected to their Active Directory acounts");

		$r = array_merge($wordPressUsers, $convertedActiveDirectoryUsers);
		$this->logger->info("After merging Active Directory/users and WordPress users " . sizeof($r) . " users have to be synchronized");

		return $r;
	}

	/**
	 * Convert the given array into our necessary format.
	 *
	 * @param $adUsers
	 *
	 * @return array|hashmap key is Active Directory objectGUID, value is username
	 */
	protected function convertActiveDirectoryUsers($adUsers)
	{
		$result = array();

		foreach ($adUsers as $adUser) {
			$attributes = $this->attributeService->findLdapAttributesOfUsername($adUser);
			$guid = $attributes->getFilteredValue(NextADInt_Adi_User_Persistence_Repository::META_KEY_OBJECT_GUID);

			$result[NextADInt_Core_Util_StringUtil::toLowerCase($guid)] = $adUser;
		}

		return $result;
	}

	/**
	 * Log number of users.
	 *
	 * @param int $users
	 */
	protected function logNumberOfUsers($users)
	{
		$elapsedTime = $this->getElapsedTime();
		$numberOfUsers = count($users);
		$this->logger->info("Number of users to import/update: $numberOfUsers ($elapsedTime seconds)");
	}

	/**
	 * Returns the value of the key "useraccountcontrol"
	 *
	 * @param array $attributes
	 *
	 * @return int 0 if parameter is empty, null or anything else
	 */
	public function userAccountControl($attributes)
	{
		$key = "useraccountcontrol";

		if (!$attributes || !isset($attributes[$key]) || !is_array($attributes[$key])) {
			return 0;
		}

		$uac = $attributes[$key][0];

		return $uac;
	}

	/**
	 * Is the account a normal account ?
	 * Checking for flags that should not be set for a normal user account ( http://www.selfadsi.org/ads-attributes/user-userAccountControl.htm )
	 *
	 * @param int $uac
	 *
	 * @return bool
	 */
	public function isNormalAccount($uac)
	{

		// ADI-517 Improved logging for UAC Binary Flag check to make it more transparent for the user and improve debugging.
		switch ($uac) {
			case (($uac & self::UF_INTERDOMAIN_TRUST_ACCOUNT) === self::UF_INTERDOMAIN_TRUST_ACCOUNT):
				$this->logger->warn("INTERDOMAIN_TRUST_ACCOUNT flag detected in userAccountControl ( $uac ). Account will not be synchronized.");
				return false;
			case (($uac & self::UF_WORKSTATION_TRUST_ACCOUNT) === self::UF_WORKSTATION_TRUST_ACCOUNT):
				$this->logger->warn("WORKSTATION_TRUST_ACCOUNT flag detected in userAccountControl ( $uac ). Account will not be synchronized.");
				return false;
			case (($uac & self::UF_SERVER_TRUST_ACCOUNT) === self::UF_SERVER_TRUST_ACCOUNT):
				$this->logger->warn("SERVER_TRUST_ACCOUNT flag detected in userAccountControl ( $uac ). Account will not be synchronized.");
				return false;
			case (($uac & self::UF_MNS_LOGON_ACCOUNT) === self::UF_MNS_LOGON_ACCOUNT):
				$this->logger->warn("MSN_LOGON_ACCOUNT flag detected in userAccountControl ( $uac ). Account will not be synchronized.");
				return false;
			case (($uac & self::UF_PARTIAL_SECRETS_ACCOUNT) === self::UF_PARTIAL_SECRETS_ACCOUNT):
				$this->logger->warn("PARTIAL_SECRETS_ACCOUNT flag detected in userAccountControl ( $uac ). Account will not be synchronized.");
				return false;
		}

		if (($uac & self::UF_NORMAL_ACCOUNT) === self::UF_NORMAL_ACCOUNT) {
			return true;
		}

		return false;
	}

	/**
	 * Is a smart card required for the account?
	 *
	 * @param int $uac
	 *
	 * @return bool
	 */
	public function isSmartCardRequired($uac)
	{
		if (($uac & self::UF_SMARTCARD_REQUIRED) === 0) {
			return false;
		}

		$this->logger->warn("SMARTCARD_REQUIRED flag detected in userAccountControl ( $uac ).");
		return true;
	}

	/**
	 * Has the account been disabled?
	 *
	 * @param int $uac
	 *
	 * @return bool
	 */
	public function isAccountDisabled($uac)
	{
		if (($uac & self::UF_ACCOUNT_DISABLE) === self::UF_ACCOUNT_DISABLE) {
			return true;
		}

		return false;
	}

	/**
	 * If guid is null, the user does not exist in Active Directory anymore.
	 * Therefore disable user and set domain sid to "empty"
	 *
	 * @param $ldapAttributes NextADInt_Ldap_Attributes
	 * @param $credentials NextADInt_Adi_Authentication_Credentials
	 * @return int
	 */
	public function disableUserWithoutValidGuid($ldapAttributes, $credentials) {

		if (null === $ldapAttributes->getFilteredValue('objectguid')) {
			// Set domain sid to empty, to prevent non existing user from getting used for sync to wordpress
			$ldapAttributes->setDomainSid('empty');

			$adiUser = $this->userManager->createAdiUser($credentials, $ldapAttributes);
			$status = $this->createOrUpdateUser($adiUser);

			$this->userManager->disable($adiUser->getId(), 'User no longer exists in Active Directory.');

			$this->logger->warn('Removed domain sid for user ' . $credentials->getLogin());

			return $status;
		}
	}

	/**
	 * Convert an Active Directory user to a WordPress user
	 *
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @param string $guid
	 * @return bool|string
	 * @throws Exception
	 */
	public function synchronizeUser(NextADInt_Adi_Authentication_Credentials $credentials, $guid)
	{
		NextADInt_Core_Assert::notNull($credentials);

		$deactivateDisabledAccounts = $this->configuration->getOptionValue(
			NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS
		);

		$synchronizeDisabledAccounts = $this->configuration->getOptionValue(
			NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS
		);

		$startTimerLdap = time();

		// TODO reduce complexity of this method.

		// ADI-204: in contrast to the login process we use the guid to determine the LDAP attributes
		$ldapAttributes = $this->attributeService->findLdapAttributesOfUser($credentials, $guid);


		// NADIS-1: Checking if the GUID of a user is valid when user does not exist in the active directory anymore. Therefore, disable user and remove domain sid
		$this->disableUserWithoutValidGuid($ldapAttributes, $credentials);

		// ADI-223: Check if user is disabled in Active Directory
		$uac = $ldapAttributes->getFilteredValue('useraccountcontrol');
		$isUserDisabled = $this->isAccountDisabled($uac);

		// ADI-223: If user is disabled and option 'synchronizeDisabledAccounts' is false, skip the user.
		if ($isUserDisabled && !$synchronizeDisabledAccounts) {
			$this->logger->info('Skipping the import of ' . $credentials->getSAMAccountName() . ' with GUID: "'. $guid . '" , because the user is deactivated in Active Directory and "Import disabled users" is not enabled.');
			return -1;
		}

		// ADI-235: add domain SID
		$ldapAttributes->setDomainSid($this->connection->getDomainSid());

		$elapsedTimeLdap = time() - $startTimerLdap;
		$this->ldapRequestTimeCounter = $this->ldapRequestTimeCounter + $elapsedTimeLdap;

		$userPrincipalName = $ldapAttributes->getFilteredValue('userprincipalname');

		// NADIS-1: added check to prevent fatal error if userPrincipalName is empty
		if (empty($userPrincipalName)) {
			$this->logger->warn('UserPrincipalName for ' . $credentials->getLogin() . ' could not be found.');
		} else {
			$credentials->setUserPrincipalName($userPrincipalName);
		}

		$adiUser = $this->userManager->createAdiUser($credentials, $ldapAttributes);

		// check account restrictions
		if ($deactivateDisabledAccounts) {
			if (!$this->checkAccountRestrictions($adiUser)) {
				return 1;
			}
		}

		$startTimerWordPress = time();

		// ADI-145: provide API
		$adiUser = apply_filters(NEXT_AD_INT_PREFIX . 'sync_ad2wp_filter_user_before_synchronize', $adiUser, $credentials, $ldapAttributes);

		$syncStatus = $this->createOrUpdateUser($adiUser);

		// ADI-145: provide API
		do_action(NEXT_AD_INT_PREFIX . 'ad2wp_after_user_synchronize', $syncStatus, $adiUser, $credentials, $ldapAttributes);

		$elapsedTimeWordPress = time() - $startTimerWordPress;
		$this->wordpressDbTimeCounter = $this->wordpressDbTimeCounter + $elapsedTimeWordPress;

		if (-1 === $syncStatus) {
			return -1;
		}

		// if option is enabled and user is disabled in AD, disable him in WordPress
		$this->synchronizeAccountStatus($adiUser, $deactivateDisabledAccounts);

		return $syncStatus;
	}

	/**
	 * Create or update an user.
	 * Due to the different requirements for login and synchronization we cannot use a common base.
	 *
	 * @param NextADInt_Adi_User $adiUser
	 *
	 * @return int 0=created,1=updated,-1=error
	 */
	protected function createOrUpdateUser(NextADInt_Adi_User $adiUser)
	{
		NextADInt_Core_Assert::notNull($adiUser);

		if (!$adiUser->getId()) {
			$startTimer = time();
			$user = $this->userManager->create($adiUser, true);
			$this->logger->info("Creating user took: " . (time() - $startTimer) . " s");
			$status = 0;
		} else {
			$user = $this->userManager->update($adiUser, true);
			$status = 1;
		}

		if (is_wp_error($user)) {
			return -1;
		}

		return $status;
	}

	/**
	 * Check account restrictions:
	 * <ul>
	 * <li>Is the user still present in Active Directory?</li>
	 * <li>Is his account a normal account?</li>
	 * <li>Is a smart card required?</li>
	 * </ul>
	 * If one of those checks matches, the account is disabled.
	 *
	 * @param NextADInt_Adi_User $adiUser
	 *
	 * @return bool
	 */
	public function checkAccountRestrictions(NextADInt_Adi_User $adiUser)
	{
		$rawLdapAttributes = $adiUser->getLdapAttributes()->getRaw();
		$username = $adiUser->getCredentials()->getSAMAccountName();

		$isInActiveDirectory = isset($rawLdapAttributes) && (sizeof($rawLdapAttributes) > 0);
		$isInWordPress = ($adiUser->getId() > 0);
		$uac = $this->userAccountControl($rawLdapAttributes);

		if (!$isInWordPress) {
			return true;
		}

		try {
			if (!$isInActiveDirectory) {
				throw new Exception(sprintf(__('User "%s" no longer found in Active Directory.', 'next-active-directory-integration'), $username));
			}

			if (!$this->isNormalAccount($uac)) {
				throw new Exception(
					sprintf(
						__(
							'User "%s" has no normal Active Directory user account. Only user accounts can be synchronized.',
							'next-active-directory-integration'
						), $username
					)
				);
			}

			if ($this->isSmartCardRequired($uac) && !$this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::ENABLE_SMARTCARD_USER_LOGIN)) {

				//ADI-594 If user is already disabled there is no need to disable him again. This prevents -DISABLED getting attached multiple times
				if (!$this->userManager->isDisabled($adiUser->getId())) {
					throw new Exception(
						sprintf(
							__('The account of user "%s" requires a smart card for login.', 'next-active-directory-integration'),
							$username
						)
					);
				}

				return false;
			}
		} catch (Exception $e) {
			$this->logger->warn("Disable user '{$username}': " . $e->getMessage());
			$this->userManager->disable($adiUser->getId(), $e->getMessage());

			return false;
		}

		return true;
	}

	/**
	 * Synchronize the user's account status (locked/enabled).
	 * If the AD account has the status "Enabled", this status will be always synchronized to WordPress.
	 * If the AD account has the status "Locked/Disabled" this status will be only synchronized with "Sync to WordPress > Automatich deactivate users".
	 *
	 * @param NextADInt_Adi_User $adiUser
	 * @param bool     $synchronizeDisabledAccounts
	 *
	 * @return bool
	 */
	public function synchronizeAccountStatus(NextADInt_Adi_User $adiUser, $synchronizeDisabledAccounts)
	{
		$uac = $this->userAccountControl($adiUser->getLdapAttributes()->getRaw());

		if (!$this->isAccountDisabled($uac)) {
			$this->logger->info("Enabling user '{$adiUser->getUserLogin()}'.");
			$this->userManager->enable($adiUser->getId());

			return true;
		}

		$this->logger->info("The user '{$adiUser->getUserLogin()}' is disabled in Active Directory.");

		if (!$synchronizeDisabledAccounts) {
			return false;
		}

		$this->logger->warn("Disabling user '{$adiUser->getUserLogin()}'.");
		$message = sprintf(__('User "%s" is disabled in Active Directory.', 'next-active-directory-integration'), $adiUser->getUserLogin());
		$this->userManager->disable($adiUser->getId(), $message);

		return false;
	}

	/**
	 * Finish synchronization with some log messages.
	 *
	 * @param int $addedUsers   amount of added users
	 * @param int $updatedUsers amount of updated users
	 * @param int $failedSync   amount of failed syncs
	 */
	protected function finishSynchronization($addedUsers, $updatedUsers, $failedSync)
	{

		$elapsedTime = $this->getElapsedTime();

		$this->logger->info("$addedUsers users have been added to the WordPress database.");
		$this->logger->info("$updatedUsers users from the WordPress database have been updated.");
		$this->logger->info("$failedSync users could not be synchronized.");
		$this->logger->info("Ldap searches took: $this->ldapRequestTimeCounter seconds");
		$this->logger->info("WordPress DB actions took: $this->wordpressDbTimeCounter seconds");
		$this->logger->info("Duration for sync: $elapsedTime seconds");
		$this->logger->info("End of Sync to WordPress");
	}
}
