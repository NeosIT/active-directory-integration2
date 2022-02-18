<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Configuration_Ui_Layout')) {
	return;
}

/**
 * NextADInt_Adi_Configuration_Ui_Layout contains the structure of the setting pages.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_Configuration_Ui_Layout
{
	const DESCRIPTION = 'description';
	const OPTIONS = 'options';
	const ANGULAR_CONTROLLER = 'angular_controller';
	const MULTISITE_ONLY = 'multisite_only';

	private static $structure = null;

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/**
	 * Get the structure of the setting page
	 *
	 * @return array|null
	 */
	public static function get()
	{
		if (null === self::$structure) {
			self::$structure = self::create();
		}

		return self::$structure;
	}

	/**
	 * Generate the structure of the setting pages.
	 *
	 * @return array
	 */
	private static function create()
	{
		return array(
			__('Profile', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'GeneralController',
				self::MULTISITE_ONLY => false,
				__('Profile Options', 'next-active-directory-integration') => array(
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::PROFILE_NAME,
						NextADInt_Adi_Configuration_Options::SUPPORT_LICENSE_KEY,
						NextADInt_Adi_Configuration_Options::IS_ACTIVE,
					),
					self::DESCRIPTION => array(
						__(
							'<span class="adi-important-message"><b>IMPORTANT NOTICE: END OF SUPPORT FOR PHP < 7.4 </b></span><br><span>We hereby inform you that as of <b>2021-12-09</b> NADI will no longer support PHP version <b>< 7.4</b> due to security support being dropped for older version as you can see in the <a href="http://php.net/supported-versions.php" target="_blank">official PHP documentation</a>. For security reasons and in order to use NADI in 2022 we hereby politely encourage you to migrate your environments to at least <b>PHP 7.4</b> until then.</span><br>',
							'next-active-directory-integration'
						),
						__(
							'On this page you can configure whether NADI should be enabled for a specific profile or not.',
							'next-active-directory-integration'
						),
					),
                ),
                __('Menu', 'next-active-directory-integration') => array(
                    self::DESCRIPTION => __(
                        'It is also possible to only disable certain NADI features.',
                        'next-active-directory-integration'
                    ),
                    self::OPTIONS => array(
                        NextADInt_Adi_Configuration_Options::SHOW_MENU_TEST_AUTHENTICATION,
                        NextADInt_Adi_Configuration_Options::SHOW_MENU_SYNC_TO_AD,
                        NextADInt_Adi_Configuration_Options::SHOW_MENU_SYNC_TO_WORDPRESS
                    )
                ),
			),
			// Environment tab
			__('Environment', 'next-active-directory-integration') => array(
				// Group Name
				self::ANGULAR_CONTROLLER => 'EnvironmentController',
				self::MULTISITE_ONLY => false,
				__('Active Directory Environment', 'next-active-directory-integration') => array(
					self::DESCRIPTION => __(
						'On this page you can configure the connection details for your Active Directory. If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/Environment.html">documentation</a>.',
						'next-active-directory-integration'
					),
					// Option elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS,
						NextADInt_Adi_Configuration_Options::PORT,
						NextADInt_Adi_Configuration_Options::ENCRYPTION,
						NextADInt_Adi_Configuration_Options::ALLOW_SELF_SIGNED,
						NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT,
						NextADInt_Adi_Configuration_Options::BASE_DN
					),
				),
				__('Verify Credentials', 'next-active-directory-integration') => array(
					self::DESCRIPTION => __(
						'Connect your WordPress site or profile to a domain.',
						'next-active-directory-integration'
					),
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::VERIFICATION_USERNAME,
						NextADInt_Adi_Configuration_Options::VERIFICATION_PASSWORD,
						NextADInt_Adi_Configuration_Options::DOMAIN_SID,
						NextADInt_Adi_Configuration_Options::NETBIOS_NAME
					)
				),
				__('Forest configuration', 'next-active-directory-integration') => array(
					self::DESCRIPTION => __(
						'This is only relevant if you are using NADI inside an AD forest. You need the premium extension <a href="https://active-directory-wp.com/premium-extension/active-directory-forest/" target="__blank">Active Directory Forest</a> to have any effect for the forest configuration.',
						'next-active-directory-integration'
					),
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::ADDITIONAL_DOMAIN_SIDS,
					)
				)
			),
            // User tab
			__('User', 'next-active-directory-integration') => array(
				// Group Name
				self::ANGULAR_CONTROLLER => 'UserController',
				self::MULTISITE_ONLY => false,
				__('User Settings', 'next-active-directory-integration') => array(
					self::DESCRIPTION => __(
						'On this page you can configure how users should be created, updated and displayed. You can also prevent specific users from authenticating against the Active Directory. If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/User.html">documentation</a>.',
						'next-active-directory-integration'
					),
					// Option elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION,
						NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX,
						NextADInt_Adi_Configuration_Options::ALLOW_PROXYADDRESS_LOGIN,
						NextADInt_Adi_Configuration_Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS,
						NextADInt_Adi_Configuration_Options::AUTO_UPDATE_USER,
						NextADInt_Adi_Configuration_Options::AUTO_UPDATE_DESCRIPTION,
						NextADInt_Adi_Configuration_Options::DEFAULT_EMAIL_DOMAIN,
						NextADInt_Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION,
						NextADInt_Adi_Configuration_Options::PREVENT_EMAIL_CHANGE,
						NextADInt_Adi_Configuration_Options::NAME_PATTERN,
						NextADInt_Adi_Configuration_Options::SHOW_USER_STATUS,
					),
				),
			),
			// Password tab
			__('Password', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'PasswordController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Password', 'next-active-directory-integration') => array(
					self::DESCRIPTION => __(
						'The password configuration page allows you to configure if users should be able to change their password, how failed authentications should be handled etc. . If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/Password.html">documentation</a>.',
						'next-active-directory-integration'
					),
					// Option elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::NO_RANDOM_PASSWORD,
						NextADInt_Adi_Configuration_Options::ENABLE_PASSWORD_CHANGE,
						NextADInt_Adi_Configuration_Options::FALLBACK_TO_LOCAL_PASSWORD,
						NextADInt_Adi_Configuration_Options::AUTO_UPDATE_PASSWORD,
						NextADInt_Adi_Configuration_Options::ENABLE_LOST_PASSWORD_RECOVERY,
					),
				),
			),
			// Permissions tab
			__('Permissions', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'PermissionController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Permissions', 'next-active-directory-integration') => array(
					self::DESCRIPTION => array(
						__(
							'On this page you can configure whether only specific Active Directory Security groups should be granted access to WordPress. You can also define if certain Active Directory security groups should have WordPress role permissions by default. If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/Permissions.html">documentation</a>.',
							'next-active-directory-integration'
						),
						__(
							'<span class="adi-pe-message"><b>Premium Extensions: </b>Custom Role Management <a href="https://active-directory-wp.com/premium-extension/">available</a>.</span>',
							'next-active-directory-integration'
						),),
					// Option elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::AUTHORIZE_BY_GROUP,
						NextADInt_Adi_Configuration_Options::AUTHORIZATION_GROUP,
						NextADInt_Adi_Configuration_Options::ROLE_EQUIVALENT_GROUPS,
						NextADInt_Adi_Configuration_Options::CLEAN_EXISTING_ROLES,
					),
				),
			),
			// SSO tab
			__('SSO', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'SsoController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Single Sign On', 'next-active-directory-integration') => array(
					self::DESCRIPTION => array(__(
						'Single Sign On Configuration. If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/Security.html">documentation</a>.',
						'next-active-directory-integration'
					),
						__(
							'<span class="adi-pe-message"><b>Premium Extensions: </b>Active Directory Forest, SingleSignOn for BuddyPress, WooCommerce und Ultimate Member <a href="https://active-directory-wp.com/premium-extension/">available</a>.</span>',
							'next-active-directory-integration'
						)),
					// Option elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::SSO_ENABLED,
						NextADInt_Adi_Configuration_Options::SSO_USER,
						NextADInt_Adi_Configuration_Options::SSO_PASSWORD,
						NextADInt_Adi_Configuration_Options::SSO_ENVIRONMENT_VARIABLE,
						NextADInt_Adi_Configuration_Options::SSO_DISABLE_FOR_XMLRPC,
						NextADInt_Adi_Configuration_Options::KERBEROS_REALM_MAPPINGS,
					),
				),
			),
            // Security tab
			__('Security', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'SecurityController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Login', 'next-active-directory-integration') => array(
					self::DESCRIPTION => array(__(
						'Login Configuration',
						'next-active-directory-integration'
					)),
					// Option elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::ENABLE_SMARTCARD_USER_LOGIN,
						NextADInt_Adi_Configuration_Options::CUSTOM_LOGIN_PAGE_ENABLED,
						NextADInt_Adi_Configuration_Options::CUSTOM_LOGIN_PAGE_URI
					),
				),
				// Group name
				__('Brute-Force-Protection', 'next-active-directory-integration') => array(
					// Group description
					self::DESCRIPTION => array(
						__(
							'For security reasons you can use the following options to prevent brute force attacks on your user accounts.',
							'next-active-directory-integration'
						),
						// editing translations with loco-translate will not detect concatenated strings
						__(
							'<div class="update-message notice inline notice-warning notice-alt"> We highly recommend you to use <a href="https://wordpress.org/plugins/better-wp-security/">iThemes Security</a> to secure your WordPress environment. <br> NADI Brute Force Protection will not receive updates anymore after the NADI v2.0.13 release and we are planning on removing it completely later this year. </div>',
							'next-active-directory-integration'
						)),
					// Group elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS,
						NextADInt_Adi_Configuration_Options::BLOCK_TIME,
						NextADInt_Adi_Configuration_Options::USER_NOTIFICATION,
						NextADInt_Adi_Configuration_Options::ADMIN_NOTIFICATION,
						NextADInt_Adi_Configuration_Options::ADMIN_EMAIL,
						NextADInt_Adi_Configuration_Options::FROM_EMAIL,
						NextADInt_Adi_Configuration_Options::ALLOW_XMLRPC_LOGIN
					),
				),
			),
			// Attributes tab
			__('Attributes', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'AttributesController',
				self::MULTISITE_ONLY => false,
				// Group description
				__('Attributes', 'next-active-directory-integration') => array(
					// Group description
					self::DESCRIPTION => array(
						__(
							'User attributes from the Active Directory are stored as user meta data. These attributes can then be used in your themes and they can be shown on the profile page of your users. ',
							'next-active-directory-integration'
						),
						__(
							'The attributes are only stored in the WordPress database if you activate <em>Automatic User Creation</em> and are only updated if you activate <em>Automatic User Update</em> on tab <em>User</em>. If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/Attributes.html">documentation</a>.',
							'next-active-directory-integration'
						),
						'',
						sprintf(__('The following WordPress attributes are reserved by NADI and cannot be used: <em>%s</em>', 'next-active-directory-integration'), implode(', ', NextADInt_Ldap_Attribute_Repository::getDefaultAttributeMetaKeys())),
						__(
							'<span class="adi-pe-message"><b>Premium Extensions: </b>BuddyPress Simple Attributes, BuddyPress Profile Photo, Profile Pictures and User Photo integration <a href="https://active-directory-wp.com/premium-extension/">are available</a>.</span>',
							'next-active-directory-integration'
						),
					),
					// Group elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES,
					),
				),
			),
			// Sync to AD tab
			__('Sync to AD', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'SyncToAdController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Sync To Active Directory', 'next-active-directory-integration') => array(
					self::DESCRIPTION => __(
						'Synchronize WordPress profiles back to Active Directory. If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/Sync_to_AD.html">documentation</a>.',
						'next-active-directory-integration'
					),
					// Group elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::SYNC_TO_AD_ENABLED,
						NextADInt_Adi_Configuration_Options::SYNC_TO_AD_USE_GLOBAL_USER,
						NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER,
						NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD,
						NextADInt_Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE,
					),
				),
			),
			// Sync to WordPress tab
			__('Sync to WordPress', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'SyncToWordpressController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Sync To WordPress', 'next-active-directory-integration') => array(
					// Group description
					self::DESCRIPTION => __(
						'You can import/update the users from your Active Directory, for example by using a cron job. If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/Sync_to_WordPress.html">documentation</a>.',
						'next-active-directory-integration'
					),
					// Group elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED,
						NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_SECURITY_GROUPS,
						NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER,
						NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD,
						NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS,
						NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS,
						NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE,
					),
				),
			),
			// Logging tab
			__('Logging', 'next-active-directory-integration') => array(
				self::ANGULAR_CONTROLLER => 'LoggingController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Logging', 'next-active-directory-integration') => array(
					// Group description
					self::DESCRIPTION => __(
						'On this tab you can configure the NADI event logger. If you require further information about this page please take a look at our <a target="_blank" href="https://active-directory-wp.com/docs/Configuration/Logger.html">documentation</a>.',
						'next-active-directory-integration'
					),
					// Group elements in group
					self::OPTIONS => array(
						NextADInt_Adi_Configuration_Options::LOGGER_ENABLE_LOGGING,
						NextADInt_Adi_Configuration_Options::LOGGER_CUSTOM_PATH,
					),
				),
			),
		);
	}
}