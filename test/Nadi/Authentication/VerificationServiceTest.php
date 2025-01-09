<?php

namespace Dreitier\Nadi\Authentication;

use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Ldap\Attribute\Service;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\ConnectionDetails;
use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Danny Meissner <dme@neos-it.de>
 * @access private
 */
class VerificationServiceTest extends BasicTestCase
{
	/* @var Connection|MockObject $ldapConnection */
	private $ldapConnection;

	/* @var Repository|MockObject $attributeRepository */
	private $attributeRepository;

	/* @var Service | MockObject $attributeService */
	private $attributeService;


	public function setUp(): void
	{
		parent::setUp();

		$this->ldapConnection = $this->createMock(Connection::class);
		$this->attributeRepository = $this->createMock(Repository::class);
		$this->attributeService = $this->getMockBuilder(Service::class)
			->disableOriginalConstructor()
			->onlyMethods(array('getObjectSid'))
			->getMock();
	}


	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @return VerificationService|MockObject
	 */
	public function sut(array $methods = [], bool $simulated = false)
	{
		return $this->getMockBuilder(VerificationService::class)
			->setConstructorArgs(
				array(
					$this->ldapConnection,
					$this->attributeRepository
				)
			)
			->onlyMethods($methods)
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

		$config = new ConnectionDetails();
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
			->with(PrincipalResolver::createCredentials($data['verification_username']))
			->willReturn("1234");

		$sut->findActiveDirectoryDomainSid($data);
	}

	/**
	 * @test
	 */
	public function getCustomAttributeService_returnLdapAttributeService()
	{
		$sut = $this->sut();
		$expected = $sut->getCustomAttributeService();

		$this->assertTrue(is_object($expected));
	}
}