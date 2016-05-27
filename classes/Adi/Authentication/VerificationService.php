<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Adi_Authentication_VerificationService')) {
	return;
}

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 */
class Adi_Authentication_VerificationService
{

	/** @var  Ldap_Connection */
	private $ldapConnection;

	/**
	 * @param Ldap_Connection           $ldapConnection
	 * @param Ldap_Attribute_Repository $attributeRepository
	 */
	public function __construct(Ldap_Connection $ldapConnection,
		Ldap_Attribute_Repository $attributeRepository
	) {
		$this->ldapConnection = $ldapConnection;
		$this->attributeRepository = $attributeRepository;
	}

	public function verifyConnection($data)
	{


		$config = new Ldap_ConnectionDetails();
		$config->setCustomDomainControllers($data["domain_controllers"]);
		$config->setCustomPort($data["port"]);
		$config->setCustomUseStartTls($data["use_tls"]);
		$config->setCustomNetworkTimeout($data["network_timeout"]);
		$config->setCustomBaseDn($data["base_dn"]);
		$config->setUsername($data["verification_username"]);
		$config->setPassword($data["verification_password"]);

		//TODO Only the save button on the environment page is disabled at the moment if no domains id is set.
		$this->ldapConnection->connect($config);

		$isConnected = $this->ldapConnection->isConnected();

		if ($isConnected) {
			
			$attributeService = new Ldap_Attribute_Service($this->ldapConnection, $this->attributeRepository);
			$objectSid = $attributeService->getObjectSid($data["verification_username"], false);

			return $objectSid;
		}

		return false;
	}

}