<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_Attribute_Repository')) {
	return;
}

/**
 * NextADInt_Ldap_Attribute_Repository provides access to LDAP/AD attributes and their definitions.
 * Definitions describes <strong>how<strong> an LDAP attribute is represented in the upper layer.
 * Ldap_Attribute objects are instantiated with help of the attribute definitions.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Ldap_Attribute_Repository
{
	/* @var NextADInt_Multisite_Configuration_Service */
	private $configuration;

	// contains custom attribute definitions
	private $customAttributeDefinitions = null;

	// contains attribute meta values (for custom and default attributes)
	private $viewableAttributeDefinitions = null;

	// contains custom and default attribute with modified meta values
	private $whitelistedAttributes = null;

	/**
	 * NextADInt_Ldap_Attribute_Repository constructor.
	 *
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration)
	{
		$this->configuration = $configuration;
	}

	private static $binaryAttributes
		= array(
			'objectguid',
		);

	private static $defaultAttributeNames
		= array(
			'cn',
			'givenname',
			'sn',
			'displayname',
			'description',
			'mail',
			'samaccountname',
			'userprincipalname',
			'useraccountcontrol',
			'objectguid',
			'domainsid',
		);

	private static $wellKnownAttributeTypes
		= array(
			'string',
			'list',
			'integer',
			'bool',
			'time',
			'timestamp',
			'octet',
			'cn',
		);

	/**
	 * Create array with attributes which must be available at startup to make ADI properly work
	 *
	 * @param array $attributes
	 *
	 * @access package
	 * @return array
	 */
	public function createDefaultAttributes($attributes = array())
	{
		foreach (self::$defaultAttributeNames as $attributeName) {
			$attribute = $this->createAttribute(null, $attributeName);
			$attributes[$attributeName] = $attribute;
		}

		return $attributes;
	}

	/**
	 * Return custom attributes which have been added by the administrator.
	 *
	 * @return array schema: array( 'attributeName' => array('attributeName', 'type', 'metaKey'), ...)
	 * @access public
	 */
	public function getCustomAttributeDefinitions()
	{
		if (null === $this->customAttributeDefinitions) {
			$this->customAttributeDefinitions = $this->findAttributeDefinitions(NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES);
		}

		return $this->customAttributeDefinitions;
	}

	/**
	 * Find attribute definitions by its permission
	 *
	 * @param string $permission
	 *
	 * @access private
	 * @return array schema: array( 'attributeName' => array('attributeName', 'type', 'metaKey'), ...)
	 */
	private function findAttributeDefinitions($permission)
	{
		$attributes = $this->configuration->getOptionValue($permission);

		return self::convertAttributeMapping($attributes);
	}

	/**
	 * Convert the given $additionAttributesString into our given format.
	 *
	 * @param $additionAttributesString string with attributes configuration
	 *
	 * @return array
	 *
	 */
	public static function convertAttributeMapping($additionAttributesString)
	{
		$r = array();

		$customAttributes = explode(";", $additionAttributesString);

		// collect previous custom attributes
		foreach ($customAttributes as $line) {
			if (empty($line)) {
				continue;
			}

			$settings = explode(":", $line);

			list($adAttribute, $dataType, $wordpressAttribute, $description, $viewInUserProfile, $syncToAd,
				$overwriteWithEmptyValue) = $settings;

			$r[$adAttribute] = array(
				'type'                => $dataType,
				'wordpress_attribute' => $wordpressAttribute,
				'overwrite'           => $overwriteWithEmptyValue,
				'description'         => $description,
				'sync_to_ad'          => $syncToAd,
				'view_in_userprofile' => $viewInUserProfile,
			);
		}

		return $r;
	}

	/**
	 * Checks $additionAttributesString for AdAttributenName conflicts.
	 *
	 * @param $additionAttributesString string with attributes configuration
	 *
	 * @return bool
	 *
	 */
	public static function checkAttributeNamesForConflict($additionAttributesString)
	{
		$adAttributeNameBuffer = array();
		$customAttributes = explode(";", $additionAttributesString);

		// collect previous custom attributes
		foreach ($customAttributes as $line) {
			if (empty($line)) {
				continue;
			}

			$settings = explode(":", $line);

			if (sizeof($adAttributeNameBuffer) <= 0) {
				$adAttributeNameBuffer[$settings[0]] = true;
				continue;
			}

			if (isset($adAttributeNameBuffer[$settings[0]])) {
				return true;
			}

			$adAttributeNameBuffer[$settings[0]] = true;
		}

		return false;
	}

	/**
	 * Create all Ldap_Attribute objects which have been defined by the administrator
	 *
	 * @param array $attributes
	 *
	 * @access package
	 * @return array
	 */
	public function createCustomAttributes($attributes = array())
	{
		$customAttributeDefinitions = $this->getCustomAttributeDefinitions();

		foreach ($customAttributeDefinitions as $attributeName => $attribute) {
			$metaObject = $this->createAttribute($attribute, $attributeName);
			$attributes[$attributeName] = $metaObject;
		}

		return $attributes;
	}

	/**
	 * Create attribute meta objects
	 *
	 * @access private
	 * @return array schema: array('cn' => new Ldap_Attribute(), 'ipphone' => new Ldap_Attribute(), ...)
	 */
	private function createWhitelistedAttributes()
	{
		$r = array();

		// merge the required attributes together with the administrator's defined attributes
		$r = $this->createDefaultAttributes($r);
		$r = $this->createCustomAttributes($r);

		return $r;
	}

	/**
	 * Return all attributes (default + additional) with the final meta values (stored in a AttributesMeta object).
	 *
	 * @return array schema: array('cn' => new Ldap_Attribute(), 'ipphone' => new Ldap_Attribute(), ...)
	 */
	public function getWhitelistedAttributes()
	{
		if (null === $this->whitelistedAttributes) {
			$this->whitelistedAttributes = $this->createWhitelistedAttributes();
		}

		return $this->whitelistedAttributes;
	}

	/**
	 * Returns all attribute names.
	 *
	 * @return array
	 */
	public function getAttributeNames()
	{
		return array_keys($this->getWhitelistedAttributes());
	}

	/**
	 * Filter all attributes by the 'show' value.
	 * Get all attributes which are visible ($show == true) or invisible ($show == false) in the user profile page
	 *
	 * @param bool|null $show
	 *
	 * @return array
	 */
	public function filterWhitelistedAttributes($show = null)
	{
		$filteredAttributes = array();
		$whitelistedAttributes = $this->getWhitelistedAttributes();

		/* @var $attribute NextADInt_Ldap_Attribute */
		foreach ($whitelistedAttributes as $attributeName => $attribute) {
			if (null === $show || $attribute->isViewable() === $show) {
				$filteredAttributes[$attributeName] = $attribute;
			}
		}

		return $filteredAttributes;
	}

	/**
	 * Return all attributes which are whitelisted and syncable back to the Active Directory
	 *
	 * @return array of Ldap_Attribute
	 */
	public function getSyncableAttributes()
	{
		$r = array();

		/** @var $attribute NextADInt_Ldap_Attribute */
		foreach ($this->getWhitelistedAttributes() as $ldapAttributeName => $attribute) {
			if ($attribute->isSyncable()) {
				$r[$ldapAttributeName] = $attribute;
			}
		}

		return $r;
	}

	/**
	 * Create a Ldap_Attribute object for an attribute.
	 *
	 * @param array  $attribute
	 * @param string $attributeName
	 *
	 * @access package
	 * @return NextADInt_Ldap_Attribute
	 */
	public function createAttribute($attribute, $attributeName)
	{

		//
		if (isset($attribute[NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_DESCRIPTION])) {
			$customDescription = $attribute[NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_DESCRIPTION];
		} else {
			$customDescription = '';
		}

		$defaultMetaKey = self::resolveDefaultAttributeMetaKey($attributeName);

		$type = self::resolveType($attribute);
		$metaKey = self::resolveWordPressAttribute($attribute, $defaultMetaKey);
		$description = NextADInt_Ldap_Attribute_Description::find($attributeName, $customDescription);

		$sync = self::resolveSyncToAd($attribute);
		$show = self::resolveViewInUserProfile($attribute);

		$overwriteWithEmpty = self::resolveOverwriteWithEmpty($attribute);

		// create object
		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setType($type);
		$metaObject->setMetakey($metaKey);
		$metaObject->setDescription($description);
		$metaObject->setSyncable($sync);
		$metaObject->setViewable($show);
		$metaObject->setOverwriteWithEmpty($overwriteWithEmpty);

		return $metaObject;
	}

	/**
	 * Get the attribute type from a line of the additional attribute definition.
	 *
	 * @param array $array
	 *
	 * @access package
	 * @return mixed|string
	 */
	public static function resolveType($array)
	{
		$type = NextADInt_Core_Util_ArrayUtil::get(NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE, $array, 'string');
		$type = NextADInt_Core_Util_StringUtil::toLowerCase(trim($type));

		if (!in_array($type, self::$wellKnownAttributeTypes)) {
			return 'string';
		}

		return $type;
	}

	/**
	 * Create the default attribute meta key for the given $attributeName.
	 *
	 * @param $attributeName
	 *
	 * @return string
	 */
	public static function resolveDefaultAttributeMetaKey($attributeName)
	{
		return NEXT_AD_INT_PREFIX . NextADInt_Core_Util_StringUtil::toLowerCase($attributeName);
	}

	/**
	 * Get the meta key from a line of the additional attribute definition.
	 *
	 * @param array  $array
	 * @param string $default
	 *
	 * @return mixed|string
	 */
	public static function resolveWordPressAttribute($array, $default = '')
	{
		$value = NextADInt_Core_Util_ArrayUtil::get(NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE, $array,
			$default);

		return trim($value);
	}

	/**
	 * Get the meta key from a line of the additional attribute definition.
	 *
	 * @param array  $array
	 * @param string $default
	 *
	 * @return mixed|string
	 */
	public static function resolveOverwriteWithEmpty($array, $default = '')
	{
		$value = NextADInt_Core_Util_ArrayUtil::get(NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_OVERWRITE_EMPTY, $array,
			$default);

		return trim($value);
	}

	/**
	 * Get the meta key from a line of the additional attribute SyncToAd.
	 *
	 * @param $array
	 *
	 * @return mixed|string
	 */
	public static function resolveSyncToAd($array)
	{
		$val = NextADInt_Core_Util_ArrayUtil::get(NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_SYNC_TO_AD, $array, false);

		return ($val === 'true');
	}

	/**
	 * Get the meta key from a line of the additional attribute viewInUserProfile.
	 *
	 * @param $array
	 *
	 * @return mixed|string
	 */
	public static function resolveViewInUserProfile($array)
	{
		$val = NextADInt_Core_Util_ArrayUtil::get(NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE, $array,
			false);

		return ($val === 'true');
	}

	/**
	 * Get the description from a line of the attribute meta values definition
	 *
	 * @param array  $additionalInformation
	 * @param string $metaKey
	 *
	 * @access package
	 * @return mixed
	 */
	public static function lookupDescription($additionalInformation, $metaKey)
	{
		if (isset($additionalInformation[1]) && $additionalInformation[1]) {
			return $additionalInformation[1];
		}

		return NextADInt_Ldap_Attribute_Description::find($metaKey, $metaKey);
	}

	/**
	 * Return an array containing the reserved attribute meta keys.
	 *
	 * @return array
	 */
	public static function getDefaultAttributeMetaKeys()
	{
		$attributes = self::getDefaultAttributeNames();

		return array_map(function($attribute) {
			return NextADInt_Ldap_Attribute_Repository::resolveDefaultAttributeMetaKey($attribute);
		}, $attributes);
	}

	/**
	 * Return all default attributes.
	 *
	 * @return array
	 */
	public static function getDefaultAttributeNames()
	{
		return self::$defaultAttributeNames;
	}

	/**
	 * Return all well known attribute types.
	 *
	 * @return array
	 */
	public static function findAllBinaryAttributes()
	{
		return self::$binaryAttributes;
	}

	/**
	 * @return array
	 */
	public static function findAllAttributeTypes()
	{
		return self::$wellKnownAttributeTypes;
	}
}