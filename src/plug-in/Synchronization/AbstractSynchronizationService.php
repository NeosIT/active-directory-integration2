<?php

namespace Dreitier\Nadi\Synchronization;

use Dreitier\ActiveDirectory\Sid;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\ConnectionDetails;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\User\Persistence\Repository;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\StringUtil;
use Dreitier\Util\Util;
use Dreitier\WordPress\Multisite\Configuration\Service;

/**
 * Base class for synchronization between WordPress and Active Directory.
 *
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
abstract class AbstractSynchronizationService
{
	/* @var Service */
	protected $multisiteConfigurationService;

	/* @var Connection */
	protected $ldapConnection;

	/* @var \Dreitier\Ldap\Attribute\Service */
	protected $ldapAttributeService;

	/* @var Logger */
	private $logger;

	/* @var ConnectionDetails */
	protected $connectionDetails;

	/* @var int */
	private $time = 0;

	/**
	 * Execution time in seconds which is required for the long-running tasks
	 */
	const REQUIRED_EXECUTION_TIME_IN_SECONDS = 18000;

	/**
	 * @param Service $multisiteConfigurationService
	 * @param Connection $ldapConection
	 * @param \Dreitier\Ldap\Attribute\Service $ldapAttributeService
	 * */
	public function __construct(Service                          $multisiteConfigurationService,
								Connection                       $ldapConection,
								\Dreitier\Ldap\Attribute\Service $ldapAttributeService
	)
	{
		$this->multisiteConfigurationService = $multisiteConfigurationService;
		$this->ldapConnection = $ldapConection;
		$this->ldapAttributeService = $ldapAttributeService;
		$this->connectionDetails = new ConnectionDetails();

		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Increase the execution time of a php script to at least 1 hour.
	 */
	public function increaseExecutionTime()
	{
		if (Util::native()->iniGet('max_execution_time') >= self::REQUIRED_EXECUTION_TIME_IN_SECONDS) {
			return;
		}

		Util::native()->iniSet('max_execution_time', self::REQUIRED_EXECUTION_TIME_IN_SECONDS);

		if (Util::native()->iniGet('max_execution_time') >= self::REQUIRED_EXECUTION_TIME_IN_SECONDS) {
			return;
		}

		$this->logger->warning(
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
		$this->connectionDetails = new ConnectionDetails();
		$this->connectionDetails->setUsername($username);
		$this->connectionDetails->setPassword($password);

		$this->ldapConnection->connect($this->connectionDetails);
		return $this->ldapConnection->checkConnection($username, $password);
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
			$guid = get_user_meta($user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . Repository::META_KEY_OBJECT_GUID, true);
			$wpUsername = $user->user_login;
			$r[StringUtil::toLowerCase($guid)] = $wpUsername;
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
			'blog_id' => get_current_blog_id(),
			'meta_key' =>NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' =>NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
					'value' => '',
					'compare' => '!=',
				),
			),
			'exclude' => array(1)
		);

		if ($userId) {
			$args['include'] = array($userId);
		}

		$users = get_users($args);
		$r = array();

		$this->logger->debug("Found '" . sizeof($users) . "' in this blog with a valid sAMAccountName'");

		foreach ($users as $user) {
			$userDomainSid = get_user_meta(
				$user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . Repository::META_KEY_DOMAINSID, true
			);

			$sid = Sid::of($userDomainSid);

			// #138: the SID can be null if this user has been imported in a previous version
			if (!$this->ldapConnection->getActiveDirectoryContext()->isMember($sid)) {
				$this->logger->warning('User with name ' . $user->user_login . 'is not a member of one of the configured domains.');
				continue;
			}

			array_push($r, $user);
		}

		$this->logger->debug(sizeof($r) . " of " . sizeof($users) . " users in this blog are assigned to one of configured domain SIDs " . $this->ldapConnection->getActiveDirectoryContext());

		return $r;
	}

	/**
	 * Check if the attribute value for an attribute is empty, if yes return an array.
	 * Workaround to prevent adLDAP from syncing "Array" as a value for an attribute to the Active Directory.
	 *
	 * @param array $attributesToSync
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
	 * Check if username is inside the current linked domain
	 *
	 * @param string $username
	 * @return bool
	 */
	public function isUsernameInDomain($username)
	{
		// TODO this method is only called from the child classes after the authentication is succeeded. Can we re-use the user_info from the authentication?
		// TODO this would prevent a second LDAP call
		$adLdap = $this->ldapConnection->getAdLdap();
		$userInfo = $adLdap->user_info($username, array("objectsid"));

		if (empty($userInfo)) {
			$this->logger->error("SID of user '$username' could not be retrieved. Is the base DN correct? Does the userPrincipalName '$username' exist and not only its sAMAccountName?'");
			return false;
		}

		$objectSid = Sid::of($userInfo[0]["objectsid"][0]);

		try {
			$this->ldapConnection->getActiveDirectoryContext()->checkMembership($objectSid);
			return true;
		} catch (\Exception $e) {
			$this->logger->warning('User ' . $username . ' is not a domain member: ' . $e->getMessage());
		}

		return false;
	}
}
