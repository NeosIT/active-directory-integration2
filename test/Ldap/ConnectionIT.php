<?php

/**
 * It_Ldap_Connection
 *
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class It_Ldap_ConnectionIT extends It_BasicTest
{
	/* @var Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var Ldap_Connection */
	private $ldapConnection;

	/* @var Ldap_ConnectionDetails */
	protected $connectionDetails;

	public function setUp()
	{
		$this->configuration = $this->createMock('Multisite_Configuration_Service');

		$this->ldapConnection = new Ldap_Connection($this->configuration);
		$this->connectionDetails = $this->createAdConnectionDetails();
		$this->ldapConnection->connect($this->connectionDetails);
		$this->prepareActiveDirectory($this->ldapConnection->getAdLdap());
	}

	public function tearDown()
	{
		$this->rollbackAdAfterConnectionIt($this->ldapConnection->getAdLdap());
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

		$this->ldapConnection->connect($this->connectionDetails);
		$this->assertEquals(null, $this->ldapConnection->getAdLdap());
	}

	/**
	 * @test
	 */
	public function findSanitizedAttributesOfUser_forAdministrator_returnAttributeValueArray()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$expectedArray = array(
			'samaccountname' => 'Jurg',
			'cn'             => 'Jurg Smith',
			'memberof'       => 'CN=AdiItGroup,OU=AdiItOu,DC=test,DC=ad'
		);

		$returnedValue = $this->ldapConnection->findSanitizedAttributesOfUser(
			$this->username1, array("sAMAccountName", "cn", "memberOf")
		);
		$this->assertEquals($expectedArray, $returnedValue);
	}

	/**
	 * @test
	 */
	public function findCorrectSuffixForUser_withThreeSuffix_returnCorrectSuffix()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$returnedValue = $this->ldapConnection->authenticate(
			get_cfg_var('AD_USERNAME'), '@company.local;@it.local;' . get_cfg_var('AD_SUFFIX'),
			$this->connectionDetails->getPassword()
		);

		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function findCorrectSuffixForUser_withThreeSuffix_returnFalse()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$returnedValue = $this->ldapConnection->authenticate(
			get_cfg_var('AD_USERNAME'), '@company.local;@it.local;@test.ut', $this->connectionDetails->getPassword()
		);

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
		$this->ldapConnection->connect($this->connectionDetails);

		$attributesToSync = array(
			"countryCode" => 1,
			"description" => "Description modified by integration Test!",
		);

		$returnedValue = $this->ldapConnection->modifyUserWithoutSchema($this->username1, $attributesToSync);
		$this->assertTrue($returnedValue);

		// Rollback everything
		$attributesToSync = array(
			"countryCode" => 0,
			"description" => "Vordefiniertes Konto für die Verwaltung des Computers bzw. der Domäne",
		);

		$returnedValue = $this->ldapConnection->modifyUserWithoutSchema($this->username1, $attributesToSync);
		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_withGroupName_returnMemberArray()
	{
		$this->ldapConnection->connect($this->connectionDetails);
		$expectedMember = array(
			strtolower($this->username1) => $this->username1
		);

		$returnedValue = $this->ldapConnection->findAllMembersOfGroup($this->groupName1);
		$this->assertEquals($expectedMember, $returnedValue);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroups_withGroupName_returnMemberArray()
	{
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
		$expectedDc = $this->connectionDetails->getCustomDomainControllers();

		$this->assertEquals($expectedDc, $returnedValue);
	}

	/**
	 * @test
	 */
	public function checkPort_withCorrectData_returnTrue()
	{
		$returnedValue = $this->ldapConnection->checkPort(
			$this->connectionDetails->getCustomDomainControllers(), $this->connectionDetails->getCustomPort(),
			$this->connectionDetails->getCustomNetworkTimeout()
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
			'base_dn' => $this->connectionDetails->getCustomBaseDn(),
			'domain_controllers' => array($this->connectionDetails->getCustomDomainControllers()),
			'ad_port' => $this->connectionDetails->getCustomPort(),
			'use_tls' => $this->connectionDetails->getCustomUseStartTls(),
			'network_timeout' => $this->connectionDetails->getCustomNetworkTimeout(),
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
		$this->assertEquals($this->connectionDetails->getCustomBaseDn(), $returnedBaseDn);
	}

	/**
	 * @test
	 */
	public function getCustomDomainControllers_withDcNotNull_returnDc()
	{
		$returnedDomainControllers = $this->ldapConnection->getDomainControllers($this->connectionDetails);
		$this->assertEquals(array($this->connectionDetails->getCustomDomainControllers()), $returnedDomainControllers);
	}

	/**
	 * @test
	 */
	public function getAdPort_withAdPortNotNull_returnPort()
	{
		$returnedPort = $this->ldapConnection->getAdPort($this->connectionDetails);
		$this->assertEquals($this->connectionDetails->getCustomPort(), $returnedPort);
	}

	/**
	 * @test
	 */
	public function getUseTls_withUseTlsNotNull_returnUseTls()
	{
		$returnedUseStartTls = $this->ldapConnection->getUseTls($this->connectionDetails);
		$this->assertEquals($this->connectionDetails->getCustomUseStartTls(), $returnedUseStartTls);
	}

	/**
	 * @test
	 */
	public function getCustomNetworkTimeout_withNetworkTimeoutNotNull_returnNetworkTimeout()
	{
		$returnedNetworkTimeout = $this->ldapConnection->getNetworkTimeout($this->connectionDetails);
		$this->assertEquals($this->connectionDetails->getCustomNetworkTimeout(), $returnedNetworkTimeout);

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
				array(Adi_Configuration_Options::DOMAIN_CONTROLLERS),
				array(Adi_Configuration_Options::PORT)
			)
			->will(
				$this->onConsecutiveCalls(
					$this->connectionDetails->getCustomDomainControllers(),
					$this->connectionDetails->getCustomPort()
				)
			);

		$actual = $this->ldapConnection->checkPorts();
		$this->assertEquals(true, $actual);
	}
}
