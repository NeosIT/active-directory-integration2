<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_Connection')) {
	return;
}

/**
 * This class abstracts the usage of the adLDAP library for easier handling common use-cases and falls back to the default values of the blog if values are not present.
 *
 * NextADInt_Ldap_Connection establishes a connection to the defined Active Directories, authenticates users and contains help functions for
 * checking group membership etc.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Ldap_Connection
{
	/* @var NextADInt_Multisite_Configuration_Service $configuration */
	private $configuration;

	/* @var adLDAP $adldap */
	private $adldap;

	/* @var Logger $logger */
	private $logger;
	
	/* @var string */
	private $siteDomainSid;

	/**
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration)
	{
		if (!class_exists('adLDAP')) {
			// get adLdap
			require_once NEXT_AD_INT_PATH . '/vendor/adLDAP/adLDAP.php';
		}

		$this->configuration = $configuration;

		$this->logger = Logger::getLogger(__CLASS__);
	}

	/**
	 * Create an connection to the Active Directory. But the state of the connection is unknown.
	 * You have to check if with $this->checkConnection().
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 */
	public function connect(NextADInt_Ldap_ConnectionDetails $connectionDetails)
	{
		$config = $this->createConfiguration($connectionDetails);

		try {
			$this->createAdLdap($config);
		} catch (Exception $e) {
			$this->logger->error('Creating AdLdap object failed.', $e);

			if (is_object($this->adldap)) {
				$this->logger->debug('adLDAP last error number: ' . print_r($this->adldap->get_last_errno(), true));
				$this->logger->debug('adLDAP last error: ' . print_r($this->adldap->get_last_error(), true));
			}
		}
	}

	/**
	 * Based upon the provided NextADInt_Ldap_Connection a configuration array for adLDAP is created.
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 *
	 * @return array
	 */
	public function createConfiguration(NextADInt_Ldap_ConnectionDetails $connectionDetails)
	{
		$useTls = $this->getUseTls($connectionDetails);

		$config = array(
			'account_suffix'     => '',
			'base_dn'            => $this->getBaseDn($connectionDetails),
			'domain_controllers' => $this->getDomainControllers($connectionDetails),
			'ad_port'            => $this->getAdPort($connectionDetails),
			'use_tls'            => $useTls,
			'network_timeout'    => $this->getNetworkTimeout($connectionDetails),
			'ad_username'        => $connectionDetails->getUsername(),
			'ad_password'        => $connectionDetails->getPassword(),
		);

		// log connection details
		$output = $config;

		if (null !== $output['ad_password']) {
			$output['ad_password'] = '*** protected password ***';
		}

		$encryption = $useTls ? 'LDAP connection is encrypted with "' . $this->getEncryption($connectionDetails) . '"' : 'LDAP connection is *not* encrypted';

		$this->logger->info($encryption);
		$this->logger->debug(print_r($output, true));

		if (strpos($output['ad_username'], '@') === false) {
			$this->logger->warn('Username for the sync user does not contain a correct suffix. If the connection to the ad fails, this could be the cause. Please make sure you added the right suffix to your global sync user at BlogOptions->Syncronizer.');
		}

		return $config;
	}

	/**
	 * Return the base DN based upon the $connectionDetails. If the base DN is not set the base DN of the current blog instance is returned.
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 *
	 * @return mixed
	 */
	public function getBaseDn(NextADInt_Ldap_ConnectionDetails $connectionDetails)
	{
		$baseDn = $connectionDetails->getBaseDn();

		if (null === $baseDn) {
			$baseDn = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::BASE_DN);
		}

		return $baseDn;
	}

	/**
	 * Return the domain controllers based upon the $connectionDetails. If no domain controller is set the domain controllers of the current blog instance are returned.
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 *
	 * @return array
	 */
	public function getDomainControllers(NextADInt_Ldap_ConnectionDetails $connectionDetails)
	{
		$domainControllers = $connectionDetails->getDomainControllers();

		if (null === $domainControllers) {
			$domainControllers = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS);
		}

		$domainControllers = NextADInt_Core_Util_StringUtil::split($domainControllers, ';');

		return $this->getDomainControllersWithEncryption($connectionDetails, $domainControllers);
	}

	/**
	 * Check if the controllers should be prefixed with 'ldaps://' or not.
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 * @param array                  $domainControllers
	 *
	 * @return array
	 */
	protected function getDomainControllersWithEncryption(NextADInt_Ldap_ConnectionDetails $connectionDetails,
														  array $domainControllers
	) {
		if ($this->getEncryption($connectionDetails) !== NextADInt_Multisite_Option_Encryption::LDAPS) {
			return $domainControllers;
		}

		return array_map(function($controller) {
			return 'ldaps://' . $controller;
		}, $domainControllers);
	}

	/**
	 * Return the port based upon the $connectionDetails. If the port is not set the port of the current blog instance is returned.
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 *
	 * @return mixed
	 */
	public function getAdPort(NextADInt_Ldap_ConnectionDetails $connectionDetails)
	{
		$port = $connectionDetails->getPort();

		if (null === $port) {
			$port = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::PORT);
		}

		return $port;
	}

	/**
	 * Return the usage of TLS based upon the $connectionDetails. If the usage of TLS is not set the usage of TLS of the current blog instance is returned.
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 *
	 * @return bool
	 */
	public function getUseTls(NextADInt_Ldap_ConnectionDetails $connectionDetails)
	{
		return $this->getEncryption($connectionDetails) === NextADInt_Multisite_Option_Encryption::STARTTLS;
	}

	/**
	 * Return the encryption based upon the $connectionDetails. If the encryption is not set the encryption of the current blog instance is returned.
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 *
	 * @return string|null
	 */
	public function getEncryption(NextADInt_Ldap_ConnectionDetails $connectionDetails)
	{
		$encryption = $connectionDetails->getEncryption();

		if (null === $encryption) {
			$encryption = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::ENCRYPTION);
		}

		return $encryption;
	}

	/**
	 * Return the network timeout based upon the $connectionDetails. If the port is not set the network timeout of the current blog instance is returned.
	 *
	 * @param NextADInt_Ldap_ConnectionDetails $connectionDetails
	 *
	 * @return mixed
	 */
	public function getNetworkTimeout(NextADInt_Ldap_ConnectionDetails $connectionDetails)
	{
		$networkTimeout = $connectionDetails->getNetworkTimeout();

		if (null === $networkTimeout) {
			$networkTimeout = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT);
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
		$this->adldap = new adLDAP($config);
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
		} catch (Exception $e) {
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
	 * @param adLDAP $adLdap
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

		$message = "Authentication for user '$username' failed because: " . $adLdap->get_last_error();
		$this->logger->error($message);

		return false;
	}

	/**
	 * Lookup the requested LDAP attributes for the user from the underlying Active Directory connection
	 *
	 * @param string $username
	 * @param array  $attributeNames
	 * @param bool   $isGUID
	 *
	 * @return array
	 */
	public function findAttributesOfUser($username, $attributeNames, $isGUID = false)
	{
		$adLdap = $this->getAdLdap();

		$this->logger->debug("Import these attributes from ad for the user '$username': " . print_r($attributeNames,
				true));
		$userInfo = $adLdap->user_info($username, $attributeNames, $isGUID);

		if ($userInfo === false) {
			$this->logger->warn("Attributes for '$username': could not be loaded. Does the sAMAccountName or userPrincipalName exist?");

			return false;
		}

		// user does exist, get first element
		$userInfo = $userInfo[0];

		$this->logger->debug("UserInfo for user '$username': " . print_r($userInfo, true));

		return $userInfo;
	}

	/**
	 * Lookup all requested attributes and instantly sanitize them.
	 *
	 * @param string $username
	 * @param array  $attributes
	 *
	 * @return array
	 */
	public function findSanitizedAttributesOfUser($username, $attributes)
	{
		$userInfo = $this->findAttributesOfUser($username, $attributes);
		$sanitized = array();

		foreach ($attributes as $attribute) {
			$attribute = NextADInt_Core_Util_StringUtil::toLowerCase($attribute);
			$array = NextADInt_Core_Util_ArrayUtil::get($attribute, $userInfo);
			$sanitized[$attribute] = NextADInt_Core_Util_ArrayUtil::get(0, $array);
		}

		return $sanitized;
	}

	/**
	 * Modify user with attributes
	 *
	 * @param string $username
	 * @param array  $attributes Map with attributes and their values
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function modifyUserWithoutSchema($username, $attributes)
	{
		if (empty($attributes)) {
			$this->logger->debug("Modifying user '$username' skipped. Found no attributes to synchronize to Active Directory.");

			return false;
		}

		$adLdap = $this->getAdLdap();
		$this->logger->debug("Modifying user '$username' with attributes: " . json_encode($attributes, true));

		try {
			$modified = $adLdap->user_modify_without_schema($username, $attributes);
		} catch (Exception $e) {
			$this->logger->error("Can not modify user '$username'.", $e);

			return false;
		}

		if (!$modified) {
			$this->logger->warn("Modifying user '$username' failed.");
			$this->logger->warn('adLDAP last error: ' . print_r($adLdap->get_last_error(), true));

			return false;
		}

		$this->logger->debug("User '$username' successfully modified.");

		return true;
	}

	/**
	 * Iterate over every configured Active Directory port and check them for availability
	 *
	 * @return bool if at least one of the configured ports is open
	 */
	public function checkPorts()
	{
		if (!NextADInt_Core_Util::native()->isFunctionAvailable('fsockopen')) {
			$this->logger->debug('Function fsockopen() is not available. Can not check server ports.');

			return false;
		}

		$domainControllers = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS);
		$domainControllers = NextADInt_Core_Util_StringUtil::split($domainControllers, ';');
		$port = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::PORT);
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
	 * @param int    $port
	 * @param int    $timeout
	 *
	 * @return bool true if port could be opened, false if port could not be opened or fsockopen is not available.
	 */
	public function checkPort($domainController, $port, $timeout)
	{
		if (!@NextADInt_Core_Util::native()->isFunctionAvailable('fsockopen')) {
			return false;
		}

		$errorCode = -1;
		$errorString = '';
		$resource = @NextADInt_Core_Util::native()->fsockopen($domainController, $port, $errorCode, $errorString, $timeout);

		if ($resource) {
			$this->logger->info("Checking address '$domainController' and port $port - OK");
			NextADInt_Core_Util::native()->fclose($resource);

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
	 * @throws Exception
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
	 * @throws Exception
	 */
	public function findAllMembersOfGroups($groups)
	{
		$groups = NextADInt_Core_Util_StringUtil::split($groups, ';');
		$allUsers = array();

		foreach ($groups as $group) {
			if ($group !== "") {
				$members = $this->findAllMembersOfGroup($group);

				$this->logger->info("In group '$group' are " . sizeof($members) . " members.");
				$this->logger->debug("Members of group '$group': " . print_r($members, true));

				// 'merge' array
				// a new key with the same name will override the old key with the same name
				$allUsers = $members + $allUsers;
			}
		}

		//return all users
		return $allUsers;
	}

	/**
	 * Return the domain SID of the current synchronization
	 *
	 * @return mixed|string
	 */
	public function getDomainSid() {
		if (empty($this->siteDomainSid)) {
			$this->siteDomainSid = $this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::DOMAIN_SID);
		}

		return $this->siteDomainSid;
	}

	/**
	 * Get all members of one group.
	 *
	 * @param string $group
	 *
	 * @return array
	 */
	public function findAllMembersOfGroup($group)
	{
		$adLdap = $this->getAdLdap();
		$group = trim($group);

		$siteDomainSid = $this->getDomainSid();

		try {
			if (false !== stripos($group, 'id:')) {
				$pgid = substr($group, stripos($group, 'id:') + 3);
				$members = $adLdap->group_members_by_primarygroupid($pgid, null, true);
			} else {
				$members = $adLdap->group_members($group, null);
			}
		} catch (Exception $e) {
			$this->logger->error("Can not get the members of group '$group'.", $e);

			return array();
		}

		if (!is_array($members)) {
			return array();
		}

		$users = array();

		foreach ($members as $member) {
			$userInfo = $adLdap->user_info($member, array('objectsid'));
			$userSid = $adLdap->convertObjectSidBinaryToString($userInfo[0]["objectsid"][0]);
			
			if (strpos($userSid, $siteDomainSid) !== false ) {
				$users[NextADInt_Core_Util_StringUtil::toLowerCase($member)] = $member;
			}			
		}

		return $users;
	}
}
