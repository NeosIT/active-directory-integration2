<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Configuration_Ui_Layout')) {
	return;
}

/**
 * Adi_Configuration_Ui_Layout contains the structure of the setting pages.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Adi_Configuration_Ui_Layout
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
			__('Profile', ADI_I18N) => array(
				self::ANGULAR_CONTROLLER => 'GeneralController',
				self::MULTISITE_ONLY => false,
				'Profile Options' => array(
					self::OPTIONS => array(
						Adi_Configuration_Options::PROFILE_NAME,
						Adi_Configuration_Options::IS_ACTIVE,
					),
					self::DESCRIPTION => __(
						'On this page you can configure whether ADI should be enabled for a specific profile or not.',
						ADI_I18N
					),
				),
				__('Menu', ADI_I18N) => array(
					self::DESCRIPTION => __(
						'It is also possible to only disable certain ADI features.',
						ADI_I18N
					),
					self::OPTIONS => array(
						Adi_Configuration_Options::SHOW_MENU_TEST_AUTHENTICATION,
						Adi_Configuration_Options::SHOW_MENU_SYNC_TO_AD,
						Adi_Configuration_Options::SHOW_MENU_SYNC_TO_WORDPRESS
					)
				),
			),
			// Tab name
			__('Environment', ADI_I18N)  => array(
				// Group Name
				self::ANGULAR_CONTROLLER => 'EnvironmentController',
				self::MULTISITE_ONLY => false,
				__('Active Directory Environment', ADI_I18N) => array(
					self::DESCRIPTION => __(
						'On this page you can configure the connection details for your Active Directory.',
						ADI_I18N
					),
					// Option elements in group
					self::OPTIONS => array(
						Adi_Configuration_Options::DOMAIN_CONTROLLERS,
						Adi_Configuration_Options::PORT,
						Adi_Configuration_Options::USE_TLS,
						Adi_Configuration_Options::NETWORK_TIMEOUT,
						Adi_Configuration_Options::BASE_DN
					),
				),
				__('Verify Credentials', ADI_I18N) => array(
					self::DESCRIPTION => __(
						'Connect your WordPress site or profile to a domain.',
						ADI_I18N
					),
					self::OPTIONS => array(
						Adi_Configuration_Options::VERIFICATION_USERNAME,
						Adi_Configuration_Options::VERIFICATION_PASSWORD,
						Adi_Configuration_Options::DOMAIN_SID
					)
				),
			),
			// Tab name
			__('User', ADI_I18N)             => array(
				// Group Name
				self::ANGULAR_CONTROLLER => 'UserController',
				self::MULTISITE_ONLY => false,
				__('User Settings', ADI_I18N) => array(
					self::DESCRIPTION => __(
						'On this page you can configure how users should be created, updated and displayed. You can also prevent specific users from authenticating against the Active Directory. ',
						ADI_I18N
					),
					// Option elements in group
					self::OPTIONS => array(
						Adi_Configuration_Options::EXCLUDE_USERNAMES_FROM_AUTHENTICATION,
						Adi_Configuration_Options::ACCOUNT_SUFFIX,
						Adi_Configuration_Options::APPEND_SUFFIX_TO_NEW_USERS,
						Adi_Configuration_Options::AUTO_CREATE_USER,
						Adi_Configuration_Options::AUTO_UPDATE_USER,
						Adi_Configuration_Options::AUTO_UPDATE_DESCRIPTION,
						Adi_Configuration_Options::DEFAULT_EMAIL_DOMAIN,
						Adi_Configuration_Options::DUPLICATE_EMAIL_PREVENTION,
						Adi_Configuration_Options::PREVENT_EMAIL_CHANGE,
						Adi_Configuration_Options::NAME_PATTERN,
						Adi_Configuration_Options::SHOW_USER_STATUS,
					),
				),
			),
			// Tab name
			__('Password', ADI_I18N)        => array(
				self::ANGULAR_CONTROLLER => 'PasswordController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Password', ADI_I18N) => array(
					self::DESCRIPTION => __(
						'The password configuration page allows you to configure if users should be able to change their password, how failed authentications should be handled and so on.',
						ADI_I18N
					),
					// Option elements in group
					self::OPTIONS => array(
						Adi_Configuration_Options::NO_RANDOM_PASSWORD,
						Adi_Configuration_Options::ENABLE_PASSWORD_CHANGE,
						Adi_Configuration_Options::FALLBACK_TO_LOCAL_PASSWORD,
						Adi_Configuration_Options::AUTO_UPDATE_PASSWORD,
						Adi_Configuration_Options::ENABLE_LOST_PASSWORD_RECOVERY,
					),
				),
			),
			// Tab name
			__('Permissions', ADI_I18N) => array(
				self::ANGULAR_CONTROLLER => 'PermissionController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Permissions', ADI_I18N) => array(
					self::DESCRIPTION => __(
						'On this page you can configure whether only specific Active Directory Security groups should be granted access to WordPress. You can also define if certain Active Directory security groups should have WordPress role permissions by default.',
						ADI_I18N
					),
					// Option elements in group
					self::OPTIONS => array(
						Adi_Configuration_Options::AUTHORIZE_BY_GROUP,
						Adi_Configuration_Options::AUTHORIZATION_GROUP,
						Adi_Configuration_Options::ROLE_EQUIVALENT_GROUPS,
					),
				),
			),
			// Tab name
			__('Security', ADI_I18N) => array(
				self::ANGULAR_CONTROLLER => 'SecurityController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('SSO', ADI_I18N) => array(
					self::DESCRIPTION => __(
						'Single Sign On Configuration (Not implemented, yet.)',
						ADI_I18N
					),
					// Option elements in group
					self::OPTIONS => array(
						Adi_Configuration_Options::AUTO_LOGIN,
					),
				),
				// Group name
				__('Brute-Force-Protection', ADI_I18N) => array(
					// Group description
					self::DESCRIPTION => __(
						'For security reasons you can use the following options to prevent brute force attacks on your user accounts.',
						ADI_I18N
					),
					// Group elements in group
					self::OPTIONS     => array(
						Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS,
						Adi_Configuration_Options::BLOCK_TIME,
						Adi_Configuration_Options::USER_NOTIFICATION,
						Adi_Configuration_Options::ADMIN_NOTIFICATION,
						Adi_Configuration_Options::ADMIN_EMAIL,
					),
				),
			),
			// Tab name
			__('Attributes', ADI_I18N) => array(
				self::ANGULAR_CONTROLLER => 'AttributesController',
				self::MULTISITE_ONLY => false,
				// Group description
				__('Attributes', ADI_I18N) => array(
					// Group description
					self::DESCRIPTION => array(
						__(
							'User attributes from the Active Directory are stored as User Meta Data. These attributes can then be used in your themes and they can be shown on the profile page of your users.',
							ADI_I18N
						),
						__(
							'The attributes are only stored in the WordPress database if you activate "Automatic User Creation" and are only updated if you activate "Automatic User Update" on tab "User".',
							ADI_I18N
						),
						'',
						sprintf(__('The following WordPress attributes are reserved by ADI and cannot be used: %s', ADI_I18N), implode(', ', Ldap_Attribute_Repository::getDefaultAttributeMetaKeys())),
					),
					// Group elements in group
					self::OPTIONS     => array(
						Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES,
					),
				),
			),
			// Tab name
			__('Sync to AD', ADI_I18N)        => array(
				self::ANGULAR_CONTROLLER => 'SyncToAdController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Sync To Active Directory', ADI_I18N) => array(
					self::DESCRIPTION => __(
						'Synchronize WordPress profiles back to Active Directory.',
						ADI_I18N
					),
					// Group elements in group
					self::OPTIONS => array(
						Adi_Configuration_Options::SYNC_TO_AD_ENABLED,
						Adi_Configuration_Options::SYNC_TO_AD_USE_GLOBAL_USER,
						Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER,
						Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD,
						Adi_Configuration_Options::SYNC_TO_AD_AUTHCODE,
					),
				),
			),
			// Tab name
			__('Sync to WordPress', ADI_I18N)      => array(
				self::ANGULAR_CONTROLLER => 'SyncToWordpressController',
				self::MULTISITE_ONLY => false,
				// Group name
				__('Sync To WordPress', ADI_I18N) => array(
					// Group description
					self::DESCRIPTION => __(
						'You can import/update the users from Active Directory, for example by using a cron job.',
						ADI_I18N
					),
					// Group elements in group
					self::OPTIONS     => array(
						Adi_Configuration_Options::SYNC_TO_WORDPRESS_ENABLED,
						Adi_Configuration_Options::SYNC_TO_WORDPRESS_SECURITY_GROUPS,
						Adi_Configuration_Options::SYNC_TO_WORDPRESS_USER,
						Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD,
						Adi_Configuration_Options::SYNC_TO_WORDPRESS_DISABLE_USERS,
						Adi_Configuration_Options::SYNC_TO_WORDPRESS_AUTHCODE,
					),
				),
			),
		);
	}
}