<?php

namespace Dreitier\Nadi\Authentication;

use Dreitier\ActiveDirectory\Sid;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\ConnectionDetails;
use Dreitier\Nadi\Authentication\SingleSignOn\Service;
use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Vendor\Monolog\Logger;

/**
 * Verify the connection for Active Directory settings
 *
 * @author Danny Meißner <dme@neos-it.de>
 */
class VerificationService
{

	/** @var  Connection */
	private $ldapConnection;

	/**
	 * @var Repository
	 */
	private $ldapAttributeRepository;
	/** @var Logger */
	private $logger;

	/**
	 * @param Connection $ldapConnection
	 * @param Repository $ldapAttributeRepository
	 */
	public function __construct(Connection $ldapConnection,
								Repository $ldapAttributeRepository
	)
	{
		$this->ldapConnection = $ldapConnection;
		$this->ldapAttributeRepository = $ldapAttributeRepository;
		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Check if the connection to the Active Directory can be established.
	 * Receive objectSid from user used to authenticate.
	 *
	 * @param array $data
	 *
	 * @return bool|Sid
	 */
	public function findActiveDirectoryDomainSid($data)
	{
		$config = new ConnectionDetails();
		$username = $data["verification_username"];

		$config->setDomainControllers($data["domain_controllers"]);
		$config->setPort($data["port"]);
		$config->setEncryption($data["encryption"]);
		$config->setAllowSelfSigned($data["allow_self_signed"]);
		$config->setNetworkTimeout($data["network_timeout"]);
		$config->setBaseDn($data["base_dn"]);
		$config->setUsername($username);
		$config->setPassword($data["verification_password"]);

		$this->ldapConnection->connect($config);

		$isConnected = $this->ldapConnection->isConnected();

		if ($isConnected) {
			$attributeService = $this->getCustomAttributeService();
			$objectSid = $attributeService->getObjectSid(PrincipalResolver::createCredentials($username));

			// ADI-412: There *should* be an objectSID as we now fall back from sAMAccountName to userPrincipalName
			if (false === $objectSid) {
				$this->logger->error("objectSID for AD user '" . $username . "' could not be found. Please check that for this account has been defined a full userPrincipalName including the UPN suffix.");

				return false;
			}

			return $objectSid;
		}

		return false;
	}

	public function findActiveDirectoryNetBiosName($data)
	{
		$attributeService = $this->getCustomAttributeService();
		$netBIOSname = $attributeService->getnetBiosName($data["verification_username"]);

		if ($netBIOSname) {
			return $netBIOSname;
		}

		return false;
	}

	/**
	 * Get service for verification process
	 *
	 * @return Service
	 */
	public function getCustomAttributeService()
	{
		return new \Dreitier\Ldap\Attribute\Service($this->ldapConnection, $this->ldapAttributeRepository);
	}
}