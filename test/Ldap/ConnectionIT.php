<?php

namespace Dreitier\Ldap;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\User\Persistence\Repository;
use Dreitier\Util\StringUtil;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class ConnectionIT extends \Dreitier\Test\BasicIntegrationTest
{
	/* @var Service | MockObject */
	private $configuration;

	/* @var Connection */
	private $ldapConnection;

	/* @var ConnectionDetails */
	protected $connectionDetails;

	public function setUp(): void
	{
		\WP_Mock::setUp();

		$this->configuration = $this->createMock(Service::class);

		$this->ldapConnection = new Connection($this->configuration);
		$this->connectionDetails = $this->createAdConnectionDetails();
		$this->ldapConnection->connect($this->connectionDetails);
		$this->prepareActiveDirectory($this->ldapConnection->getAdLdap());
	}

	public function tearDown(): void
	{
		$this->rollbackAdAfterConnectionIt($this->ldapConnection->getAdLdap());
		\WP_Mock::tearDown();
		Mockery::close();
	}

	/**
	 * @test
	 */
	public function connectToAd_withCorrectCredentials()
	{
		$this->ldapConnection->connect($this->connectionDetails);
	}

	/**
	 * @test
	 */
	public function connectToAd_withWrongCredentials_returnFalse()
	{
		// Set AD Connection to be tested
		$connectionDetails = $this->createAdConnectionDetails();
		$connectionDetails->setPassword('wrongPa$$w0rd');

		// Create a new Ldap Connection
		$connection = new Connection($this->configuration);
		$connection->connect($connectionDetails);

		$this->assertEquals(null, $connection->getAdLdap());
	}

	/**
	 * @test
	 */
	public function findSanitizedAttributesOfUser_forAdministrator_returnAttributeValueArray()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$expectedArray = array(
			'samaccountname' => 'Jurg',
			'cn' => 'Jurg Smith',
			'memberof' => 'CN=AdiItGroup,OU=AdiItOu,DC=test,DC=ad'
		);

		$returnedValue = $this->ldapConnection->findSanitizedAttributesOfUser(
			$this->username1, array("sAMAccountName", "cn", "memberOf")
		);
		$this->assertEquals($expectedArray, $returnedValue);
	}

	/**
	 * @test
	 */
	public function authenticateUser_withCorrectCredentials_returnTrue()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$returnedValue = $this->ldapConnection->authenticate(get_cfg_var('AD_USERNAME'), get_cfg_var('AD_SUFFIX'), get_cfg_var('AD_PASSWORD'));

		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function authenticateUser_withWrongCredentials_returnFalse()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$returnedValue = $this->ldapConnection->authenticate(get_cfg_var('AD_USERNAME'), get_cfg_var('AD_SUFFIX'), 'password123s');

		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function isConnected()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$returnedValue = $this->ldapConnection->isConnected();

		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_withAttributesToSync_RollbackAfterwards_returnTrue()
	{

		$wpUser = new \WP_User();
		$wpUser->ID = 666;
		$wpUser->user_login = $this->username1;

		$this->ldapConnection->connect($this->connectionDetails);
		$ldapAttribute = $this->ldapConnection->findSanitizedAttributesOfUser($this->username1, array('objectguid'));
		$userGuid = StringUtil::binaryToGuid($ldapAttribute['objectguid']);

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array($wpUser->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . Repository::META_KEY_OBJECT_GUID, true),
			'times' => 2,
			'return' => $userGuid
		));

		$attributesToSync = array(
			"countryCode" => 1,
			"description" => "Description modified by integration Test!",
		);


		$returnedValue = $this->ldapConnection->modifyUserWithoutSchema($wpUser, $attributesToSync);

		$this->assertTrue($returnedValue);

		// Rollback everything
		$attributesToSync = array(
			"countryCode" => 0,
			"description" => "Vordefiniertes Konto für die Verwaltung des Computers bzw. der Domäne",
		);

		$returnedValue = $this->ldapConnection->modifyUserWithoutSchema($wpUser, $attributesToSync);
		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_withGroupName_returnMemberArray()
	{
		$this->ldapConnection->connect($this->connectionDetails);

		$expectedMember = array(
			$this->username1
		);

		$returnedValue = $this->ldapConnection->findAllMembersOfGroup($this->groupName1);
		$this->assertEquals($expectedMember, $returnedValue);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroups_withGroupName_returnMemberArray()
	{
		//Workaround to bypass domainSid check in IT
		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::DOMAIN_SID)
			->willReturn('S-1-5');

		$this->ldapConnection->connect($this->connectionDetails);
		$expectedMember = array(
			strtolower($this->username1) => $this->username1,
			strtolower($this->username2) => $this->username2,
		);

		$returnedValue = $this->ldapConnection->findAllMembersOfGroups($this->groupName1 . ";" . $this->groupName2);

		$this->assertEquals($expectedMember, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getLastUsedDC_withAdIp_returnIpAsString()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$returnedValue = $this->ldapConnection->getLastUsedDC();
		$expectedDc = $this->connectionDetails->getDomainControllers();

		$this->assertEquals($expectedDc, $returnedValue);
	}

	/**
	 * @test
	 */
	public function checkPort_withCorrectData_returnTrue()
	{
		$returnedValue = $this->ldapConnection->checkPort(
			$this->connectionDetails->getDomainControllers(), $this->connectionDetails->getPort(),
			$this->connectionDetails->getNetworkTimeout()
		);

		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function checkPort_withWrongData_returnFalse()
	{
		$returnedValue = $this->ldapConnection->checkPort('173.16.100.31', 123, 2);

		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function createConfiguration_withConnectionDetails_returnConfigAsArray()
	{
		$returnedConfig = $this->ldapConnection->createConfiguration($this->connectionDetails);

		$expectedConfig = array(
			'account_suffix' => '',
			'base_dn' => $this->connectionDetails->getBaseDn(),
			'domain_controllers' => array($this->connectionDetails->getDomainControllers()),
			'ad_port' => $this->connectionDetails->getPort(),
			'use_tls' => false,
			'use_ssl' => false,
			'network_timeout' => $this->connectionDetails->getNetworkTimeout(),
			'ad_username' => $this->connectionDetails->getUsername(),
			'ad_password' => $this->connectionDetails->getPassword()
		);

		$this->assertEquals($expectedConfig, $returnedConfig);
	}

	/**
	 * @test
	 */
	public function getCustomBaseDn_withBaseDnNotEmpty_returnBaseDn()
	{
		$returnedBaseDn = $this->ldapConnection->getBaseDn($this->connectionDetails);
		$this->assertEquals($this->connectionDetails->getBaseDn(), $returnedBaseDn);
	}

	/**
	 * @test
	 */
	public function getCustomDomainControllers_withDcNotNull_returnDc()
	{
		$returnedDomainControllers = $this->ldapConnection->getDomainControllers($this->connectionDetails);
		$this->assertEquals(array($this->connectionDetails->getDomainControllers()), $returnedDomainControllers);
	}

	/**
	 * @test
	 */
	public function getAdPort_withAdPortNotNull_returnPort()
	{
		$returnedPort = $this->ldapConnection->getAdPort($this->connectionDetails);
		$this->assertEquals($this->connectionDetails->getPort(), $returnedPort);
	}


	/**
	 * @test
	 */
	public function getCustomNetworkTimeout_withNetworkTimeoutNotNull_returnNetworkTimeout()
	{
		$returnedNetworkTimeout = $this->ldapConnection->getNetworkTimeout($this->connectionDetails);
		$this->assertEquals($this->connectionDetails->getNetworkTimeout(), $returnedNetworkTimeout);

		$this->ldapConnection->connect($this->connectionDetails);
		$adLDAP = $this->ldapConnection->getAdLdap();
	}

	/**
	 * @test
	 */
	public function checkPort_testAdPort_returnTrue()
	{
		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(Options::DOMAIN_CONTROLLERS),
				array(Options::PORT)
			)
			->will(
				$this->onConsecutiveCalls(
					$this->connectionDetails->getDomainControllers(),
					$this->connectionDetails->getPort()
				)
			);

		$actual = $this->ldapConnection->checkPorts();
		$this->assertEquals(true, $actual);
	}
}
