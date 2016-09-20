<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Configuration_Options')) {
	return;
}

/**
 * NextADInt_Adi_Configuration_Options contains names and the structure of elements displayed on the settings page.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class NextADInt_Adi_Configuration_Options implements NextADInt_Multisite_Option_Provider
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
	const ENCRYPTION = 'encryption';
	const USE_TLS = 'use_tls';
	const NETWORK_TIMEOUT = 'network_timeout';
	const BASE_DN = 'base_dn';
	const VERIFICATION_USERNAME = 'verification_username';
	const VERIFICATION_PASSWORD = 'verification_password';
	const DOMAIN_SID = 'domain_sid';

	// User - User Settings
	const EXCLUDE_USERNAMES_FROM_AUTHENTICATION = 'exclude_usernames_from_authentication';
	const ACCOUNT_SUFFIX = 'account_suffix';
	const USE_SAMACCOUNTNAME_FOR_NEW_USERS = 'use_samaccountname_for_new_users';
	const AUTO_CREATE_USER = 'auto_create_user';
	const AUTO_UPDATE_USER = 'auto_update_user';
	const AUTO_UPDATE_DESCRIPTION = 'auto_update_description';
	const DEFAULT_EMAIL_DOMAIN = 'default_email_domain';
	const DUPLICATE_EMAIL_PREVENTION = 'duplicate_email_prevention';
	const PREVENT_EMAIL_CHANGE = 'prevent_email_change';
	const NAME_PATTERN = 'name_pattern';
	const SHOW_USER_STATUS = 'show_user_status';
    const ALLOW_DOWN_LEVEL_LOGON_NAME = 'allow_down_level_logon_name';

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

	// Single Sign On
	const SSO_ENABLED = 'sso';
	const SSO_USER = 'sso_user';
	const SSO_PASSWORD = 'sso_password';
	const SSO_ENVIRONMENT_VARIABLE = 'sso_environment_variable';

	// additional attribute mapping
	const ATTRIBUTES_COLUMN_TYPE = "type";
	const ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE = "wordpress_attribute";
	const ATTRIBUTES_COLUMN_OVERWRITE_EMPTY = "overwrite";
	const ATTRIBUTES_COLUMN_DESCRIPTION = "description";
	const ATTRIBUTES_COLUMN_SYNC_TO_AD = "sync_to_ad";
	const ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE= "view_in_userprofile";

	// Logger
	const LOGGER_ENABLE_LOGGING = 'logger_enable_logging';
	const LOGGER_CUSTOM_PATH = 'logger_custom_path';

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
			return (!$item[NextADInt_Multisite_Option_Attribute::TRANSIENT]);
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
		$title = NextADInt_Multisite_Option_Attribute::TITLE;
		$type = NextADInt_Multisite_Option_Attribute::TYPE;
		$description = NextADInt_Multisite_Option_Attribute::DESCRIPTION;
		$detail = NextADInt_Multisite_Option_Attribute::DETAIL;
		$elements = NextADInt_Multisite_Option_Attribute::ELEMENTS;

		// the key for the default value
		$default = NextADInt_Multisite_Option_Attribute::DEFAULT_VALUE;

		$disabled = NextADInt_Multisite_Option_Attribute::DISABLED;
		$disabledMessage = NextADInt_Multisite_Option_Attribute::DISABLED_MESSAGE;

		$sanitizer = NextADInt_Multisite_Option_Attribute::SANITIZER;
		$angularAttributes = NextADInt_Multisite_Option_Attribute::ANGULAR_ATTRIBUTES;
		$showPermission = NextADInt_Multisite_Option_Attribute::SHOW_PERMISSION;
		$transient = NextADInt_Multisite_Option_Attribute::TRANSIENT;

		return array(
			self::PROFILE_NAME      => array(
				$title          => __('Profile name:', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TEXT,
				$description => __(
					'Name for the current profile',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'Name for the current profile',
					NEXT_AD_INT_I18N
				),
				$sanitizer   => array('string'),
				$default => '',
				$angularAttributes => 'ng-disabled="((true)',
				$showPermission => false,
				$transient      => true,
			),
			self::SUPPORT_LICENSE_KEY      => array(
				$title          => __('Support license key:', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TEXT,
				$description => __(
					"Please enter your support license key here, if you have a paid ADI 2.0 license. It is required to receive support from <a href='https://neos-it.de'>NeosIT GmbH</a>.",
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					"Please enter your support license key here, if you have a paid ADI 2.0 license. It is required to receive support from <a href='https://neos-it.de'>NeosIT GmbH</a>.",
					NEXT_AD_INT_I18N
				),
				$sanitizer   => array('string'),
				$default => '',
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			self::IS_ACTIVE         => array(
				$title 		=> __('Enable ADI', NEXT_AD_INT_I18N),
				$type		=> NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enable/Disable Active Directory Integration',
					NEXT_AD_INT_I18N
				),
				$detail => __(
					'Active Directory Integration is only used if this checkbox is enabled. If you are running a WordPress Multisite you can force a disabled ADI plug-in for specific blogs.',
					NEXT_AD_INT_I18N
				),
				$default => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			self::SHOW_MENU_TEST_AUTHENTICATION	=> array(
				$title 		=> __(
					'Enable "Test authentication"',
					NEXT_AD_INT_I18N
				),
				$type		=> NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enable/Disable Test authentication',
					NEXT_AD_INT_I18N
				),
				$detail => __(
					'The menu entry "Test authentication" can be enabled or disabled, based upon this configuration',
					NEXT_AD_INT_I18N
				),
				$default        => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			self::SHOW_MENU_SYNC_TO_AD	=> array(
				$title 		=> __(
					'Enable "Sync to AD"',
					NEXT_AD_INT_I18N
				),
				$type		=> NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enable/Disable Sync to AD',
					NEXT_AD_INT_I18N
				),
				$detail => __(
					'The menu entry "Sync to AD" can be enabled or disabled, based upon this configuration',
					NEXT_AD_INT_I18N
				),
				$default        => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			self::SHOW_MENU_SYNC_TO_WORDPRESS	=> array(
				$title 		=> __(
					'Enable "Sync to WordPress"',
					NEXT_AD_INT_I18N
				),
				$type		=> NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enable/Disable Sync to WordPress',
					NEXT_AD_INT_I18N
				),
				$detail => __(
					'The menu entry "Sync to WordPress" can be enabled or disabled, based upon this configuration',
					NEXT_AD_INT_I18N
				),
				$default        => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient      => false,
			),
			// Domain Controllers (separate with semicolons)
			self::DOMAIN_CONTROLLERS            => array(
				$title       => __('Domain controllers', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'Domain controllers which will be used to authenticate and authorize the users',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'The domain controller represents the Active Directory server used to authenticate and authorize your users. You can find your currently set Domain Controller via "Start -> Run -> nslookup".',
					NEXT_AD_INT_I18N
				),
				$default     => '',
				$angularAttributes => '',
				$sanitizer   => array('string'),
				$showPermission => true,
				$transient      => false,
			),
			// Port on which AD listens (default 389)
			self::PORT                          => array(
				$title       => __('Port', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::NUMBER,
				$description => __('Port on which Active Directory listens. Unencrypted LDAP or STARTTLS uses port 389. LDAPS listens on port 636.', NEXT_AD_INT_I18N),
				$detail      => __(
					'This option defines the Active Directory communication port and is by default set to 389.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => 389,
				$sanitizer   => array('integerRange', 0, 65535),
				$showPermission    => true,
				$transient         => false,
			),
			// Secure the connection between the Drupal and the LDAP servers using START_TLS.
			self::ENCRYPTION => array(
				$title             => __('Use encryption', NEXT_AD_INT_I18N),
				$type              => NextADInt_Multisite_Option_Type::SELECT,
				$elements          => array(
					__('None', NEXT_AD_INT_I18N)     => NextADInt_Multisite_Option_Encryption::NONE,
					__('STARTTLS', NEXT_AD_INT_I18N) => NextADInt_Multisite_Option_Encryption::STARTTLS,
					__('LDAPS', NEXT_AD_INT_I18N)    => NextADInt_Multisite_Option_Encryption::LDAPS,
				),
				$description       => __('This option handles the encryption type for the LDAP connection.', NEXT_AD_INT_I18N),
				$detail            => array(
					__('This option handles the encryption type for the LDAP connection.',
						NEXT_AD_INT_I18N),
					__('None: No encryption is be used.', NEXT_AD_INT_I18N),
					__('STARTTLS: Enabling this option activates TLS (Transport Layer Security), which secures the data transport between your Active Directory server and WordPress by encrypting the data. If you want to use STARTTLS, the "Port" option should be set as default("389"). Please note that STARTTLS is not the same as LDAPS.', NEXT_AD_INT_I18N),
					__('LDAPS: The LDAP connection uses LDAPS. By default, Active Directory listens on port 636 for LDAPS connections.',
						NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default           => 'none',
				$sanitizer         => array('selection', 0),
				$showPermission    => true,
				$transient         => false,
			),
			// network timeout (LDAP_OPT_NETWORK_TIMEOUT) in seconds
			self::NETWORK_TIMEOUT               => array(
				$title       => __('LDAP network timeout', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::NUMBER,
				$description => __(
					'Seconds after the connection attempt to Active Directory times out. After this period WordPress falls back to local authentication. By default it is set to "5".',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'This option describes the time in seconds which has to pass after a connection attempt to the Active Dirctory server before the the connection times out and falls back to the local WordPress authentication. The default value for this option is "5".',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => 5,
				$sanitizer   => array('integerRange', 0, 'unlimited', 5),
				$showPermission    => true,
				$transient         => false,
			),
			// LDAP/AD BASE DN
			self::BASE_DN                       => array(
				$title       => __('Base DN', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TEXT,
				$description => __(
					'Base DN (e.g. "ou=unit,dc=domain,dc=tld" or "cn=users,dc=domain,dc=tld")', NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'You can find your Active Directory Base DN, if you follow this step by step guide correctly.',
						NEXT_AD_INT_I18N
					),
					__('1. Connect to your Active Directory server by using Remote Desktop.', NEXT_AD_INT_I18N),
					__('2. Start -> Administrative Tools -> Active Directory Users and Computers', NEXT_AD_INT_I18N),
					__('3. Click on your Domain Controller -> right-click Users -> Properties', NEXT_AD_INT_I18N),
					__('4. Attribute -> Select distinguishedName in the list -> press View', NEXT_AD_INT_I18N),
					__('5. Copy your Base DN.', NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// VERIFICATION USERNAME
			self::VERIFICATION_USERNAME            => array(
				$title       => __('Username', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TEXT,
				$description => __(
					'Username used to authenticated against the Active Directory in order to connect your WordPress site or profile to a domain (e.g. administration@test.ad)', NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'You have to enter a valid Active Directory account to connect your WordPress site to a specific Active Directory domain.',
						NEXT_AD_INT_I18N
					),
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// VERIFICATION PASSWORD
			self::VERIFICATION_PASSWORD            => array(
				$title       => __('Password', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::VERIFICATION_PASSWORD,
				$description => __(
					'Password used to authenticate against the Active Directory in order to connect your WordPress site or profile to a domain.' , NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'You have to enter the password for the Active Directory user in order to connect your WordPress site or profile to a domain.',
						NEXT_AD_INT_I18N
					),
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// DOMAINS ID
			self::DOMAIN_SID           => array(
				$title       => __('Domain Sid', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::DOMAIN_SID,
				$description => __(
					'Shows whether the current WordPress site is connected to an AD domain or not.', NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'Shows whether the current WordPress site is connected to an AD domain or not.',
						NEXT_AD_INT_I18N
					),
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// this usernames will always be excluded from ADI authentication
			self::EXCLUDE_USERNAMES_FROM_AUTHENTICATION => array(
				$title		=> __('Exclude usernames from authentication', NEXT_AD_INT_I18N),
				$type		=> NextADInt_Multisite_Option_Type::EDITABLE_LIST,
				$description	=> __(
					'Entered usernames will always be excluded from ADI login authentication. Usernames are case-insensitive.',
					NEXT_AD_INT_I18N
				),
				$detail 	=> array(
					__(
						'Every username you have entered will not be authenticated against the Active Directory, instead the WordPress authentication mechanism is used.',
						NEXT_AD_INT_I18N
					)
				),
				$default	=> '',
				$sanitizer   => array('accumulation', ';', array('string', false, true)),
				$showPermission => true,
				$transient      => false,
			),
			// account Suffix (will be appended to all usernames created in WordPress, as well as used in the Active Directory authentication process
			self::ACCOUNT_SUFFIX                   => array(
				$title       => __('Account suffix', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'This suffix will be appended to all usernames during the Active Directory authentication process, e.g. "@company.local".',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'The Account suffix is added to all usernames during the Active Directory authentication process.',
						NEXT_AD_INT_I18N
					),
					__(
						'Example: An *Account Suffix* "@company.local" is used. When the user "my_username" logs in, the fully username is set to "my_username@company.local".',
						NEXT_AD_INT_I18N
					),
					'<strong>' . __('Do not forget to start the suffix with "@".', NEXT_AD_INT_I18N) . '</strong>',
					__(
						'If you have multiple account suffixes like *@emea.company.local*, *@africa.company.local* enter each of them and put the primary domain name (@company.local) at the *last* position.',
						NEXT_AD_INT_I18N
					)
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('accumulation', ';', array('string', false, true)),
				$showPermission    => true,
				$transient         => false,
			),
			// Should the sAMAccountName be used for newly created users instead of the UserPrincipalName?
			self::USE_SAMACCOUNTNAME_FOR_NEW_USERS => array(
				$title       => __('Use sAMAccountName for newly created users', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'If checked, the sAMAccountName will be set as username for newly created users.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'Instead of using the user principal name for newly created users, the sAMAccountName will be used.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Should a new user be created automatically if not already in the WordPress database?
			self::AUTO_CREATE_USER              => array(
				$title       => __('Automatic user creation', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'If enabled, users will be created in your WordPress instance after they have successful authenticated.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'Created users will obtain the role defined under "New User Default Role" on the "General Options" tab. (Not Implemented Yet.)',
						NEXT_AD_INT_I18N
					),
					__('This option is separated from the Role Equivalent Groups option, below.', NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Should the users be updated in the WordPress database everytime they logon? (Works only if automatic user creation is set.
			self::AUTO_UPDATE_USER              => array(
				$title       => __('Automatic user synchronization', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __('After a successful login the WordPress profile of the user will be automatically synchronized with his Active Directory account.',
					NEXT_AD_INT_I18N),
				$detail      => array(
					__(
						'Enabling this option will result in ADI synchronizing the user\'s information every time they login in WordPress.',
						NEXT_AD_INT_I18N
					),
					__('Requires "Automatic user creation" to be enabled.', NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Update users description if $_auto_update_user is true
			self::AUTO_UPDATE_DESCRIPTION       => array(
				$title       => __('Automatic update user description', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Automatically updates the description of users who login and new created users', NEXT_AD_INT_I18N
				),
				$detail      => __(
					'This option will only work if you already have enabled "Automatic user creation" and "Automatic user update". As the title says it will automatically update the description of new created users and users who login.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Default Email Domain (eg. 'domain.tld')
			self::DEFAULT_EMAIL_DOMAIN          => array(
				$title       => __('Default email domain', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TEXT,
				$description => __(
					'If the Active Directory attribute "mail" is blank, a user\'s email will be set to username@ValueOfThisTextbox.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'If the Active Directory attribute "mail" is blank, a user\'s email will be set to username@ValueOfThisTextbox.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// How to deal with duplicate email addresses
			self::DUPLICATE_EMAIL_PREVENTION    => array(
				$title       => __('Email address conflict handling', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::SELECT,
				$elements    => array(
					__('Prevent (recommended)', NEXT_AD_INT_I18N) => 'prevent',
					__('Allow (UNSAFE)', NEXT_AD_INT_I18N)        => 'allow',
					__('Create', NEXT_AD_INT_I18N)                => 'create',
				),
				$description => __('This option handles email address conflicts caused by multiple user creation using the same email address.', NEXT_AD_INT_I18N),
				$detail      => array(
					__('This option handles email address conflicts caused by multiple user creation using the same email address. WordPress does only allow unique email addresses in an installation.',
						NEXT_AD_INT_I18N),
					__(
						'Prevent: User is not created, if his email address is already in use by another user. (recommended)',
						NEXT_AD_INT_I18N
					),
					__('Allow: Allow users to share one email address. (UNSAFE)', NEXT_AD_INT_I18N),
					__('Create: In case of a conflict, the new user is created with a unique and randomly generated email address.',
						NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default     => 'prevent',
				$sanitizer   => array('selection', 0),
				$showPermission    => true,
				$transient         => false,
			),
			// Prevent email change by ADI Users (not for admins)
			self::PREVENT_EMAIL_CHANGE          => array(
				$title       => __('Prevent email change', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Prevents users authenticated by Active Directory from changing their email address in WordPress.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'Activating this option will forbid users authenticated by Active Directory to change their email address in in WordPress.',
						NEXT_AD_INT_I18N
					),
					__('This option does not apply to the administrator.', NEXT_AD_INT_I18N),
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
				$title       => __('Display name', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::SELECT,
				$elements    => array(
					__('sAMAccountName (the username)', NEXT_AD_INT_I18N)     => 'samaccountname',
					__('displayName', NEXT_AD_INT_I18N)                       => 'displayname',
					__('description', NEXT_AD_INT_I18N)                       => 'description',
					__('givenName (firstname)', NEXT_AD_INT_I18N)             => 'givenname',
					__('SN (lastname)', NEXT_AD_INT_I18N)                     => 'sn',
					__('givenName SN (firstname lastname)', NEXT_AD_INT_I18N) => 'givenname sn',
					__('CN (Common Name, the whole name)', NEXT_AD_INT_I18N)  => 'cn',
					__('mail', NEXT_AD_INT_I18N)                              => 'mail',
				),
				$description       => __(
					'This option allows you to configure how users should be displayed at new posts/comments.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'This option allows you to configure how users should be displayed at new posts/comments.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => 'samaccountname', // TODO soll dieser Wert wirklich als default-Wert dienen?
				$sanitizer   => array('selection', 0),
				$showPermission    => true,
				$transient         => false,
			),
			// show disabled and ADI user status on user list
			self::SHOW_USER_STATUS              => array(
				$title       => __('Show user status', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __('Show additional columns (<em>ADI User</em>, <em>disabled</em>) in WordPress\' users list.',
					NEXT_AD_INT_I18N),
				$detail      => __('Show additional columns', NEXT_AD_INT_I18N),
				$default     => true,
				$sanitizer   => array('boolean'),
				$showPermission => true,
				$transient      => false,
			),
            // use local (WordPress) password as fallback if authentication against AD fails
            self::FALLBACK_TO_LOCAL_PASSWORD    => array(
                $title       => __('Fallback to local password', NEXT_AD_INT_I18N),
                $type        => NextADInt_Multisite_Option_Type::CHECKBOX,
                $description => __(
                    'Fallback to local(WordPress) password check if Active Directory authentication fails.', NEXT_AD_INT_I18N
                ),
                $detail      => array(
                    __(
                        'If option is enabled, users who failed authenticating against Active Directory can authenticate again against the local WordPress password check.',
                        NEXT_AD_INT_I18N
                    ),
                    __(
                        'But this might be a security risk (for example, if the local password is outdated). <b>It\'s recommended to turn this off.</b>',
                        NEXT_AD_INT_I18N
                    ),
                ),
                $angularAttributes => '',
                $default     => false,
                $sanitizer   => array('boolean'),
                $showPermission    => true,
                $transient         => false,
            ),
			// Use the real password when a user is created
			self::NO_RANDOM_PASSWORD            => array(
				$title       => __('Set local password on first successful login', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'The first time a user logs on his local password will be equated with the password he used to authenticate against the Active Directory.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'The first time a user logs on his local password will be equated with the password he used to authenticate against the Active Directory. If this option is deactivated a random password for this user will be set.',
						NEXT_AD_INT_I18N
					),
					__('If this option is deactivated a random password for this user will be set.', NEXT_AD_INT_I18N),
					__('The option does only work if *User > Automatic user creation* is enabled.', NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Enable/Disable password changes
			self::ENABLE_PASSWORD_CHANGE        => array(
				$title       => __('Allow local password changes', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enabling this option allows users to change their local WordPress password.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__('Enabling this option allows users to change their local WordPress password.', NEXT_AD_INT_I18N),
					__('This option has <strong>no</strong> effect to the Active Directory password and will <strong>not</strong> synchronized back to the Active Directory.',
						NEXT_AD_INT_I18N),
					__('This option is only be used after the WordPress authentication against the AD fails because of a network timeout.',
						NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Update password on every successful login
			self::AUTO_UPDATE_PASSWORD          => array(
				$title       => __('Automatic password update', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'This option updates the local password every time a user successfully logs in.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'This option updates the local password every time a user successfully logs in. If a user has changed his Active Directory password and successfully authenticates against Active Directory while trying to login to WordPress, his local WordPress password will be equated with the new Active Directory password.',
						NEXT_AD_INT_I18N
					),
					__(
						'Note: Activating this option makes little sense if <em>Allow local password changes</em> is enabled.',
						NEXT_AD_INT_I18N
					),
					__('Works only if <em>User &gt; Automatic user creation</em> and <em>User &gt; Automatic user synchronization</em> is enabled.',
						NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Check Login authorization by group membership
			self::AUTHORIZE_BY_GROUP            => array(
				$title       => __('Authorize by group membership', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'This option authorizes only members of the given Active Directory security groups to gain access to WordPress.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'This option authorizes only members of the given Active Directory security groups to gain access to WordPress. This authorization occurs <strong>after</strong> the authentication.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Group name for authorization.
			self::AUTHORIZATION_GROUP           => array(
				$title       => __('Authorization group(s)', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'If not empty, only the defined security groups have access to WordPress.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'Please define your Authorization group(s) here.',
						NEXT_AD_INT_I18N
					),
					__('Works only if "User > Automatic user creation" and "User > Automatic user synchronization" are enabled.',
						NEXT_AD_INT_I18N),
				),
				$angularAttributes => 'ng-disabled="((!option.authorize_by_group) || ((permission.authorization_group == 2) || (permission.authorization_group == 1))',
				$default     => '',
				$sanitizer   => array('accumulation', ';', array('string')),
				$showPermission    => true,
				$transient         => false,
			),
			// Role Equivalent Groups (wp-role1=ad-group1;wp-role2=ad-group2;...)
			self::ROLE_EQUIVALENT_GROUPS        => array(
				$title       => __('Role equivalent groups', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TABLE,
				$description => __(
					'List of one or multiple Active Directory security groups which correspond to WordPress\' roles.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'Please enter the names of the Active Directory security groups which correspond to WordPress user roles using the following schema.',
						NEXT_AD_INT_I18N
					),
					__('Example:', NEXT_AD_INT_I18N),
					__('ad-group=wp-role;', NEXT_AD_INT_I18N),
					__('wordpressadmins=administrator;', NEXT_AD_INT_I18N),
					__('wordpressmoderator=editor;', NEXT_AD_INT_I18N),
					__('wordpressuser=contributor;', NEXT_AD_INT_I18N),
					__(
						'You can find a whole table of Roles and Capabilities at: ' . "<a href="
						. 'http://codex.wordpress.org/Roles_and_Capabilities'
						. '>http://codex.wordpress.org/Roles_and_Capabilities</a>' . ' (3.8 Capability vs. Role Table)',
						NEXT_AD_INT_I18N
					),
					__(
						'Group memberships cannot be checked across domains.  So if you have two domains, instr and qc, and qc is the domain specified above, if instr is linked to qc, I can authenticate instr users, but not check instr group memberships.',
						NEXT_AD_INT_I18N
					),
				),
				$angularAttributes => 'ng-disabled="(((permission.role_equivalent_groups == 2) || (permission.role_equivalent_groups == 1))',
				$default     => '',
				$sanitizer   => array('accumulation', ';', array('valueAssignment', '=')),
				$showPermission    => true,
				$transient         => false,
			),
            // allow the pre-Windows 2000 user logon name
            self::ALLOW_DOWN_LEVEL_LOGON_NAME => array(
                $title => __('Allow Down-Level Logon Name for login', NEXT_AD_INT_I18N),
                $type => NextADInt_Multisite_Option_Type::CHECKBOX,
                $description => __('Allow Down-Level Logon Name (also known as pre-Windows 2000 user logon name) like TEST\klammer for login.', NEXT_AD_INT_I18N),
                $detail => array(
                    __('This option is disabled by default because the Down-Level Logon Name is not unique.', NEXT_AD_INT_I18N),
                    __('Make sure that nobody has an already used Down-Level Logon Name.', NEXT_AD_INT_I18N),
                ),
                $angularAttributes => '',
                $default => false,
                $sanitizer => array('boolean'),
                $showPermission => true,
                $transient => false,
            ),
			// Enable lost password recovery
			self::ENABLE_LOST_PASSWORD_RECOVERY => array(
				$title       => __('Enable lost password recovery', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __('The user can reset his password in the login screen.', NEXT_AD_INT_I18N),
				$detail      => __(
					'Enabling this option will allow users to reset their local password in the login screen.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Settings for Autologin
			self::SSO_ENABLED                           => array(
				$title             => __('Enable SSO', NEXT_AD_INT_I18N),
				$type              => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description       => __(
					'This option will grant users the possibility to Single Sign On WordPress once they got authenticated against Active Directory.',
					NEXT_AD_INT_I18N
				),
				$detail            => __(
					'This option will grant users the possibility to Single Sign On WordPress once they got authenticated against Active Directory.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default           => false,
				$sanitizer         => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			self::SSO_USER                              => array(
				$title             => __('Service account username', NEXT_AD_INT_I18N),
				$type              => NextADInt_Multisite_Option_Type::TEXT,
				$description       => __(
					'Username of an Active Directory account with read permissions for the users in the Active Directory (e.g. "ldapuser@company.local").',
					NEXT_AD_INT_I18N
				),
				$detail            => __(
					'Username of an Active Directory account with read permissions for the users in the Active Directory (e.g. "ldapuser@company.local").',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sso) || ((permission.sso == 2) || (permission.sso == 1))',
				$default           => '',
				$sanitizer         => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			self::SSO_PASSWORD                          => array(
				$title             => __('Service account password', NEXT_AD_INT_I18N),
				$type              => NextADInt_Multisite_Option_Type::PASSWORD,
				$description       => __('Password of an Active Directory account with read permissions for the users in the Active Directory.',
					NEXT_AD_INT_I18N),
				$detail            => __(
					'Password of an Active Directory account with read permissions for the users in the Active Directory.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sso) || ((permission.sso == 2) || (permission.sso == 1))',
				$default           => '',
				$sanitizer         => array('string', false, false),
				$showPermission    => true,
				$transient         => false,
			),
			self::SSO_ENVIRONMENT_VARIABLE              => array(
				$title             => __('Username variable', NEXT_AD_INT_I18N),
				$type              => NextADInt_Multisite_Option_Type::SELECT,
				$elements          => array(
					NextADInt_Adi_Authentication_SingleSignOn_Variable::REMOTE_USER   => NextADInt_Adi_Authentication_SingleSignOn_Variable::REMOTE_USER,
					NextADInt_Adi_Authentication_SingleSignOn_Variable::X_REMOTE_USER => NextADInt_Adi_Authentication_SingleSignOn_Variable::X_REMOTE_USER,
				),
				$description       => __(
					'The PHP server variable which is used by the web server to retrieve the current user!',
					NEXT_AD_INT_I18N
				),
				$detail            => array(
					sprintf(
						__('%s: The default server variable.', NEXT_AD_INT_I18N),
						NextADInt_Adi_Authentication_SingleSignOn_Variable::REMOTE_USER
					),
					sprintf(
						__('%s: Is used when working with proxies. The variable "REMOTE_USER" must be forwarded from the proxy.',
							NEXT_AD_INT_I18N),
						NextADInt_Adi_Authentication_SingleSignOn_Variable::X_REMOTE_USER
					),
				),
				$angularAttributes => 'ng-disabled="((!option.sso) || ((permission.sso == 2) || (permission.sso == 1))',
				$default           => NextADInt_Adi_Authentication_SingleSignOn_Variable::REMOTE_USER,
				$sanitizer         => array('selection', 0),
				$showPermission    => true,
				$transient         => false,
			),
			// Maximum number of failed login attempts before the account is blocked
			self::MAX_LOGIN_ATTEMPTS            => array(
				$title       => __('Maximum number of allowed login attempts', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::NUMBER,
				$description => __(
					'Maximum number of failed login attempts before a user account is blocked. If empty or "0" <em>Brute Force Protection</em> is disabled.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'Please enter the amount of tries a user has to login with his correct username and password combination before he gets blocked for the specific time.',
						NEXT_AD_INT_I18N
					),
					__(
						'You can set the time below this option.',
						NEXT_AD_INT_I18N
					),
					__(
						'If you want to disable the bruteforce protection just set the Maximum number of allowed login attempts to 0.',
						NEXT_AD_INT_I18N
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
				$title       => __('Blocking time', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::NUMBER,
				$description => __(
					'Time in seconds for which the account is blocked if too many failed login attempts occurred.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'With help of this option you can set the time a user is being blocked after the amount of failed login attempts has been reached..',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => 30,
				$sanitizer   => array('integerRange', 0, 'unlimited', 30),
				$showPermission    => true,
				$transient         => false,
			),
			// Send email to user if his account is blocked.
			self::USER_NOTIFICATION             => array(
				$title       => __('Notify users of blocked account', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __('Notify user by e-mail when his account has been blocked.', NEXT_AD_INT_I18N),
				$detail      => __(
					'If enabled *ADI* notifies the user by email about the circumstance that his account has been blocked.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Send email to admin if a user account is blocked.
			self::ADMIN_NOTIFICATION            => array(
				$title       => __('Notify admins of blocked account', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __('Notify admin(s) by e-mail when an user account has been blocked.', NEXT_AD_INT_I18N),
				$detail      => __(
					'Enabling this option will notify the admin(s) about blocked user accounts as well.', NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Administrator's email address(es) where notifications should be sent to.
			self::ADMIN_EMAIL                   => array(
				$title       => __('Email addresses for notifications', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'Given email addresses will recieve emails for admin notifications.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'If the admin notification event is triggered and no admin email has been set, the email is forwarded to the blog administrator\'s email address.',
					NEXT_AD_INT_I18N
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
				$title                                         => __('Additional User Attributes', NEXT_AD_INT_I18N),
				$type                                          => NextADInt_Multisite_Option_Type::CUSTOM,
				NextADInt_Multisite_Option_Attribute::TYPE_STRUCTURE => array(
					// first input field; the user can use a well known user attribute or can use his own user attribute
					array(
						$type        => NextADInt_Multisite_Option_Type::COMBOBOX,
						$description => __('AD User Attribute:', NEXT_AD_INT_I18N),
						$elements    => NextADInt_Ldap_Attribute_Description::findAll(),
						$sanitizer   => array('string', false, true, true),
					),
					// second input field; the user can only should between certain data types
					array(
						$type        => NextADInt_Multisite_Option_Type::SELECT,
						$description => __('Data Type:', NEXT_AD_INT_I18N),
						$elements    => array(
							__('Only a text', NEXT_AD_INT_I18N)         => 'string',
							__('An array with texts', NEXT_AD_INT_I18N) => 'list',
							__('Natural number', NEXT_AD_INT_I18N)      => 'integer',
							__('Boolean', NEXT_AD_INT_I18N)             => 'bool',
							__('Octet', NEXT_AD_INT_I18N)               => 'octet',
							__('Time', NEXT_AD_INT_I18N)                => 'time',
							__('Timestamp', NEXT_AD_INT_I18N)           => 'timestamp',
							__('CN', NEXT_AD_INT_I18N)                  => 'cn',
						),
						$angularAttributes => '',
						$sanitizer   => array('selection'),
					),
					array(
						$type        => NextADInt_Multisite_Option_Type::TEXT,
						$description => __('User Meta Key:', NEXT_AD_INT_I18N),
						$sanitizer   => array('string'),
					),
				),
				$description                                   => __(
					'Enter additional Active Directory attributes.',
					NEXT_AD_INT_I18N
				),
				$detail                                        => array(
					__(
						'Additional Attributes that should appear on the user profile must also be placed in "Attributes to show".',
						NEXT_AD_INT_I18N
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
				$title       => __('Enable sync to AD', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Synchronize the user\'s WordPress profile back to Active Directory.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'By enabling this option ADI will automatically synchronize the user\'s profile back to the Active Directory if they have changed.',
						NEXT_AD_INT_I18N
					),
					__('The synchronization is automatically triggered whenever a user profile gets updated.',
						NEXT_AD_INT_I18N),
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Use global Sync to AD User
			self::SYNC_TO_AD_USE_GLOBAL_USER => array(
				$title       => __('Use Sync To AD service account', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'By enabling this option users will not be asked for their Active Directory password when updating their WordPress user profile. Instead a central Active Directory service account is used.<br />If this option is <strong>disabled</strong>, you have to configure your Active Directory properyly.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'By enabling this option users will not be asked for their Active Directory password when updating their WordPress user profile. Instead a central Active Directory service account will be used.',
						NEXT_AD_INT_I18N
					),
					__(
						'If this option is disabled, you need to set the required permissions in the Active Directory. Please refer to the documentation on how to do this.',
						NEXT_AD_INT_I18N
					),
					__(
						'NOTICE: The password of this service account is stored encrypted, but **USE IT AT YOUR OWN RISK**. To avoid this you have to grant your users the permission to change their own AD attributes. See FAQ for details.',
						NEXT_AD_INT_I18N
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
				$title       => __('Service account username', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TEXT,
				$description => __(
					'Username of an Active Directory account with write permissions for the users in the Active Directory (e.g. administrator@company.local).',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'If you define a Sync To AD service account with write permissions inside the Active Directory, changes will automatically be written in the Active Directory database through this user.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad)||(!option.sync_to_ad_use_global_user) || ((permission.sync_to_ad_global_user == 2) || (permission.sync_to_ad_global_user == 1))',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// Password of global Dd user
			self::SYNC_TO_AD_GLOBAL_PASSWORD => array(
				$title       => __('Service account password', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::PASSWORD,
				$description => __(
					'Password for Sync To AD service account. Leave empty if password should not be changed.', NEXT_AD_INT_I18N
				),
				$detail      => __(
					'This option defines a NEW password for the Sync To AD service account. Leave this field blank if you do not want to change the password.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad)||(!option.sync_to_ad_use_global_user) || ((permission.sync_to_ad_global_password == 2) || (permission.sync_to_ad_global_password == 1))', //
				$default     => '',
				$sanitizer   => array('string', false, false),
				$showPermission    => true,
				$transient         => false,
			),
			// AUTHCODE for Sync to AD. Sync to AD will only work, if this AUTHCODE is send as a post-parameter to the blog index.php
			self::SYNC_TO_AD_AUTHCODE       => array(
				$title       => __('Auth code', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::AUTHCODE,
				$description => __(
					'This code is needed for Sync to AD.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'The authentication code is used to authenticate the global sync user against the Active Directory.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad) || ((permission.sync_to_ad_authcode == 2) || (permission.sync_to_ad_authcode == 1))',
				$default => '',
				NextADInt_Multisite_Option_Attribute::PERSIST_DEFAULT_VALUE => true,
				$sanitizer   => array('authcode'),
				$showPermission                                   => true,
				$transient                                        => false,
			),
			// enable Sync to WordPress
			self::SYNC_TO_WORDPRESS_ENABLED  => array(
				$title       => __('Enable sync to WordPress', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Enabling this option will allow ADI to sync users from the Active Directory to the WordPress Database.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'Enabling this option will allow ADI to sync users from the Active Directory to the WordPress Database.',
						NEXT_AD_INT_I18N
					),
					__(
						'Hint: You could use a cron job, which is nothing more than a timetable executing the Sync to WordPress/update at from you defined times, to keep your WordPress database always up to date with your Active Directory user information.',
						NEXT_AD_INT_I18N
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
				$title       => __('Auth code', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::AUTHCODE,
				$description => __('This code is needed for Sync to WordPress.', NEXT_AD_INT_I18N),
				$detail      => __(
					'This code is needed for Sync to WordPress.',
					NEXT_AD_INT_I18N
				),
				$default => '',
				NextADInt_Multisite_Option_Attribute::PERSIST_DEFAULT_VALUE => true,
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_authcode == 2) || (permission.sync_to_wordpress_authcode == 1))',
				$sanitizer   => array('authcode'),
				$showPermission                                   => true,
				$transient                                        => false,
			),
			// Import members of these security groups (separated by semicolons)
			self::SYNC_TO_WORDPRESS_SECURITY_GROUPS => array(
				$title       => __('Import members of security groups', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::EDITABLE_LIST,
				$description => __(
					'The members of the security groups entered here will be imported. See documentation how to import members of Domain Users or Domain Administrators.',
					NEXT_AD_INT_I18N
				),
				$detail      => array(
					__(
						'Here you can enter the security groups which will be imported every time Sync To WordPress is executed.',
						NEXT_AD_INT_I18N
					),
					__(
						'If you want to include the users of the built in user group "Domain Users" you have to enter "id:513". More information can be found in the official documentation.',
						NEXT_AD_INT_I18N
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
				$title       => __('Service account username', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TEXT,
				$description => __(
					'Username of an Active Directory account with read permissions for the users in the Active Directory (e.g. "ldapuser@company.local").',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'In order to import multiple users at once you have to define a user with Active Directory users read permission.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_user == 2) || (permission.sync_to_wordpress_user == 1))',
				$default     => '',
				$sanitizer   => array('string'),
				$showPermission    => true,
				$transient         => false,
			),
			// password for Sync to WordPress User (will be stored encrypted)
			self::SYNC_TO_WORDPRESS_PASSWORD      => array(
				$title       => __('Service account password', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::PASSWORD,
				$description => __('Password for Sync to WordPress User.', NEXT_AD_INT_I18N),
				$detail      => __(
					'As you already defined the Sync to WordPress service account username before you now have to define the password for this user as well.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_user == 2) || (permission.sync_to_wordpress_user == 1))',
				$default     => '',
				$sanitizer   => array('string', false, false),
				$showPermission    => true,
				$transient         => false,
			),
			// Prevent email change by ADI Users (not for admins)
			self::SYNC_TO_WORDPRESS_DISABLE_USERS => array(
				$title       => __('Automatic deactivate users', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'Deactivated users can only be re-activated manually by administrators on their users profile page or by using the *Sync to WordPress* feature.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'Deactivated users can only be re-activated manually by administrators on their users profile page or by using the *Sync to WordPress* feature.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.disable_users == 2) || (permission.disable_users == 1))',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Prevent email change by ADI Users (not for admins)
			self::LOGGER_ENABLE_LOGGING => array(
				$title       => __('Enable Logging', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::CHECKBOX,
				$description => __(
					'If activated NADI will create a logfile.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'If activated NADI will create a logfile and start logging to it. Default path is "PLUGINPATH/logs/debug.log"',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => '',
				$default     => false,
				$sanitizer   => array('boolean'),
				$showPermission    => true,
				$transient         => false,
			),
			// Prevent email change by ADI Users (not for admins)
			self::LOGGER_CUSTOM_PATH => array(
				$title       => __('Custom Path', NEXT_AD_INT_I18N),
				$type        => NextADInt_Multisite_Option_Type::TEXT,
				$description => __(
					'If you don´t have permission for the default path on your system you can set a new path. The logfile will be created at the set location.',
					NEXT_AD_INT_I18N
				),
				$detail      => __(
					'If you don´t have permission for the default path on your system you can set a new path. The logfile will be created at the set location.',
					NEXT_AD_INT_I18N
				),
				$angularAttributes => 'ng-disabled="((!option.logger_enable_logging) || ((permission.logger_enable_logging == 2) || (permission.logger_enable_logging == 1))',
				$default     => '',
				$sanitizer   => array('string'),
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
		$adAttribute = __('AD Attribute', NEXT_AD_INT_I18N);
		$description = __('Description', NEXT_AD_INT_I18N);

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