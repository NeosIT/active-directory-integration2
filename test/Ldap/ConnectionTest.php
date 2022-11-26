<?php

namespace Dreitier\Ldap;

use Dreitier\ActiveDirectory\Context;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\User\Persistence\Repository;
use Dreitier\Test\BasicTest;
use Dreitier\Util\Internal\Native;
use Dreitier\Util\Util;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\Option\Encryption;
use Dreitier\AdLdap\AdLdap;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class ConnectionTest extends BasicTest
{
	/* @var Service|MockObject $attributes */
	private $configuration;

	/* @var adLDAP|MockObject $attributes */
	private $adLDAP;

	/* @var Native|\Mockery\MockInterface */
	private $internalNative;

	/** @var Context|MockObject */
	private $activeDirectoryContext;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
		$this->activeDirectoryContext = $this->createMock(Context::class);
		$this->adLDAP = $this->createMock(AdLdap::class);

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		Util::native($this->internalNative);
	}

	public function tearDown(): void
	{
		parent::tearDown();
		// release mocked native functions
		Util::native(null);
	}

	/**
	 * @param $methods
	 *
	 * @return Connection|MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder(Connection::class)
			->setConstructorArgs(array($this->configuration, $this->activeDirectoryContext))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function connect_createConnection_doNothing()
	{
		$sut = $this->sut(array('createConfiguration', 'createAdLdap', 'getAdLdap'));

		$connectionDetails = new ConnectionDetails();

		$config = array(
			'account_suffix' => '',
			'base_dn' => 'office.local',
			'domain_controllers' => array('1.2.3.4'),
			'ad_port' => 389,
			'use_tls' => true,
			'network_timeout' => 5,
			'ad_username' => 'admin',
			'ad_password' => '12345',
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
		$sut = $this->sut(array('getBaseDn', 'getDomainControllers', 'getAdPort', 'getUseTls', 'getUseSsl', 'getNetworkTimeout', 'getAllowSelfSigned'));

		$expected = array(
			'account_suffix' => '',
			'base_dn' => 'abba',
			'domain_controllers' => array('192.168.56.101'),
			'ad_port' => 389,
			'use_tls' => true,
			'use_ssl' => false,
			'network_timeout' => 5,
			'ad_username' => 'tobi',
			'ad_password' => 'Streng Geheim',
			'allow_self_signed' => true
		);

		$log = array(
			'account_suffix' => '',
			'base_dn' => 'abba',
			'domain_controllers' => array('192.168.56.101'),
			'ad_port' => 389,
			'use_tls' => true,
			'use_ssl' => false,
			'network_timeout' => 5,
			'ad_username' => 'tobi',
			'ad_password' => '*** protected password ***',
			'allow_self_signed' => true
		);

		$connectionDetails = new ConnectionDetails();
		$connectionDetails->setUsername('tobi');
		$connectionDetails->setPassword('Streng Geheim');

		// create phpunit expections
		parent::expects($sut, $this->once(), 'getBaseDn', $connectionDetails, 'abba');
		parent::expects($sut, $this->once(), 'getDomainControllers', $connectionDetails, array('192.168.56.101'));
		parent::expects($sut, $this->once(), 'getAdPort', $connectionDetails, 389);
		parent::expects($sut, $this->once(), 'getUseTls', $connectionDetails, true);
		parent::expects($sut, $this->once(), 'getUseSsl', $connectionDetails, false);
		parent::expects($sut, $this->once(), 'getNetworkTimeout', $connectionDetails, 5);
		parent::expects($sut, $this->once(), 'getAllowSelfSigned', $connectionDetails, true);

		$actual = $sut->createConfiguration($connectionDetails);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @since 2.1.13
	 * @issue ADI-713
	 * @test
	 */
	public function ADI_713_register_userInfo_hookIsRegistered()
	{
		$sut = $this->sut(null);
		\WP_Mock::expectFilterAdded(NEXT_AD_INT_PREFIX . 'ldap_map_userinfo', array($sut, 'mapUserInfo'), 10, 5);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function getBaseDn_withCustomValue_returnCustomValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new ConnectionDetails();
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

		$connectionDetails = new ConnectionDetails();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::BASE_DN)
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

		$connectionDetails = new ConnectionDetails();
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

		$connectionDetails = new ConnectionDetails();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::DOMAIN_CONTROLLERS)
			->willReturn('default');

		$actual = $sut->getDomainControllers($connectionDetails);
		$this->assertEquals(array('default'), $actual);
	}

	/**
	 * @test
	 */
	public function getAdPort_withCustomValue_returnCustomValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new ConnectionDetails();
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

		$connectionDetails = new ConnectionDetails();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::PORT)
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

		$connectionDetails = new ConnectionDetails();
		$connectionDetails->setEncryption(Encryption::LDAPS);

		$actual = $sut->getUseTls($connectionDetails);
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function getUseTls_withoutCustomValue_returnDefaultValue()
	{
		$sut = $this->sut(array('getEncryption'));

		$connectionDetails = new ConnectionDetails();

		$this->expects($sut, $this->once(), 'getEncryption', $connectionDetails, Encryption::STARTTLS);

		$actual = $sut->getUseTls($connectionDetails);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function getNetworkTimeout_withCustomValue_returnCustomValue()
	{
		$sut = $this->sut(null);

		$connectionDetails = new ConnectionDetails();
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

		$connectionDetails = new ConnectionDetails();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::NETWORK_TIMEOUT)
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

		$this->adLDAP->expects($this->once())
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

		$this->adLDAP->expects($this->once())
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

		$userQuery = UserQuery::forPrincipal("hugo");
		$attributeNames = array("sn", "givenname", "mail");

		$adResult = array(
			'result',
			'count' => 1,
			0 => array(
				'sn' => array(
					'count' => 1,
					0 => 'Brown',
				),
				0 => 'sn',
			),
		);

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('user_info')
			->with('hugo', array("sn", "givenname", "mail"))
			->willReturn($adResult);

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'ldap_map_userinfo')
			->with(false, $adResult, $adResult['count'], $userQuery, $attributeNames)
			->reply($adResult[0]);

		$actual = $sut->findAttributesOfUser($userQuery, $attributeNames);
		$this->assertEquals($adResult[0], $actual);
	}

	/**
	 * @issue ADI-713
	 * @since 2.1.13
	 * @test
	 */
	public function ADI_713_mapUserInfo_returnsFirstMatch_ifOneIsFound()
	{
		$userQuery = UserQuery::forPrincipal("username");
		$matchesFromLdap = array(array('FIRST'));

		$sut = $this->sut(null);
		$actual = $sut->mapUserInfo(false, $matchesFromLdap, sizeof($matchesFromLdap), $userQuery, array());

		$this->assertEquals($matchesFromLdap[0], $actual);
	}

	/**
	 * @issue ADI-713
	 * @since 2.1.13
	 * @test
	 */
	public function ADI_713_mapUserInfo_returnsFalse_ifMultipleAreFound()
	{
		$userQuery = UserQuery::forPrincipal("username");
		$matchesFromLdap = array(array('FIRST'), array('SECOND'));

		$sut = $this->sut(null);
		$actual = $sut->mapUserInfo(false, $matchesFromLdap, sizeof($matchesFromLdap), $userQuery, array());

		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_emptyAttributes_returnFalse()
	{
		$sut = $this->sut(null);

		$wpUser = new \WP_User();
		$wpUser->user_login = 'testUsername';
		$wpUser->ID = 1;

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($wpUser->ID, NEXT_AD_INT_PREFIX . Repository::META_KEY_OBJECT_GUID, true),
				'times' => 1,
				'return' => array())
		);

		$actual = $sut->modifyUserWithoutSchema($wpUser, array());

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_throwException_returnFalse()
	{
		$sut = $this->sut(array('getAdLdap'));

		$wpUser = new \WP_User();
		$wpUser->user_login = 'testUsername';
		$wpUser->ID = 1;

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($wpUser->ID, NEXT_AD_INT_PREFIX . Repository::META_KEY_OBJECT_GUID, true),
				'times' => 1,
				'return' => 'xxxx-xxxx-xxxx-xxxx')
		);

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method("user_modify_without_schema")
			->with('xxxx-xxxx-xxxx-xxxx', array("sn", "givename", "mail"))
			->willThrowException(new \Exception());

		$actual = $sut->modifyUserWithoutSchema($wpUser, array("sn", "givename", "mail"));
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_emptyResponse_returnFalse()
	{
		$sut = $this->sut(array('getAdLdap'));

		$wpUser = new \WP_User();
		$wpUser->user_login = 'testUsername';
		$wpUser->ID = 1;

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($wpUser->ID, NEXT_AD_INT_PREFIX . Repository::META_KEY_OBJECT_GUID, true),
				'times' => 1,
				'return' => 'xxxx-xxxx-xxxx-xxxx')
		);

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method("user_modify_without_schema")
			->with('xxxx-xxxx-xxxx-xxxx', array("sn", "givename", "mail"))
			->willReturn(false);

		$actual = $sut->modifyUserWithoutSchema($wpUser, array("sn", "givename", "mail"));
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function modifyUserWithoutSchema_successfulChange_returnTrue()
	{
		$sut = $this->sut(array('getAdLdap'));

		$wpUser = new \WP_User();
		$wpUser->user_login = 'testUsername';
		$wpUser->ID = 1;

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($wpUser->ID, NEXT_AD_INT_PREFIX . Repository::META_KEY_OBJECT_GUID, true),
				'times' => 1,
				'return' => 'xxxx-xxxx-xxxx-xxxx')
		);

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method("user_modify_without_schema")
			->with('xxxx-xxxx-xxxx-xxxx', array("sn", "givename", "mail"))
			->willReturn(true);

		$actual = $sut->modifyUserWithoutSchema($wpUser, array("sn", "givename", "mail"));
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
				array(Options::DOMAIN_CONTROLLERS),
				array(Options::PORT)
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
		$sut = $this->sut(array('findAllMembersOfGroup', 'filterDomainMembers'));

		$expected = array(
			'aa' => 'aA',
			'bb' => 'Bb',
			'cc' => 'CC',
			'd' => 'd',
			'eee' => 'eEe',
		);

		$groupA = array('aa' => 'aA',
			'bb' => 'Bb',
			'cc' => 'wrong');
		$groupB = array('cc' => 'CC',
			'd' => 'd',
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

		$sut->expects($this->exactly(2))
			->method('filterDomainMembers')
			->withConsecutive(
				array($groupA),
				array($groupB)
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
	public function filterDomainMembers_itConvertsArrayIntoAssociativeArray()
	{
		$sut = $this->sut(array('getAdLdap'));

		$userInfoA = array(
			0 => array(
				"objectsid" => array(
					0 => "S-1-5-21-3623811015-3361044348-30300820-555"
				)
			)
		);

		$userInfoB = array(
			0 => array(
				"objectsid" => array(
					0 => "S-1-5-21-3623811015-3361044348-30300820-666"
				)
			)
		);

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->exactly(2))
			->method('user_info')
			->withConsecutive(
				array('a'),
				array('b')
			)
			->will(
				$this->onConsecutiveCalls(
					$userInfoA,
					$userInfoB
				)
			);

		$this->activeDirectoryContext->expects($this->exactly(2))
			->method('isMember')
			->withConsecutive(
				array($this->callback(function ($sid) {
					return $sid->getFormatted() == 'S-1-5-21-3623811015-3361044348-30300820-555';
				}), false),
				array($this->callback(function ($sid) {
					return $sid->getFormatted() == 'S-1-5-21-3623811015-3361044348-30300820-666';
				}), false),
			)
			->will(
				$this->onConsecutiveCalls(
					true,
					false /* wrong SID */
				)
			);

		$actual = $sut->filterDomainMembers(array('a', 'b'));

		$this->assertEquals(array('a' => 'a'), $actual);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_getMembersOfPrimaryGroupId_returnMembers()
	{
		$sut = $this->sut(array('getAdLdap', 'getDomainSid'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('group_members_by_primarygroupid')
			->with('123', null, true)
			->willReturn(array('a'));

		$expected = array(
			'a'
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

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('group_info')
			->with('groupA')
			->willReturn(array('group_info'));

		$this->adLDAP->expects($this->once())
			->method('group_members')
			->with('groupA', null)
			->willReturn(array('a'));


		$expected = array(
			'a'
		);

		$actual = $sut->findAllMembersOfGroup(' groupA ');
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_throwException_returnFalse()
	{
		$sut = $this->sut(array('getAdLdap'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('group_members')
			->with('groupA', null)
			->willThrowException(new \Exception());

		$this->adLDAP->expects($this->once())
			->method('group_info')
			->with('groupA')
			->willReturn(array('group_info'));

		$actual = $sut->findAllMembersOfGroup(' groupA ');
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function findAllMembersOfGroup_groupMembersNotAnArray_returnFalse()
	{
		$sut = $this->sut(array('getAdLdap'));

		$sut->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLDAP);

		$this->adLDAP->expects($this->once())
			->method('group_info')
			->with('groupA')
			->willReturn(array('group_info'));

		$this->adLDAP->expects($this->once())
			->method('group_members')
			->with('groupA', null)
			->willReturn(false);

		$actual = $sut->findAllMembersOfGroup(' groupA ');
		$this->assertEquals(false, $actual);
	}
}
