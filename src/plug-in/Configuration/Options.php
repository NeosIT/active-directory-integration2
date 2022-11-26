<?php

namespace Dreitier\Nadi\Configuration;

use Dreitier\Ldap\Attribute\Description;
use Dreitier\Nadi\Authentication\SingleSignOn\Variable;
use Dreitier\WordPress\Multisite\Option\Type;
use Dreitier\WordPress\Multisite\Option\Attribute;
use Dreitier\WordPress\Multisite\Option\Encryption;
use Dreitier\WordPress\Multisite\Option\Provider;

/**
 * Options contains names and the structure of elements displayed on the settings page.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class Options implements Provider
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
	const ALLOW_SELF_SIGNED = 'allow_self_signed';
	const NETWORK_TIMEOUT = 'network_timeout';
	const BASE_DN = 'base_dn';
	const VERIFICATION_USERNAME = 'verification_username';
	const VERIFICATION_PASSWORD = 'verification_password';
	const DOMAIN_SID = 'domain_sid';
	const NETBIOS_NAME = 'netbios_name';
	const ADDITIONAL_DOMAIN_SIDS = 'additional_domain_sids';

	// Forest
	const KERBEROS_REALM_MAPPINGS = 'kerberos_realm_mappings';

	// User - User Settings
	const EXCLUDE_USERNAMES_FROM_AUTHENTICATION = 'exclude_usernames_from_authentication';
	const ACCOUNT_SUFFIX = 'account_suffix';
	const ALLOW_PROXYADDRESS_LOGIN = 'allow_proxyaddress_login';
	const USE_SAMACCOUNTNAME_FOR_NEW_USERS = 'use_samaccountname_for_new_users';
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
	const CLEAN_EXISTING_ROLES = 'clean_existing_roles';

	// Security
	const FALLBACK_TO_LOCAL_PASSWORD = 'fallback_to_local_password';
	const ENABLE_LOST_PASSWORD_RECOVERY = 'enable_lost_password_recovery';
	const ENABLE_SMARTCARD_USER_LOGIN = 'enable_smartcard_user_login';
	const ALLOW_XMLRPC_LOGIN = 'allow_xmlrpc_login';

	// User Meta - User Meta
	const ADDITIONAL_USER_ATTRIBUTES = 'additional_user_attributes';
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
	const SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS = 'sync_to_wordpress_import_disabled_users';

	// Security - Single Sign On
	const SSO_ENABLED = 'sso';
	const SSO_USER = 'sso_user';
	const SSO_PASSWORD = 'sso_password';
	const SSO_ENVIRONMENT_VARIABLE = 'sso_environment_variable';
	const SSO_DISABLE_FOR_XMLRPC = 'sso_disable_for_xmlrpc';

	// Custom Login Page
	const CUSTOM_LOGIN_PAGE_ENABLED = 'custom_login_page_enabled';
	const CUSTOM_LOGIN_PAGE_URI = 'custom_login_page_uri';

	// additional attribute mapping
	const ATTRIBUTES_COLUMN_TYPE = "type";
	const ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE = "wordpress_attribute";
	const ATTRIBUTES_COLUMN_OVERWRITE_EMPTY = "overwrite";
	const ATTRIBUTES_COLUMN_DESCRIPTION = "description";
	const ATTRIBUTES_COLUMN_SYNC_TO_AD = "sync_to_ad";
	const ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE = "view_in_userprofile";

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

		return array_filter($data, function ($item) {
			return (!$item[Attribute::TRANSIENT]);
		});
	}

	/**
	 * This method generates all the meta information for an option elements.
	 * The keys (like self::DOMAIN_CONTROLLERS, self::PORT) and their values are option elements.
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
		$title = Attribute::TITLE;
		$type = Attribute::TYPE;
		$description = Attribute::DESCRIPTION;
		$detail = Attribute::DETAIL;
		$elements = Attribute::ELEMENTS;

		// the key for the default value
		$default = Attribute::DEFAULT_VALUE;

		$disabled = Attribute::DISABLED;
		$disabledMessage = Attribute::DISABLED_MESSAGE;

		$sanitizer = Attribute::SANITIZER;
		$angularAttributes = Attribute::ANGULAR_ATTRIBUTES;
		$angularButtonAttributes = Attribute::ANGULAR_BUTTON_ATTRIBUTES;
		$showPermission = Attribute::SHOW_PERMISSION;
		$transient = Attribute::TRANSIENT;

		return array(
			self::PROFILE_NAME => array(
				$title => __('Profile name:', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					'Name for the current profile',
					'next-active-directory-integration'
				),
				$detail => __(
					'Name for the current profile',
					'next-active-directory-integration'
				),
				$sanitizer => array('string'),
				$default => '',
				$angularAttributes => 'ng-disabled="((true)',
				$showPermission => false,
				$transient => true,
			),
			self::SUPPORT_LICENSE_KEY => array(
				$title => __('Support plan key:', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					"Please enter your <em>Support plan</em>'s key here, if you have purchased <a href='https://active-directory-wp.com/shop-overview/'>Support plan</a>. It is required to receive professional support from <a href='https://active-directory-wp.com'>active-directory-wp.com</a>.",
					'next-active-directory-integration'
				),
				$detail => __(
					"Please enter your <em>Support plan</em>'s key here, if you have purchased <a href='https://active-directory-wp.com/shop-overview/'>Support plan</a>. It is required to receive professional support from <a href='https://active-directory-wp.com'>active-directory-wp.com</a>.",
					'next-active-directory-integration'
				),
				$sanitizer => array('string'),
				$default => '',
				$angularAttributes => '',
				$showPermission => true,
				$transient => false,
			),
			self::IS_ACTIVE => array(
				$title => __('Enable NADI', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Enable/Disable Next Active Directory Integration',
					'next-active-directory-integration'
				),
				$detail => __(
					'Next Active Directory Integration is only used if this option is checked. If you are running a WordPress Multisite Installation you can disable the  NADI plug-in for specific sites.',
					'next-active-directory-integration'
				),
				$default => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient => false,
			),
			self::SHOW_MENU_TEST_AUTHENTICATION => array(
				$title => __(
					'Enable "Test authentication"',
					'next-active-directory-integration'
				),
				$type => Type::CHECKBOX,
				$description => __(
					'Enable/Disable Test authentication',
					'next-active-directory-integration'
				),
				$detail => __(
					'The menu entry "Test authentication" can be enabled or disabled, based upon this configuration',
					'next-active-directory-integration'
				),
				$default => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient => false,
			),
			self::SHOW_MENU_SYNC_TO_AD => array(
				$title => __(
					'Enable "Sync to AD"',
					'next-active-directory-integration'
				),
				$type => Type::CHECKBOX,
				$description => __(
					'Enable/Disable Sync to AD',
					'next-active-directory-integration'
				),
				$detail => __(
					'The menu entry "Sync to AD" can be enabled or disabled, based upon this configuration',
					'next-active-directory-integration'
				),
				$default => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient => false,
			),
			self::SHOW_MENU_SYNC_TO_WORDPRESS => array(
				$title => __(
					'Enable "Sync to WordPress"',
					'next-active-directory-integration'
				),
				$type => Type::CHECKBOX,
				$description => __(
					'Enable/Disable Sync to WordPress',
					'next-active-directory-integration'
				),
				$detail => __(
					'The menu entry "Sync to WordPress" can be enabled or disabled, based upon this configuration',
					'next-active-directory-integration'
				),
				$default => true,
				$angularAttributes => '',
				$showPermission => true,
				$transient => false,
			),
			// Domain Controllers (separate with semicolons)
			self::DOMAIN_CONTROLLERS => array(
				$title => __('Domain controllers', 'next-active-directory-integration'),
				$type => Type::EDITABLE_LIST,
				$description => __(
					'Domain controllers used to authenticate and authorize the users',
					'next-active-directory-integration'
				),
				$detail => __(
					'The domain controller represents the Active Directory server used to authenticate and authorize your users. You can find your currently set Domain Controller via "Start -> Run -> nslookup".',
					'next-active-directory-integration'
				),
				$default => '',
				$angularAttributes => '',
				$angularButtonAttributes => 'ng-show="!$parent.is_input_empty(new_domain_controllers)"',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// Port on which AD listens (default 389)
			self::PORT => array(
				$title => __('Port', 'next-active-directory-integration'),
				$type => Type::NUMBER,
				$description => __('Port on which the Active Directory listens. Unencrypted LDAP or STARTTLS use port 389. LDAPS listens on port 636.', 'next-active-directory-integration'),
				$detail => __(
					'This option defines the Active Directory communication port and is by default set to 389.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => 389,
				$sanitizer => array('integerRange', 0, 65535),
				$showPermission => true,
				$transient => false,
			),
			// Secure the connection between the Drupal and the LDAP servers using START_TLS.
			self::ENCRYPTION => array(
				$title => __('Use encryption', 'next-active-directory-integration'),
				$type => Type::SELECT,
				$elements => array(
					__('None', 'next-active-directory-integration') => Encryption::NONE,
					__('STARTTLS', 'next-active-directory-integration') => Encryption::STARTTLS,
					__('LDAPS', 'next-active-directory-integration') => Encryption::LDAPS,
				),
				$description => __('This option handles the encryption type for the LDAP connection.', 'next-active-directory-integration'),
				$detail => array(
					__('This option handles the encryption type for the LDAP connection.',
						'next-active-directory-integration'),
					__('None: No encryption is be used.', 'next-active-directory-integration'),
					__('STARTTLS: Enabling this option activates TLS (Transport Layer Security), which secures the data transport between your Active Directory server and WordPress by encrypting the data. If you want to use STARTTLS, the "Port" option should be set as default("389"). Please note that STARTTLS is not the same as LDAPS.', 'next-active-directory-integration'),
					__('LDAPS: The LDAP connection uses LDAPS. By default, Active Directory listens on port 636 for LDAPS connections.',
						'next-active-directory-integration'),
				),
				$angularAttributes => '',
				$default => 'none',
				$sanitizer => array('selection', 0),
				$showPermission => true,
				$transient => false,
			),
			// self-signed certificate (LDAP_OPT_X_TLS_REQUIRE_CERT) Boolean
			self::ALLOW_SELF_SIGNED => array(
				$title => __('Allow self-signed certificates', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Allow self-signed certificates',
					'next-active-directory-integration'
				),
				$detail => __(
					'The option <em>Allow self-signed certificates</em> can be used to let NADI accept self-signed certificates without installing them on the host running this WordPress instance.',
					'next-active-directory-integration'
				),
				$default => NULL,
				$sanitizer => array('boolean'),
				$angularAttributes => '',
				$showPermission => true,
				$transient => false,
			),
			// network timeout (LDAP_OPT_NETWORK_TIMEOUT) in seconds
			self::NETWORK_TIMEOUT => array(
				$title => __('LDAP network timeout', 'next-active-directory-integration'),
				$type => Type::NUMBER,
				$description => __(
					'Seconds after the connection attempt to Active Directory times out. After this period WordPress falls back to local authentication. By default it is set to "5".',
					'next-active-directory-integration'
				),
				$detail => __(
					'This option describes the time in seconds which has to pass after a connection attempt to the Active Dirctory server before the the connection times out and falls back to the local WordPress authentication. The default value for this option is "5".',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => 5,
				$sanitizer => array('integerRange', 0, 'unlimited', 5),
				$showPermission => true,
				$transient => false,
			),
			// LDAP/AD BASE DN
			self::BASE_DN => array(
				$title => __('Base DN', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					'Base DN (e.g. <em>dc=domain,dc=tld</em> or <em>ou=unit,dc=domain,dc=tld</em> or <em>cn=users,dc=domain,dc=tld</em>). The correct <em>Base DN</em> depends upon how your Active Directory has been set up.', 'next-active-directory-integration'
				),
				$detail => array(
					__(
						'You can find your Active Directory Base DN, if you follow this step by step guide correctly.',
						'next-active-directory-integration'
					),
					__('1. Connect to your Active Directory server by using Remote Desktop.', 'next-active-directory-integration'),
					__('2. Start -> Administrative Tools -> Active Directory Users and Computers', 'next-active-directory-integration'),
					__('3. Click on your Domain Controller -> right-click Users -> Properties', 'next-active-directory-integration'),
					__('4. Attribute -> Select distinguishedName in the list -> press View', 'next-active-directory-integration'),
					__('5. Copy your Base DN.', 'next-active-directory-integration'),
				),
				$angularAttributes => '',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// VERIFICATION USERNAME
			self::VERIFICATION_USERNAME => array(
				$title => __('Username', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					'Username used to authenticate against the Active Directory in order to connect your WordPress site or profile to a domain (e.g. <em>administrator@ad.company.com</em>)', 'next-active-directory-integration'
				),
				$detail => array(
					__(
						'You have to enter a valid Active Directory account to connect your WordPress site to a specific Active Directory domain.',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => '',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// VERIFICATION PASSWORD
			self::VERIFICATION_PASSWORD => array(
				$title => __('Password', 'next-active-directory-integration'),
				$type => Type::VERIFICATION_PASSWORD,
				$description => __(
					'Password used to authenticate against the Active Directory in order to connect your WordPress site or profile to a domain.', 'next-active-directory-integration'
				),
				$detail => array(
					__(
						'You have to enter the password for the Active Directory user in order to connect your WordPress site or profile to a domain.',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => '',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// DOMAINS ID
			self::DOMAIN_SID => array(
				$title => __('Domain SID', 'next-active-directory-integration'),
				$type => Type::DOMAIN_SID,
				$description => __(
					'Shows if the current WordPress site is connected to an Active Directory domain.', 'next-active-directory-integration'
				),
				$detail => array(
					__(
						'Shows if the current WordPress site is connected to an Active Directory domain.',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => '',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			self::NETBIOS_NAME => array(
				$title => __('NetBIOS name', 'next-active-directory-integration'),
				$type => Type::LABEL,
				$description => __(
					'The NetBIOS name of the connected Active Directory is required for NTLM SSO.', 'next-active-directory-integration'
				),
				$detail => array(
					__(
						'The NetBIOS name of the connected Active Directory is required for NTLM SSO.',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => '',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// additional domain SIDs when using a Global Catalog
			self::ADDITIONAL_DOMAIN_SIDS => array(
				$title => __('Additional domain SIDs', 'next-active-directory-integration'),
				$type => Type::EDITABLE_LIST,
				$description => __(
					'Recognize users during import from these domain SIDs. The specified SIDs are used in <strong>addition</strong> to the already connected domain above.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'When using a <em>Global Catalog</em> handling more than one domain, you have to specify each additional domain inside it',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => '',
				$angularButtonAttributes => 'ng-show="!$parent.is_input_empty(new_additional_domain_sids)"',
				$default => '',
				$sanitizer => array('accumulation', ';', array('string', false, true)),
				$showPermission => true,
				$transient => false,
			),
			// this usernames will always be excluded from ADI authentication
			self::EXCLUDE_USERNAMES_FROM_AUTHENTICATION => array(
				$title => __('Exclude usernames from authentication', 'next-active-directory-integration'),
				$type => Type::EDITABLE_LIST,
				$description => __(
					'The entered usernames above will always be excluded from NADI login authentication. The exclusions are applying to form- and SSO-based logins. Usernames are case-insensitive.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'Every username you have entered will not be authenticated against the Active Directory, instead the WordPress authentication mechanism is used. You have to explicitly declare every username with every used suffix.',
						'next-active-directory-integration'
					)
				),
				$default => '',
				$angularButtonAttributes => 'ng-show="!$parent.is_input_empty(new_exclude_usernames_from_authentication)"',
				$sanitizer => array('accumulation', ';', array('string', false, true)),
				$showPermission => true,
				$transient => false,
			),
			// account Suffix (will be appended to all usernames created in WordPress, as well as used in the Active Directory authentication process
			self::ACCOUNT_SUFFIX => array(
				$title => __('Account suffix', 'next-active-directory-integration'),
				$type => Type::EDITABLE_LIST,
				$description => __(
					'This suffix will be appended to all usernames during the Active Directory authentication process, e.g. <em>@ad.company.com</em>.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'The Account suffix is added to all usernames during the Active Directory authentication process.',
						'next-active-directory-integration'
					),
					__(
						'Example: An *Account Suffix* <em>@ad.company.com</em> is used. When the user <em>my_username</em> logs in, the complete username is set to <em>my_username@company.local</em>.',
						'next-active-directory-integration'
					),
					'<strong>' . __('Do not forget to start the suffix with "@".', 'next-active-directory-integration') . '</strong>',
					__(
						'If you have multiple account suffixes like <em>@emea.ad.company.com</em>, <em>@africa.ad.company.com</em> enter each of them and put the primary domain name (<em>@ad.company.com</em>) at the *last* position.',
						'next-active-directory-integration'
					)
				),
				$angularAttributes => '',
				$angularButtonAttributes => 'ng-show="!$parent.is_input_empty(new_account_suffix)"',
				$default => '',
				$sanitizer => array('accumulation', ';', array('string', false, true)),
				$showPermission => true,
				$transient => false,
			),
			// Should the user be able to use one of their ProxyAddresses instead of their sAMAccountName for login? Their sAMAccountName will be looked up from the ProxyAddress.
			self::ALLOW_PROXYADDRESS_LOGIN => array(
				$title => __('Allow users to login with one of their proxyAddresses', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'If checked, users will be able to use one of their <em>proxyAddresses</em> instead of their <em>sAMAccountName</em> to login.',
					'next-active-directory-integration'
				),
				$detail => __(
					'Instead of using the user principal name for newly created users, the <em>sAMAccountName</em> will be used. The <em>proxyAddresses</em> will be used to lookup the <em>sAMAccountName</em>.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Should the sAMAccountName be used for newly created users instead of the UserPrincipalName?
			self::USE_SAMACCOUNTNAME_FOR_NEW_USERS => array(
				$title => __('Use sAMAccountName for newly created users', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'If checked, the <em>sAMAccountName</em> will be set as username for newly created users.',
					'next-active-directory-integration'
				),
				$detail => __(
					'Instead of using the user principal name for newly created users, the <em>sAMAccountName</em> will be used.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Should the users be updated in the WordPress database everytime they logon? (Works only if automatic user creation is set.
			self::AUTO_UPDATE_USER => array(
				$title => __('Automatic user synchronization', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __('After a successfully login a user\'s WordPress profile will be automatically synchronized with her Active Directory account.',
					'next-active-directory-integration'),
				$detail => array(
					__(
						'Enabling this option will result in NADI synchronizing the user\'s information every time they login to WordPress.',
						'next-active-directory-integration'
					),
					__('Requires "Automatic user creation" to be enabled.', 'next-active-directory-integration'),
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Update users description if $_auto_update_user is true
			self::AUTO_UPDATE_DESCRIPTION => array(
				$title => __('Automatically update user description', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Automatically updates the description of users after login and for newly created users', 'next-active-directory-integration'
				),
				$detail => __(
					'This option will only work if you already have "Automatic user creation" and "Automatic user update" enabled. As the title says it will automatically update the description of newly created users and users who login.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Default Email Domain (eg. 'domain.tld')
			self::DEFAULT_EMAIL_DOMAIN => array(
				$title => __('Default email domain', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					'If the Active Directory attribute "mail" is blank, a user\'s email will be set to <em>username@${default_email_domain}</em>.',
					'next-active-directory-integration'
				),
				$detail => __(
					'If the Active Directory attribute "mail" is blank, a user\'s email will be set to <em>username@${default_email_domain}</em>.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// How to deal with duplicate email addresses
			self::DUPLICATE_EMAIL_PREVENTION => array(
				$title => __('Email address conflict handling', 'next-active-directory-integration'),
				$type => Type::SELECT,
				$elements => array(
					__('Prevent (recommended)', 'next-active-directory-integration') => 'prevent',
					__('Allow (UNSAFE)', 'next-active-directory-integration') => 'allow',
					__('Create', 'next-active-directory-integration') => 'create',
				),
				$description => __('This option handles email address conflicts caused by creating multiple users using the same email address.', 'next-active-directory-integration'),
				$detail => array(
					__('This option handles email address conflicts caused by creating multiple users using the same email address. WordPress requires a unique email address for each user in an installation.',
						'next-active-directory-integration'),
					__(
						'Prevent: User is not created, if his email address is already in use by another user. (recommended)',
						'next-active-directory-integration'
					),
					__('Allow: Allow users to share one email address. (UNSAFE)', 'next-active-directory-integration'),
					__('Create: In case of a conflict, the new user is created with a unique and randomly generated email address.',
						'next-active-directory-integration'),
				),
				$angularAttributes => '',
				$default => 'prevent',
				$sanitizer => array('selection', 0),
				$showPermission => true,
				$transient => false,
			),
			// Prevent email change by ADI Users (not for admins)
			self::PREVENT_EMAIL_CHANGE => array(
				$title => __('Prevent email change', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Prevents users authenticated by Active Directory from changing their email address in WordPress.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'Activating this option will forbid users authenticated by Active Directory to change their email address in in WordPress.',
						'next-active-directory-integration'
					),
					__('This option does not apply to the administrator.', 'next-active-directory-integration'),
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Set user's display_name to an AD attribute or to username if left blank
			// Possible values: description, displayname, mail, sn, cn, givenname, samaccountname, givenname sn
			self::NAME_PATTERN => array(
				$title => __('Display name', 'next-active-directory-integration'),
				$type => Type::SELECT,
				$elements => array(
					__('sAMAccountName (the username)', 'next-active-directory-integration') => 'samaccountname',
					__('displayName', 'next-active-directory-integration') => 'displayname',
					__('description', 'next-active-directory-integration') => 'description',
					__('givenName (firstname)', 'next-active-directory-integration') => 'givenname',
					__('SN (lastname)', 'next-active-directory-integration') => 'sn',
					__('givenName SN (firstname lastname)', 'next-active-directory-integration') => 'givenname sn',
					__('CN (Common Name, the whole name)', 'next-active-directory-integration') => 'cn',
					__('mail', 'next-active-directory-integration') => 'mail',
				),
				$description => __(
					'This option allows you to configure how a user\'s name should be displayed inside posts or comments.',
					'next-active-directory-integration'
				),
				$detail => __(
					'This option allows you to configure how a user\'s name should be displayed inside posts or comments.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => 'samaccountname', // TODO soll dieser Wert wirklich als default-Wert dienen?
				$sanitizer => array('selection', 0),
				$showPermission => true,
				$transient => false,
			),
			// show disabled and ADI user status on user list
			self::SHOW_USER_STATUS => array(
				$title => __('Show user status', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __('Show additional columns (<em>NADI User</em>, <em>disabled</em>) inside the WordPress users list.',
					'next-active-directory-integration'),
				$detail => __('Show additional columns', 'next-active-directory-integration'),
				$default => true,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// use local (WordPress) password as fallback if authentication against AD fails
			self::FALLBACK_TO_LOCAL_PASSWORD => array(
				$title => __('Fallback to local password', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Fallback to WordPress\' local password check if Active Directory authentication fails.', 'next-active-directory-integration'
				),
				$detail => array(
					__(
						'If this option is enabled, users who failed to authenticate against Active Directory will try to authenticate using the local WordPress password.',
						'next-active-directory-integration'
					),
					__(
						'But this might be a security risk (for example, if the local password is outdated). <b>It\'s recommended to turn this off.</b>',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Use the real password when a user is created
			self::NO_RANDOM_PASSWORD => array(
				$title => __('Set local password on first successful login', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'The first time a user successfully logs in, her local password will be equated with the password she used to authenticate against the Active Directory.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'The first time a user successfully logs in, her local password will be equated with the password she used to authenticate against the Active Directory. If this option is deactivated a random password for this user will be set.',
						'next-active-directory-integration'
					),
					__('If this option is deactivated a random password for this user will be set.', 'next-active-directory-integration'),
					__('The option does only work if *User > Automatic user creation* is enabled.', 'next-active-directory-integration'),
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Enable/Disable password changes
			self::ENABLE_PASSWORD_CHANGE => array(
				$title => __('Allow local password changes', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Enabling this option allows users to change their local WordPress password.',
					'next-active-directory-integration'
				),
				$detail => array(
					__('Enabling this option allows users to change their local WordPress password.', 'next-active-directory-integration'),
					__('This option has <strong>no</strong> effect on the Active Directory password and will <strong>not</strong> be synchronized back to the Active Directory.',
						'next-active-directory-integration')
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Update password on every successful login
			self::AUTO_UPDATE_PASSWORD => array(
				$title => __('Automatic password update', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'This option updates the local password every time a user successfully logs in.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'This option updates the local password every time a user successfully logs in. If a user has changed her Active Directory password and successfully authenticates against Active Directory while trying to login to WordPress, his local WordPress password will be equated with the new Active Directory password.',
						'next-active-directory-integration'
					),
					__(
						'Note: Activating this option makes little sense if <em>Allow local password changes</em> is enabled.',
						'next-active-directory-integration'
					),
					__('Works only if <em>User &gt; Automatic user creation</em> and <em>User &gt; Automatic user synchronization</em> is enabled.',
						'next-active-directory-integration'),
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Check Login authorization by group membership
			self::AUTHORIZE_BY_GROUP => array(
				$title => __('Authorize by group membership', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'This option authorizes only members of the given Active Directory security groups to gain access to WordPress.',
					'next-active-directory-integration'
				),
				$detail => __(
					'This option authorizes only members of the given Active Directory security groups to gain access to WordPress. This authorization occurs <strong>after</strong> the authentication.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Group name for authorization.
			self::AUTHORIZATION_GROUP => array(
				$title => __('Authorization group(s)', 'next-active-directory-integration'),
				$type => Type::EDITABLE_LIST,
				$description => __(
					'If not empty, only the defined security groups have access to WordPress.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'Please define your Authorization group(s) here.',
						'next-active-directory-integration'
					),
					__('Works only if "User > Automatic user creation" and "User > Automatic user synchronization" are enabled.',
						'next-active-directory-integration'),
				),
				$angularAttributes => 'ng-disabled="((!option.authorize_by_group) || ((permission.authorization_group == 2) || (permission.authorization_group == 1))',
				$angularButtonAttributes => 'ng-show="!$parent.is_input_empty(new_authorization_group)"',
				$default => '',
				$sanitizer => array('accumulation', ';', array('string')),
				$showPermission => true,
				$transient => false,
			),

			// Role Equivalent Groups (wp-role1=ad-group1;wp-role2=ad-group2;...)
			self::ROLE_EQUIVALENT_GROUPS => array(
				$title => __('Role equivalent groups', 'next-active-directory-integration'),
				$type => Type::TABLE,
				$description => __(
					'List of one or multiple Active Directory security groups which correspond to WordPress\' roles.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'Please enter the names of the Active Directory security groups which correspond to WordPress user roles using the following schema.',
						'next-active-directory-integration'
					),
					__('Example:', 'next-active-directory-integration'),
					__('ad-group=wp-role;', 'next-active-directory-integration'),
					__('wordpressadmins=administrator;', 'next-active-directory-integration'),
					__('wordpressmoderator=editor;', 'next-active-directory-integration'),
					__('wordpressuser=contributor;', 'next-active-directory-integration'),
					__(
						'You can find a whole table of Roles and Capabilities at: ' . "<a href="
						. 'http://codex.wordpress.org/Roles_and_Capabilities'
						. '>http://codex.wordpress.org/Roles_and_Capabilities</a>' . ' (3.8 Capability vs. Role Table)',
						'next-active-directory-integration'
					),
					__(
						'Group memberships cannot be checked across domains.  So if you have two domains, instr and qc, and qc is the domain specified above, if instr is linked to qc, I can authenticate instr users, but not check instr group memberships.',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => 'ng-disabled="(((permission.role_equivalent_groups == 2) || (permission.role_equivalent_groups == 1))',
				$angularButtonAttributes => "ng-class='{\"adi-button-hidden\": !(!\$parent.is_input_empty(newItemField1) && !\$parent.is_input_empty(newItemField2)) } '",
				$default => '',
				$sanitizer => array('accumulation', ';', array('valueAssignment', '=')),
				$showPermission => true,
				$transient => false,
			),
			// Disabling this option will result in NADI not removing previous assigned WordPress roles anymore while updating the user
			self::CLEAN_EXISTING_ROLES => array(
				$title => __('Clean existing Roles', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Disabling this option will result in NADI not removing previous assigned WordPress roles anymore while updating the user.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'-',
						'next-active-directory-integration'
					)),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Enable lost password recovery
			self::ENABLE_LOST_PASSWORD_RECOVERY => array(
				$title => __('Enable lost password recovery', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __('The user can reset his password in the login screen.', 'next-active-directory-integration'),
				$detail => __(
					'Enabling this option will allow users to reset their local password in the login screen.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Settings for Autologin
			self::SSO_ENABLED => array(
				$title => __('Enable SSO', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'This option will grant users the possibility to use Single Sign On with WordPress once they got authenticated against Active Directory.',
					'next-active-directory-integration'
				),
				$detail => __(
					'This option will grant users the possibility to use Single Sign On with WordPress once they got authenticated against Active Directory.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			self::SSO_USER => array(
				$title => __('Service account username', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					'Username of an Active Directory account with read permissions for the users in the Active Directory (e.g. <em>ldapuser@ad.company.com</em>).',
					'next-active-directory-integration'
				),
				$detail => __(
					'Username of an Active Directory account with read permissions for the users in the Active Directory (e.g. <em>ldapuser@ad.company.com</em>).',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sso) || ((permission.sso == 2) || (permission.sso == 1))',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			self::SSO_PASSWORD => array(
				$title => __('Service account password', 'next-active-directory-integration'),
				$type => Type::PASSWORD,
				$description => __('Password of an Active Directory account with read permissions for the users in the Active Directory.',
					'next-active-directory-integration'),
				$detail => __(
					'Password of an Active Directory account with read permissions for the users in the Active Directory.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sso) || ((permission.sso == 2) || (permission.sso == 1))',
				$default => '',
				$sanitizer => array('string', false, false),
				$showPermission => true,
				$transient => false,
			),
			self::SSO_ENVIRONMENT_VARIABLE => array(
				$title => __('Username variable', 'next-active-directory-integration'),
				$type => Type::SELECT,
				$elements => array(
					Variable::REMOTE_USER => Variable::REMOTE_USER,
					Variable::X_REMOTE_USER => Variable::X_REMOTE_USER,
					Variable::HTTP_X_REMOTE_USER => Variable::HTTP_X_REMOTE_USER,
					Variable::PHP_AUTH_USER => Variable::PHP_AUTH_USER
				),
				$description => __(
					'The PHP server variable which is used by the web server to retrieve the current user.',
					'next-active-directory-integration'
				),
				$detail => array(
					sprintf(
						__('%s: The default server variable.', 'next-active-directory-integration'),
						Variable::REMOTE_USER
					),
					sprintf(
						__('%s: Is used when working with proxies. The variable "REMOTE_USER" must be forwarded from the proxy.',
							'next-active-directory-integration'),
						Variable::X_REMOTE_USER
					),
					sprintf(
						__('%s: All http headers are prefixed HTTP_ so that you can distinguish between them and environment variables.',
							'next-active-directory-integration'),
						Variable::HTTP_X_REMOTE_USER
					),
					sprintf(
						__('%s: PHP default username variable variable.', 'next-active-directory-integration'),
						Variable::PHP_AUTH_USER
					),
					sprintf('<table class="table">
  					<thead>
   					 	<tr>
     				 		<th scope="col">Variable</th>
      						<th scope="col">Value</th>
    					</tr>
  					</thead>
  					<tbody>
    					<tr>
      						<td>REMOTE_USER</td>
      						<td>%s</td>
    					</tr>
						<tr>
						  <td>X-REMOTE-USER</td>
						  <td>%s</td>
						</tr>
						<tr>
						  <td>HTTP_X_REMOTE_USER</td>
						  <td>%s</td>
						</tr>
						<tr>
						  <td>PHP_AUTH_USER</td>
						  <td>%s</td>
						</tr>
					  </tbody>
					</table>',
						(isset($_SERVER["REMOTE_USER"]) ? $_SERVER["REMOTE_USER"] : ""),
						(isset($_SERVER["X-REMOTE-USER"]) ? $_SERVER["X-REMOTE-USER"] : ""),
						(isset($_SERVER["HTTP_X_REMOTE_USER"]) ? $_SERVER["HTTP_X_REMOTE_USER"] : ""),
						(isset($_SERVER["PHP_AUTH_USER"]) ? $_SERVER["PHP_AUTH_USER"] : "")

					)),
				$angularAttributes => 'ng-disabled="((!option.sso) || ((permission.sso == 2) || (permission.sso == 1))',
				$default => Variable::REMOTE_USER,
				$sanitizer => array('selection', 0),
				$showPermission => true,
				$transient => false,
			),
			// NADIS-92/ADI-679: Add option to disable SSO when using XML-RPC
			self::SSO_DISABLE_FOR_XMLRPC => array(
				$title => __('Disable SSO for XML-RPC', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'When using the XML-RPC endpoint, SSO will be disabled',
					'next-active-directory-integration'
				),
				$detail => __(
					'If you want to make tools like Live Writer working in an SSO environment, SSO must be deactivated for XML-RPC as Live Writer does not work with Kerberos or NTLM.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sso) || ((permission.sso == 2) || (permission.sso == 1))',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Kerberos realm mappings
			self::KERBEROS_REALM_MAPPINGS => array(
				$title => __('Kerberos realm mappings', 'next-active-directory-integration'),
				$type => Type::TEXTAREA,
				$description => __(
					'Specify all Kerberos realms and their designated UPN suffixes',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'This is only required if you are inside an Active Directory forest with a Global Catalog and Kerberos enabled. You need the <em>Multi-domain Forest Premium Extension</em>em>',
						'next-active-directory-integration'
					),
					__('Example:', 'next-active-directory-integration'),
					__('FIRSTKRBREALM.COM=upnsuffix1.com;', 'next-active-directory-integration'),
					__('SECONDKRBREALM.COM=upnsuffix2.de,upnsuffix3.org;', 'next-active-directory-integration'),
					__(
						'Please take a look at our documentation at <a href="https://active-directory-wp.com/docs/Configuration/Sso" target="__blank">https://active-directory-wp.com/docs/Configuration/Sso</a>',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => 'ng-disabled="((!option.sso) || ((permission.sso == 2) || (permission.sso == 1))',
				$default => '',
				$showPermission => true,
				$transient => false,
			),
			// Allows users who usually require a smart card to log in using NADI
			self::ENABLE_SMARTCARD_USER_LOGIN => array(
				$title => __('Enable login for smart card Users', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __('Enabling this option allows users, who require a smart card, to authenticate against the Active Directory.',
					'next-active-directory-integration'),
				$detail => __('Enables login for smart card users.', 'next-active-directory-integration'),
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Allows users to authenticate against the Active Directory using NADI on a custom login page
			self::CUSTOM_LOGIN_PAGE_ENABLED => array(
				$title => __('Enables login using a custom login page', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __('Enabling this option allows users to authenticate against the Active Directory using custom login page.',
					'next-active-directory-integration'),
				$detail => __('Enables authentication against the Active Directory on a custom login page.', 'next-active-directory-integration'),
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			self::CUSTOM_LOGIN_PAGE_URI => array(
				$title => __('Custom login page URI', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __('URI of a custom login page where authentication against the Active Directory will be enabled. By default, the custom login page\'s URI is <em>/login</em>.',
					'next-active-directory-integration'),
				$detail => __('If you use a custom login page URI like <em>/login</em>, please enter it to enable Active Directory authentication check on this page.', 'next-active-directory-integration'),
				$angularAttributes => 'ng-disabled="(!option.custom_login_page_enabled',
				$default => '/login',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// Send email to admin if a user account is blocked.
			self::ALLOW_XMLRPC_LOGIN => array(
				$title => __('Allow login via XML-RPC', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __('Allow login via XML-RPC', 'next-active-directory-integration'),
				$detail => __(
					'Allow login via XML-RPC', 'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// List of additional user attributes that can be defined by the admin
			// The attributes are seperated by a new line and have the format:
			//   <Attribute name>:<type>
			// where type can be one of the following: string, integer, bool, image, time, timestamp
			//   thumbnailphoto:image
			//   whencreated:time
			self::ADDITIONAL_USER_ATTRIBUTES => array(
				$title => __('Additional User Attributes', 'next-active-directory-integration'),
				$type => Type::CUSTOM,
				Attribute::TYPE_STRUCTURE => array(
					// first input field; the user can use a well known user attribute or can use his own user attribute
					array(
						$type => Type::COMBOBOX,
						$description => __('AD User Attribute:', 'next-active-directory-integration'),
						$elements => Description::findAll(),
						$sanitizer => array('string', false, true, true),
					),
					// second input field; the user can only should between certain data types
					array(
						$type => Type::SELECT,
						$description => __('Data Type:', 'next-active-directory-integration'),
						$elements => array(
							__('Only a text', 'next-active-directory-integration') => 'string',
							__('An array with texts', 'next-active-directory-integration') => 'list',
							__('Natural number', 'next-active-directory-integration') => 'integer',
							__('Boolean', 'next-active-directory-integration') => 'bool',
							__('Octet', 'next-active-directory-integration') => 'octet',
							__('Time', 'next-active-directory-integration') => 'time',
							__('Timestamp', 'next-active-directory-integration') => 'timestamp',
							__('CN', 'next-active-directory-integration') => 'cn',
						),
						$angularAttributes => '',
						$sanitizer => array('selection'),
					),
					array(
						$type => Type::TEXT,
						$description => __('User Meta Key:', 'next-active-directory-integration'),
						$sanitizer => array('string'),
					),
				),
				$description => __(
					'Enter additional Active Directory attributes.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'Additional Attributes that should appear on the user profile must also be placed in "Attributes to show".',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => 'ng-disabled="(((permission.additional_user_attributes == 1) || (permission.additional_user_attributes == 2))',
				$angularButtonAttributes => "ng-class='{\"adi-button-hidden\": !(is_input_complete()) } '",
				$default => '',
				$sanitizer => array('custom'), // all in lower case
				$showPermission => true,
				$transient => false,
			),
			// Enable Sync to AD
			self::SYNC_TO_AD_ENABLED => array(
				$title => __('Enable Sync To AD', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Synchronize the user\'s WordPress profile back to Active Directory.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'By enabling this option NADI will automatically synchronize the user\'s profile back to the Active Directory if it has changed.',
						'next-active-directory-integration'
					),
					__('The synchronization is automatically triggered whenever a user profile gets updated.',
						'next-active-directory-integration'),
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Use global Sync to AD User
			self::SYNC_TO_AD_USE_GLOBAL_USER => array(
				$title => __('Use Sync To AD service account', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'By enabling this option users will not be asked for their Active Directory password when updating their WordPress user profile. Instead a central Active Directory service account is used.<br />If this option is <strong>disabled</strong>, you have to configure your Active Directory properly.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'By enabling this option users will not be asked for their Active Directory password when updating their WordPress user profile. Instead a central Active Directory service account will be used.',
						'next-active-directory-integration'
					),
					__(
						'If this option is disabled, you need to set the required permissions in the Active Directory. Please refer to the documentation on how to do this.',
						'next-active-directory-integration'
					),
					__(
						'NOTICE: The password of this service account is stored encrypted, but **USE IT AT YOUR OWN RISK**. To avoid this you have to grant your users the permission to change their own AD attributes. See FAQ for details.',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad) || ((permission.sync_to_ad_use_global_user == 2) || (permission.sync_to_ad_use_global_user == 1))',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Account name of global Sync to AD user
			self::SYNC_TO_AD_GLOBAL_USER => array(
				$title => __('Service account username', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					'Username of an Active Directory account with write permissions for the users in the Active Directory (e.g. <em>administrator@ad.company.com</em>).',
					'next-active-directory-integration'
				),
				$detail => __(
					'If you define a <em>Sync To AD service account</em> with write permissions inside the Active Directory, changes will automatically be written to the Active Directory database using this user.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad)||(!option.sync_to_ad_use_global_user) || ((permission.sync_to_ad_global_user == 2) || (permission.sync_to_ad_global_user == 1))',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// Password of global Dd user
			self::SYNC_TO_AD_GLOBAL_PASSWORD => array(
				$title => __('Service account password', 'next-active-directory-integration'),
				$type => Type::PASSWORD,
				$description => __(
					'Password for <em>Sync To AD service account</em>. Leave empty if password should not be changed.', 'next-active-directory-integration'
				),
				$detail => __(
					'This option defines a NEW password for the <em>Sync To AD service account</em>. Leave this field blank if you do not want to change the password.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad)||(!option.sync_to_ad_use_global_user) || ((permission.sync_to_ad_global_password == 2) || (permission.sync_to_ad_global_password == 1))', //
				$default => '',
				$sanitizer => array('string', false, false),
				$showPermission => true,
				$transient => false,
			),
			// AUTHCODE for Sync to AD. Sync to AD will only work, if this AUTHCODE is send as a post-parameter to the blog index.php
			self::SYNC_TO_AD_AUTHCODE => array(
				$title => __('Auth code', 'next-active-directory-integration'),
				$type => Type::AUTHCODE,
				$description => __(
					'This code is needed for <em>Sync To AD</em>.',
					'next-active-directory-integration'
				),
				$detail => __(
					'The authentication code is used to authenticate the global sync user against the Active Directory.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_ad) || ((permission.sync_to_ad_authcode == 2) || (permission.sync_to_ad_authcode == 1))',
				$default => '',
				Attribute::PERSIST_DEFAULT_VALUE => true,
				$sanitizer => array('authcode'),
				$showPermission => true,
				$transient => false,
			),
			// enable Sync to WordPress
			self::SYNC_TO_WORDPRESS_ENABLED => array(
				$title => __('Enable Sync To WordPress', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Enabling this option will allow NADI to sync users from the Active Directory to the WordPress database.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'Enabling this option will allow NADI to sync users from the Active Directory to the WordPress database.',
						'next-active-directory-integration'
					),
					__(
						'Hint: You could use a cron job, which is nothing more than a timetable executing the Sync to WordPress/update at defined times, to always keep your WordPress database up to date with your Active Directory user information.',
						'next-active-directory-integration'
					),
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// AUTHCODE for Sync to WordPress. Sync to WordPress will only work, if this AUTHCODE is send as a post-parameter to the blog index.php
			self::SYNC_TO_WORDPRESS_AUTHCODE => array(
				$title => __('Auth code', 'next-active-directory-integration'),
				$type => Type::AUTHCODE,
				$description => __('This code is needed for <em>Sync To WordPress</em>.', 'next-active-directory-integration'),
				$detail => __(
					'This code is needed for Sync to WordPress.',
					'next-active-directory-integration'
				),
				$default => '',
				Attribute::PERSIST_DEFAULT_VALUE => true,
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_authcode == 2) || (permission.sync_to_wordpress_authcode == 1))',
				$sanitizer => array('authcode'),
				$showPermission => true,
				$transient => false,
			),
			// Import members of these security groups (separated by semicolons)
			self::SYNC_TO_WORDPRESS_SECURITY_GROUPS => array(
				$title => __('Import members of security groups', 'next-active-directory-integration'),
				$type => Type::EDITABLE_LIST,
				$description => __(
					'Only members of the security groups entered here will be imported. See the documentation on how to import members of Domain Users or Domain Administrators.',
					'next-active-directory-integration'
				),
				$detail => array(
					__(
						'Here you can enter the security groups which will be imported every time <em>Sync To WordPress</em> is executed.',
						'next-active-directory-integration'
					),
					__(
						'If you want to include the users of the built in user group <em>Domain Users</em> you have to enter <em>id:513</em>. More information can be found in the official documentation.',
						'next-active-directory-integration'
					),
				),
				$default => '',
				$sanitizer => array('accumulation', ';', array('string')),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_security_groups  == 2) || (permission.sync_to_wordpress_security_groups  == 1))',
				$angularButtonAttributes => 'ng-show="!$parent.is_input_empty(new_sync_to_wordpress_security_groups)"',
				$showPermission => true,
				$transient => false,
			),
			// name of Sync to WordPress User in Active Directory
			self::SYNC_TO_WORDPRESS_USER => array(
				$title => __('Service account username', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					'Username of an Active Directory account with read permissions for the users in the Active Directory (e.g. <em>ldapuser@ad.company.com</em>).',
					'next-active-directory-integration'
				),
				$detail => __(
					'In order to import multiple users at once you have to define a user with Active Directory users read permission.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_user == 2) || (permission.sync_to_wordpress_user == 1))',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
			// password for Sync to WordPress User (will be stored encrypted)
			self::SYNC_TO_WORDPRESS_PASSWORD => array(
				$title => __('Service account password', 'next-active-directory-integration'),
				$type => Type::PASSWORD,
				$description => __('Password for <em>Sync To WordPress</em> user.', 'next-active-directory-integration'),
				$detail => __(
					'As you already defined the <em>Sync To WordPress service account username</em> before you now have to define the password for this user as well.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_user == 2) || (permission.sync_to_wordpress_user == 1))',
				$default => '',
				$sanitizer => array('string', false, false),
				$showPermission => true,
				$transient => false,
			),
			// ADI-223: Deactivate users to be imported if they are disabled in Active Directory
			self::SYNC_TO_WORDPRESS_DISABLE_USERS => array(
				$title => __('Automatically deactivate users', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Deactivated users can only be re-activated manually by administrators on the users profile page or by using the <em>Sync To WordPress</em> feature.',
					'next-active-directory-integration'
				),
				$detail => __(
					'Deactivated users can only be re-activated manually by administrators on the users profile page or by using the <em>Sync To WordPress</em> feature.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.disable_users == 2) || (permission.disable_users == 1))',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Import users even if they are disabled in active directory
			self::SYNC_TO_WORDPRESS_IMPORT_DISABLED_USERS => array(
				$title => __('Import disabled users', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'Users deactivated in Active Directory are synchronized to WordPress.',
					'next-active-directory-integration'
				),
				$detail => __(
					'Users deactivated in Active Directory are synchronized to WordPress.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.sync_to_wordpress_enabled) || ((permission.sync_to_wordpress_import_disabled_users == 2) || (permission.sync_to_wordpress_import_disabled_users == 1))',
				$default => true,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Enable Logging
			self::LOGGER_ENABLE_LOGGING => array(
				$title => __('Enable Logging', 'next-active-directory-integration'),
				$type => Type::CHECKBOX,
				$description => __(
					'If enabled, NADI will create a logfile at the default location <em>' . NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH . 'logs/debug.log</em>. <button class="button button-primary" ng-click="activateLogging()" ng-show="isSaveDisabled"> Save Logging Configurations</button>',
					'next-active-directory-integration'
				),
				$detail => __(
					'If enabled, NADI will create a logfile and start logging to it. Default path is <em>' . NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH . 'logs/debug.log</em>.',
					'next-active-directory-integration'
				),
				$angularAttributes => '',
				$default => false,
				$sanitizer => array('boolean'),
				$showPermission => true,
				$transient => false,
			),
			// Define a custom path for the logfile to be created at
			self::LOGGER_CUSTOM_PATH => array(
				$title => __('Custom Path', 'next-active-directory-integration'),
				$type => Type::TEXT,
				$description => __(
					'The logfile will be created at the set location. (e.g. <em>/custompath/logs/</em>).',
					'next-active-directory-integration'
				),
				$detail => __(
					'If you do not have permission for the default path on your system you can set a new path. The <em>debug.log</em> file will be created at the configured location.',
					'next-active-directory-integration'
				),
				$angularAttributes => 'ng-disabled="((!option.logger_enable_logging) || ((permission.logger_enable_logging == 2) || (permission.logger_enable_logging == 1))',
				$default => '',
				$sanitizer => array('string'),
				$showPermission => true,
				$transient => false,
			),
		);
	}
}