<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_VerificationService')) {
	return;
}

/**
 * Verify the connection for Active Directory settings
 *
 * @author Danny Meißner <dme@neos-it.de>
 */
class Adi_Authentication_VerificationService
{

	/** @var  Ldap_Connection */
	private $ldapConnection;

	/**
	 * @param Ldap_Connection 			$ldapConnection
	 * @param Ldap_Attribute_Repository $attributeRepository
	 */
	public function __construct(Ldap_Connection $ldapConnection,
								Ldap_Attribute_Repository $attributeRepository
	)
	{
		$this->ldapConnection = $ldapConnection;
		$this->attributeRepository = $attributeRepository;
	}

	/**
	 * Check if the connection to the Active Directory can be established. Receive objectSid from user used to authenticate.
	 * @param array $data
	 *
	 * @return bool false || string $objectId
	 * 
	 */
	public function verifyActiveDirectoryDomain($data)
	{
		$config = new Ldap_ConnectionDetails();
		$config->setCustomDomainControllers($data["domain_controllers"]);
		$config->setCustomPort($data["port"]);
		$config->setCustomUseStartTls($data["use_tls"]);
		$config->setCustomNetworkTimeout($data["network_timeout"]);
		$config->setCustomBaseDn($data["base_dn"]);
		$config->setUsername($data["verification_username"]);
		$config->setPassword($data["verification_password"]);
		
		$this->ldapConnection->connect($config);

		$isConnected = $this->ldapConnection->isConnected();

		if ($isConnected) {
			$attributeService = $this->getCustomAttributeService();
			$objectSid = $attributeService->getObjectSid($data["verification_username"], false);

			return $objectSid;
		}

		return false;
	}

	/**
	 * Get Ldap_Attribute_Service for verification process
	 * 
	 * @return Ldap_Attribute_Service
	 */
	public function getCustomAttributeService() {
		return new Ldap_Attribute_Service($this->ldapConnection, $this->attributeRepository);
	}
}