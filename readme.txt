=== Next Active Directory Integration ===
Contributors: NeosIT GmbH
Tags: authentication, active directory, ldap, authorization, security, windows
Requires at least: 4.0
Tested up to: 4.6.0
Stable tag: REPLACE_BY_JENKINS_SCRIPT
License: GPLv3

Next Active Directory Integration allows WordPress to authenticate, authorize, create and update users against Microsoft Active Directory.


== Description ==

*Next Active Directory Integration* allows WordPress to authenticate, authorize, create and update users against Microsoft Active Directory. *Next ADI* ist a complete rewrite of its predecessor Active Directory Integration and therefore an own plugin.
You can easily import users from your Active Directory into your WordPress instance and keep both synchronized through *Next Active Directory Integration's* features.

* Authenticating WordPress users against one or multiple AD Server
* Authorizing users by Active Directory group memberships
* Managing Active Directory authentication for WordPress Multisite installations
* Single Sign On with Kerberos or NTLM
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

Even if *Next ADI* is available for free we hope you purchase a support license to let us continue the work on Next Active Directory Integration.

= Requirements =

* WordPress since 4.0
* PHP >= 5.3
* LDAP support
* OpenSSL Support for TLS (recommended)

== Frequently Asked Questions ==

For further information please read the [FAQ](https://www.active-directory-wp.com/docs/FAQ.html) of our [official documentation](https://www.active-directory-wp.com/docs/Getting_Started.html).

= Does *Next ADI* support OpenLDAP, Active Directory Federation Services (AD FS) or Microsoft Azure? =
No. *Next ADI* does only support on-premise installations of Active Directory instances tested against Windows Server 2003, 2008 and 2012 in their different versions.

= Does *Next ADI* support multiple Active Directory domains? =
We do __not officially__ support multiple standalone Active Directories at the moment. In theory you can use multiple Active Directory domains when running WordPress in a multisite network:
- Each Active Directory domain __must__ have a unique UPN (userPrincipalName) suffix.
- Create a new profile for each Active Directory domain
- Enable *User > Append suffix to new users*. This option must not be changed.
- Fill the UPN suffixes of the domain into *User > Account suffix*
- Assign the profile to the corresponding sites.

It is necessary that each user of __every__ Active Directory domain has a unique *userPrincipalName*.
But as already said: we do not support multiple standalone ADs.

= Is Azure Active Directory supported? =
No. AAD does not exposes LDAP so there is no way to access your AAD users.

= Is Azure Active Directory Domain Services supported? =
We do not support Azure Active Directory Domain Services but from a technical point of view it works. AAD DS exposes LDAP so you can use all features from *Next ADI*.

= Is it possible to use TLS with a self-signed certificate on the AD server? =
Please read the [Use encrpytion with TLS](!Networking)

= Can I use LDAPS instead of TLS? =
Yes, you can. Just put "ldaps://" in front of the server in the option labeled "Domain Controller" (e.g. "ldaps://dc.domain.tld"), enter 636 as port and deactivate the option "Use TLS". But have in mind, that

= Is it possible to get more information from the Test Tool? =
Yes. Since 1.0-RC1 you get more information from the Test Tool by setting WordPress into debug mode. Simply add DEFINE('WP_DEBUG',true); to your wp-config.php.

= Where are the AD attributes stored in WordPress? =
If you activate "Automatic user creation" and "Automatic user synchronization" any AD attribute is stored inside the table `wp_usermeta`.
You can set the meta key as you like or use the default behavior, where the meta key is set to next_ad_int_<attribute> (e.g. next_ad_int_physicaldeliveryofficename for the *office* attribute).

= With WordPress 4.5 I can login with my e-mail address. Is this supported by Active Directory Integration? =
No. After *Next ADI* has been enabled it uses only the userPrincipalName or sAMAaccountName of the user for authentication. If you exclude a given username WordPress' default login method is used which supports login by e-mail.

= Authentication is successful but the user is not authorized by group membership. What is wrong? =
There can be some reasons for this behaviour:

- A common mistake is that the Base DN is set to a wrong value. If the user resides in an Organizational Unit (OU) that is not "below" the Base DN the groups the user belongs to can not be determined.
    A quick solution is to set the Base DN to something like dc=mydomain,dc=local without any OU.
- Another common mistake is to use __ou__=users,dc=mydomain,dc=local instead of __cn__=users,dc=mydomain,dc=local as Base DN.
- Depending upon your *Next ADI* and Active Directory configuration you may enter the following situation: the *sAMAccountname* of the user is "testA" and the userPrincipalName is "testB".
The authentication phase will succeed in both cases b/c internally the Active Directory checks both attributes.
The group membership for the authorization is looked up by adLDAP. If no "@" character is present in the username, adLDAP uses the *sAMAccountname* attribute to lookup the user.
The username "testB" won't be able to login because the lookup of his group returns always an empty set. The easiest way to fix this problem is to use the same sAMAccountName and userPrincipalName.

= Why will no users be imported if I'm using "Domain Users" as security group for Bulk Import? =
Here we have a special problem with the builtin security group "Domain Users". In detail: the security group "Domain Users" is usually the primary group of all users. In this case the members of this security group are not listed in the members attribute of the group. To import all users of the security group "Domain Users" you must set the option "Import members of security groups" to "Domain Users;id:513". The part "id:513" means "Import all users whos primaryGroupID is 513." And as you might have guessed, 513 is the ID of the security group "Domain Users".

= I have problems with accounts that have special characters in the username. What can I do? =
It is never a good idea to allow special characters in usernames! For *Next ADI* it won't be a problem, but in WordPress only lowercase letters (a-z) and numbers are allowed. The only option is to change the usernames in AD. Hey! Stop! Don't shoot the messenger.

= Is there an official bug tracker for *Next ADI*? =
Yes, we use GitHub. Any issue provided from the community will go there: https://github.com/NeosIT/active-directory-integration2/issues.

= How do you handle support requests? =
Please purchase a support license and open a ticket.

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
10. Extend WordPress' user list with Active Directory information
11. Profile assignment in WordPress Multisite
12. Custom *Next ADI* profile in WordPress Multisite environment


== Installation ==

= Requirements =
To install Next Active Directory Integration you need at least WordPress 4.0  and PHP 5.3

Although only tested with Apache 2.2 and 2.4 *Next ADI* should work with all other common web servers like nginx and IIS.

Next Active Directory Integration requires a few PHP modules to be enabled. Please verify in your `php.ini` that *ldap*, *mcrypt* and *mbstring* are activated. But it is very likely, that *mcrypt* is already enabled and not listed in the `php.ini`.
If you are planning to use encryption for your LDAP connection - which we *highly* suggest - you although need *openssl* to be enabled.

	; required by *Next ADI*
	extension=php_ldap.dll
	extension=php_mbstring.dll
	; required by LDAP/S and STARTTLS
	extension=php_openssl.dll

= Migration from ADI 1.x to Next ADI =
Please read [our migration guide](https://www.active-directory-wp.com/docs/Migration/index.html) carefully!

= Installation =

**Next ADI** can be easily installed from the [WordPress Plugin Directory](https://codex.wordpress.org/Managing_Plugins#Installing_Plugins).

It is also possible to download the latest version from [https://downloads.wordpress.org/plugin/next-active-directory-integration.zip"](https://downloads.wordpress.org/plugin/next-active-directory-integration.zip) and unpack the folder to your *wordpress/wp-content/plugins* directory.

Developers can clone the [Git Repository](https://github.com/NeosIT/active-directory-integration2) inside their *wordpress/wp-content/plugins* directory and download the dependencies with *composer*.

= Single Site =
- Visit your WordPress blog and login into your WordPress dashboard as *Administrator*
- Click on the *Plugins* in the left the navigation bar
- Activate the *Next Active Directory Integration* plug-in

= Network installation =
- Visit your WordPress network dashboard as *Super Admin*
- Click on the *Plugins* link in the left the navigation bar
- Activate the *Next Active Directory Integration* plug-in. In a WordPress network installation *Next ADI* should be *Network activated*.

You can enable/disable *Next ADI* for specific blogs by using the *Profiles* feature of *Next ADI*.
It is __not__ possible to activate *Next ADI* for a site inside a network.

== Changelog ==

For detailed information you can visit the official [GitHub repository of Active Directory Integration 2](https://github.com/NeosIT/active-directory-integration2)

= 2.0.6 =
* FIX: unescape values in $_GET/$_POST/$_SERVER which are already been escaped by WordPress (GitHub #20 Thanks to plantjes)

= 2.0.5 =
* FIX: check if AD result is not empty before accessing distinguishedname (GitHub #16 Thanks to petterannerwall)
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
* ADD: usernames can be explicitly excluded from authentication
* ADD: menu entries of *Next ADI* can be hidden
* ADD: *Next ADI* can be disabled per Multisite site environment
* CHANGE: large user interface improvements
* CHANGE: complete rewrite of the PHP backend
* CHANGE: userPrincipalName is leading attribute for user identification instead of samaccountname
* FIX: Role Equivalent Groups can be mapped to multiple WordPress roles instead of only one
* and much much more we can not list here. Please take the time and read the official documentation :-)

= 1.x (Active Directory Integration) =
* deprecated and no further development
