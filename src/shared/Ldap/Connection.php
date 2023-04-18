<?php

namespace Dreitier\Ldap;

use Dreitier\ActiveDirectory\Context;
use Dreitier\ActiveDirectory\Sid;
use Dreitier\AdLdap\AdLdap;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\User\Persistence\Repository;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\ArrayUtil;
use Dreitier\Util\StringUtil;
use Dreitier\Util\Util;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Option\Encryption;

/**
 * This class abstracts the usage of the adLDAP library for easier handling common use-cases and falls back to the default values of the blog if values are not present.
 *
 * Connection establishes a connection to the defined Active Directories, authenticates users and contains help functions for
 * checking group membership etc.
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 */
class Connection
{
	/* @var Service $configuration */
	private $configuration;

	/* @var AdLdap $adldap */
	private $adldap;

	/* @var Logger $logger */
	private $logger;

	/* @var Context */
	private $activeDirectoryContext;

	/**
	 * @param Service $multisiteConfigurationService
	 * @param Context $activeDirectoryContext
	 */
	public function __construct(Service $multisiteConfigurationService,
								Context $activeDirectoryContext
	)
	{
		$this->configuration = $multisiteConfigurationService;
		$this->activeDirectoryContext = $activeDirectoryContext;

		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Register additional hooks
	 * @since 2.0.0
	 */
	public function register()
	{
		// ADI-713: Map user information when search for GUID, userPrincipalName or sAMAccountName
		add_filter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'ldap_map_userinfo', array($this, 'mapUserInfo'), 10, 5);
	}

	/**
	 * Create an connection to the Active Directory. But the state of the connection is unknown.
	 * You have to check if with $this->checkConnection().
	 *
	 * @param ConnectionDetails $connectionDetails
	 */
	public function connect(ConnectionDetails $connectionDetails)
	{
		$config = $this->createConfiguration($connectionDetails);

		try {
			$this->createAdLdap($config);
		} catch (\Exception $e) {
			$this->logger->error('Creating adLDAP object failed. ' . $e->getMessage());

			if (is_object($this->adldap)) {
				$this->logger->debug('adLDAP last error number: ' . print_r($this->adldap->get_last_errno(), true));
				$this->logger->debug('adLDAP last error: ' . print_r($this->adldap->get_last_error(), true));
			}
		}
	}

	/**
	 * Based upon the provided Connection a configuration array for adLDAP is created.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return array
	 */
	public function createConfiguration(ConnectionDetails $connectionDetails)
	{
		$useTls = $this->getUseTls($connectionDetails);
		$useSsl = $this->getUseSsl($connectionDetails);

		$config = array(
			'account_suffix' => '',
			'base_dn' => $this->getBaseDn($connectionDetails),
			'domain_controllers' => $this->getDomainControllers($connectionDetails),
			'ad_port' => $this->getAdPort($connectionDetails),
			'use_tls' => $useTls,    // STARTTLS
			// ADI-482 enable LDAPS support
			'use_ssl' => $useSsl,  // LDAP over SSL
			'network_timeout' => $this->getNetworkTimeout($connectionDetails),
			'allow_self_signed' => $this->getAllowSelfSigned($connectionDetails),
			'ad_username' => $connectionDetails->getUsername(),
			'ad_password' => $connectionDetails->getPassword(),
		);

		// log connection details
		$output = $config;

		if (null !== $output['ad_password']) {
			$output['ad_password'] = '*** protected password ***';
		}

		$encryption = $useTls | $useSsl ? 'LDAP connection is encrypted with "' . $this->getEncryption($connectionDetails) . '"' : 'LDAP connection is *not* encrypted';

		$this->logger->info($encryption);

		// Logging single lines to keep the conversion pattern
		foreach ($output as $key => $line) {
			// Check and imploding for DC array
			if (is_array($line)) {
				$line = implode(' ', $line);
			}
			$this->logger->debug($key . ' = ' . $line);
		}

		$username = (isset($output['ad_username']) && !empty($output['ad_username'])) ? $output['ad_username'] : '';

		if (strpos($username, '@') === false) {
			$this->logger->warning('Username for the sync user does not contain a correct suffix. If the connection to the ad fails, this could be the cause. Please make sure you have added all UPN suffixes to the configuration tab User -> Account suffix.');
		}

		return $config;
	}

	/**
	 * Return the base DN based upon the $connectionDetails. If the base DN is not set the base DN of the current blog instance is returned.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return mixed
	 */
	public function getBaseDn(ConnectionDetails $connectionDetails)
	{
		$baseDn = $connectionDetails->getBaseDn();

		if (null === $baseDn) {
			$baseDn = $this->configuration->getOptionValue(Options::BASE_DN);
		}

		return $baseDn;
	}

	/**
	 * Return the domain controllers based upon the $connectionDetails. If no domain controller is set the domain controllers of the current blog instance are returned.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return array
	 */
	public function getDomainControllers(ConnectionDetails $connectionDetails)
	{
		$domainControllers = $connectionDetails->getDomainControllers();

		if (null === $domainControllers) {
			$domainControllers = $this->configuration->getOptionValue(Options::DOMAIN_CONTROLLERS);
		}

		$domainControllers = StringUtil::split($domainControllers, ';');

		return $domainControllers;
	}

	/**
	 * Return the port based upon the $connectionDetails. If the port is not set the port of the current blog instance is returned.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return mixed
	 */
	public function getAdPort(ConnectionDetails $connectionDetails)
	{
		$port = $connectionDetails->getPort();

		if (null === $port) {
			$port = $this->configuration->getOptionValue(Options::PORT);
		}

		return $port;
	}

	/**
	 * Return the usage of TLS based upon the $connectionDetails. If the usage of TLS is not set the usage of TLS of the current blog instance is returned.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return bool
	 */
	public function getUseTls(ConnectionDetails $connectionDetails)
	{
		return $this->getEncryption($connectionDetails) === Encryption::STARTTLS;
	}

	/**
	 * Return the usage of SSL based upon the $connectionDetails. If the usage of SSL is not set the usage of SSL of the current blog instance is returned.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return bool
	 */
	public function getUseSsl(ConnectionDetails $connectionDetails)
	{
		return $this->getEncryption($connectionDetails) === Encryption::LDAPS;
	}

	/**
	 * Return the encryption based upon the $connectionDetails. If the encryption is not set the encryption of the current blog instance is returned.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return string|null
	 */
	public function getEncryption(ConnectionDetails $connectionDetails)
	{
		$encryption = $connectionDetails->getEncryption();

		if (null === $encryption) {
			$encryption = $this->configuration->getOptionValue(Options::ENCRYPTION);
		}

		return $encryption;
	}

	/**
	 * Return allow_self_signed based upon the $connectionDetails.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return mixed
	 */
	public function getAllowSelfSigned(ConnectionDetails $connectionDetails)
	{
		$allowSelfSigned = $connectionDetails->getAllowSelfSigned();

		if (null === $allowSelfSigned) {
			$allowSelfSigned = $this->configuration->getOptionValue(Options::ALLOW_SELF_SIGNED);
		}

		return $allowSelfSigned;
	}

	/**
	 * Return the network timeout based upon the $connectionDetails. If the port is not set the network timeout of the current blog instance is returned.
	 *
	 * @param ConnectionDetails $connectionDetails
	 *
	 * @return mixed
	 */
	public function getNetworkTimeout(ConnectionDetails $connectionDetails)
	{
		$networkTimeout = $connectionDetails->getNetworkTimeout();

		if (null === $networkTimeout) {
			$networkTimeout = $this->configuration->getOptionValue(Options::NETWORK_TIMEOUT);
		}

		return $networkTimeout;
	}

	/**
	 * Create new adLDAP object with $config.
	 *
	 * @param array $config
	 */
	function createAdLdap($config)
	{
		$this->adldap = new AdLdap($config);
	}

	/**
	 * Get current initialized adLDAP object.
	 *
	 * @return adLDAP
	 */
	function getAdLdap()
	{
		return $this->adldap;
	}

	/**
	 * Check connection to Active Directory
	 *
	 * @return bool
	 */
	public function isConnected()
	{
		return is_object($this->adldap);
	}

	/**
	 *  Find the sAMAccountName associated with a ProxyAddress
	 *
	 * @param string $proxyAddress The proxy address to check
	 *
	 * @return false if not found or the sAMAccountName.
	 */
	public function findByProxyAddress($proxyAddress)
	{
		return $this->adldap->findByProxyAddress($proxyAddress);
	}

	/**
	 * Authenticate the user
	 *
	 * @param string $username
	 * @param string $suffix
	 * @param string $password
	 *
	 * @return boolean
	 */
	public function authenticate($username, $suffix, $password)
	{
		return $this->authenticateUser($this->getAdLdap(), $username, $suffix, $password);
	}

	/**
	 * Check if an connection with the username and password can be created.
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool
	 */
	public function checkConnection($username, $password)
	{
		if (!$this->adldap) {
			return false;
		}

		try {
			$success = $this->adldap->authenticate($username, $password);
		} catch (\Exception $e) {
			$this->logger->error('During connecting to AD an exception was thrown:', $e);
			$success = false;
		}

		if ($success) {
			$dc = $this->getAdLdap()->get_last_used_dc();
			$this->logger->debug("Connection established with Domain Controller: $dc");

			return true;
		}

		$this->logger->error('Connection with AD failed. User: "' . $username
			. '" could not be authenticated against the AD.');

		return false;
	}

	/**
	 * Try to authenticate the username with one $suffix.
	 *
	 * @param AdLdap $adLdap
	 * @param string $username
	 * @param string $suffix
	 * @param string $password
	 *
	 * @return bool true if authentication was successful, false if it failed
	 */
	public function authenticateUser($adLdap, $username, $suffix, $password)
	{
		$suffix = trim($suffix);
		$adLdap->set_account_suffix($suffix);

		$message = "Trying to authenticate user with username '$username' and account suffix '$suffix'";
		$this->logger->debug($message);

		if ($adLdap->authenticate($username, $password)) {
			$message = "Authentication successful for username '$username' and account suffix '$suffix'.";
			$this->logger->debug($message);

			return true;
		}

		try {
			$adLdap->throwConnectionError("Authentication for user '$username' failed");
		} catch (\Exception $ex) {
			$this->logger->error($ex->getMessage());
		}

		return false;
	}

	/**
	 * Lookup the requested LDAP attributes for the user from the underlying Active Directory connection
	 *
	 * @param UserQuery $userQuery
	 * @param array $attributeNames
	 *
	 * @return array
	 */
	public function findAttributesOfUser(UserQuery $userQuery, $attributeNames)
	{
		$adLdap = $this->getAdLdap();

		$matchesFromLdap = $adLdap->user_info($userQuery->getPrincipal(), $attributeNames, $userQuery->isGuid());

		if ($matchesFromLdap === false) {
			$this->logger->warning("Query '$userQuery' did not return any values. Does the sAMAccountName or userPrincipalName exist? Is the provided base DN valid? Is the Kerberos realm mapped");

			return false;
		}

		// ADI-713: try to extract the user's information from a list of arrays
		$userInfo = apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'ldap_map_userinfo', false, $matchesFromLdap, $matchesFromLdap['count'], $userQuery, $attributeNames);

		if ($userInfo) {
			$this->logger->debug("UserInfo for user '" . $userQuery . "': " . $this->__debug($userInfo));
		}

		return $userInfo;
	}

	/**
	 * After the Active Directory has been queried to look for a GUID, userPrincipalName or sAMAccountName, this method will be called.
	 *
	 * @param boolean|mixed $bestMatch The best match being used. It is false if no match has been found yet.
	 * @param array $matchesFromLdap
	 * @param integer $totalMatches number of matches; due to the adLDAP structure
	 * @param UserQuery $userQuery
	 * @param array $attributeNames
	 * @return array|boolean the $bestMatch exactly one match or false
	 * @issue ADI-713
	 * @since 2.1.13
	 */
	public function mapUserInfo($bestMatch, $matchesFromLdap, $totalMatches, UserQuery $userQuery, $attributeNames)
	{
		// there has not been a best match specified; this method is the fallback option
		if (!$bestMatch) {
			// we got more than one result for the DC/GC; this can happen if a sAMAccountName is queried inside a AD forest
			if ($totalMatches > 1) {
				$this->logger->error('The user query "' . $userQuery . "' returned " . $totalMatches . ' results. You have to do additional configuration if you are running NADI inside an AD forest.');
				$bestMatch = false;
			} // we have exactly one result, so we will use it
			else {
				$bestMatch = $matchesFromLdap[0];
			}
		}

		return $bestMatch;
	}

	/**
	 * Find the NetBIOS name of the underlying LDAP connection
	 *
	 * @return bool|string false if name is missing, string if NetBIOS name could be found
	 */
	public function findNetBiosName()
	{
		$adLdap = $this->getAdLdap();

		$this->logger->debug("Trying to find NetBIOS name");
		$filter = "netbiosname";
		$netbios = $adLdap->get_configuration($filter);

		if ($netbios === false) {
			$this->logger->warning("No NetBIOS name found. Maybe base DN is wrong or partition scheme is misconfigured.");

			return false;
		}

		$this->logger->debug("Found NetBIOS name '" . $netbios . "' for domain SIDs " . $this->activeDirectoryContext);

		return $netbios;
	}

	/**
	 * Custom debug method for information to prevent output of long binary data
	 *
	 * @issue ADI-420
	 * @issue ADI-628 refactored methode since each() is flagged deprecated with PHP 7.2.5
	 * @param array $userInfo in adLDAP format
	 * @return string
	 */
	private function __debug($userInfo = array())
	{
		$result = "";
		$maxOutputChars = 32;

		foreach ($userInfo as $key => $attribute) {
			if (!is_numeric($key)) {
				continue;
			}

			$result .= "$attribute={";
			$data = $userInfo[$attribute];

			foreach ($data as $index => $element) {
				if (!is_numeric($index)) {
					continue;
				}

				// remove any linebreaks or carriagereturns from the attributes
				$element = preg_replace("/\r\n|\r|\n/", '', $element);

				if ($attribute === "objectguid") {
					try {
						$element = StringUtil::binaryToGuid($element);
					} catch (\Exception $exception) {
						$this->logger->error("An exception occurred trying to convert binary to GUID. Exception: " . $exception->getMessage());
					}
				}

				$result .= StringUtil::firstChars($element, 500);

			}

			$result .= "}, ";

		}

		if (strlen($result) > 0) {
			// remove last ", " part if given
			$result = substr($result, 0, -2);
		}

		return $result;
	}

	/**
	 * Lookup all requested attributes and instantly sanitize them.
	 *
	 * @param UserQuery $userQuery
	 * @param array $attributes
	 *
	 * @return array
	 */
	public function findSanitizedAttributesOfUser(UserQuery $userQuery, $attributes)
	{
		$userInfo = $this->findAttributesOfUser($userQuery, $attributes);
		$sanitized = array();

		foreach ($attributes as $attribute) {
			$attribute = StringUtil::toLowerCase($attribute);
			$array = ArrayUtil::get($attribute, $userInfo);
			$sanitized[$attribute] = ArrayUtil::get(0, $array);
		}

		return $sanitized;
	}

	/**
	 * Modify user with attributes
	 * ADI-452: Method now takes $wpUser object as first parameter so we can easily access username and Active Directory guid.
	 *
	 * @param \WP_User $wpUser
	 * @param array $attributes Map with attributes and their values
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function modifyUserWithoutSchema($wpUser, $attributes)
	{
		$username = $wpUser->user_login;
		$userGuid = get_user_meta($wpUser->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . Repository::META_KEY_OBJECT_GUID, true);

		if (empty($attributes)) {
			$this->logger->warning("Modifying user '$username' skipped. Found no attributes to synchronize to Active Directory.");

			return false;
		}

		$adLdap = $this->getAdLdap();
		$this->logger->info("Modifying user '$username' with attributes: " . json_encode($attributes, true));

		try {
			// ADI-452 Trying to update user via GUID.
			$modified = $adLdap->user_modify_without_schema($userGuid, $attributes, true);
		} catch (\Exception $e) {
			$this->logger->error("Can not modify user '$username'. " . $e->getMessage());

			return false;
		}

		if (!$modified) {
			$this->logger->warning("Modifying user '$username' failed.");
			$this->logger->warning('adLDAP last error: ' . print_r($adLdap->get_last_error(), true));

			return false;
		}

		$this->logger->info("User '$username' successfully modified.");

		return true;
	}

	/**
	 * Iterate over every configured Active Directory port and check them for availability
	 *
	 * @return bool if at least one of the configured ports is open
	 */
	public function checkPorts()
	{
		if (!Util::native()->isFunctionAvailable('fsockopen')) {
			$this->logger->error('Function fsockopen() is not available. Can not check server ports.');

			return false;
		}

		$domainControllers = $this->configuration->getOptionValue(Options::DOMAIN_CONTROLLERS);
		$domainControllers = StringUtil::split($domainControllers, ';');
		$port = $this->configuration->getOptionValue(Options::PORT);
		$timeout = 2;

		$this->logger->info('Checking domain controller ports:');

		foreach ($domainControllers as $domainController) {
			if (true == $this->checkPort($domainController, $port, $timeout)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check port $port at the domain controller address $domainController with the timeout $timeout.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $domainController
	 * @param int $port
	 * @param int $timeout
	 *
	 * @return bool true if port could be opened, false if port could not be opened or fsockopen is not available.
	 */
	public function checkPort($domainController, $port, $timeout)
	{
		if (!@Util::native()->isFunctionAvailable('fsockopen')) {
			return false;
		}

		$errorCode = -1;
		$errorString = '';
		$resource = @Util::native()->fsockopen($domainController, $port, $errorCode, $errorString, $timeout);

		if ($resource) {
			$this->logger->info("Checking address '$domainController' and port $port - OK");
			Util::native()->fclose($resource);

			return true;
		}

		$this->logger->error("Checking address '$domainController' and port $port - FAILED");
		$this->logger->error("Error number: $errorCode Error message: $errorString");

		return false;
	}

	/**
	 * Get the last used DC
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getLastUsedDC()
	{
		return $this->getAdLdap()->get_last_used_dc();
	}

	/**
	 * Find all members of all groups $groups;
	 * This method accepts strings like: admins;employees;id:666;superAdmins
	 *
	 * @param string $groups separated by semicolon
	 *
	 * @return array
	 * @throws \Exception
	 */
	public function findAllMembersOfGroups($groups)
	{
		$groups = StringUtil::split($groups, ';');
		$allUsers = array();

		foreach ($groups as $group) {
			if (empty($group)) {
				// group name is empty
				continue;
			}

			$groupMembers = $this->findAllMembersOfGroup($group);

			if ($groupMembers === false) {
				// false means that the security group could not be retrieved
				$this->logger->error('Could not find Active Directory Security Group with name: ' . $group);
				continue;
			}

			// load user information of group members
			$domainMembersOfGroup = $this->filterDomainMembers($groupMembers);

			$this->logger->info("In group '$group' are " . sizeof($groupMembers) . " members from which " . sizeof($domainMembersOfGroup) . " belongs to the AD domain of this blog");
			$this->logger->debug("Members of group '$group': " . print_r($domainMembersOfGroup, true));

			// 'merge' array
			// a new key with the same name will override the old key with the same name
			$allUsers = $domainMembersOfGroup + $allUsers;
		}

		// return all users
		return $allUsers;
	}

	/**
	 * Filter array so that only members of the domain belonging to the current NADI profile are returned
	 *
	 * @param array $members associative array with key => lower-case username, value => username
	 * @return array
	 */
	function filterDomainMembers($members = array())
	{
		$adLdap = $this->getAdLdap();
		$r = array();

		foreach ($members as $member) {
			$userInfo = $adLdap->user_info($member, array('objectsid'));

			$objectSid = Sid::of($userInfo[0]["objectsid"][0]);

			if (!$this->activeDirectoryContext->isMember($objectSid)) {
				$formatted = $objectSid ? $objectSid->getFormatted() : 'unknown_sid';
				$this->logger->debug("Object '" . $formatted . "' does not belong to one of the configured domains of " . $this->activeDirectoryContext);
				continue;
			}

			$r[StringUtil::toLowerCase($member)] = $member;
		}

		return $r;
	}

	/**
	 * Get all members of one group.
	 *
	 * @param string $group
	 *
	 * @return array containing the sAMAccountNames of the members of the security or primary group - if the group does exist
	 * @return false if the security group could not be found
	 */
	public function findAllMembersOfGroup($group)
	{
		$adLdap = $this->getAdLdap();
		$group = trim($group);

		try {
			if (false !== stripos($group, 'id:')) {
				$pgid = substr($group, stripos($group, 'id:') + 3);
				return $adLdap->group_members_by_primarygroupid($pgid, null, true);
			} else {
				// ADI-397: Log message that Active Directory security group could not be found
				$groupInfo = $adLdap->group_info($group);

				if (!$groupInfo || (sizeof($groupInfo) == 0)) {
					$this->logger->error("Security group '" . $group . "' could not be retrieved from Active Directory. Make sure that the security group does exist in the provided base DN.");
					return false;
				}

				return $adLdap->group_members($group, null);
			}
		} catch (\Exception $e) {
			$this->logger->error("Can not get the members of group '$group'. " . $e->getMessage());
		}

		return false;
	}

	/**
	 * @return Context
	 */
	public function getActiveDirectoryContext()
	{
		return $this->activeDirectoryContext;
	}
}
