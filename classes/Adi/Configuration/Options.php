<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Configuration_Options')) {
	return;
}

/**
 * Adi_Configuration_Options contains names and the structure of elements displayed on the settings page.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class Adi_Configuration_Options implements Multisite_Option_Provider
{
	// General
	const PROFILE_NAME = 'profile_name';
	const SUPPORT_LICENSE_KEY = 'support_license_key';
	const IS_ACTIVE = 'is_active';
	const SHOW_MENU_TEST_AUTHENTICATION = 'show_menu_test_authentication';
	const SHOW_MENU_SYNC_TO_AD = 'show_menu_sync_to_ad';
	const SHOW_MENU_SYNC_TO_WORDPRESS = 'show_menu_sync_to_wordpress';

	// Server
	const DOMAIN_CONTROLLERS = 'domain_controllers';
	const PORT = 'port';
	const USE_TLS = 'use_tls';
	const NETWORK_TIMEOUT = 'network_timeout';
	const BASE_DN = 'base_dn';

	// User - User Settings
	const EXCLUDE_USERNAMES_FROM_AUTHENTICATION = 'exclude_usernames_from_authentication';
	const ACCOUNT_SUFFIX = 'account_suffix';
	const APPEND_SUFFIX_TO_NEW_USERS = 'append_suffix_to_new_users';
	const AUTO_CREATE_USER = 'auto_create_user';
	const AUTO_UPDATE_USER = 'auto_update_user';
	const AUTO_UPDATE_DESCRIPTION = 'auto_update_description';
	const DEFAULT_EMAIL_DOMAIN = 'default_email_domain';
	const DUPLICATE_EMAIL_PREVENTION = 'duplicate_email_prevention';
	const PREVENT_EMAIL_CHANGE = 'prevent_email_change';
	const NAME_PATTERN = 'name_pattern';
	const SHOW_USER_STATUS = 'show_user_status';

	// User - Passwords
	const ENABLE_PASSWORD_CHANGE = 'enable_password_change';
	const NO_RANDOM_PASSWORD = 'no_random_password';
	const AUTO_UPDATE_PASSWORD = 'auto_update_password';

	// Permissions
	const AUTHORIZE_BY_GROUP = 'authorize_by_group';
	const AUTHORIZATION_GROUP = 'authorization_group';
	const ROLE_EQUIVALENT_GROUPS = 'role_equivalent_groups';

	// Security
	const FALLBACK_TO_LOCAL_PASSWORD = 'fallback_to_local_password';
	const ENABLE_LOST_PASSWORD_RECOVERY = 'enable_lost_password_recovery';

	// Security - Brute Force Protection
	const MAX_LOGIN_ATTEMPTS = 'max_login_attempts';
	const BLOCK_TIME = 'block_time';
	const USER_NOTIFICATION = 'user_notification';
	const ADMIN_NOTIFICATION = 'admin_notification';
	const ADMIN_EMAIL = 'admin_email';

	// User Meta - User Meta
	const ADDITIONAL_USER_ATTRIBUTES = 'additional_user_attributes';
	const USERMETA_EMPTY_OVERWRITE = 'usermeta_empty_overwrite';
	const SHOW_ATTRIBUTES = 'show_attributes';
	const ATTRIBUTES_TO_SHOW = 'attributes_to_show';

	// User Meta - Sync to AD
	const SYNC_TO_AD_ENABLED = 'sync_to_ad';
	const SYNC_TO_AD_USE_GLOBAL_USER = 'sync_to_ad_use_global_user';
	const SYNC_TO_AD_GLOBAL_USER = 'sync_to_ad_global_user';
	const SYNC_TO_AD_GLOBAL_PASSWORD = 'sync_to_ad_global_password';
	const SYNC_TO_AD_AUTHCODE = 'sync_to_ad_authcode';

	// User Sync to WordPress & Update
	const SYNC_TO_WORDPRESS_ENABLED = 'sync_to_wordpress_enabled';
	const SYNC_TO_WORDPRESS_AUTHCODE = 'sync_to_wordpress_authcode';
	const SYNC_TO_WORDPRESS_SECURITY_GROUPS = 'sync_to_wordpress_security_groups';
	const SYNC_TO_WORDPRESS_USER = 'sync_to_wordpress_user';
	const SYNC_TO_WORDPRESS_PASSWORD = 'sync_to_wordpress_password';
	const SYNC_TO_WORDPRESS_DISABLE_USERS = 'disable_users';

	// New Features
	const AUTO_LOGIN = 'auto_login';

	// additional attribute mapping
	const ATTRIBUTES_COLUMN_TYPE = "type";
	const ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE = "wordpress_attribute";
	const ATTRIBUTES_COLUMN_OVERWRITE_EMPTY = "overwrite";
	const ATTRIBUTES_COLUMN_DESCRIPTION = "description";
	const ATTRIBUTES_COLUMN_SYNC_TO_AD = "sync_to_ad";
	const ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE= "view_in_userprofile";

	// array containing all setting elements
	private $optionsMetadata = array();

	/**
	 * Get the option meta data for an option.
	 *
	 * @param string $name
	 *
	 * @return array|null
	 */
	public function get($name)
	{
		// get all metadata
		$optionsMetadata = $this->getAll();

		// get option element
		if ($this->existOption($name)) {
			return $optionsMetadata[$name];
		}

		return null;
	}

	/**
	 * Exists the option with the name $name?
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function existOption($name)
	{
		$optionsMetadata = $this->getAll();

		return isset($optionsMetadata[$name]);
	}

	/**
	 * Get all option elements.
	 *
	 * @return array
	 */
	public function getAll()
	{
		// lazy loading
		if (empty($this->optionsMetadata)) {
			$this->optionsMetadata = self::generate();
		}

		// get option elements
		return $this->optionsMetadata;
	}

	/**
	 * Get all option elements that are not transient.
	 *
	 * @return mixed
	 */
	public function getNonTransient()
	{
		$data = $this->getAll();

		return array_filter($data, function($item) {
			return (!$item[Multisite_Option_Attribute::TRANSIENT]);
		});
	}

	/**
	 * This method generates all the meta information for a option elements.
	 * The keys (like self::DOMAIN_CONTROLLERS, self::PORT) and it is values are option elements.
	 * The key is the internal name for the option and the value is the option meta data.
	 *
	 * The option meta data contains information like:
	 * $title (big name on the left side in the settings menu)
	 * $type (type of the option, determine how the option value should be rendered (password, select, checkbox))
	 * $description (short description below the option)
	 * $detail (full description below the short description)
	 * $elements (array of allowed values for example a select)
	 * $default (the default value will be used as a fallback value)
	 * $disabled (disable the option in the settings menu)
	 * $disabledMessage (show message instead of disabled option)
	 * $sanitizer (how should the option value be processed? remove leading spaces?)
	 *
	 * @return array
	 */
	private static function generate()
	{
		$title = Multisite_Option_Attribute::TITLE;
		$type = Multisite_Option_Attribute::TYPE;
		$description = Multisite_Option_Attribute::DESCRIPTION;
		$detail = Multisite_Option_Attribute::DETAIL;
		$elements = Multisite_Option_Attribute::ELEMENTS;

		// the key for the default value
		$default = Multisite_Option_Attribute::DEFAULT_VALUE;

		$disabled = Multisite_Option_Attribute::DISABLED;
		$disabledMessage = Multisite_Option_Attribute::DISABLED_MESSAGE;

		$sanitizer = Multisite_Option_Attribute::SANITIZER;
		$angularAttributes = Multisite_Option_Attribute::ANGULAR_ATTRIBUTES;
		$showPermission = Multisite_Option_Attribute::SHOW_PERMISSION;
		$transient = Multisite_Option_Attribute::TRANSIENT;

		return array(
			self::PROFILE_NAME      => array(
				$title          => __('Profile name:', ADI_I18N),
				$type        => Multisite_Option_Type::TEXT,
				$description => __(
					'Name for the current profile',
					ADI_I18N
				),
				$detail      => __(
					'Name for the current profile',
					ADI_I18N
				),
				$sanitizer   => array('string'),
				$default => '',
				$angularAttributes => 'ng-disabled="((true)',
				$showPermission => false,
				$transient      => true,
			),
			self::SUPPORT_LICENSE_KEY      => array(
				$title          => __('Support license key:', ADI_I18N),
				$type        => Multisite_Option_Type::TEXT,
				$description => __(
					"Please enter your support license key here, if you have a paid ADI 2.0 license. It is required to receive support from <a href='https://neos-it.de'>NeosIT GmbH</a>.",
					ADI_I18N
				),
				$detail      => __(
					"Please enter your support license key here, if you have a paid ADI 2.0 license. It is required to receive support from <a href='https://neos-it.de'>NeosIT GmbH</a>.",
					ADI_I18N
				),
				$sanitizer   => array('string'),
				$default => '',
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			self::IS_ACTIVE         => array(
				$title 		=> __('Enable ADI', ADI_I18N),
				$type		=> Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enable/Disable Active Directory Integration',
					ADI_I18N
				),
				$detail => __(
					'Active Directory Integration is only used if this checkbox is enabled. If you are running a WordPress Multisite you can force a disabled ADI plug-in for specific blogs.',
					ADI_I18N
				),
				$default => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			self::SHOW_MENU_TEST_AUTHENTICATION	=> array(
				$title 		=> __(
					'Enable "Test authentication"',
					ADI_I18N
				),
				$type		=> Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enable/Disable Test authentication',
					ADI_I18N
				),
				$detail => __(
					'The menu entry "Test authentication" can be enabled or disabled, based upon this configuration',
					ADI_I18N
				),
				$default        => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			self::SHOW_MENU_SYNC_TO_AD	=> array(
				$title 		=> __(
					'Enable "Sync to AD"',
					ADI_I18N
				),
				$type		=> Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enable/Disable Sync to AD',
					ADI_I18N
				),
				$detail => __(
					'The menu entry "Sync to AD" can be enabled or disabled, based upon this configuration',
					ADI_I18N
				),
				$default        => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			self::SHOW_MENU_SYNC_TO_WORDPRESS	=> array(
				$title 		=> __(
					'Enable "Sync to WordPress"',
					ADI_I18N
				),
				$type		=> Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enable/Disable Sync to WordPress',
					ADI_I18N
				),
				$detail => __(
					'The menu entry "Sync to WordPress" can be enabled or disabled, based upon this configuration',
					ADI_I18N
				),
				$default        => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			// Domain Controllers (separate with semicolons)
			self::DOMAIN_CONTROLLERS            => array(
				$title       => __('Domain controllers', ADI_I18N),
				$type        => Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'Domain controllers which will be used to authenticate and authorize the users',
					ADI_I18N
				),
				$detail      => __(
					'The domain controller represents the Active Directory server used to authenticate and authorize your users. You can find your currently set Domain Controller via "Start -> Run -> nslookup".',
					ADI_I18N
				),
				$default     => '',
				$angularAttributes => '',
				$sanitizer   => array('string'),
				$showPermission => true,
				$transient      => false,
			),
			// Port on which AD listens (default 389)
			self::PORT                          => array(
				$title       => __('Port', ADI_I18N),
				$type        => Multisite_Option_Type::NUMBER,
				$description => __('Port on which Active Directory listens (defaults to "389").', ADI_I18N),
				$detail      => __(
					'This option defines the Active Directory communication port and is by default set to 389.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => 389,
				$sanitizer   => array('integerRange', 0, 65535),
				$showPermission    => true,
				$transient         => false,
			),
			// Secure the connection between the Drupal and the LDAP servers using START_TLS.
			self::USE_TLS                       => array(
				$title           => __('Use STARTTLS', ADI_I18N),
				$type            => Multisite_Option_Type::CHECKBOX,
				$description     => __(
					'Secures the connection between the WordPress and the Active Directory servers using STARTTLS',
					ADI_I18N
				),
				$detail          => __(
					'Enabling this option activates the TLS (Transport Layer Security), which secures the data transport between your Active Directory server and WordPress by encrypting the data. If you want to use STARTTLS, the "Port" option has to be set as default("389"). Please note that STARTTLS is not the same as LDAP/S.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default         => false,
				$disabled        => !extension_loaded('openssl'),
				$disabledMessage => __(
					'<b>You must enable the PHP module "openssl" before you can use STARTTLS.</b>', ADI_I18N
				),
				$sanitizer       => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// network timeout (LDAP_OPT_NETWORK_TIMEOUT) in seconds
			self::NETWORK_TIMEOUT               => array(
				$title       => __('LDAP network timeout', ADI_I18N),
				$type        => Multisite_Option_Type::NUMBER,
				$description => __(
					'Seconds after the connection attempt to Active Directory times out. After this period WordPress falls back to local authentication. By default it is set to "5".',
					ADI_I18N
				),
				$detail      => __(
					'This option describes the time in seconds which has to pass after a connection attempt to the Active Dirctory server before the the connection times out and falls back to the local WordPress authentication. The default value for this option is "5".',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => 5,
				$sanitizer   => array('integerRange', 0, 'unlimited', 5),
				$showPermission    => true,
				$transient         => false,
			),
			// LDAP/AD BASE DN
			self::BASE_DN                       => array(
				$title       => __('Base DN', ADI_I18N),
				$type        => Multisite_Option_Type::TEXT,
				$description => __(
					'Base DN (e.g. "ou=unit,dc=domain,dc=tld" or "cn=users,dc=domain,dc=tld")', ADI_I18N
				),
				$detail      => array(
					__(
						'You can find your Active Directory Base DN, if you follow this step by step guide correctly.',
						ADI_I18N
					),
					__('1. Connect to your Active Directory server by using Remote Desktop.', ADI_I18N),
					__('2. Start -> Administrative Tools -> Active Directory Users and Computers', ADI_I18N),
					__('3. Click on your Domain Controller -> right-click Users -> Properties', ADI_I18N),
					__('4. Attribute -> Select distinguishedName in the list -> press View', ADI_I18N),
					__('5. Copy your Base DN.', ADI_I18N),
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// this usernames will always be excluded from ADI authentication
			self::EXCLUDE_USERNAMES_FROM_AUTHENTICATION => array(
				$title		=> __('Exclude usernames from authentication', ADI_I18N),
				$type		=> Multisite_Option_Type::EDITABLE_LIST,
				$description	=> __(
					'Entered usernames will always be excluded from ADI login authentication.',
					ADI_I18N
				),
				$detail 	=> array(
					__(
						'Every username you have entered will not be authenticated against the Active Directory, instead the WordPress authentication mechanism is used.',
						ADI_I18N
					)
				),
				$default	=> '',
				$sanitizer   => array('accumulation', ';', array('string', false, true)),
				$showPermission => true,
				$transient      => false,
			),
			// account Suffix (will be appended to all usernames created in WordPress, as well as used in the Active Directory authentication process
			self::ACCOUNT_SUFFIX                => array(
				$title       => __('Account suffix', ADI_I18N),
				$type        => Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'This suffix will be appended to all usernames during the Active Directory authentication process, e.g. "@company.local".',
					ADI_I18N
				),
				$detail      => array(
					__(
						'The Account suffix is added to all usernames during the Active Directory authentication process.',
						ADI_I18N
					),
					__(
						'Example: An *Account Suffix* "@company.local" is used. When the user "my_username" logs in, the fully username is set to "my_username@company.local".',
						ADI_I18N
					),
					'<strong>' . __('Do not forget to start the suffix with "@".', ADI_I18N) . '</strong>',
					__(
						'If you have multiple account suffixes like *@emea.company.local*, *@africa.company.local* enter each of them and put the primary domain name (@company.local) at the *last* position.',
						ADI_I18N
					)
					),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('accumulation', ';', array('string', false, true)),
				$showPermission    => true,
				$transient         => false,
			),
			// Should the account suffix be appended to the usernames created in WordPress?
			self::APPEND_SUFFIX_TO_NEW_USERS    => array(
				$title       => __('Append suffix to new users', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'If checked, the account suffix (see above) will be appended to the usernames of new created users.',
					ADI_I18N
				),
				$detail      => __(
					'This option will automatically add the previously defined *Account suffix* to all new created users. This means if you create the user *newuser* and already have configured your account suffix e.g. *@company.local*, this option will automatically change the username to *newuser@company.local*.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Should a new user be created automatically if not already in the WordPress database?
			self::AUTO_CREATE_USER              => array(
				$title       => __('Automatic user creation', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'If enabled, users will be created in your WordPress instance after they have successful authenticated.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'Created users will obtain the role defined under "New User Default Role" on the "General Options" tab. (Not Implemented Yet.)',
						ADI_I18N
					),
					__('This option is separated from the Role Equivalent Groups option, below.', ADI_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Should the users be updated in the WordPress database everytime they logon? (Works only if automatic user creation is set.
			self::AUTO_UPDATE_USER              => array(
				$title       => __('Automatic user synchronization', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __('After a successful login the WordPress profile of the user will be automatically synchronized with his Active Directory account.',
					ADI_I18N),
				$detail      => array(
					__(
						'Enabling this option will result in ADI synchronizing the user\'s information every time they login in WordPress.',
						ADI_I18N
					),
					__('Requires "Automatic user creation" to be enabled.', ADI_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Update users description if $_auto_update_user is true
			self::AUTO_UPDATE_DESCRIPTION       => array(
				$title       => __('Automatic update user description', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Automatically updates the description of users who login and new created users', ADI_I18N
				),
				$detail      => __(
					'This option will only work if you already have enabled "Automatic user creation" and "Automatic user update". As the title says it will automatically update the description of new created users and users who login.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Default Email Domain (eg. 'domain.tld')
			self::DEFAULT_EMAIL_DOMAIN          => array(
				$title       => __('Default email domain', ADI_I18N),
				$type        => Multisite_Option_Type::TEXT,
				$description => __(
					'If the Active Directory attribute "mail" is blank, a user\'s email will be set to username@ValueOfThisTextbox.',
					ADI_I18N
				),
				$detail      => __(
					'If the Active Directory attribute "mail" is blank, a user\'s email will be set to username@ValueOfThisTextbox.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// How to deal with duplicate email addresses
			self::DUPLICATE_EMAIL_PREVENTION    => array(
				$title       => __('Email address conflict handling', ADI_I18N),
				$type        => Multisite_Option_Type::SELECT,
				$elements    => array(
					__('Prevent (recommended)', ADI_I18N) => 'prevent',
					__('Allow (UNSAFE)', ADI_I18N)        => 'allow',
					__('Create', ADI_I18N)                => 'create',
				),
				$description => __('This option handles email address conflicts caused by multiple user creation using the same email address.', ADI_I18N),
				$detail      => array(
					__('This option handles email address conflicts caused by multiple user creation using the same email address. WordPress does only allow unique email addresses in an installation.',
						ADI_I18N),
					__(
						'Prevent: User is not created, if his email address is already in use by another user. (recommended)',
						ADI_I18N
					),
					__('Allow: Allow users to share one email address. (UNSAFE)', ADI_I18N),
					__('Create: In case of a conflict, the new user is created with a unique and randomly generated email address.',
						ADI_I18N),
				),
				$angularAttributes => '',
				$default     => 'prevent',
				$sanitizer   => array('selection', 0),
				$showPermission    => true,
				$transient         => false,
			),
			// Prevent email change by ADI Users (not for admins)
			self::PREVENT_EMAIL_CHANGE          => array(
				$title       => __('Prevent email change', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Prevents users authenticated by Active Directory from changing their email address in WordPress.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'Activating this option will forbid users authenticated by Active Directory to change their email address in in WordPress.',
						ADI_I18N
					),
					__('This option does not apply to the administrator.', ADI_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Set user's display_name to an AD attribute or to username if left blank
			// Possible values: description, displayname, mail, sn, cn, givenname, samaccountname, givenname sn
			self::NAME_PATTERN                  => array(
				$title       => __('Display name', ADI_I18N),
				$type        => Multisite_Option_Type::SELECT,
				$elements    => array(
					__('sAMAccountName (the username)', ADI_I18N)     => 'samaccountname',
					__('displayName', ADI_I18N)                       => 'displayname',
					__('description', ADI_I18N)                       => 'description',
					__('givenName (firstname)', ADI_I18N)             => 'givenname',
					__('SN (lastname)', ADI_I18N)                     => 'sn',
					__('givenName SN (firstname lastname)', ADI_I18N) => 'givenname sn',
					__('CN (Common Name, the whole name)', ADI_I18N)  => 'cn',
					__('mail', ADI_I18N)                              => 'mail',
				),
				$description       => __(
					'This option allows you to configure how users should be displayed at new posts/comments.',
					ADI_I18N
				),
				$detail      => __(
					'This option allows you to configure how users should be displayed at new posts/comments.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => 'samaccountname', // TODO soll dieser Wert wirklich als default-Wert dienen?
				$sanitizer   => array('selection', 0),
				$showPermission    => true,
				$transient         => false,
			),
			// show disabled and ADI user status on user list
			self::SHOW_USER_STATUS              => array(
				$title       => __('Show user status', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __('Show additional columns (<em>ADI User</em>, <em>disabled</em>) in WordPress\' users list.',
					ADI_I18N),
				$detail      => __('', ADI_I18N),
				$default     => true,
				$sanitizer   => array('boolean'),
				$showPermission => true,
				$transient      => false,
			),
			// Use the real password when a user is created
			self::NO_RANDOM_PASSWORD            => array(
				$title       => __('Set local password on first successful login', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'The first time a user logs on his local password will be equated with the password he used to authenticate against the Active Directory.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'The first time a user logs on his local password will be equated with the password he used to authenticate against the Active Directory. If this option is deactivated a random password for this user will be set.',
						ADI_I18N
				),
					__('If this option is deactivated a random password for this user will be set.', ADI_I18N),
					__('The option does only work if *User > Automatic user creation* is enabled.', ADI_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Enable/Disable password changes
			self::ENABLE_PASSWORD_CHANGE        => array(
				$title       => __('Allow local password changes', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enabling this option allows users to change their local WordPress password.',
					ADI_I18N
				),
				$detail      => array(
					__('Enabling this option allows users to change their local WordPress password.', ADI_I18N),
					__('This option has <strong>no</strong> effect to the Active Directory password and will <strong>not</strong> synchronized back to the Active Directory.',
						ADI_I18N),
					__('This option is only be used after the WordPress authentication against the AD fails because of a network timeout.',
						ADI_I18N),
					),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Update password on every successful login
			self::AUTO_UPDATE_PASSWORD          => array(
				$title       => __('Automatic password update', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'This option updates the local password every time a user successfully logs in.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'This option updates the local password every time a user successfully logs in. If a user has changed his Active Directory password and successfully authenticates against Active Directory while trying to login to WordPress, his local WordPress password will be equated with the new Active Directory password.',
						ADI_I18N
					),
					__(
						'Note: Activating this option makes little sense if <em>Allow local password changes</em> is enabled.',
						ADI_I18N
					),
					__('Works only if <em>User &gt; Automatic user creation</em> and <em>User &gt; Automatic user synchronization</em> is enabled.',
						ADI_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Check Login authorization by group membership
			self::AUTHORIZE_BY_GROUP            => array(
				$title       => __('Authorize by group membership', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'This option authorizes only members of the given Active Directory security groups to gain access to WordPress.',
					ADI_I18N
				),
				$detail      => __(
					'This option authorizes only members of the given Active Directory security groups to gain access to WordPress. This authorization occurs <strong>after</strong> the authentication.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Group name for authorization.
			self::AUTHORIZATION_GROUP           => array(
				$title       => __('Authorization group(s)', ADI_I18N),
				$type        => Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'If not empty, only the defined security groups have access to WordPress.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'Please define your Authorization group(s) here.',
						ADI_I18N
					),
					__('Works only if "User > Automatic user creation" and "User > Automatic user synchronization" are enabled.',
						ADI_I18N),
				),
				$angularAttributes => 'ng-disabled="((!option.authorize_by_group) || ((permission.authorization_group == 2) || (permission.authorization_group == 1))',
				$default     => '',
				$sanitizer   => array('accumulation', ';', array('string')),
				$showPermission    => true,
				$transient         => false,
			),
			// Role Equivalent Groups (wp-role1=ad-group1;wp-role2=ad-group2;...)
			self::ROLE_EQUIVALENT_GROUPS        => array(
				$title       => __('Role equivalent groups', ADI_I18N),
				$type        => Multisite_Option_Type::TABLE,
				$description => __(
					'List of one or multiple Active Directory security groups which correspond to WordPress\' roles.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'Please enter the names of the Active Directory security groups which correspond to WordPress user roles using the following schema.',
						ADI_I18N
					),
					__('Example:', ADI_I18N),
					__('ad-group=wp-role;', ADI_I18N),
					__('wordpressadmins=administrator;', ADI_I18N),
					__('wordpressmoderator=editor;', ADI_I18N),
					__('wordpressuser=contributor;', ADI_I18N),
					__(
						'You can find a whole table of Roles and Capabilities at: ' . "<a href="
						. 'http://codex.wordpress.org/Roles_and_Capabilities'
						. '>http://codex.wordpress.org/Roles_and_Capabilities</a>' . ' (3.8 Capability vs. Role Table)',
						ADI_I18N
					),
					__(
						'Group memberships cannot be checked across domains.  So if you have two domains, instr and qc, and qc is the domain specified above, if instr is linked to qc, I can authenticate instr users, but not check instr group memberships.',
						ADI_I18N
					),
				),
				$angularAttributes => 'ng-disabled="(((permission.role_equivalent_groups == 2) || (permission.role_equivalent_groups == 1))',
				$default     => '',
				$sanitizer   => array('accumulation', ';', array('valueAssignment', '=')),
				$showPermission    => true,
				$transient         => false,
			),
			// use local (WordPress) password as fallback if authentication against AD fails
			self::FALLBACK_TO_LOCAL_PASSWORD    => array(
				$title       => __('Fallback to local password', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Fallback to local(WordPress) password check if Active Directory authentication fails.', ADI_I18N
				),
				$detail      => array(
					__(
						'If option is enabled, users who failed authenticating against Active Directory can authenticate again against the local WordPress password check.',
						ADI_I18N
					),
					__(
						'But this might be a security risk (for example, if the local password is outdated). <b>It\'s recommended to turn this off.</b>',
						ADI_I18N
					),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Enable lost password recovery
			self::ENABLE_LOST_PASSWORD_RECOVERY => array(
				$title       => __('Enable lost password recovery', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __('The user can reset his password in the login screen.', ADI_I18N),
				$detail      => __(
					'Enabling this option will allow users to reset their local password in the login screen.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Settings for Autologin
			self::AUTO_LOGIN                    => array(
				$title       => __('Auto Login', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'This option will grant users the possibility to Single Sign On WordPress once they got authenticated against Active Directory. (Not Implemented Yet.)',
					ADI_I18N
				),
				$detail      => __(
					'This option will grant users the possibility to Single Sign On WordPress once they got authenticated against Active Directory. (Not Implemented Yet.)',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Maximum number of failed login attempts before the account is blocked
			self::MAX_LOGIN_ATTEMPTS            => array(
				$title       => __('Maximum number of allowed login attempts', ADI_I18N),
				$type        => Multisite_Option_Type::NUMBER,
				$description => __(
					'Maximum number of failed login attempts before a user account is blocked. If empty or "0" <em>Brute Force Protection</em> is disabled.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'Please enter the amount of tries a user has to login with his correct username and password combination before he gets blocked for the specific time.',
						ADI_I18N
					),
					__(
						'You can set the time below this option.',
						ADI_I18N
					),
					__(
						'If you want to disable the bruteforce protection just set the Maximum number of allowed login attempts to 0.',
						ADI_I18N
					),
				),
				$angularAttributes => '',
				$default     => 3,
				$sanitizer   => array('integerRange', 0, 'unlimited', 3),
				$showPermission    => true,
				$transient         => false,
			),
			// Number of seconds an account is blocked after the maximum number of failed login attempts is reached.
			self::BLOCK_TIME                    => array(
				$title       => __('Blocking time', ADI_I18N),
				$type        => Multisite_Option_Type::NUMBER,
				$description => __(
					'Time in seconds for which the account is blocked if too many failed login attempts occurred.',
					ADI_I18N
				),
				$detail      => __(
					'With help of this option you can set the time a user is being blocked after the amount of failed login attempts has been reached..',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => 30,
				$sanitizer   => array('integerRange', 0, 'unlimited', 30),
				$showPermission    => true,
				$transient         => false,
			),
			// Send email to user if his account is blocked.
			self::USER_NOTIFICATION             => array(
				$title       => __('Notify users of blocked account', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __('Notify user by e-mail when his account has been blocked.', ADI_I18N),
				$detail      => __(
					'If enabled *ADI* notifies the user by email about the circumstance that his account has been blocked.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Send email to admin if a user account is blocked.
			self::ADMIN_NOTIFICATION            => array(
				$title       => __('Notify admins of blocked account', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __('Notify admin(s) by e-mail when an user account has been blocked.', ADI_I18N),
				$detail      => __(
					'Enabling this option will notify the admin(s) about blocked user accounts as well.', ADI_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Administrator's email address(es) where notifications should be sent to.
			self::ADMIN_EMAIL                   => array(
				$title       => __('Email addresses for notifications', ADI_I18N),
				$type        => Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'Given email addresses will recieve emails for admin notifications.',
					ADI_I18N
				),
				$detail      => __(
					'If the admin notification event is triggered and no admin email has been set, the email is forwarded to the blog administrator\'s email address.',
					ADI_I18N
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('accumulation', ';', array('email')),
				$showPermission    => true,
				$transient         => false,
			),
			// List of additional user attributes that can be defined by the admin
			// The attributes are seperated by a new line and have the format:
			//   <Attribute name>:<type>
			// where type can be one of the following: string, integer, bool, image, time, timestamp
			//   thumbnailphoto:image
			//   whencreated:time
			self::ADDITIONAL_USER_ATTRIBUTES    => array(
				$title                                         => __('Additional User Attributes', ADI_I18N),
				$type                                          => Multisite_Option_Type::CUSTOM,
				Multisite_Option_Attribute::TYPE_STRUCTURE => array(
					// first input field; the user can use a well known user attribute or can use his own user attribute
					array(
						$type        => Multisite_Option_Type::COMBOBOX,
						$description => __('AD User Attribute:', ADI_I18N),
						$elements    => Ldap_Attribute_Description::findAll(),
						$sanitizer   => array('string', false, true, true),
					),
					// second input field; the user can only should between certain data types
					array(
						$type        => Multisite_Option_Type::SELECT,
						$description => __('Data Type:', ADI_I18N),
						$elements    => array(
							__('Only a text', ADI_I18N)         => 'string',
							__('An array with texts', ADI_I18N) => 'list',
							__('Natural number', ADI_I18N)      => 'integer',
							__('Boolean', ADI_I18N)             => 'bool',
							__('Octet', ADI_I18N)               => 'octet',
							__('Time', ADI_I18N)                => 'time',
							__('Timestamp', ADI_I18N)           => 'timestamp',
							__('CN', ADI_I18N)                  => 'cn',
						),
						$angularAttributes => '',
						$sanitizer   => array('selection'),
					),
					array(
						$type        => Multisite_Option_Type::TEXT,
						$description => __('User Meta Key:', ADI_I18N),
						$sanitizer   => array('string'),
					),
				),
				$description                                   => __(
					'Enter additional Active Directory attributes.',
					ADI_I18N
				),
				$detail                                        => array(
					__(
						'Additional Attributes that should appear on the user profile must also be placed in "Attributes to show".',
						ADI_I18N
					),
					),
				$angularAttributes => 'ng-disabled="(((permission.additional_user_attributes == 1) || (permission.additional_user_attributes == 2))',
				$default                                       => '',
				$sanitizer                                     => array('custom'), // all in lower case
				$showPermission => true,
				$transient      => false,
			),
			// Enable Sync to AD
			self::SYNC_TO_AD_ENABLED                 => array(
				$title       => __('Enable sync to AD', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Synchronize the user\'s WordPress profile back to Active Directory.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'By enabling this option ADI will automatically synchronize the user\'s profile back to the Active Directory if they have changed.',
						ADI_I18N
					),
					__('The synchronization is automatically triggered whenever a user profile gets updated.',
						ADI_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Use global Sync to AD User
			self::SYNC_TO_AD_USE_GLOBAL_USER => array(
				$title       => __('Use Sync To AD service account', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'By enabling this option users will not be asked for their Active Directory password when updating their WordPress user profile. Instead a central Active Directory service account is used.<br />If this option is <strong>disabled</strong>, you have to configure your Active Directory properyly.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'By enabling this option users will not be asked for their Active Directory password when updating their WordPress user profile. Instead a central Active Directory service account will be used.',
						ADI_I18N
					),
					__(
						'If this option is disabled, you need to set the required permissions in the Active Directory. Please refer to the documentation on how to do this.',
						ADI_I18N
					),
					__(
						'NOTICE: The password of this service account is stored encrypted, but **USE IT AT YOUR OWN RISK**. To avoid this you have to grant your users the permission to change their own AD attributes. See FAQ for details.',
						ADI_I18N
					),
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad) || ((permission.sync_to_ad_use_global_user == 2) || (permission.sync_to_ad_use_global_user == 1))',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Account name of global Sync to AD user
			self::SYNC_TO_AD_GLOBAL_USER     => array(
				$title       => __('Service account username', ADI_I18N),
				$type        => Multisite_Option_Type::TEXT,
				$description => __(
					'Username of an Active Directory account with write permissions for the users in the Active Directory (e.g. administrator@company.local).',
					ADI_I18N
				),
				$detail      => __(
					'If you define a Sync To AD service account with write permissions inside the Active Directory, changes will automatically be written in the Active Directory database through this user.',
					ADI_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad)||(!option.sync_to_ad_use_global_user) || ((permission.sync_to_ad_global_user == 2) || (permission.sync_to_ad_global_user == 1))',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// Password of global Dd user
			self::SYNC_TO_AD_GLOBAL_PASSWORD => array(
				$title       => __('Service account password', ADI_I18N),
				$type        => Multisite_Option_Type::PASSWORD,
				$description => __(
					'Password for Sync To AD service account. Leave empty if password should not be changed.', ADI_I18N
				),
				$detail      => __(
					'This option defines a NEW password for the Sync To AD service account. Leave this field blank if you do not want to change the password.',
					ADI_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad)||(!option.sync_to_ad_use_global_user) || ((permission.sync_to_ad_global_password == 2) || (permission.sync_to_ad_global_password == 1))', //
				$default     => '',
				$sanitizer   => array('string', false, false),
				$showPermission    => true,
				$transient         => false,
			),
			// AUTHCODE for Sync to AD. Sync to AD will only work, if this AUTHCODE is send as a post-parameter to the blog index.php
			self::SYNC_TO_AD_AUTHCODE       => array(
				$title       => __('Auth code', ADI_I18N),
				$type        => Multisite_Option_Type::AUTHCODE,
				$description => __(
					'This code is needed for Sync to AD.',
					ADI_I18N
				),
				$detail      => __(
					'The authentication code is used to authenticate the global sync user against the Active Directory.',
					ADI_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad) || ((permission.sync_to_ad_authcode == 2) || (permission.sync_to_ad_authcode == 1))',
				$default => '',
				Multisite_Option_Attribute::PERSIST_DEFAULT_VALUE => true,
				$sanitizer   => array('authcode'),
				$showPermission                                   => true,
				$transient                                        => false,
			),
			// enable Sync to WordPress
			self::SYNC_TO_WORDPRESS_ENABLED  => array(
				$title       => __('Enable sync to WordPress', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enabling this option will allow ADI to sync users from the Active Directory to the WordPress Database.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'Enabling this option will allow ADI to sync users from the Active Directory to the WordPress Database.',
						ADI_I18N
					),
					__(
						'Hint: You could use a cron job, which is nothing more than a timetable executing the Sync to WordPress/update at from you defined times, to keep your WordPress database always up to date with your Active Directory user information.',
						ADI_I18N
					),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// AUTHCODE for Sync to WordPress. Sync to WordPress will only work, if this AUTHCODE is send as a post-parameter to the blog index.php
			self::SYNC_TO_WORDPRESS_AUTHCODE        => array(
				$title       => __('Auth code', ADI_I18N),
				$type        => Multisite_Option_Type::AUTHCODE,
				$description => __('This code is needed for Sync to WordPress.', ADI_I18N),
				$detail      => __(
					'This code is needed for Sync to WordPress.',
					ADI_I18N
				),
				$default => '',
				Multisite_Option_Attribute::PERSIST_DEFAULT_VALUE => true,
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_authcode == 2) || (permission.sync_to_wordpress_authcode == 1))',
				$sanitizer   => array('authcode'),
				$showPermission                                   => true,
				$transient                                        => false,
			),
			// Import members of these security groups (separated by semicolons)
			self::SYNC_TO_WORDPRESS_SECURITY_GROUPS => array(
				$title       => __('Import members of security groups', ADI_I18N),
				$type        => Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'The members of the security groups entered here will be imported. See documentation how to import members of Domain Users or Domain Administrators.',
					ADI_I18N
				),
				$detail      => array(
					__(
						'Here you can enter the security groups which will be imported every time Sync To WordPress is executed.',
						ADI_I18N
					),
					__(
						'If you want to include the users of the built in user group "Domain Users" you have to enter "id:513". More information can be found in the official documentation.',
						ADI_I18N
					),
				),
				$default     => '',
				$sanitizer   => array('accumulation', ';', array('string')),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_security_groups  == 2) || (permission.sync_to_wordpress_security_groups  == 1))',
				$showPermission    => true,
				$transient         => false,
			),
			// name of Sync to WordPress User in Active Directory
			self::SYNC_TO_WORDPRESS_USER     => array(
				$title       => __('Service account username', ADI_I18N),
				$type        => Multisite_Option_Type::TEXT,
				$description => __(
					'Username of an Active Directory account with read permissions for the users in the Active Directory (e.g. "ldapuser@company.local").',
					ADI_I18N
				),
				$detail      => __(
					'In order to import multiple users at once you have to define a user with Active Directory users read permission.',
					ADI_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_user == 2) || (permission.sync_to_wordpress_user == 1))',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// password for Sync to WordPress User (will be stored encrypted)
			self::SYNC_TO_WORDPRESS_PASSWORD      => array(
				$title       => __('Service account password', ADI_I18N),
				$type        => Multisite_Option_Type::PASSWORD,
				$description => __('Password for Sync to WordPress User.', ADI_I18N),
				$detail      => __(
					'As you already defined the Sync to WordPress service account username before you now have to define the password for this user as well.',
					ADI_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_user == 2) || (permission.sync_to_wordpress_user == 1))',
				$default     => '',
				$sanitizer   => array('string', false, false),
				$showPermission    => true,
				$transient         => false,
			),
			// Prevent email change by ADI Users (not for admins)
			self::SYNC_TO_WORDPRESS_DISABLE_USERS => array(
				$title       => __('Automatic deactivate users', ADI_I18N),
				$type        => Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Deactivated users can only be re-activated manually by administrators on their users profile page or by using the *Sync to WordPress* feature.',
					ADI_I18N
				),
				$detail      => __(
					'Deactivated users can only be re-activated manually by administrators on their users profile page or by using the *Sync to WordPress* feature.',
					ADI_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.disable_users == 2) || (permission.disable_users == 1))',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
		);
	}

	/**
	 * Return a table with common Active Directory user attribute names + descriptions
	 *
	 * @return string,
	 */
	public function commonActiveDirectoryAttributes()
	{
		$adAttribute = __('AD Attribute', ADI_I18N);
		$description = __('Description', ADI_I18N);

		$table
			= "<table>
<tr>
    <th>$adAttribute</th>
    <th>$description</th>
</tr>";

		$descriptions = Adit_Ad_AttributeDescriptions::findAll();
		foreach ($descriptions as $attribute => $description) {
			$table .= "<tr><th class='nsp_short_form_table'>$attribute</th><th class='nsp_short_form_table'>$description</th></tr>";
		}

		$table .= '</table>';

		return $table;
	}
}