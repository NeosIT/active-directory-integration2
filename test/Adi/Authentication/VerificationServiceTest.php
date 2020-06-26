<?php

/**
 * @author Danny Meissner <dme@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Authentication_VerificationServiceTest extends Ut_BasicTest
{
	/* @var NextADInt_Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection */
	private $ldapConnection;

	/* @var NextADInt_Ldap_Attribute_Repository|PHPUnit_Framework_MockObject_MockObject $attributeRepository */
	private $attributeRepository;
	
	/* @var NextADInt_Ldap_Attribute_Service | PHPUnit_Framework_MockObject_MockObject $attributeService */
	private $attributeService;


	public function setUp()
	{
		parent::setUp();

		$this->ldapConnection = $this->createMock('NextADInt_Ldap_Connection');
		$this->attributeRepository = $this->createMock('NextADInt_Ldap_Attribute_Repository');
		$this->attributeService = $this->getMockBuilder('NextADInt_Ldap_Attribute_Service')
			->disableOriginalConstructor()
			->setMethods(array('getObjectSid'))
			->getMock();
	}


	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @return NextADInt_Adi_Authentication_VerificationService|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null, $simulated = false)
	{
		return $this->getMockBuilder('NextADInt_Adi_Authentication_VerificationService')
			->setConstructorArgs(
				array(
					$this->ldapConnection,
					$this->attributeRepository
				)
			)
			->setMethods($methods)
			->getMock();
	}


	/**
	 * @test
	 */
	public function verifyActiveDirectoryDomain_whenConnected_itReturnsObjectSid()
	{
		$sut = $this->sut(array('getCustomAttributeService'));
		
		$data = array(
			'domain_controllers' => array('127.0.0.1'),
			'port' => 389,
			'encryption' => 'none',
			'network_timeout' => 5,
			'base_dn' => 'DC=test;DC=ad',
			'verification_username' => 'administrator',
			'verification_password' => 'password',
			'allow_self_signed' => true
		);
		
		$config = new NextADInt_Ldap_ConnectionDetails();
		$config->setDomainControllers($data["domain_controllers"]);
		$config->setPort($data["port"]);
		$config->setEncryption($data["encryption"]);
		$config->setNetworkTimeout($data["network_timeout"]);
		$config->setBaseDn($data["base_dn"]);
		$config->setUsername($data["verification_username"]);
		$config->setPassword($data["verification_password"]);
		$config->setAllowSelfSigned($data['allow_self_signed']);
		
		$this->ldapConnection->expects($this->once())
			->method('isConnected')
			->willReturn(true);

		$this->ldapConnection->expects($this->once())
			->method('connect')
			->with($config)
			->willReturn(true);
		
		$sut->expects($this->once())
			->method('getCustomAttributeService')
			->willReturn($this->attributeService);
		
		$this->attributeService->expects($this->once())
			->method('getObjectSid')
			->with(NextADInt_Adi_Authentication_PrincipalResolver::createCredentials($data['verification_username']))
			->willReturn("1234");
		
		$sut->findActiveDirectoryDomainSid($data);
	}
	
	/**
	 * @test
	 */
	public function getCustomAttributeService_returnLdapAttributeService() {
		$sut = $this->sut();
		$expected = $sut->getCustomAttributeService();
		
		$this->assertTrue(is_object($expected));
	}
}