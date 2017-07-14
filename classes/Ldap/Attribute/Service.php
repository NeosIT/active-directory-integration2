<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_Attribute_Service')) {
	return;
}

/**
 * NextADInt_Ldap_Attribute_Service acts as a gateway for whitelisted LDAP attributes which are allowed to read in the frontend and posted to the backend.
 * The administrator can whitelist different attributes on a per blog or per multisite base.
 *
 * @author Tobias Hellmann <tobias.hellmann@neos-it.de>
 * @access private
 */
class NextADInt_Ldap_Attribute_Service
{
	/**
	 * @var NextADInt_Ldap_Attribute_Repository
	 */
	private $attributeRepository;

	/**
	 * @var NextADInt_Ldap_Connection
	 */
	private $ldapConnection;

	/* @var Logger */
	private $logger;

	/**
	 * NextADInt_Ldap_Attribute_Service constructor.
	 *
	 * @param NextADInt_Ldap_Connection $ldapConnection
	 * @param NextADInt_Ldap_Attribute_Repository $attributeRepository
	 */
	public function __construct(NextADInt_Ldap_Connection $ldapConnection, NextADInt_Ldap_Attribute_Repository $attributeRepository)
	{
		$this->attributeRepository = $attributeRepository;
		$this->ldapConnection = $ldapConnection;

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * This method sanitizes the user attribute values from the Active Directory.
	 * Missing user attribute names entries will be added (with an empty string as value).
	 * If the user attribute value is an array and has got the key 'count', then implode the array to an string.
	 *
	 * @param array $attributeNames
	 * @param array|false $ldapData
	 *
	 * @access package
	 *
	 * @return array
	 */
	function parseLdapResponse($attributeNames = array(), $ldapData)
	{
		NextADInt_Core_Assert::notNull($attributeNames);

		$sanitizedValues = array();

		// iterate over allUserAttributes and try to find corresponding values from Active Directory
		foreach ($attributeNames as $name) {
			$sanitizedValues[$name] = self::getLdapAttribute($name, $ldapData);
		}

		return $sanitizedValues;
	}

	/**
	 * Get the requested attribute value from the LDAP response array
	 *
	 * @param string $attributeName
	 * @param array|false $ldapData array with LDAP raw data or false if no data has been set
	 *
	 * @return string
	 */
	public static function getLdapAttribute($attributeName, $ldapData)
	{
		// default value
		$attributeName = NextADInt_Core_Util_StringUtil::toLowerCase($attributeName);
		$sanitizedValue = '';

		// check $attributeValues
		if (is_array($ldapData) && isset($ldapData[$attributeName])) {
			$sanitizedValue = $ldapData[$attributeName];
		}

		// convert array to string
		if (is_array($sanitizedValue)) {
			// remove count if it exists
			if (isset($sanitizedValue['count'])) {
				unset($sanitizedValue['count']);
			}

			$sanitizedValue = implode("\n", $sanitizedValue);
		}

		// if our attribute is registered as a binary string, we convert it to a real string
		if (NextADInt_Core_Util_ArrayUtil::containsIgnoreCase($attributeName,
			NextADInt_Ldap_Attribute_Repository::findAllBinaryAttributes())
		) {
			$sanitizedValue = NextADInt_Core_Util_StringUtil::binaryToGuid($sanitizedValue);
		}

		return $sanitizedValue;
	}

	/**
	 * @param NextADInt_Ldap_Attribute $attribute
	 * @param array $ldapData
	 *
	 * @return array
	 */
	public static function getLdapValue($attribute, $ldapData)
	{
		// values from $userAttributeArray
		$value = $ldapData[$attribute->getMetakey()];

		// if $type is a list, then split the string value
		if ('list' === $attribute->getType()) {
			$syncValue = NextADInt_Core_Util_StringUtil::splitText($value);
		} else {
			$syncValue = $value;
		}

		// do not return an empty array or string
		if (!$syncValue) {
			return array(' ');
		}

		// non array values must be capsule in an array
		if (!is_array($syncValue)) {
			return array($syncValue);
		}

		return $syncValue;
	}

	/**
	 * Find all LDAP attributes for the user which have been enabled in the blog or multisite
	 *
	 * @param string $username GUID, sAMAccountName or userPrincipalName
	 * @param bool $isGUID
	 *
	 * @return NextADInt_Ldap_Attributes
	 */
	public function findLdapAttributesOfUsername($username, $isGUID = false)
	{
		$attributeNames = $this->attributeRepository->getAttributeNames();
		$raw = array();

		// ADI-145: provide API
		$attributeNames = apply_filters(NEXT_AD_INT_PREFIX .  'ldap_filter_synchronizable_attributes', $attributeNames, $username, $isGUID);

		if (!empty($username)) {
			// make sure that only non-empty usernames are resolved
			$raw = $this->ldapConnection->findAttributesOfUser($username, $attributeNames, $isGUID);
		}

		$filtered = $this->parseLdapResponse($attributeNames, $raw);

		return new NextADInt_Ldap_Attributes($raw, $filtered);
	}

	/**
	 * Find the LDAP attributes for the given credentials or guid.
	 *
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @param string $guid
	 *
	 * @return NextADInt_Ldap_Attributes
	 */
	public function findLdapAttributesOfUser(NextADInt_Adi_Authentication_Credentials $credentials, $guid)
	{
		if (isset($guid)) {
			$ldapAttributes = $this->findLdapAttributesOfUsername($guid, true);
		}

		if (empty($ldapAttributes) || (false == $ldapAttributes->getRaw())) {
			$ldapAttributes = $this->findLdapAttributesOfUsername($credentials->getSAMAccountName());
		}

		if (empty($ldapAttributes) || (false == $ldapAttributes->getRaw())) {
			$ldapAttributes = $this->findLdapAttributesOfUsername($credentials->getUserPrincipalName());
		}

		if (empty($ldapAttributes) || (false == $ldapAttributes->getRaw())) {
			$this->logger->debug('Cannot find valid ldap attributes for the given user.');
		}

		return $ldapAttributes;
	}

	/**
	 * Find the custom LDAP attribute for the given username
	 * @param string $username
	 * @param string $attribute
	 * @return bool|string false if attribute is empty or not inside the returned array of attribute values
	 */
	public function findLdapCustomAttributeOfUsername($username, $attribute)
	{
		$attributes = array($attribute);

		$raw = $this->ldapConnection->findAttributesOfUser($username, $attributes, false);
		$filtered = $this->parseLdapResponse($attributes, $raw);

		// ADI-412: If the user has no upn
		if (!isset($filtered[$attribute]) || empty($filtered[$attribute])) {
			return false;
		}

		return $filtered[$attribute];
	}

	/**
	 * Find a single attribute for the give credentials. It first tests the sAMAccountName and then the userPrincipalName of the credentials
	 *
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @param string $attribute
	 * @return string|bool if attribute could not be found it returns false
	 */
	public function findLdapCustomAttributeOfUser(NextADInt_Adi_Authentication_Credentials $credentials, $attribute)
	{
		$value = $this->findLdapCustomAttributeOfUsername($credentials->getUserPrincipalName(), $attribute);

		if (false === $value) {
			$this->logger->warn("Could not locate custom attribute '" . $attribute . "' for userPrincipalName '" . $credentials->getUserPrincipalName() . "'. Fall back to sAMAccountName...'");

			$value = $this->findLdapCustomAttributeOfUsername($credentials->getSAMAccountName(), $attribute);
		}

		return $value;
	}

	/**
	 * Find LDAP attribute containing the objectSid. At first it uses the full userPrincipalName and then falls back to the sAMAccountName to prevent non-resolveable AD usernames.
	 *
	 * @param NextADInt_Adi_Authentication_Credentials $credentials
	 * @return NextADInt_Ldap_Attributes|false false if username account could not be found
	 */
	public function getObjectSid(NextADInt_Adi_Authentication_Credentials $credentials)
	{
		NextADInt_Core_Assert::notNull($credentials, "credentials must not be null");
		$objectSid = $this->findLdapCustomAttributeOfUser($credentials, 'objectsid');

		if (false === $objectSid) {
			return false;
		}

		return $this->ldapConnection->getAdLdap()->convertObjectSidBinaryToString($objectSid);
	}

	/**
	 * Delegate to NextADInt_Ldap_Connection#findNetBiosName
	 *
	 * @return string|boolean
	 */
	public function getNetBiosName() {
		$netBiosName = $this->ldapConnection->findNetBiosName();

		return $netBiosName;
	}

	/**
	 * @return NextADInt_Ldap_Attribute_Repository
	 */
	public function getRepository()
	{
		return $this->attributeRepository;
	}
}