<?php

/**
 * Ut_NextADInt_Ldap_ConnectionTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Ldap_ConnectionTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject $attributes */
	private $configuration;

	/* @var adLDAP|PHPUnit_Framework_MockObject_MockObject $attributes */
	private $adLDAP;

	/* @var NextADInt_Core_Util_Internal_Native|\Mockery\MockInterface */
	private $internalNative;

	public function setUp()
	{
		if (!class_exists('adLDAP')) {
			//get adLdap
			require_once ADI_PATH . '/vendor/adLDAP/adLDAP.php';
		}

		parent::setUp();
		$this->configuration = parent::createMock('NextADInt_Multisite_Configuration_Service');
		$this->adLDAP = parent::createMock('adLDAP');

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		NextADInt_Core_Util::native($this->internalNative);
	}

	public function tearDown()
	{
		parent::tearDown();
		// release mocked native functions
		NextADInt_Core_Util::native(null);
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Ldap_Connection|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		return $connection = $this->getMockBuilder('NextADInt_Ldap_Connection')
			->setConstructorArgs(array($this->configuration))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function connect_createConnection_doNothing()
	{
		$sut = $this->sut(array('createConfiguration', 'createAdLdap', 'getAdLdap'));

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();

		$config = array(
			'account_suffix'     => '',
			'base_dn'            => 'office.local',
			'domain_controllers' => array('1.2.3.4'),
			'ad_port'            => 389,
			'use_tls'            => true,
			'network_timeout'    => 5,
			'ad_username'        => 'admin',
			'ad_password'        => '12345',
		);

		$sut->expects($this->once())
			->method('createConfiguration')
			->with($connectionDetails)
			->willReturn($config);

		$sut->expects($this->once())
			->method('createAdLdap')
			->with($config);

		$sut->connect($connectionDetails);
	}

	/**
	 * @test
	 */
	public function createConfiguration_returnsConfiguration()
	{
		$sut = $this->sut(array('getBaseDn', 'getDomainControllers', 'getAdPort', 'getUseTls', 'getNetworkTimeout'));

		$expected = array(
			'account_suffix'     => '',
			'base_dn'            => 'abba',
			'domain_controllers' => array('192.168.56.101'),
			'ad_port'            => 389,
			'use_tls'            => true,
			'network_timeout'    => 5,
			'ad_username'        => 'tobi',
			'ad_password'        => 'Streng Geheim',
		);

		$log = array(
			'account_suffix'     => '',
			'base_dn'            => 'abba',
			'domain_controllers' => array('192.168.56.101'),
			'ad_port'            => 389,
			'use_tls'            => true,
			'network_timeout'    => 5,
			'ad_username'        => 'tobi',
			'ad_password'        => '*** protected password ***',
		);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();
		$connectionDetails->setUsername('tobi');
		$connectionDetails->setPassword('Streng Geheim');

		// create phpunit expections
		parent::expects($sut, $this->once(), 'getBaseDn', $connectionDetails, 'abba');
		parent::expects($sut, $this->once(), 'getDomainControllers', $connectionDetails, array('192.168.56.101'));
		parent::expects($sut, $this->once(), 'getAdPort', $connectionDetails, 389);
		parent::expects($sut, $this->once(), 'getUseTls', $connectionDetails, true);
		parent::expects($sut, $this->once(), 'getNetworkTimeout', $connectionDetails, 5);

		$actual = $sut->createConfiguration($connectionDetails);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getBaseDn_withCustomValue_returnCustomValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();
		$connectionDetails->setBaseDn('custom');

		$actual = $sut->getBaseDn($connectionDetails);
		$this->assertEquals('custom', $actual);
	}

	/**
	 * @test
	 */
	public function getBaseDn_withoutCustomValue_returnDefaultValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::BASE_DN)
			->willReturn('default');

		$actual = $sut->getBaseDn($connectionDetails);
		$this->assertEquals('default', $actual);
	}

	/**
	 * @test
	 */
	public function getDomainControllers_withCustomValue_returnCustomValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();
		$connectionDetails->setDomainControllers('custom;custom2');

		$actual = $sut->getDomainControllers($connectionDetails);
		$this->assertEquals(array('custom', 'custom2'), $actual);
	}

	/**
	 * @test
	 */
	public function getDomainControllers_withoutCustomValue_returnDefaultValue()
	{
		$sut = $this->sut(array('getDomainControllersWithEncryption'));

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS)
			->willReturn('default');

		$this->expects($sut, $this->once(), 'getDomainControllersWithEncryption', $connectionDetails, array('default'));

		$actual = $sut->getDomainControllers($connectionDetails);
		$this->assertEquals(array('default'), $actual);
	}

	/**
	 * @test
	 */
	public function getAdPort_withCustomValue_returnCustomValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();
		$connectionDetails->setPort('custom');

		$actual = $sut->getAdPort($connectionDetails);
		$this->assertEquals('custom', $actual);
	}

	/**
	 * @test
	 */
	public function getAdPort_withoutCustomValue_returnDefaultValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::PORT)
			->willReturn('default');

		$actual = $sut->getAdPort($connectionDetails);
		$this->assertEquals('default', $actual);
	}

	/**
	 * @test
	 */
	public function getUseTls_withCustomValue_returnCustomValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();
		$connectionDetails->setEncryption(NextADInt_Multisite_Option_Encryption::LDAPS);

		$actual = $sut->getUseTls($connectionDetails);
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function getUseTls_withoutCustomValue_returnDefaultValue()
	{
		$sut = $this->sut(array('getEncryption'));

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();

		$this->expects($sut, $this->once(), 'getEncryption', $connectionDetails, NextADInt_Multisite_Option_Encryption::STARTTLS);

		$actual = $sut->getUseTls($connectionDetails);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function getNetworkTimeout_withCustomValue_returnCustomValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();
		$connectionDetails->setNetworkTimeout(5);

		$actual = $sut->getNetworkTimeout($connectionDetails);
		$this->assertEquals(5, $actual);
	}

	/**
	 * @test
	 */
	public function getNetworkTimeout_withoutCustomValue_returnDefaultValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::NETWORK_TIMEOUT)
			->willReturn('default');

		$actual = $sut->getNetworkTimeout($connectionDetails);
		$this->assertEquals('default', $actual);
	}

	/**
	 * @test
	 */
	public function getAdLDAP_withAdLdap()
	{
		$sut = $this->sut(null);

		$sut->createAdLdap(array());

		$actual = $sut->getAdLdap();
		$this->assertTrue($actual instanceof adLDAP);
	}

	/**
	 * @test
	 */
	public function isConnected_noConnectionEstablished_returnFalse()
	{
		$sut = $this->sut(null);

		$sut->createAdLdap(array());
		$actual = $sut->isConnected();
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function isConnected_connectionEstablished_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isConnected();
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function authenticateUser_success_returnTrue()
	{
		$sut = $this->sut(array('getAdLdap'));

		$this->adLDAP->expects($this->at(0))
			->method('set_account_suffix')
			->with('@a.de');

		$this->adLDAP->expects($this->once())
			->method('authenticate')
			->with('hugo', 'pw')
			->willReturn(true);

		$actual = $sut->authenticateUser($this->adLDAP, 'hugo', ' @a.de   ', 'pw');
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function authenticateUser_unsuccessful_returnFalse()
	{
		$sut = $this->sut(array('getAdLdap'));

		$this->adLDAP->expects($this->at(0))
			->method('set_account_suffix')
			->with('@a.de');

		$this->adLDAP->expects($this->once())
			->method('authenticate')
			->with('hugo', 'pw')
			->willReturn(false);

		$actual = $sut->authenticateUser($this->adLDAP, 'hugo', ' @a.de', 'pw');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function findAttributesOfUser_processAdInformation_returnAttributes()
	{
		$sut = $this->sut(array('getAdLdap'));

		$adResult = array(
			'result',
			0 => array(
				'sn' => array(
					'count' => 1,
					0       => 'Brown',
				),
				0    => 'sn',
			),
		);

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('user_info')
			->with('hugo', array("sn", "givenname", "mail"))
			->willReturn($adResult);

		$actual = $sut->findAttributesOfUser('hugo', array("sn", "givenname", "mail"));
		$this->assertEquals($adResult[0], $actual);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_emptyAttributes_returnFalse()
	{
		$sut = $this->sut(null);

		$actual = $sut->modifyUserWithoutSchema("testUsername", array());

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_throwException_returnFalse()
	{
		$sut = $this->sut(array('getAdLdap'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method("user_modify_without_schema")
			->with('hugo', array("sn", "givename", "mail"))
			->willThrowException(new Exception());

		$actual = $sut->modifyUserWithoutSchema('hugo', array("sn", "givename", "mail"));
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_emptyResponse_returnFalse()
	{
		$sut = $this->sut(array('getAdLdap'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method("user_modify_without_schema")
			->with('hugo', array("sn", "givename", "mail"))
			->willReturn(false);

		$actual = $sut->modifyUserWithoutSchema('hugo', array("sn", "givename", "mail"));
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_successfulChange_returnTrue()
	{
		$sut = $this->sut(array('getAdLdap'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method("user_modify_without_schema")
			->with('hugo', array("sn", "givename", "mail"))
			->willReturn(true);

		$actual = $sut->modifyUserWithoutSchema('hugo', array("sn", "givename", "mail"));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function checkPorts_fsockopenIsDeactivated_returnFalse()
	{
		$sut = $this->sut(null);

		$this->internalNative->expects($this->once())
			->method('isFunctionAvailable')
			->with('fsockopen')
			->willReturn(false);

		$actual = $sut->checkPorts();
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function checkPorts_fsockopenIsAvailable_delegateToMethod()
	{
		$sut = $this->sut(array('checkPort'));

		$this->internalNative->expects($this->once())
			->method('isFunctionAvailable')
			->with('fsockopen')
			->willReturn(true);

		$this->configuration->expects($this->exactly(2))
			->method('getOptionValue')
			->withConsecutive(
				array(NextADInt_Adi_Configuration_Options::DOMAIN_CONTROLLERS),
				array(NextADInt_Adi_Configuration_Options::PORT)
			)
			->will(
				$this->onConsecutiveCalls(
					'127.0.0.1;localhost',
					80
				)
			);

		$sut->expects($this->exactly(2))
			->method('checkPort')
			->withConsecutive(
				array('127.0.0.1', 80, 2),
				array('localhost', 80, 2)
			)
			->will(
				$this->onConsecutiveCalls(
					false,
					true
				)
			);

		$sut->checkPorts();
	}

	/**
	 * @test
	 */
	public function checkPort_fsockopenNotAvailable_returnFalse()
	{
		$sut = $this->sut(null);

		$this->internalNative->expects($this->once())
			->method('isFunctionAvailable')
			->with('fsockopen')
			->willReturn(false);

		$actual = $sut->checkPort('127.0.0.1', '389', 5);
		$this->assertEquals(false, $actual);
	}


	/**
	 * @test
	 */
	public function checkPort_unsuccessfulResponse_returnFalse()
	{
		$sut = $this->sut(null);

		$this->internalNative->expects($this->once())
			->method('isFunctionAvailable')
			->with('fsockopen')
			->willReturn(true);

		$this->internalNative->expects($this->once())
			->method('fsockopen')
			->with('127.0.0.1', '389', -1, '', 5)
			->willReturn(false);

		$actual = $sut->checkPort('127.0.0.1', '389', 5);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function checkPort_successfulResponse_returnTrue()
	{
		$sut = $this->sut(array('isFsockopenAvailable'));

		$this->internalNative->expects($this->once())
			->method('isFunctionAvailable')
			->with('fsockopen')
			->willReturn(true);

		$this->internalNative->expects($this->once())
			->method('fsockopen')
			->with('127.0.0.1', '389', -1, '', 5)
			->willReturn(true);

		$this->internalNative->expects($this->once())
			->method('fclose')
			->with(true);

		$actual = $sut->checkPort('127.0.0.1', '389', 5);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function getLastUsedDC()
	{
		$sut = $this->sut(array('getAdLdap'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('get_last_used_dc')
			->willReturn('dc');

		$actual = $sut->getLastUsedDC();
		$this->assertEquals('dc', $actual);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroups_delegateToMethod_returnMembers()
	{
		$sut = $this->sut(array('findAllMembersOfGroup'));

		$expected = array(
			'aa'  => 'aA',
			'bb'  => 'Bb',
			'cc'  => 'CC',
			'd'   => 'd',
			'eee' => 'eEe',
		);

		$groupA = array('aa' => 'aA',
						'bb' => 'Bb',
						'cc' => 'wrong');
		$groupB = array('cc'  => 'CC',
						'd'   => 'd',
						'eee' => 'eEe');

		$sut->expects($this->exactly(2))
			->method('findAllMembersOfGroup')
			->withConsecutive(
				array('groupA'),
				array('groupB')
			)
			->will(
				$this->onConsecutiveCalls(
					$groupA,
					$groupB
				)
			);

		$actual = $sut->findAllMembersOfGroups('groupA;groupB');
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_getMembersOfPrimarGroupId_returnMembers()
	{
		$sut = $this->sut(array('getAdLdap', 'getDomainSid'));

		$userInfo = array(
			0 => array(
				"objectsid" => array(
					0 => "1234"
				)
			)
		);

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$sut->expects($this->once())
			->method('getDomainSid')
			->willReturn('1234');

		$this->adLDAP->expects($this->once())
			->method('group_members_by_primarygroupid')
			->with('123', null, true)
			->willReturn(array('huGo'));

		$this->adLDAP->expects($this->once())
			->method('user_info')
			->with('huGo')
			->willReturn($userInfo);

		$this->adLDAP->expects($this->once())
			->method('convertObjectSidBinaryToString')
			->with('1234')
			->willReturn('1234');

		$expected = array(
			'hugo' => 'huGo',
		);

		$actual = $sut->findAllMembersOfGroup(' id:123 ');
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_getMembersOfGroupName_returnMembers()
	{
		$sut = $this->sut(array('getAdLdap', 'getDomainSid'));

		$userInfo = array(
			0 => array(
				"objectsid" => array(
					0 => "1234"
				)
			)
		);

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$sut->expects($this->once())
			->method('getDomainSid')
			->willReturn('1234');

		$this->adLDAP->expects($this->once())
			->method('group_members')
			->with('groupA', null)
			->willReturn(array('huGo'));

		$this->adLDAP->expects($this->once())
			->method('user_info')
			->with('huGo')
			->willReturn($userInfo);

		$this->adLDAP->expects($this->once())
			->method('convertObjectSidBinaryToString')
			->with('1234')
			->willReturn('1234');

		$expected = array(
			'hugo' => 'huGo',
		);

		$actual = $sut->findAllMembersOfGroup(' groupA ');
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_throwException_returnEmptyArray()
	{
		$sut = $this->sut(array('getAdLdap'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('group_members')
			->with('groupA', null)
			->willThrowException(new Exception());

		$actual = $sut->findAllMembersOfGroup(' groupA ');
		$this->assertEquals(array(), $actual);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_groupMembersNotAnArray_returnEmptyArray()
	{
		$sut = $this->sut(array('getAdLdap'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('group_members')
			->with('groupA', null)
			->willReturn(false);

		$actual = $sut->findAllMembersOfGroup(' groupA ');
		$this->assertEquals(array(), $actual);
	}
}
