<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_User_LoginSucceededService')) {
	return;
}

/**
 * This service contains callbacks which are executed <strong>after</strong> a user has been authenticated authorized.
 *
 * @author  Stefan Fiedler <sfi@neos-it.de>
 * @since 2.1.9
 * @access
 */
class NextADInt_Adi_User_LoginSucceededService
{
	/** @var NextADInt_Adi_LoginState */
	private $loginState = null;

	/** @var NextADInt_Ldap_Attribute_Service $attributeService */
	private $attributeService;

	/** @var Logger $logger */
	private $logger;

	/** @var NextADInt_Adi_User_Manager $userManager */
	private $userManager;

	/** @var NextADInt_Ldap_Connection $ldapConnection */
	private $ldapConnection;

	/** @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	/**
	 * NextADInt_Adi_User_LoginSucceededService constructor.
	 *
	 * @param NextADInt_Adi_LoginState $loginState
	 * @param NextADInt_Ldap_Attribute_Service $attributeService
	 * @param NextADInt_Adi_User_Manager $userManager
	 * @param NextADInt_Ldap_Connection $ldapConnection
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 */
	public function __construct(
		NextADInt_Adi_LoginState $loginState,
		NextADInt_Ldap_Attribute_Service $attributeService,
		NextADInt_Adi_User_Manager $userManager = null,
		NextADInt_Ldap_Connection $ldapConnection,
		NextADInt_Multisite_Configuration_Service $configuration
	) {
		$this->loginState = $loginState;
		$this->attributeService = $attributeService;
		$this->userManager = $userManager;
		$this->ldapConnection = $ldapConnection;
		$this->configuration = $configuration;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	public function register()
	{
		// after authentication AND authorization of NADI has succeeded, we have to create or update the WordPress user; this is the last 'authenticate' hook in the whole chain
		add_filter('authenticate', array($this, 'updateOrCreateAfterSuccessfulLogin'), 19, 3);

		// login_succeeded is the callback to signal the current user is authenticated and authorized
		// TODO: Dokumentation (API und Workflow anpassen!)
		// this filter returns a WP_User or WP_Error object
		add_filter(NEXT_AD_INT_PREFIX . 'login_succeeded', array($this, 'updateOrCreateUser'), 10, 1);

		// custom filters
		add_filter(NEXT_AD_INT_PREFIX . 'auth_before_create_or_update_user', array($this, 'beforeCreateOrUpdateUser'),
			10, 2);
		add_filter(NEXT_AD_INT_PREFIX . 'auth_after_create_or_update_user', array($this, 'afterCreateOrUpdateUser'), 10,
			3);
	}

	public function updateOrCreateAfterSuccessfulLogin($authenticatedCredentials, $username, $password = null)
	{
		return apply_filters(NEXT_AD_INT_PREFIX . 'login_succeeded', $authenticatedCredentials);
	}

	/**
	 * This method updates or creates an user depending on the parameters.
	 * It internally delegates to createUser or updateUser.
	 *
	 * @param NextADInt_Adi_Authentication_Credentials|WP_Error $wpUser
	 *
	 * @return false|int|WP_Error
	 * @throws Exception
	 */
	public function updateOrCreateUser(
		$wpUser
	) {
		if ( ! $this->loginState->isAuthenticated() || $this->loginState->isAuthorized() === false) {
			return false;
		}

		if (!($wpUser instanceof NextADInt_Adi_Authentication_Credentials)) {
			return $wpUser;
		}

		$authenticatedCredentials = $wpUser;

		// ADI-204: during login we have to use the authenticated user principal name
		$ldapAttributes = $this->attributeService->findLdapAttributesOfUser($authenticatedCredentials, null);

		// ADI-395: wrong base DN leads to exception during Test Authentication
		// If the base DN is wrong then no LDAP attributes can be loaded and getRaw() is false
		if (false === $ldapAttributes->getRaw()) {
			$this->logger->error("Unable to create / update the user due to missing ldap attributes.");


			return false;
		}

		// update the real sAMAccountName of the credentials. This could be totally different from the userPrincipalName user for login
		$authenticatedCredentials->setSAMAccountName($ldapAttributes->getFilteredValue('samaccountname'));

		/**
		 * This filter can be used in order to implement custom checks validating the ldapAttributes and credentials of
		 * the user currently trying to authenticate against your Active Directory.
		 *
		 * By default this filter returns true | boolean
		 *
		 */
		$preCreateStatus = apply_filters(NEXT_AD_INT_PREFIX . 'auth_before_create_or_update_user',
			$authenticatedCredentials, $ldapAttributes);

		if ( ! $preCreateStatus) {
			$this->logger->debug('The preCreateStatus returned false. The user will not be created or updated. If this behavior is not intended, please verify your custom logic using the "auth_before_create_or_update_user" filter works properly.');

			return false;
		}

		$adiUser = $this->userManager->createAdiUser($authenticatedCredentials, $ldapAttributes);

		// ADI-309: domain SID gets not synchronized
		$domainSid = $this->ldapConnection->getDomainSid();
		$adiUser->getLdapAttributes()->setDomainSid($domainSid);

		if ($adiUser->getId()) {
			$wpUser = $this->updateUser($adiUser);
		} else {
			$wpUser = $this->createUser($adiUser);
		}

		if (is_wp_error($wpUser)) {
			$this->logger->error("Unable to update or create '" . $adiUser . "': " . $wpUser->get_error_message());

			return $wpUser;
		}

		$authenticatedCredentials->setWordPressUserId($wpUser->ID);

		/**
		 * This filter can be used in order to implement custom checks validating the credentials, ldapAttributes and $wpUser of
		 * the user currently trying to authenticate against your Active Directory. You can intercept the authentication process
		 * by returning false.
		 *
		 * By default the $wpUser | WP_USER is returned.
		 */
		return apply_filters(NEXT_AD_INT_PREFIX . 'auth_after_create_or_update_user', $authenticatedCredentials,
			$ldapAttributes, $wpUser);
	}

	/**
	 * If "Auto Create User" is enabled, the user is created. If "Auto Create User" is disabled, it returns a WP_Error
	 *
	 *
	 * @param NextADInt_Adi_User $user
	 *
	 * @return false|int|WP_Error false if creation is only simulated; int if user has been created by underlying repository; WP_Error if autoCreateUser is disabled.
	 */
	public function createUser(NextADInt_Adi_User $user)
	{
		$this->logger->debug("Checking preconditions for creating new user " . $user);
		$autoCreateUser = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::AUTO_CREATE_USER);

		// ADI-117: The behavior changed with 2.0.x and has been agreed with CST on 2016-03-02.
		// In 1.0.x users were created even if auoCreateUser was false but they had a role equivalent group.
		// With 2.0.x the user is only created if the option "Auto Create User" is enabled.
		if ( ! $autoCreateUser) {
			$error = 'This user exists in Active Directory, but not in the local WordPress instance. The option "Auto Create User" is __not__ enabled but should be.';
			$this->logger->error($error);

			return new WP_Error(
				'invalid_username', __(
					$error,
					'next-active-directory-integration'
				)
			);
		}

		// if $this->userManager is null, then do not create the user
		if ( ! $this->userManager) {
			$this->logger->warn(
				"User '{$user->getUsername()}' will not be created because the user login is only simulated."
			);

			return false;
		}

		// create user and return WP_User
		return $this->userManager->create($user);
	}

    /**
     * If "Auto Update User" is enabled, the user's profile data is updated. In any case if a $userRole is present, it is synchronized with the backend.
     *
     * @param NextADInt_Adi_User $user
     *
     * @return false|WP_User false if creation is only simulated; int if user has been updated.
     * @throws Exception
     */
	function updateUser(NextADInt_Adi_User $user)
	{
		$this->logger->debug("Checking preconditions for updating existing user " . $user);

		$autoUpdateUser     = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::AUTO_UPDATE_USER);
		$autoUpdatePassword = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::AUTO_UPDATE_PASSWORD);

		// ADI-474: Update the password if the respective option is enabled
		if ($autoUpdatePassword) {
			$this->userManager->updatePassword($user);
		}
		// ADI-116: The behavior changed with 2.0.x and has been agreed with CST on 2016-03-02.
		// In 1.0.x users were only updated if the options "Auto Create User" AND "Auto Update User" had been enabled.
		// With 2.0.x the option "Auto Update User" is only responsible for that.
		if ($autoUpdateUser) {
			// updateWordPressAccount already delegates to role update and updating of sAMAccountName
			return $this->userManager->update($user);
		}

		// in any case the sAMAccountName has to be updated
		$this->userManager->updateSAMAccountName($user->getId(), $user->getCredentials()->getSAMAccountName());

		// if autoUpdateUser is disabled we still have to update his role
		$this->userManager->updateUserRoles($user->getId(), $user->getRoleMapping());

		// get WP_User from NextADInt_Adi_User
		return $this->userManager->findById($user->getId());
	}


	/**
	 * Previously authenticated and authorized users are checked for an explicitly disabled account
	 *
	 * @param WP_User|WP_Error|NextADInt_Adi_Authentication_Credentials $wpUser
	 *
	 * @return WP_User|WP_Error
	 * @throws Exception
	 */
	public function checkUserEnabled($wpUser, $password = null)
	{
		if (!($wpUser instanceof WP_user)) {
			return $wpUser;
		}

		$userId = $wpUser->ID;

		if ($userId) {
			if ($this->userManager->isDisabled($userId)) {
				$reason = get_user_meta($userId, NEXT_AD_INT_PREFIX . 'user_disabled_reason', true);
				$this->logger->debug("User is disabled. Reason: $reason");

				remove_filter('authenticate', 'wp_authenticate_username_password', 20, 3);
				remove_filter('authenticate', 'wp_authenticate_email_password', 20, 3);

				return new WP_Error('user_disabled', __('<strong>ERROR</strong>: The user has been disabled'));
			}
		}

		return $wpUser;
	}

	/**
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @param NextADInt_Ldap_Attributes $ldapAttributes
	 *
	 * @return boolean
	 */
	public function beforeCreateOrUpdateUser($credentials, $ldapAttributes)
	{
		$this->logger->info("Hook beforeCreateOrUpdateUser executed");

		return true;
	}

	/**
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @param NextADInt_Adi_User $adiUser
	 * @param WP_User $wpUser
	 *
	 * @return boolean|WP_User
	 */
	public function afterCreateOrUpdateUser($credentials, $adiUser, $wpUser)
	{
		$this->logger->info("Hook afterCreateOrUpdateUser executed, wpUser: '" . is_object($wpUser) . "'");

		return $wpUser;
	}
}