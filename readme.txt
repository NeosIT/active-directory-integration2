=== Next Active Directory Integration ===
Contributors: dreitier,dreitierci,schakko,neosit
Tags: authentication, active directory, ldap, ldaps, authorization, security, windows, sso, login, domain, controller
Requires at least: 5.6
Tested up to: 6.2
Stable tag: REPLACE_VERSION_BY_CI
License: GPLv3
Donate link: https://active-directory-wp.com

Next Active Directory Integration allows WordPress to authenticate, authorize, create and update users against Microsoft Active Directory.

== Description ==

*Next Active Directory Integration* allows WordPress to authenticate, authorize, create and update users against Microsoft Active Directory. *NADI* ist a complete rewrite of its predecessor Active Directory Integration and therefore an own plugin.
You can easily import users from your Active Directory into your WordPress instance and keep both synchronized through *Next Active Directory Integration's* features.

Even if *NADI* is available for free we hope you purchase a plan to let us continue the work on Next Active Directory Integration.
You can purchase commercial support plans at [https://www.active-directory-wp.com/shop-overview/](https://www.active-directory-wp.com/shop-overview/). The support plans give you access to our premium extensions and guarantee an ongoing development of the plug-in.

= Features =

* Authenticating WordPress users against one or multiple AD Server
* Authorizing users by Active Directory group memberships
* Managing Active Directory authentication for WordPress Multisite installations
* Single Sign On with Kerberos sponsored by [Colt Technology Services](http://colt.net) and [Digital Elite](https://app.digitalelite.co.uk/)
* Automatically create and update WordPress users based upon their Active Directory membership
* Mapping of Active Directory security groups to WordPress roles
* Protection against brute force password hacking attacks
* User and/or admin e-mail notification on failed login attempts
* Multi-language support (at the moment only English is included)
* Determining WordPress display name from Active Directory attributes
* Synchronizing Active Directory attributes and WordPress user meta information in both ways
* Embed customized Active Directory attributes in WordPress user's profile
* Enable/disable password changes for local (non-Active Directory) WordPress users
* Disable user accounts in WordPress if they are disabled in Active Directory.
* Set users local WordPress password on first and/or on every successful login
* Option to disable fallback to local (WordPress) authentication.
* Support for Active Directory forest environments.
* and much much more

= Premium Extensions =

As an owner of a valid support plan you have access to the following premium extensions:

* Profile Pictures: Synchronize profile photos from Active Directory to WordPress without a 3rd party plug-in
* BuddyPress profile photo: Synchronize profile photos from Active Directory to BuddyPress
* Buddy Press simple attributes: Synchronize attributes from Active Directory/NADI to BuddyPress' custom profiles
* Login with Ultimate Member: Let UM users log in by using NADI
* Login with WooCommerce: Let WooCommerce users log in by using NADI
* WP-CLI: Execute common NADI tasks (Sync to WordPress, Sync to AD) with help of WP-CLI
* Active Directory Forest: Be able to use one WordPress instance with your whole Active Directory forest environment

= Requirements =

* WordPress since 5.6
* PHP >= 8.0
* LDAP support
* OpenSSL Support for TLS (recommended)

== Frequently Asked Questions ==

Please read the [FAQ](https://www.active-directory-wp.com/docs/FAQ.html) of our [official documentation](https://www.active-directory-wp.com/docs/Getting_Started.html).

== Screenshots ==

1. Generic profile options
2. Environment options to connect to your Active Directory domain
3. User settings
4. Password settings
5. Permissions for authorization groups and role equivalent groups
6. Security
7. Attribute mapping from Active Directory to WordPress
8. Sync to Active Directory
9. Sync to WordPress
10. Logging
11. Extend WordPress' user list with Active Directory information
12. Profile assignment in WordPress Multisite
13. Custom *NADI* profile in WordPress Multisite environment


== Installation ==

= Requirements =
To install Next Active Directory Integration you need at least WordPress 5.6 and PHP 8.0.

Although only tested with Apache 2.2 and 2.4 *NADI* should work with all other common web servers like nginx and IIS.

Next Active Directory Integration requires a few PHP modules to be enabled. Please verify in your `php.ini` that *ldap* and *openssl* are activated.

	; required by *NADI*
	extension=php_ldap.dll
	extension=php_openssl.dll

= Important =

NADI requires PHP 8.0 or later. The reason is that security support for PHP version prior 8.x have been dropped by the maintainers as you can see in the official PHP documentation http://php.net/supported-versions.php.
For security reasons and in order to use NADI in 2023 we hereby politely encourage you to migrate your environments to at least PHP 8.0 until then.

Thank you all for your support and understanding.

Best regards,
your NADI team.

= Installation =

**NADI** can be easily installed from the [WordPress Plugin Directory](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

It is also possible to download the latest version from [https://downloads.wordpress.org/plugin/next-active-directory-integration.zip"](https://downloads.wordpress.org/plugin/next-active-directory-integration.zip) and unpack the folder to your *wordpress/wp-content/plugins* directory.

Developers can clone the [Git Repository](https://github.com/NeosIT/active-directory-integration2) inside their *wordpress/wp-content/plugins* directory and download the dependencies with *composer*.

= Single Site =
- Visit your WordPress blog and login into your WordPress dashboard as *Administrator*
- Click on the *Plugins* in the left the navigation bar
- Activate the *Next Active Directory Integration* plug-in

= Network installation =
- Visit your WordPress network dashboard as *Super Admin*
- Click on the *Plugins* link in the left the navigation bar
- Activate the *Next Active Directory Integration* plug-in. In a WordPress network installation *NADI* should be *Network activated*.

You can enable/disable *NADI* for specific blogs by using the *Profiles* feature of *NADI*.
It is __not__ possible to activate *NADI* for a site inside a network.

== Changelog ==

For detailed information you can visit the official [GitHub repository of Next Active Directory Integration](https://github.com/NeosIT/active-directory-integration2)

= UNRELEASED =
* FIXED: Twig functions in default namespace conflict with other WordPress plug-ins (#185)
* FIXED: Conversion of empty or string-based LDAP timestamp attributes (#184)

= 3.0.5 =
* FIXED: WP_MS_Sites_List_Table not found when running in Multisite (#183)

= 3.0.4 =
* CHANGED: WordPress 6.2 compatibility has been checked

= 3.0.3 =
* FIXED: Postpone stub creation in favor of other autoloaders (#181)
* FIXED: Check for availability of PHP 8.0 or deactivate plug-in (#179)
* FIXED: Class Dreitier\Util\Logger\LogFacade not found (#178)
* FIXED: Deprecation notice with PHP 8.x for null constructor parameters of WordPressErrorException

= 3.0.2 =
* FIXED: Provide stub for Logger class (#177)

= 3.0.1 =
* FIXED: Include PHP 8.2 into test suite (#175)
* FIXED: class ShowSingleSignOnLink not found (#176)

= 3.0.0 =
* FIXED: Usage of custom auto-loader and strauss to prevent conflicting namespaces (#165)
* FIXED: Detect and use constant NEXT_ACTIVE_DIRECTORY_INTEGRATION_ENCRYPTION_KEY to be able to change WordPress' AUTH_SALT (#173)
* CHANGED: Upgraded monolog/monolog to 2.8.0 to make NADI working with WP-Rocket (#169)
* CHANGED: PHP 8.0 runtime is now required (#170)

= 2.3.8 =
* FIXED: Detect and use constant NEXT_ACTIVE_DIRECTORY_INTEGRATION_ENCRYPTION_KEY to be able to change WordPress' AUTH_SALT (#173)

= 2.3.7 =
* FIXED: ldap_search fails on PHP 7.4 (#171)

= 2.3.6 =
* FIXED: With PHP 8.1, ldap_get_entries expects an LDAP\Result instance as second parameter (#166)
* FIXED: Deprecation warning when Test Authentication is triggered with PHP 8.1 (#168)
* ADDED: Notification in WordPress' plug-in page for upcoming v3
* ADDED: Compatibility with new Premium Extensions which are already using v3's  code

= 2.3.5 =
* FIXED: Blank configuration page with PHP >= 8.0 and missing AUTH_SALT constant (#164)

= 2.3.4 =
* CHANGED: twig/twig dependency updated to 3.4.3 (#162)
* CHANGED: WordPress 6.1 compatibility has been checked
* ADDED: deprecation warning for the PHP 7.x branch 

= 2.3.3 =
* CHANGED: "master" branch has been replaced with "main" branch.
* ADDED: lifecycle hooks after the authentication has succeeded, so Wordfence can be used (#160)
* CHANGED: WordPress 6.0 compatibility has been checked

= 2.3.2 =
* FIXED: Critical WordPress error if a matching profile for SSO authentication can not be found (#152, NADISUP-7)
* FIXED: Uncaught TypeError when checking userAccountControl attribute (#151)
* FIXED: For specific Active Directory forest structures, the NETBIOS name can not be resolved during verification of the credentials (#153, NADISUP-8)
* ADDED: Option for specifying a custom login page URI; special thanks to GitHub user *czoIg* for contributing this functionality (#154)

= 2.3.1 =
* CHANGED: WordPress 5.9 compatibility has been checked
* REMOVED: CI testing support for PHP 7.3 as mentioned in README.md
 
= 2.3.0 =
* FIXED: when a user can not be found by email address, findByProxyAddress returns false. Configured *Sync to WordPress* credentials are still required for logging in with email addresses. (#146)
* FIXED: when using SSO-based logins, the "Exclude usernames from authentication" option still applies (#142)
* ADDED: hooks for checking if NADI's authentication applies for a given username (#142)
* CHANGED: WordPress 5.8.1 compatibility has been checked
* CHANGED: WordPress 5.8.2 compatibility has been checked
* REMOVED: Parameter $useLocalWordPressUser in NextADInt_Adi_Mail_Notification (#135)
* REMOVED: Option 'Automatic user creation'. This option has been implicitly enabled for all installations and is no longer required (#134)
* CHANGED: PHP 8.1 compatibility has been checked; Twig has to be updated with the next release (#148)

= 2.2.3 =
* FIXED: Sync to WordPress fails if user is no longer present in Active Directory (#141)

= 2.2.2 =
* FIXED: Boolean options can't be persisted correctly with 2.2.1 (#140)

= 2.2.1 =
* FIXED: Missing meta_key "domainsid" results in TypeError or NextADInt_ActiveDirectory_Context::isMember (#133)
* FIXED: Warning: Trying to access array offset on value of type null (#139)

= 2.2.0 =
* ADDED: Kerberos principals are no longer treated as userPrincipalNames (ADI-715)
* ADDED: When using Kerberos SSO principals, you can map the Kerberos realm to multiple UPN suffixes (requires *Active Directory Forest* premium extension) (ADI-715)
* ADDED: When using NADI in an AD forest, you can now specify all SIDs of the connected domains (requires *Active Directory Forest* premium extension) (ADI-715)
* FIXED: When using a Global Catalog (GC), users with same sAMAccountName but different userPrincipalNames are not assigned correct during authentication (NADIS-133)
* FIXED-SECURITY: Users with same UPN prefix and password but different UPN suffix would be logged in with the wrong account (ADI-716)
* CHANGED: WordPress 5.6.1 compatibility has been checked
* CHANGED: WordPress 5.7 compatibility has been checked
* CHANGED: PHP 8.0 compatibility has been added (ADI-718, #132, #137)
* FIXED: Deprecation warning when trying to send mail notification for blocked users (ADI-719)
* FIXED: Option "Blog admin sets the option value." had no effect in Multisite environments (#124)
* DEPRECATION-WARNING: For the upcoming release 2.3.0 we will remove "Internal password migration" (#136), "Automatic user creation" (#134) and "Email address conflict handling" (#133)
* DEV: Slightly transition new issues to GitHub instead of internal Jira

= 2.1.12 =
* ADDED: allow self signed certificates (#107)
* CHANGED: notices for minimum PHP version 7.2 due to EOL of PHP 7.1
* FIXED: Test compatibility with latest stable PHPUnit version
* FIXED: PHP 7.4 compatibility and deprecation of some ldap_* functions (#127)
* FIXED: various typos and formatting errors in the administration user interface
* ADDED: hook for triggering Sync To WordPress and Sync To AD (ADI-526)

= 2.1.11 =
* CHANGED: Tested compatibility with upcoming version 5.4 of WordPress

= 2.1.10 =

* CHANGED: minimum PHP version to PHP 7.2 due to EOL of PHP 7.1
* CHANGED: Twig version updated to 1.41.0 (ADI-707)
* FIXED: When a non-existing user inside in WordPress authenticates in a multisite environment the first time, a warning is triggered (ADI-705)
* FIXED: A deleted user from Active Directory is mapped to the wrong user in WordPress; thanks to *T. Kowalchuk* (ADI-702)
* FIXED: PHP warning if user is deleted from Active Directory (ADI-701)
* FIXED: PHP error if touching of log file failed
* FIXED: "-DISABLED" suffix is added everytime a user is synchronized (ADI-697, NADIS-110)
* ADDED: hook next_ad_int_user_before_disable (ADI-699)
* FIXED: curly brace access (#119)

= 2.1.9 =
* ADDED: Premium extension [WP-CLI](https://active-directory-wp.com/premium-extension/) to execute "Sync to WordPress/AD" with wp-cli to circumvent webserver/proxy timeouts (NADIS-98)
* ADDED: option to disable SSO when using XML-RPC (ADI-679, NADIS-92)
* FIXED: when changing the sAMAccountName or userPrincipalName in the AD a new user would have been created in WordPress (ADI-688, NADIS-89)
* FIXED: Ultimate Member premium plug-in no longer works with new NADI version (ADI-687, NADIS-96)
* FIXED: bug in adLDAP library; when LDAPS is enabled a custom port would not have been applied (ADI-690, NADIS-94)
* ADDED: hook next_ad_int_user_create_email which is executed when "Duplicate email prevention" is set to "Create" (ADI-691)
* FIXED: various issues with "Duplicate email prevention"; refactored logic (ADI-691)
* FIXED: NADI got disabled when using any WP-CLI command (ADI-692)
* ADDED: logging configuration can be set with filters (ADI-693)

= 2.1.8 =
* FIXED: compatibility issues when using the Woffice theme (ADI-659)
* FIXED: missing email parameter when creating users;  thanks to *nefarius* (#74, ADI-615)
* FIXED: an issue with the 'Prevent email change' option, https://wordpress.org/support/topic/new-user-creation-error/; thanks to *mlipenk* (ADI-670)
* ADDED: new hook to hide the 'Log in using SSO' option, https://wordpress.org/support/topic/remove-link-log-in-using-sso-on-login-page/; thanks to *vyatcheslav* (ADI-672)
* FIXED: refactored post authentication logic into separate services (ADI-671, ADI-673)

= 2.1.7 =
* FIXED: the hooks auth_before_create_or_update_user and auth_after_create_or_update_user were not registered so the SSO authentication always failed silently (ADI-668)

= 2.1.6 =
* FIXED: custom authentication filters were not registered properly and this fixes SSO related issues (ADI-665)
* FIXED: test authentication will now properly check for authorization groups again

= 2.1.5 =
* FIXED: replaced all references to the deprecated each-function with foreach (ADI-628)
* FIXED: authorization groups will now properly prevent users from logging in, https://wordpress.org/support/topic/authorization-groups-not-working/; thanks to *shmew22*, *pokertour* (ADI-664, #92)
* FIXED: the menu-visibility options were missing inside the profile-tab, https://wordpress.org/support/topic/menu-items-missing-3/; thanks to *5tu* (ADI-663);
* ADDED: 2 new filters to allow for custom validation during the authentication process; thanks to *Destabilizator* (ADI-657, #89)

= 2.1.4 =
* FIXED: isUserAuthorized() prevented login for users successfully authenticated via SSO at Active Directory due username was passed instead of guid
* FIXED: HelperTabs not opening anymore due bootstrap css .hidden class overwrites WordPress css .hidden class
* FIXED: verify connection input and button share the same element ID causing a DOM error in the browser console log
* ADDED: SSO Username variable helper tab content now contains a table including all supported variables and their current $_SERVER values

= 2.1.3 =
* ADDED: added message on the profile configuration page to inform customers about end of PHP version <7.1 support
* ADDED: json response for "Sync to WordPress" triggered via powershell
* ADDED: improved logging in within the Connection.php class
* ADDED: missing German translations
* ADDED: PHP_AUTH_USER to SSO username variables
* FIXED: app.config and password.controller.config being flagged by customer firewalls / security plugins which resulted in them not being loaded properly (renamed them)
* FIXED: redirect to target site not working properly after being authenticated via NADI SSO
* FIXED: isUserAuthorized() not working properly with UPNs
* FIXED: "Set local Password" not working if "Automatic user update" was enabled at the same time
* FIXED: "Overwrite with empty value" not working anymore

= 2.1.2 =
* FIXED: NTLM authentication not working if samAccountName of a user does not match the part of the UPN in front of the suffix
* REMOVED: NADI support license nag message on WordPress plug-in page

= 2.1.1 =
* ADDED: proxy address login; special thanks to *nedwidek* for contributing this functionality (#59)
* ADDED: profile picture ad attributes to the ad attributes dropdown at the ad attributes configuration page
* ADDED: claims based authentication; special thanks to *rgottsch* for contributing this functionality (#44)
* ADDED: new option to decide if you want to grant smart card users access to the WordPress environment
* ADDED: links to the specific documentation pages for each configuration page
* ADDED: powershell script to trigger "Sync to WordPress" and "Sync to AD"; special thanks to *nemchik* for contributing this (#64)
* FIXED: it is now possible to enter an empty base dn (#49)
* FIXED: adjusted base DN description
* FIXED: typo in LoginService.php (#59)
* REMOVED: whitespaces inside the rendered curl and wget tags
* REMOVED: old code that caused an warning with PHP 7.2.0 changes to count() and sizeOf()

= 2.1.0 =
* ADDED: NADI is now using Monolog for everything related to logs
* ADDED: added a button to manually persist "Logging" configurations
* FIXED: user attributes are now correctly logged
* FIXED: fixed a problem where the port configuration for LDAPS was not used
* FIXED: updated twig to the latest 1.x version. (2.x requires >= PHP 7.0.0)
* ADDED: debug logs messages will be not displayed in the frontend log anymore in order to prevent an overflow
* ADDED: dummy logger in order to prevent outdated premium extensions from crashing
* REMOVED: removed log4php from NADI

= 2.0.14 =
* ADDED: added frontend information banners for NADI premium extensions
* ADDED: added frontend information about why "Sync to WordPress" can not be started
* FIXED: members of not mapped security groups will now receive the default role "Subscriber"
* FIXED: "Clean existing Roles" is now default set to false
* ADDED: added new style for configuration page
* FIXED: fixed some style issues
* ADDED: added logic to determine if a NADI option already exists in the DB to prevent the problem saving options with default value true
* ADDED: added detailed log on which UAC flag is responsible for users not being imported
* FIXED: fixed logs destroying the user profile while trying to update a user profile / also caught exception
* FIXED: fixed template conditions causing problems in Microsoft Edge


= 2.0.13 =
* FIXED: switched from mcrypt to defuse/php-encryption
* FIXED: decoupled password update from user update to allow for automatic password updates without 'auto update user' set to true
* FIXED: marked brute force protection deprecated
* FIXED: minor bugs when using ldap over ssl
* ADDED: sync to ad now uses the GUID for synchronization
* FIXED: verify domain controller connectivity before incrementing brute force protection counter
* FIXED: custom attributes inside the user profile will prioritize the custom description; thanks to *mzemann*
* FIXED: changed the look of Sync to AD, Sync to WordPress and Test authentication
* ADDED: added row to users list for premium extension (custom user role management)
* FIXED: added the complete dirname when using require_once inside index.php (#47)

= 2.0.12 =
* ADDED: internationalization for all plugin strings, see https://translate.wordpress.org/projects/wp-plugins/next-active-directory-integration (ADI-432, ADI-436)
* FIXED: roles are now mapped using the GUID instead of sAMAccountName (ADI-428)
* ADDED: option for preventing disabled users to be synchronized to WordPress (ADI-223)
* ADDED: validation for Base DN
* FIXED: problem when sending brute force protection notifications via email (ADI-464)
* FIXED: non NADI users being blocked by the brute force protection
* FIXED: brute force protection now checks for the whole username (ADI-424)
* FIXED: updating user profiles without bind user (ADI-439)
* FIXED: countdown for brute force protection (ADI-456)

= 2.0.11 =
* ADDED: NTLM support for SSO (sponsored by Vogels - www.vogels.com)
* ADDED: implementation of hooks to provide an API (ADI-145)
* ADDED: premium extensions available for support license subscribers
* ADDED: log reason for not being able to increase max_execution_time (ADI-396)
* ADDED: log message that AD security group could not be found (ADI-397)
* ADDED: improve logging number of users to import from AD to WordPress (ADI-414)
* FIXED: synchronization does not work b/c getDomainSid returns "S-0" (ADI-412)
* FIXED: "Test authentication" does not allow characters like a backward slash (ADI-421)
* FIXED: permanent redirects after successful authentication (ADI-422)
* FIXED: error "the current user is being initialized without using $wp->init()" when using BuddyPress (ADI-416)
* FIXED: blocking of users with long user names (ADI-330)
* FIXED: get_blog_details replaced with get_site to maintain compatibility with WordPress 4.7+ (ADI-419)
* FIXED: restrict output of debug LDAP user information to only an amount of characters (ADI-420)
* FIXED: Sync to WordPress: default WordPress role "Subscriber" is not assigned (ADI-401)
* FIXED: Users with admin role granted by WordPress lose their role when logging into the site for the first time (ADI-380)

= 2.0.10 =
* ADDED: option to enable/disable authentication using XML-RPC
* FIXED: reworked user interface
* FIXED: sync ends after deleted account is no longer present in Active Directory
* FIXED: integration tests
* FIXED: emails will not be sent to administrators; thanks to *kyleflan* (#27)
* FIXED: users can now be disabled from WordPress
* ADDED: HTTP_X_REMOTE_USER is now an option in the SSO configuration; thanks to *laughtonsm* (#29)

= 2.0.9 =
* FIXED: add missing default value for method parameter

= 2.0.8 =
* FIXED: remove PHP 5.6 code; thanks to *requincreative* (#22)

= 2.0.7 =
* ADDED: custom user roles can be used in "Role equivalent groups"
* ADDED: the logger is disabled by default (and can be enabled inside the configuration)
* ADDED: log file path can be configured (default is wp-content/plugins/next-active-directory-integration/logs/debug.log)

= 2.0.6 =
* FIXED: show activation notice only after activating this plugin (https://wordpress.org/support/topic/activate-plugin-notification-bug/); thanks to bobchou9
* FIXED: SSO/verify-password errors by un-quoting values in $_GET/$_POST/$_SERVER. WordPress quotes all entries in $_GET/$_POST/$_SERVER automatically; thanks to plantjes (#20)

= 2.0.5 =
* FIXED: check if AD result is not empty before accessing distinguished name; thanks to petterannerwall (#16)
* ADDED: optional support for Down-Level User Name (like TEST\klammer) (#18)

= 2.0.4 =
* ADDED: make log pattern configurable (https://wordpress.org/support/topic/please-add-timestamps-to-the-debug-log-file/); Thanks to tmuikku

= 2.0.3 =
* FIXED: brute force protection is now be disabled; thanks to Munnday (David Munn) (#14)
* FIXED: the max count of login tries and the block time are now editable

= 2.0.2 =
* FIXED: SSO caused a PHP error during login; thanks to Jason Taylor and Munnday (David Munn) (#13)

= 2.0.1 =
* FIXED: missing german translation

= 2.0.0 =
* ADDED: support for WordPress Multisite through profiles
* ADDED: Profiles can be customized, including the permission of every option
* ADDED: support for PHP7
* ADDED: detailed documentation at https://www.active-directory-wp.com/docs/Getting_Started.html
* ADDED: experimental support for multiple Active Directory domains; see FAQ
* ADDED: easier handling and description of encryption methods for LDAP
* ADDED: additional columns in Multisite overview for networks and users
* ADDED: user names can be explicitly excluded from authentication
* ADDED: menu entries of *Next ADI* can be hidden
* ADDED: *Next ADI* can be disabled per Multisite site environment
* CHANGED: large user interface improvements
* CHANGED: complete rewrite of the PHP backend
* CHANGED: userPrincipalName is leading attribute for user identification instead of sAMAccountName
* FIXED: Role Equivalent Groups can be mapped to multiple WordPress roles instead of only one
* and much much more we can not list here. Please take the time and read the official documentation :-)

= 1.x (Active Directory Integration) =
* deprecated and no further development
