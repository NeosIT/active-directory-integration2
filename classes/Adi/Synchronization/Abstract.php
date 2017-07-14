<?php

/**
 * Base class for synchronization between WordPress and Active Directory.
 *
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
abstract class NextADInt_Adi_Synchronization_Abstract
{
	/* @var NextADInt_Multisite_Configuration_Service */
	protected $configuration;

	/* @var NextADInt_Ldap_Connection */
	protected $connection;

	/* @var NextADInt_Ldap_Attribute_Service */
	protected $attributeService;

	/* @var Logger $logger */
	private $logger;

	/* @var NextADInt_Ldap_Connection */
	protected $connectionDetails;

	/* @var int*/
	private $time = 0;

	/**
	 * Execution time in seconds which is required for the long-running tasks
	 */
	const REQUIRED_EXECUTION_TIME_IN_SECONDS = 18000;

	/**
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 * @param NextADInt_Ldap_Connection                 $connection
	 * @param NextADInt_Ldap_Attribute_Service          $attributeService
	 * */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration,
		NextADInt_Ldap_Connection $connection,
		NextADInt_Ldap_Attribute_Service $attributeService
	) {
		$this->configuration = $configuration;
		$this->connection = $connection;
		$this->attributeService = $attributeService;
		$this->connectionDetails = new NextADInt_Ldap_ConnectionDetails();

		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Increase the execution time of a php script to at least 1 hour.
	 */
	public function increaseExecutionTime()
	{
		if (NextADInt_Core_Util::native()->iniGet('max_execution_time') >= self::REQUIRED_EXECUTION_TIME_IN_SECONDS) {
			return; 
		}

		NextADInt_Core_Util::native()->iniSet('max_execution_time', self::REQUIRED_EXECUTION_TIME_IN_SECONDS);

		if (NextADInt_Core_Util::native()->iniGet('max_execution_time') >= self::REQUIRED_EXECUTION_TIME_IN_SECONDS) {
			return;
		}

		$this->logger->warn(
			'Can not increase PHP configuration option \'max_execution_time\' to '
			. self::REQUIRED_EXECUTION_TIME_IN_SECONDS . ' seconds. This can happen when running PHP in safe mode. The only workaround is to turn off safe mode or change the time limit in the php.ini'
		);
	}

	/**
	 * Establish a connection to the Active Directory server.
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return bool connection success
	 */
	public function connectToAdLdap($username, $password)
	{
		$this->connectionDetails = new NextADInt_Ldap_ConnectionDetails();
		$this->connectionDetails->setUsername($username);
		$this->connectionDetails->setPassword($password);

		$this->connection->connect($this->connectionDetails);
		return $this->connection->checkConnection($username, $password);
	}

	/**
	 * Start timer.
	 */
	public function startTimer()
	{
		$this->time = time();
	}

	/**
	 * Get the passed time since startTimer was called.
	 *
	 * @return int
	 */
	public function getElapsedTime()
	{
		return time() - $this->time;
	}

	/**
	 * Return an array with the mapping between the Active Directory sAMAccountName (key) and their WordPress username (value).
	 *
	 * @return array|hashmap key is Active Directory objectGUID, value is WordPress username
	 */
	public function findActiveDirectoryUsernames()
	{
		$users = $this->findActiveDirectoryUsers();

		$r = array();

		foreach ($users as $user) {
			$guid = get_user_meta($user->ID, NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_OBJECT_GUID, true);
			$userDomainSid = get_user_meta(
				$user->ID, NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_DOMAINSID, true
			);

			if ($this->isVerifiedDomainMember($userDomainSid)) {
				$wpUsername = $user->user_login;
				$r[NextADInt_Core_Util_StringUtil::toLowerCase($guid)] = $wpUsername;
			}
		}

		return $r;
	}

	/**
	 * Find all WordPress users which have their origin in the Active Directory.
	 *
	 * It searches the WordPress user table for the meta key 'samaccountname'. The attribute 'samaccountname' is synchronized during login/update.
	 *
	 * @param null|int $userId if specified it only finds the user with the given ID
	 *
	 * @return array
	 */
	public function findActiveDirectoryUsers($userId = null)
	{
		$args = array(
			'blog_id'    => get_current_blog_id(),
			'meta_key'   => NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
					'value'   => '',
					'compare' => '!=',
				),
			),
			'exclude'    => array(1)
		);

		if ($userId) {
			$args['include'] = array($userId);
		}

		$users = get_users($args);
		$r = array();

		$this->logger->debug("Found '" . sizeof($users) . "' in this blog with a valid sAMAccountName'");

		foreach ($users as $user) {
			$userDomainSid = get_user_meta(
				$user->ID, NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_DOMAINSID, true
			);

			if ($this->isVerifiedDomainMember($userDomainSid)) {
				array_push($r, $user);
			}
		}

		$this->logger->debug(sizeof($r) . " of " . sizeof($users) . " users in this blog are assigned to the domain SID '" . $this->connection->getDomainSid() . "'");

		return $r;
	}

	/**
	 * Check if the attribute value for an attribute is empty, if yes return an array.
	 * Workaround to prevent adLDAP from syncing "Array" as a value for an attribute to the Active Directory.
	 *
	 * @param array  $attributesToSync
	 * @param string $metaKey
	 *
	 * @return bool
	 */
	public function isAttributeValueEmpty($attributesToSync, $metaKey)
	{
		if (empty($attributesToSync[$metaKey][0])) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the user is a member of the Active Directory domain connected to the WordPress site via its domain SID
	 *
	 * @param string $userDomainSid
	 *
	 * @return bool true if user is member of domain
	 */
	public function isVerifiedDomainMember($userDomainSid)
	{
		if ($userDomainSid == $this->connection->getDomainSid()) {
			return true;
		}
		
		return false;
	}

	/**
	 * Check if username is inside the current linked domain
	 *
	 * @param string $username
	 * @return bool
	 */
	public function isUsernameInDomain($username) {
		// TODO this method is only called from the child classes after the authentication is succeeded. Can we re-use the user_info from the authentication?
		// TODO this would prevent a second LDAP call
		$adLdap = $this->connection->getAdLdap();
		$binarySid = $adLdap->user_info($username, array("objectsid"));
		$stringSid = $adLdap->convertObjectSidBinaryToString($binarySid[0]["objectsid"][0]);
		$usersDomainSid = NextADInt_Core_Util_StringUtil::objectSidToDomainSid($stringSid);

		if (empty($binarySid)) {
			$this->logger->error("SID of user '$username' could not be retrieved. Is the base DN correct? Does the userPrincipalName '$username' exist and not only its sAMAccountName?'");
			return false;
		}

		if ($this->isVerifiedDomainMember($usersDomainSid)) {
			return true;
		}

		$this->logger->warn('User ' . $username . ' with SID ' . $usersDomainSid . ' (domain SID: ' . $usersDomainSid . ') is not member of domain with domain SID "' . $this->connection->getDomainSid() . "'");
		return false;
	}
}
