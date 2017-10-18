<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_Authentication_VerificationService')) {
	return;
}

/**
 * Verify the connection for Active Directory settings
 *
 * @author Danny Meißner <dme@neos-it.de>
 */
class NextADInt_Adi_Authentication_VerificationService
{

	/** @var  NextADInt_Ldap_Connection */
	private $ldapConnection;

	/** @var Logger */
	private $logger;

	/**
	 * @param NextADInt_Ldap_Connection 			$ldapConnection
	 * @param NextADInt_Ldap_Attribute_Repository $attributeRepository
	 */
	public function __construct(NextADInt_Ldap_Connection $ldapConnection,
								NextADInt_Ldap_Attribute_Repository $attributeRepository
	)
	{
		$this->ldapConnection = $ldapConnection;
		$this->attributeRepository = $attributeRepository;
		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Check if the connection to the Active Directory can be established. 
	 * Receive objectSid from user used to authenticate.
	 * 
	 * @param array $data
	 *
	 * @return bool false || string $objectId
	 */
	public function findActiveDirectoryDomainSid($data)
	{
		$config = new NextADInt_Ldap_ConnectionDetails();
		$username = $data["verification_username"];

		$config->setDomainControllers($data["domain_controllers"]);
		$config->setPort($data["port"]);
		$config->setEncryption($data["encryption"]);
		$config->setNetworkTimeout($data["network_timeout"]);
		$config->setBaseDn($data["base_dn"]);
		$config->setUsername($username);
		$config->setPassword($data["verification_password"]);
		
		$this->ldapConnection->connect($config);

		$isConnected = $this->ldapConnection->isConnected();

		if ($isConnected) {
			$attributeService = $this->getCustomAttributeService();
			$objectSid = $attributeService->getObjectSid(new NextADInt_Adi_Authentication_Credentials($username));

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

		if($netBIOSname) {
			return $netBIOSname;
		}

		return false;
	}

	/**
	 * Get NextADInt_Ldap_Attribute_Service for verification process
	 * 
	 * @return NextADInt_Ldap_Attribute_Service
	 */
	public function getCustomAttributeService() {
		return new NextADInt_Ldap_Attribute_Service($this->ldapConnection, $this->attributeRepository);
	}
}