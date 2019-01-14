<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authorization_Service')) {
    return;
}

/**
 * NextADInt_Adi_Authorization_Service contains Authorization procedures like checking for NADI authorization groups
 *
 * @author  Christopher Klein <ckl@neos-it.de>
 * @since 2.1.8
 * @access
 */
class NextADInt_Adi_Authorization_Service
{
    /** @var Logger */
    private $logger;

    /**
     * @var NextADInt_Multisite_Configuration_Service
     */
    private $configuration;

    /**
     * @var NextADInt_Adi_User_Manager
     */
    private $userManager;

    /**
     * @var NextADInt_Adi_Role_Manager
     */
    private $roleManager;

    /**
     * @var NextADInt_Adi_LoginState
     */
    private $loginState;

    /**
     * NextADInt_Adi_Authorization_Service constructor.
     * @param NextADInt_Multisite_Configuration_Service $configuration
     * @param NextADInt_Adi_User_Manager $userManager
     * @param NextADInt_Adi_Role_Manager $roleManager
     * @param NextADInt_Adi_LoginState $loginState
     */
    public function __construct(NextADInt_Multisite_Configuration_Service $configuration,
                                NextADInt_Adi_User_Manager $userManager,
                                NextADInt_Adi_Role_Manager $roleManager,
                                NextADInt_Adi_LoginState $loginState
    )
    {
        $this->logger = NextADInt_Core_Logger::getLogger();

        $this->configuration = $configuration;
        $this->userManager = $userManager;
        $this->roleManager = $roleManager;
        $this->loginState = $loginState;
    }

    public function register()
    {
        // we register authorizeAfterAuthentication as last filter in the authentication chain so we can do post-authentication things aka authorization
        add_filter('authenticate', array($this, 'authorizeAfterAuthentication'), 1000, 3);

        // we need our own authorize filter so we can trigger authorization from SSO and connectivity test page
        add_filter('authorize', array($this, 'isUserInAuthorizationGroup'), 10, 1);
        add_filter('authorize', array($this, 'isUserEnabled'), 10, 1);
    }

    /**
     * Authorization callback will be run as last 'authenticate' filter in chain
     * @param $wpUser
     * @param $username
     * @param null $password
     * @return mixed
     */
    public function authorizeAfterAuthentication($wpUser, $username, $password = null) {
        return apply_filters('authorize', $wpUser);
    }

    /**
     * Check if authorization is required.
     * Authorization is *not* required if
     * <ul>
     * <li>a user could has not been previously authenticated</li>
     * <li>or the user i the first user of a WordPress instance: the administrator</li>
     * </ul>
     * @param $wpUser
     * @return false|int
     */
    function checkAuthorizationRequired($wpUser)
    {
        // if any error has previously occurred or we could not find any valid user, we don't have to proceed
        if (!($wpUser instanceof WP_User)) {
            return false;
        }

        // admin account is explicitly excluded from authorization
        if ($wpUser->ID == 1) {
            return false;
        }

        return $wpUser->ID;
    }

    /**
     * Check if the given user is an AD user and has a
     * @param null|WP_User|WP_Error $wpUser
     * @return WP_Error|WP_User
     * @throws Exception
     */
    public function isUserInAuthorizationGroup($wpUser)
    {
        if ($userId = $this->checkAuthorizationRequired($wpUser)) {
            $authorizeByGroup = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::AUTHORIZE_BY_GROUP);

            if (!$authorizeByGroup) {
                // authorization by group has not been enabled, so do not check further
                return $wpUser;
            }

            // user should have been synchronized before
            $userGuid = get_user_meta($userId, NEXT_AD_INT_PREFIX . 'objectguid', true);
            // user must have been authenticated at the Active Directory so we have an available AD/LDAP connection for fetching his roles
            $hasBeenAuthenticatedAtActiveDirectory = $this->loginState->isAuthenticated();

            if (!$userGuid || !$hasBeenAuthenticatedAtActiveDirectory) {
                $this->logger->warn("User has no GUID or has not been previously authenticated at the Active Directory; can't check role membership");
                // without having GUID and being authenticated we must assume that the user is a local user.
                // As a local user we can't check for AD group membership and so he is authorized.
                return $wpUser;
            }

            // create role mapping with user's GUID
            $roleMapping = $this->roleManager->createRoleMapping($userGuid);

            if (!$this->roleManager->isInAuthorizationGroup($roleMapping)) {
                $this->logger->error("User with GUID: '$userGuid' is not in an authorization group.");

                return new WP_Error('user_not_authorized', __('<strong>ERROR</strong>: You could not be authorized'));
            }

            $this->logger->info('User is in authorization group and has been authorized');
        }

        return $wpUser;
    }

    /**
     * Previously authenticated users are checked for an explicitly disabled account
     *
     * @param null|WP_User|WP_Error $wpUser
     * @return WP_Error|WP_User
     * @throws Exception
     */
    public function isUserEnabled($wpUser)
    {
        if ($userId = $this->checkAuthorizationRequired($wpUser)) {
            if ($this->userManager->isDisabled($userId)) {
                $reason = get_user_meta($userId, NEXT_AD_INT_PREFIX . 'user_disabled_reason', true);
                $this->logger->debug("User is disabled. Reason: $reason");

                return new WP_Error('user_disabled', __('<strong>ERROR</strong>: The user has been disabled'));
            }
        }

        return $wpUser;
    }
}