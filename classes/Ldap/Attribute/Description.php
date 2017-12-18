<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_Attribute_Description')) {
	return;
}

/**
 * NextADInt_Ldap_Attribute_Description contains the translated descriptions for common Active Directory/LDAP attributes.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class NextADInt_Ldap_Attribute_Description
{
	private static $descriptions = null;

	private static function initializeDescriptions() {
		self::$descriptions = array(
			// General
			'cn'                            => __('Common Name', 'next-active-directory-integration'),
			'givenname'                     => __('First name', 'next-active-directory-integration'),
			'initials'                      => __('Initials', 'next-active-directory-integration'),
			'sn'                            => __('Last name', 'next-active-directory-integration'),
			'displayname'                   => __('Display name', 'next-active-directory-integration'),
			'description'                   => __('Description', 'next-active-directory-integration'),
			'physicaldeliveryofficename'    => __('Office', 'next-active-directory-integration'),
			'telephonenumber'               => __('Telephone number', 'next-active-directory-integration'),
			'mail'                          => __('E-mail', 'next-active-directory-integration'),
			'wwwhomepage'                   => __('Web Page', 'next-active-directory-integration'),

			// Account
			'samaccountname'                => __('User logon name', 'next-active-directory-integration'),

			// Address
			'streetaddress'                 => __('Street', 'next-active-directory-integration'),
			'postofficebox'                 => __('P.O. Box', 'next-active-directory-integration'),
			'l'                             => __('City', 'next-active-directory-integration'),
			'st'                            => __('State', 'next-active-directory-integration'),
			'postalcode'                    => __('ZIP/Postal cide', 'next-active-directory-integration'),
			'c'                             => __('Country abbreviation', 'next-active-directory-integration'),
			'co'                            => __('Country', 'next-active-directory-integration'),
			'countrycode'                   => __('Country code (number)', 'next-active-directory-integration'),

			// Telephones
			'homephone'                     => __('Home', 'next-active-directory-integration'),
			'otherhomephone'                => __('Home (other)', 'next-active-directory-integration'),
			'pager'                         => __('Pager', 'next-active-directory-integration'),
			'otherpager'                    => __('Pager (other)', 'next-active-directory-integration'),
			'mobile'                        => __('Mobile', 'next-active-directory-integration'),
			'othermobile'                   => __('Mobile (Other)', 'next-active-directory-integration'),
			'facsimiletelephonenumber'      => __('Fax', 'next-active-directory-integration'),
			'otherfacsimiletelephonenumber' => __('Fax (other)', 'next-active-directory-integration'),
			'ipphone'                       => __('IP Phone', 'next-active-directory-integration'),
			'otheripphone'                  => __('IP Phone (other)', 'next-active-directory-integration'),
			'info'                          => __('Notes', 'next-active-directory-integration'),

			// Organization
			'title'                         => __('Title', 'next-active-directory-integration'),
			'department'                    => __('Department', 'next-active-directory-integration'),
			'company'                       => __('Company', 'next-active-directory-integration'),
			'manager'                       => __('Manager', 'next-active-directory-integration'),
			'directreports'                 => __('Direct reports', 'next-active-directory-integration'),

			// Pictures
			'thumbnailPhoto'                => __('Thumbnail Photo', 'next-active-directory-integration'),
			'jpegPhoto'                 	=> __('Jpeg Photo', 'next-active-directory-integration'),
			'thumbnailLogo'                 => __('Thumbnail Logo', 'next-active-directory-integration'),
		);
	}

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/*
	 * Check if there is a custom description $custom description
	 * If no custom description exists, then find all default ad attribute descriptions and look for a match.
	 * If it is a custom ad attribute with no description given return the wordpress_attribute name as string.
	 * @param $attribute
	 * @param $customDescription
	 *
	 * @return mixed
	 */
	public static function find($attribute, $customDescription)
	{

		// check for custom description
		if ($customDescription) {
			return $customDescription;
		}

		// get all descriptions
		$descriptions = self::findAll();

		// get single value
		if (isset($descriptions[$attribute])) {
			return $descriptions[$attribute];
		}

		return $attribute;
	}

	/**
	 * Return the associative array with attribute as key and its description as value.
	 * array('attribute' => 'description', ...)
	 *
	 * @return array
	 */
	public static function findAll()
	{
		if (null === self::$descriptions) {
			self::initializeDescriptions();
		}

		// return all values
		return self::$descriptions;
	}
}