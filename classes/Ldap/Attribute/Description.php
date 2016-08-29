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
			'cn'                            => __('Common Name', ADI_I18N),
			'givenname'                     => __('First name', ADI_I18N),
			'initials'                      => __('Initials', ADI_I18N),
			'sn'                            => __('Last name', ADI_I18N),
			'displayname'                   => __('Display name', ADI_I18N),
			'description'                   => __('Description', ADI_I18N),
			'physicaldeliveryofficename'    => __('Office', ADI_I18N),
			'telephonenumber'               => __('Telephone number', ADI_I18N),
			'mail'                          => __('E-mail', ADI_I18N),
			'wwwhomepage'                   => __('Web Page', ADI_I18N),

			// Account
			'samaccountname'                => __('User logon name', ADI_I18N),

			// Address
			'streetaddress'                 => __('Street', ADI_I18N),
			'postofficebox'                 => __('P.O. Box', ADI_I18N),
			'l'                             => __('City', ADI_I18N),
			'st'                            => __('State', ADI_I18N),
			'postalcode'                    => __('ZIP/Postal cide', ADI_I18N),
			'c'                             => __('Country abbreviation', ADI_I18N),
			'co'                            => __('Country', ADI_I18N),
			'countrycode'                   => __('Country code (number)', ADI_I18N),

			// Telephones
			'homephone'                     => __('Home', ADI_I18N),
			'otherhomephone'                => __('Home (other)', ADI_I18N),
			'pager'                         => __('Pager', ADI_I18N),
			'otherpager'                    => __('Pager (other)', ADI_I18N),
			'mobile'                        => __('Mobile', ADI_I18N),
			'othermobile'                   => __('Mobile (Other)', ADI_I18N),
			'facsimiletelephonenumber'      => __('Fax', ADI_I18N),
			'otherfacsimiletelephonenumber' => __('Fax (other)', ADI_I18N),
			'ipphone'                       => __('IP Phone', ADI_I18N),
			'otheripphone'                  => __('IP Phone (other)', ADI_I18N),
			'info'                          => __('Notes', ADI_I18N),

			// Organization
			'title'                         => __('Title', ADI_I18N),
			'department'                    => __('Department', ADI_I18N),
			'company'                       => __('Company', ADI_I18N),
			'manager'                       => __('Manager', ADI_I18N),
			'directreports'                 => __('Direct reports', ADI_I18N),
		);
	}

	private function __construct()
	{
	}

	private function __clone()
	{
	}

	/*
	 * Get the description for the attribute $attribute.
	 * If the description does not exists, then $fallbackValue will be returned.
	 *
	 * @param $attribute
	 * @param $fallback
	 *
	 * @return mixed
	 */
	public static function find($attribute, $fallback)
	{
		// get all descriptions
		$descriptions = self::findAll();

		// get single value
		if (isset($descriptions[$attribute])) {
			return $descriptions[$attribute];
		}

		return $fallback;
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