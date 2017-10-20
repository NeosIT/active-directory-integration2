=== Next Active Directory Integration ===
Contributors: neosit,tobi823,fatsquirrel,schakko,medan123
Tags: authentication, active directory, ldap, authorization, security, windows
Requires at least: 4.0
Tested up to: 4.7.2
Stable tag: REPLACE_BY_JENKINS_SCRIPT
License: GPLv3

Next Active Directory Integration allows WordPress to authenticate, authorize, create and update users against Microsoft Active Directory.


== Description ==

*Next Active Directory Integration* allows WordPress to authenticate, authorize, create and update users against Microsoft Active Directory. *NADI* ist a complete rewrite of its predecessor Active Directory Integration and therefore an own plugin.
You can easily import users from your Active Directory into your WordPress instance and keep both synchronized through *Next Active Directory Integration's* features.

Even if *NADI* is available for free we hope you purchase a support license to let us continue the work on Next Active Directory Integration.
You can purchase commercial support licences at [https://www.active-directory-wp.com/shop-overview/](https://www.active-directory-wp.com/shop-overview/). The support license does also contain access to our premium extensions.

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
* and much much more

= Premium Extensions =

As an owner of a valid support license you have access to the following premium extensions:

* Profile Pictures: Synchronize profile photos from Active Directory to WordPress without a 3rd party plug-in
* BuddyPress profile photo: Synchronize profile photos from Active Directory to BuddyPress
* Buddy Press simple attributes: Synchronize attributes from Active Directory/NADI to BuddyPress' custom profiles
* Login with Ultimate Member: Let UM users log in by using NADI
* Login with WooCommerce: Let WooCommerce users log in by using NADI

= Requirements =

* WordPress since 4.0
* PHP >= 5.6
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
To install Next Active Directory Integration you need at least WordPress 4.0 and PHP 5.3

Although only tested with Apache 2.2 and 2.4 *NADI* should work with all other common web servers like nginx and IIS.

Next Active Directory Integration requires a few PHP modules to be enabled. Please verify in your `php.ini` that *ldap*, *mbstring* and *openssl* are activated.

	; required by *NADI*
	extension=php_ldap.dll
	extension=php_mbstring.dll
	extension=php_openssl.dll

= Migration from ADI 1.x to NADI =
Please read [our migration guide](https://www.active-directory-wp.com/docs/Migration/index.html) carefully!

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

For detailed information you can visit the official [GitHub repository of Active Directory Integration 2](https://github.com/NeosIT/active-directory-integration2)

= 2.0.15 =
* ADD: NADI is now using Monolog for everything related to logs
* ADD: added a button to manually persist "Logging" configurations
* FIX: user attributes are now correctly logged
* FIX: fixed a problem where the port configuration for LDAPS was not used
* FIX: updated twig to the latest 1.x version. (2.x requires >= PHP 7.0.0)
* ADD: debug logs messages will be not displayed in the frontend log anymore in order to prevent an overflow
* REMOVED: removed log4php from NADI

= 2.0.14 =
* ADD: added frontend information banners for NADI premium extensions
* ADD: added frontend information about why "Sync to WordPress" can not be started
* FIX: members of not mapped security groups will now receive the default role "Subscriber"
* FIX: "Clean existing Roles" is now default set to false
* ADD: added new style for configuration page
* FIX: fixed some style issues
* ADD: added logic to determine if a NADI option already exists in the DB to prevent the problem saving options with default value true
* ADD: added detailed log on which UAC flag is responsible for users not beeing imported
* FIX: fixed logs destroying the user profile while trying to update a user profile / also catched exception
* FIX: fixed template conditions causing problems in Microsoft Edge


= 2.0.13 =
* FIX: switched from mcrypt to defuse/php-encryption
* FIX: decoupled password update from user update to allow for automatic password updates without 'auto update user' set to true
* FIX: marked brute force protection deprecated
* FIX: minor bugs when using ldap over ssl
* ADD: sync to ad now uses the GUID for syncronization
* FIX: verify domain controller connectivity before incrementing brute force protection counter
* FIX: custom attributes inside the user profile will prioritize the custom description (thanks to mzemann)
* FIX: changed the look of Sync to AD, Sync to WordPress and Test authentication
* ADD: added row to users list for premium extension (custom user role management)
* FIX: added the complete dirname when using require_once inside index.php (GitHub #47)

= 2.0.12 =
* ADD: internationalization for all plugin strings (ADI-432 ADI-436 see https://translate.wordpress.org/projects/wp-plugins/next-active-directory-integration)
* FIX: roles are now mapped using the GUID instead of sAMAccountName (ADI-428)
* ADD: option for preventing disabled users to be synchronized to WordPress (ADI-223)
* ADD: validation for Base DN
* FIX: problem when sending brute force protection notifications via email (ADI-464)
* FIX: non NADI users being blocked by the brute force protection
* FIX: brute force protection now checks for the whole username (ADI-424)
* FIX: updating user profiles without bind user (ADI-439)
* FIX: countdown for brute force protection (ADI-456)

= 2.0.11 =
* ADD: NTLM support for SSO (sponsored by Vogels - www.vogels.com)
* ADD: implementation of hooks to provide an API (ADI-145)
* ADD: premium extensions available for support license subscribers
* ADD: log reason for not being able to increase max_execution_time (ADI-396)
* ADD: log message that AD security group could not be found (ADI-397)
* ADD: improve logging number of users to import from AD to WordPress (ADI-414)
* FIX: synchronization does not work b/c getDomainSid returns "S-0" (ADI-412)
* FIX: "Test authentication" does not allow characters like a backward slash (ADI-421)
* FIX: permanent redirects after successful authentication (ADI-422)
* FIX: error "the current user is being initialized without using $wp->init()" when using BuddyPress (ADI-416)
* FIX: blocking of users with long user names (ADI-330)
* FIX: get_blog_details replaced with get_site to maintain compatibility with WordPress 4.7+ (ADI-419)
* FIX: restrict output of debug LDAP user information to only an amount of characters (ADI-420)
* FIX: Sync to WordPress: default WordPress role "Subscriber" is not assigned (ADI-401)
* FIX: Users with admin role granted by WordPress lose their role when logging into the site for the first time (ADI-380)

= 2.0.10 =
* ADD: option to enable/disable authentication using XML-RPC
* FIX: reworked user interface
* FIX: sync ends after deleted account is no longer present in Active Directory
* FIX: integration tests
* FIX: emails will not be sent to administrators (GitHub #27 Thanks to kyleflan)
* FIX: users can now be disabled from WordPress
* ADD: HTTP_X_REMOTE_USER is now an option in the SSO configuration (GitHub #29 Thanks to laughtonsm)

= 2.0.9 =
* FIX: add missing default value for method parameter

= 2.0.8 =
* FIX: remove PHP 5.6 code (GitHub #22 Thanks to requincreative)

= 2.0.7 =
* ADD: custom user roles can be used in "Role equivalent groups"
* ADD: the logger is disabled by default (and can be enabled inside the configuration)
* ADD: log file path can be configured (default is wp-content/plugins/next-active-directory-integration/logs/debug.log)

= 2.0.6 =
* FIX: show activation notice only after activating this plugin (https://wordpress.org/support/topic/activate-plugin-notification-bug/ Thanks to bobchou9)
* FIX: SSO/verify-password errors by un-quoting values in $_GET/$_POST/$_SERVER. WordPress quotes all entries in $_GET/$_POST/$_SERVER automatically (GitHub #20 Thanks to plantjes)

= 2.0.5 =
* FIX: check if AD result is not empty before accessing distinguished name (GitHub #16 Thanks to petterannerwall)
* ADD: optional support for Down-Level User Name (like TEST\klammer) (GitHub #18)

= 2.0.4 =
* ADD: make log pattern configurable (https://wordpress.org/support/topic/please-add-timestamps-to-the-debug-log-file/ Thanks to tmuikku)

= 2.0.3 =
* FIX: brute force protection is now be disabled (GitHub #14 Thanks to Munnday (David Munn))
* FIX: the max count of login tries and the block time are now editable

= 2.0.2 =
* FIX: SSO caused a PHP error during login (GitHub #13 Thanks to Jason Taylor and Munnday (David Munn))

= 2.0.1 =
* FIX: missing german translation

= 2.0.0 =
* ADD: support for WordPress Multisite through profiles
* ADD: Profiles can be customized, including the permission of every option
* ADD: support for PHP7
* ADD: detailed documentation at https://www.active-directory-wp.com/docs/Getting_Started.html
* ADD: experimental support for multiple Active Directory domains; see FAQ
* ADD: easier handling and description of encryption methods for LDAP
* ADD: additional columns in Multisite overview for networks and users
* ADD: user names can be explicitly excluded from authentication
* ADD: menu entries of *Next ADI* can be hidden
* ADD: *Next ADI* can be disabled per Multisite site environment
* CHANGE: large user interface improvements
* CHANGE: complete rewrite of the PHP backend
* CHANGE: userPrincipalName is leading attribute for user identification instead of sAMAccountName
* FIX: Role Equivalent Groups can be mapped to multiple WordPress roles instead of only one
* and much much more we can not list here. Please take the time and read the official documentation :-)

= 1.x (Active Directory Integration) =
* deprecated and no further development