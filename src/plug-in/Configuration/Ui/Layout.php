<?php

namespace Dreitier\Nadi\Configuration\Ui;

use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Nadi\Configuration\Options;

/**
 * Layout contains the structure of the setting pages.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Layout
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
						Options::PROFILE_NAME,
						Options::SUPPORT_LICENSE_KEY,
						Options::IS_ACTIVE,
					),
					self::DESCRIPTION => array(
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
						Options::SHOW_MENU_TEST_AUTHENTICATION,
						Options::SHOW_MENU_SYNC_TO_AD,
						Options::SHOW_MENU_SYNC_TO_WORDPRESS
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
						Options::DOMAIN_CONTROLLERS,
						Options::PORT,
						Options::ENCRYPTION,
						Options::ALLOW_SELF_SIGNED,
						Options::NETWORK_TIMEOUT,
						Options::BASE_DN
					),
				),
				__('Verify Credentials', 'next-active-directory-integration') => array(
					self::DESCRIPTION => __(
						'Connect your WordPress site or profile to a domain.',
						'next-active-directory-integration'
					),
					self::OPTIONS => array(
						Options::VERIFICATION_USERNAME,
						Options::VERIFICATION_PASSWORD,
						Options::DOMAIN_SID,
						Options::NETBIOS_NAME
					)
				),
				__('Forest configuration', 'next-active-directory-integration') => array(
					self::DESCRIPTION => __(
						'This is only relevant if you are using NADI inside an AD forest. You need the premium extension <a href="https://active-directory-wp.com/premium-extension/active-directory-forest/" target="__blank">Active Directory Forest</a> to have any effect for the forest configuration.',
						'next-active-directory-integration'
					),
					self::OPTIONS => array(
						Options::ADDITIONAL_DOMAIN_SIDS,
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
						Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION,
						Options::ACCOUNT_SUFFIX,
						Options::ALLOW_PROXYADDRESS_LOGIN,
						Options::USE_SAMACCOUNTNAME_FOR_NEW_USERS,
						Options::AUTO_UPDATE_USER,
						Options::AUTO_UPDATE_DESCRIPTION,
						Options::DEFAULT_EMAIL_DOMAIN,
						Options::DUPLICATE_EMAIL_PREVENTION,
						Options::PREVENT_EMAIL_CHANGE,
						Options::NAME_PATTERN,
						Options::SHOW_USER_STATUS,
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
						Options::NO_RANDOM_PASSWORD,
						Options::ENABLE_PASSWORD_CHANGE,
						Options::FALLBACK_TO_LOCAL_PASSWORD,
						Options::AUTO_UPDATE_PASSWORD,
						Options::ENABLE_LOST_PASSWORD_RECOVERY,
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
						Options::AUTHORIZE_BY_GROUP,
						Options::AUTHORIZATION_GROUP,
						Options::ROLE_EQUIVALENT_GROUPS,
						Options::CLEAN_EXISTING_ROLES,
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
						Options::SSO_ENABLED,
						Options::SSO_USER,
						Options::SSO_PASSWORD,
						Options::SSO_ENVIRONMENT_VARIABLE,
						Options::SSO_DISABLE_FOR_XMLRPC,
						Options::KERBEROS_REALM_MAPPINGS,
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
						Options::ENABLE_SMARTCARD_USER_LOGIN,
						Options::CUSTOM_LOGIN_PAGE_ENABLED,
						Options::CUSTOM_LOGIN_PAGE_URI,
						Options::ALLOW_XMLRPC_LOGIN
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
						sprintf(__('The following WordPress attributes are reserved by NADI and cannot be used: <em>%s</em>', 'next-active-directory-integration'), implode(', ', Repository::getDefaultAttributeMetaKeys())),
						__(
							'<span class="adi-pe-message"><b>Premium Extensions: </b>BuddyPress Simple Attributes, BuddyPress Profile Photo, Profile Pictures and User Photo integration <a href="https://active-directory-wp.com/premium-extension/">are available</a>.</span>',
							'next-active-directory-integration'
						),
					),
					// Group elements in group
					self::OPTIONS => array(
						Options::ADDITIONAL_USER_ATTRIBUTES,
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
						Options::SYNC_TO_AD_ENABLED,
						Options::SYNC_TO_AD_USE_GLOBAL_USER,
						Options::SYNC_TO_AD_GLOBAL_USER,
						Options::SYNC_TO_AD_GLOBAL_PASSWORD,
						Options::SYNC_TO_AD_AUTHCODE,
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
						Options::SYNC_TO_WORDPRESS_ENABLED,
						Options::SYNC_TO_WORDPRESS_SECURITY_GROUPS,
						Options::SYNC_TO_WORDPRESS_USER,
						Options::SYNC_TO_WORDPRESS_PASSWORD,
						Options::SYNC_TO_WORDPRESS_DISABLE_USERS,
						Options::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS,
						Options::SYNC_TO_WORDPRESS_AUTHCODE,
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
						Options::LOGGER_ENABLE_LOGGING,
						Options::LOGGER_CUSTOM_PATH,
					),
				),
			),
		);
	}
}