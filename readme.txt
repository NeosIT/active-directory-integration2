﻿=== Next Active Directory Integration ===
Contributors: neosit,tobi823,fatsquirrel,schakko,medan123
Tags: authentication, active directory, ldap, authorization, security, windows, sso
Requires at least: 5.6
Tested up to: 5.8.2
Stable tag: REPLACE_BY_JENKINS_SCRIPT
License: GPLv3

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
* PHP >= 7.4
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
To install Next Active Directory Integration you need at least WordPress 5.6 and PHP 7.4

Although only tested with Apache 2.2 and 2.4 *NADI* should work with all other common web servers like nginx and IIS.

Next Active Directory Integration requires a few PHP modules to be enabled. Please verify in your `php.ini` that *ldap* and *openssl* are activated.

	; required by *NADI*
	extension=php_ldap.dll
	extension=php_openssl.dll

= Important =

As of *2021-12-09* NADI did *no* longer support PHP version *< 7.4*. The reason is that security support for PHP 7.3 and below has beeen dropped by the maintainers as you can see in the official PHP documentation http://php.net/supported-versions.php. 
For security reasons and in order to use NADI in 2022 we hereby politely encourage you to migrate your environments to at least PHP 7.4 until then.

Thank you all for your support and understanding.

Best regards,
your NADI team.

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

For detailed information you can visit the official [GitHub repository of Next Active Directory Integration](https://github.com/NeosIT/active-directory-integration2)

= 2.3.0 =
* FIXED: when a user can not be found by email address, findByProxyAddress returns false (gh-#146). Configured *Sync to WordPress* credentials are still required for logging in with email addresses.
* FIXED: when using SSO-based logins, the "Exclude usernames from authentication" option still applies (gh-#142)
* ADDED: hooks for checking if NADI's authentication applies for a given username (gh-#142)
* CHANGED: WordPress 5.8.1 compatibility has been checked
* CHANGED: WordPress 5.8.2 compatibility has been checked
* REMOVED: Parameter $useLocalWordPressUser in NextADInt_Adi_Mail_Notification (gh-#135)
* REMOVED: Option 'Automatic user creation'. This option has been implicitly enabled for all installations and is no longer required (gh-#134)

= 2.2.3 =
* FIXED: Sync to WordPress fails if user is no longer present in Active Directory (gh-#141)

= 2.2.2 =
* FIXED: Boolean options can't be persisted correctly with 2.2.1 (gh-#140)

= 2.2.1 =
* FIXED: Missing meta_key "domainsid" results in TypeError or NextADInt_ActiveDirectory_Context::isMember (gh-#133)
* FIXED: Warning: Trying to access array offset on value of type null (gh-#139)

= 2.2.0 =
* ADDED: Kerberos principals are no longer treated as userPrincipalNames (ADI-715)
* ADDED: When using Kerberos SSO principals, you can map the Kerberos realm to multiple UPN suffixes (requires *Active Directory Forest* premium extension) (ADI-715)
* ADDED: When using NADI in an AD forest, you can now specify all SIDs of the connected domains (requires *Active Directory Forest* premium extension) (ADI-715)
* FIXED: When using a Global Catalog (GC), users with same sAMAccountName but different userPrincipalNames are not assigned correct during authentication (NADIS-133)
* FIXED-SECURITY: Users with same UPN prefix and password but different UPN suffix would be logged in with the wrong account (ADI-716)
* CHANGED: WordPress 5.6.1 compatibility has been checked
* CHANGED: WordPress 5.7 compatibility has been checked
* CHANGED: PHP 8.0 compatibility has been added (ADI-718, gh-#132, gh-#137)
* FIXED: Deprecation warning when trying to send mail notification for blocked users (ADI-719)
* FIXED: Option "Blog admin sets the option value." had no effect in Multisite environments (gh-#124)
* DEPRECATION-WARNING: For the upcoming release 2.3.0 we will remove "Internal password migration" (gh-#136), "Automatic user creation" (gh-#134) and "Email address conflict handling" (gh-#133)
* DEV: Slightly transition new issues to GitHub instead of internal Jira

= 2.1.12 =
* ADDED: PR gh-#107: allow self signed certificates
* CHANGED: notices for minimum PHP version 7.2 due to EOL of PHP 7.1
* FIXED: Test compatibility with latest stable PHPUnit version
* FIXED: gh-#127: PHP 7.4 compatibility and deprecation of some ldap_* functions
* FIXED: various typos and formatting errors in the administration user interface
* ADDED: hook for triggering Sync To WordPress and Sync To AD (ADI-526)

= 2.1.11 =
* CHANGED: Tested compatibility with upcoming version 5.4 of WordPress

= 2.1.10 =

* CHANGED: minimum PHP version to PHP 7.2 due to EOL of PHP 7.1
* CHANGED: Twig version updated to 1.41.0 (ADI-707)
* FIXED: When a non-existing user inside in WordPress authenticates in a multisite environment the first time, a warning is triggered (ADI-705)
* FIXED: A deleted user from Active Directory is mapped to the wrong user in WordPress; thanks to T. Kowalchuk (ADI-702)
* FIXED: PHP warning if user is deleted from Active Directory (ADI-701)
* FIXED: PHP error if touching of log file failed
* FIXED: "-DISABLED" suffix is added everytime a user is synchronized (ADI-697, NADIS-110)
* ADDED: hook next_ad_int_user_before_disable (ADI-699)
* FIXED: curly brace access (GitHub #119)

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
* FIXED: missing email parameter when creating users (GitHub #74 Thanks to nefarius, ADI-615)
* FIXED: an issue with the 'Prevent email change' option (https://wordpress.org/support/topic/new-user-creation-error/ Thanks to mlipenk, ADI-670)
* ADDED: new hook to hide the 'Log in using SSO' option (https://wordpress.org/support/topic/remove-link-log-in-using-sso-on-login-page/ Thanks to vyatcheslav, ADI-672)
* FIXED: refactored post authentication logic into separate services (ADI-671, ADI-673)

= 2.1.7 =
* FIXED: the hooks auth_before_create_or_update_user and auth_after_create_or_update_user were not registered so the SSO authentication always failed silently (ADI-668)

= 2.1.6 =
* FIXED: custom authentication filters were not registered properly (ADI-665) this will fix SSO related issues
* FIXED: test authentication will now properly check for authorization groups again

= 2.1.5 =
* FIXED: replaced all references to the deprecated each-function with foreach (ADI-628)
* FIXED: authorization groups will now properly prevent users from logging in (ADI-664, https://wordpress.org/support/topic/authorization-groups-not-working/ Thanks to shmew22, GitHub #92 Thanks to pokertour)
* FIXED: the menu-visibility options were missing inside the profile-tab (ADI-663, https://wordpress.org/support/topic/menu-items-missing-3/ Thanks to 5tu)
* ADDED: 2 new filters to allow for custom validation during the authentication process (ADI-657, GitHub #89 Thanks to Destabilizator)

= 2.1.4 =
* FIXED: isUserAuthorized() prevented login for users successfully authenticated via SSO at Active Directory due username was passed instead of guid
* FIXED: HelperTabs not opening anymore due bootstrap css .hidden class overwrites WordPress css .hidden class
* FIXED: verify connection input and button share the same element ID causing a DOM error in the browser console log
* ADDED: SSO Username variable helper tab content now contains a table including all supported variables and their current $_SERVER values

= 2.1.3 =
* ADD: added message on the profile configuration page to inform customers about end of PHP version <7.1 support
* ADD: json response for "Sync to WordPress" triggered via powershell
* ADD: improved logging in within the Connection.php class
* ADD: missing German translations
* ADD: PHP_AUTH_USER to SSO username variables
* FIXED: app.config and password.controller.config being flagged by customer firewalls / security plugins which resulted in them not being loaded properly (renamed them)
* FIXED: redirect to target site not working properly after being authenticated via NADI SSO
* FIXED: isUserAuthorized() not working properly with UPNs
* FIXED: "Set local Password" not working if "Automatic user update" was enabled at the same time
* FIXED: "Overwrite with empty value" not working anymore

= 2.1.2 =
* FIXED: NTLM authentication not working if samAccountName of a user does not match the part of the UPN in front of the suffix
* REMOVED: NADI support license nag message on WordPress plug-in page

= 2.1.1 =
* ADD: Github#59 proxy address login (Special thanks to Github user *nedwidek* for contributing this functionality)
* ADD: profile picture ad attributes to the ad attributes dropdown at the ad attributes configuration page
* ADD: Github#44 claims based authentication (Special thanks to Github user *rgottsch* for contributing this functionality)
* ADD: new option to decide if you want to grant smart card users access to the WordPress environment
* ADD: links to the specific documentation pages for each configuration page
* ADD: Github#64 powershell script to trigger "Sync to WordPress" and "Sync to AD" (Special thanks to Github user *nemchik* for contributing this)
* FIX: Github#49 its now possible to enter an empty base dn
* FIX: adjusted base DN description
* FIX: Github#59 typo in LoginService.php
* REMOVED: whitespaces inside the rendered curl and wget tags
* REMOVED: old code that caused an warning with PHP 7.2.0 changes to count() and sizeOf()

= 2.1.0 =
* ADD: NADI is now using Monolog for everything related to logs
* ADD: added a button to manually persist "Logging" configurations
* FIX: user attributes are now correctly logged
* FIX: fixed a problem where the port configuration for LDAPS was not used
* FIX: updated twig to the latest 1.x version. (2.x requires >= PHP 7.0.0)
* ADD: debug logs messages will be not displayed in the frontend log anymore in order to prevent an overflow
* ADD: dummy logger in order to prevent outdated premium extensions from crashing
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
