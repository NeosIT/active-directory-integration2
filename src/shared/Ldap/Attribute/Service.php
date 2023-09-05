<?php

namespace Dreitier\Ldap\Attribute;

use Dreitier\ActiveDirectory\Sid;
use Dreitier\Ldap\Attributes;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\UserQuery;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\ArrayUtil;
use Dreitier\Util\Assert;
use Dreitier\Util\StringUtil;
use Dreitier\Nadi\Authentication\Credentials;

/**
 * acts as a gateway for whitelisted LDAP attributes which are allowed to read in the frontend and posted to the backend.
 * The administrator can whitelist different attributes on a per blog or per multisite base.
 *
 * @author Tobias Hellmann <tobias.hellmann@neos-it.de>
 * @access private
 */
class Service
{
	/**
	 * @var \Dreitier\Ldap\Attribute\Repository
	 */
	private $ldapAttributeRepository;

	/**
	 * @var Connection
	 */
	private $ldapConnection;

	/* @var Logger */
	private $logger;

	/**
	 * @param Connection $ldapConnection
	 * @param Repository $ldapAttributeRepository
	 */
	public function __construct(Connection $ldapConnection, Repository $ldapAttributeRepository)
	{
		$this->ldapAttributeRepository = $ldapAttributeRepository;
		$this->ldapConnection = $ldapConnection;

		$this->logger = NadiLog::getInstance();
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
	function parseLdapResponse($attributeNames, $ldapData)
	{
		Assert::notNull($attributeNames);

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
		$attributeName = StringUtil::toLowerCase($attributeName);
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
		if (ArrayUtil::containsIgnoreCase($attributeName,
			Repository::findAllBinaryAttributes())
		) {
			$sanitizedValue = StringUtil::binaryToGuid($sanitizedValue);
		}

		return $sanitizedValue;
	}

	/**
	 * @param Attribute $attribute
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
			$syncValue = StringUtil::splitText($value);
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
	 * Find all LDAP attributes for a user based upon the given query
	 *
	 * @param UserQuery $userQuery GUID, sAMAccountName or userPrincipalName
	 *
	 * @return Attributes
	 */
	public function findLdapAttributesOfUser(UserQuery $userQuery)
	{
		$attributeNames = $this->ldapAttributeRepository->getAttributeNames();
		$raw = array();

		// ADI-145: provide API
		$attributeNames = apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'ldap_filter_synchronizable_attributes', $attributeNames, $userQuery);

		if (!empty($userQuery->getPrincipal())) {
			// make sure that only non-empty usernames are resolved
			$raw = $this->ldapConnection->findAttributesOfUser($userQuery, $attributeNames);
		}

		$filtered = $this->parseLdapResponse($attributeNames, $raw);

		return new Attributes($raw, $filtered);
	}

	/**
	 * Based upon the query, different markers are tried to find the user:
	 * <ul>
	 * <li>if the query is specified as GUID; this is searched first</li>
	 * <li>userPrincipalName is tried as second</li>
	 * <li>sAMAccountNAme is tried last in line. This can deliver multiple results in an AD forest. You have to use the Active Directory Forest premium extension</li>
	 * </ul>
	 *
	 * @param UserQuery $userQuery
	 * @return Attributes
	 * @issue ADI-713
	 */
	public function resolveLdapAttributes(UserQuery $userQuery)
	{
		/**
		 * @var Attributes
		 */
		$ldapAttributes = null;

		// GUID has priority
		if ($userQuery->isGuid()) {
			$ldapAttributes = $this->findLdapAttributesOfUser($userQuery);
		}

		// NADIS-133: When using a Global Catalog (GC), users with same sAMAccountName but different userPrincipalNames are not assigned correct during authentication
		// this requires us to lookup the userPrincipalName *before* the sAMAccountName
		if (empty($ldapAttributes) || (false == $ldapAttributes->getRaw())) {
			$ldapAttributes = $this->findLdapAttributesOfUser($userQuery->withPrincipal($userQuery->getCredentials()->getUserPrincipalName()));
		}

		// fallback to the sAMAccountName
		if (empty($ldapAttributes) || (false == $ldapAttributes->getRaw())) {
			$ldapAttributes = $this->findLdapAttributesOfUser($userQuery->withPrincipal($userQuery->getCredentials()->getSAMAccountName()));
		}

		if (empty($ldapAttributes) || (false == $ldapAttributes->getRaw())) {
			$this->logger->debug('Cannot find valid ldap attributes for the given user.');
		}

		return $ldapAttributes;
	}

	/**
	 * Find the custom LDAP attribute for the given user query
	 * @param UserQuery $userQuery
	 * @param string $attribute
	 * @return bool|string false if attribute is empty or not inside the returned array of attribute values
	 */
	public function findLdapCustomAttributeOfUser(UserQuery $userQuery, $attribute)
	{
		$attributes = array($attribute);

		$raw = $this->ldapConnection->findAttributesOfUser($userQuery, $attributes);
		$filtered = $this->parseLdapResponse($attributes, $raw);

		// ADI-412: If the user has no upn
		if (!isset($filtered[$attribute]) || empty($filtered[$attribute])) {
			return false;
		}

		return $filtered[$attribute];
	}

	/**
	 * Find a single attribute for the given credentials. It first tests the userPrincipalName  and then the sAMAccountName of the credentials
	 *
	 * @param UserQuery $userQuery
	 * @param string $attribute
	 * @return string|bool if attribute could not be found it returns false
	 */
	public function resolveLdapCustomAttribute(UserQuery $userQuery, $attribute)
	{
		$value = $this->findLdapCustomAttributeOfUser($userQuery->withPrincipal($userQuery->getCredentials()->getUserPrincipalName()), $attribute);

		if (false === $value) {
			$this->logger->warning("Could not locate custom attribute '" . $attribute . "' for query '" . $userQuery->getCredentials()->getUserPrincipalName() . "'. Fall back to sAMAccountName...'");

			$value = $this->findLdapCustomAttributeOfUser($userQuery->withPrincipal($userQuery->getCredentials()->getSAMAccountName()), $attribute);
		}

		return $value;
	}

	/**
	 * Find LDAP attribute containing the objectSid. At first it uses the full userPrincipalName and then falls back to the sAMAccountName to prevent non-resolveable AD usernames.
	 *
	 * @param Credentials $credentials
	 * @return Sid|false false if username account could not be found
	 */
	public function getObjectSid(Credentials $credentials)
	{
		Assert::notNull($credentials, "credentials must not be null");
		$objectSid = $this->findLdapCustomAttributeOfUser($credentials->toUserQuery(), 'objectsid');

		if (false === $objectSid) {
			return false;
		}

		return Sid::of($objectSid);
	}

	/**
	 * Delegate to Connection#findNetBiosName
	 *
	 * @return string|boolean
	 */
	public function getNetBiosName()
	{
		$netBiosName = $this->ldapConnection->findNetBiosName();

		return $netBiosName;
	}

	/**
	 * @return Repository
	 */
	public function getRepository()
	{
		return $this->ldapAttributeRepository;
	}
}