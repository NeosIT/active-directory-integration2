<?php

/**
 * Adi_Synchronization_Stub is a stub for NextADInt_Adi_Synchronization_Abstract
 */
class Adi_Synchronization_Stub extends NextADInt_Adi_Synchronization_Abstract
{
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration, NextADInt_Ldap_Connection $connection, NextADInt_Ldap_Attribute_Service $attributeService)
	{
		parent::__construct($configuration, $connection, $attributeService);
	}
}

/**
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Synchronization_AbstractTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Ldap_Connection | PHPUnit_Framework_MockObject_MockObject */
	private $ldapConnection;

	/* @var NextADInt_Ldap_Attribute_Service | PHPUnit_Framework_MockObject_MockObject */
	private $attributeService;
	
	/* @var adLDAP | PHPUnit_Framework_MockObject_MockObject */
	private $adLDAP;

	/* @var NextADInt_Core_Util_Internal_Native|\Mockery\MockInterface */
	private $internalNative;

	public function setUp() : void
	{
		parent::setUp();

		if (!class_exists('adLDAP')) {
			//get adLdap
			require_once NEXT_AD_INT_PATH . '/vendor/adLDAP/adLDAP.php';
		}

		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('NextADInt_Ldap_Connection');
		$this->attributeService = $this->createMock('NextADInt_Ldap_Attribute_Service');
		$this->adLDAP = parent::createMock('adLDAP');

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		NextADInt_Core_Util::native($this->internalNative);
	}

	public function tearDown() : void
	{
		parent::tearDown();
		NextADInt_Core_Util::native(null);
	}

	/**
	 * @param null $methods
	 *
	 * @return Adi_Synchronization_Stub|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Synchronization_Stub')
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->ldapConnection,
					$this->attributeService
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function increaseExecutionTime_whenSettingIsInsufficient_itSetsMaxExecutionTime()
	{
		$sut = $this->sut();
		
		$this->internalNative->expects($this->exactly(2))
			->method('iniGet')
			->withConsecutive(
				array('max_execution_time'),
				array('max_execution_time')
			)
			->will($this->onConsecutiveCalls(
				"5000",
				'18000'
			));
		
		$this->internalNative->expects($this->once())
			->method("iniSet")
			->with('max_execution_time', "18000");
		
		$sut->increaseExecutionTime();
		
	}

	/**
	 * @test
	 */
	public function connectToLdap_itReturnsConnectionAfterCheck()
	{
		$sut = $this->sut();
		
		$connectionDetails = new NextADInt_Ldap_ConnectionDetails();
		$connectionDetails->setUsername("administrator");
		$connectionDetails->setPassword("password");
		
		$this->ldapConnection->expects($this->once())
			->method("connect")
			->with($connectionDetails);

		$this->ldapConnection->expects($this->once())
			->method("checkConnection")
			->with("administrator", "password")
			->willReturn(true);
		
		$actual = $sut->connectToAdLdap("administrator", "password");
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function findActiveDirectoryUsernames_itIgnoresNonDomainMember()
	{
		$sut = $this->sut(array('findActiveDirectoryUsers'));

		$sut->expects($this->once())
			->method("findActiveDirectoryUsers")
			->willReturn([]);
		
		$actual = $sut->findActiveDirectoryUsernames();
		
		$this->assertTrue(is_array($actual));
		$this->assertTrue(sizeof($actual) === 0);
	}

	/**
	 * @test
	 */
	public function findActiveDirectoryUsernames_itReturnsDomainMember()
	{
		$domainSid = 'S-1-5-21-3623811015-3361044348-30300820';
		$context = new NextADInt_ActiveDirectory_Context([$domainSid]);

		$sut = $this->sut(array('isVerifiedDomainMember', 'findActiveDirectoryUsers'));

		$users = array(
			0 => new WP_User()
		);

		$users[0]->ID = 1;
		$users[0]->user_login = "administrator";


		$expected = array(
			'1234' => 'administrator'
		);

		WP_Mock::wpFunction('get_user_meta', array(
				'args'   => array('1', NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_OBJECT_GUID, true),
				'times'  => '1',
				'return' => "1234")
		);

		$sut->expects($this->once())
			->method("findActiveDirectoryUsers")
			->willReturn($users);

		$actual = $sut->findActiveDirectoryUsernames();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findActiveDirectoryUsers_itOnlyReturnsDomainMembers()
	{
		$domainSid = 'S-1-5-21-3623811015-3361044348-30300820';
		$context = new NextADInt_ActiveDirectory_Context([$domainSid]);

		$sut = $this->sut(array('isVerifiedDomainMember'));

		$users = array(
			0 => new WP_User(),
			1 => new WP_User()
		);

		$users[0]->ID = 1;
		$users[0]->user_login = 'administrator';
		$users[1]->ID = 2;
		$users[1]->user_login = 'NotDomainMemberAdministrator';
		
		$args = array(
			'blog_id'    => '1',
			'meta_key'   => NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
					'value'   => '',
					'compare' => '!=',
				),
			),
			'exclude'    => array(1)
		);

		$expected = array(
			0 => new WP_User()
		);

		$expected[0]->ID = 1;
		$expected[0]->user_login = 'administrator';

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => '1',
			'return' => '1',
		));
		
		WP_Mock::wpFunction('get_users', array(
				'args'   => array($args),
				'times'  => '1',
				'return' => $users)
		);		

		WP_Mock::wpFunction('get_user_meta', array(
				'args'   => array('1', NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_DOMAINSID, true),
				'times'  => '1',
				'return' => "S-1-5-21-3623811015-3361044348-30300820-1013")
		);

		WP_Mock::wpFunction('get_user_meta', array(
				'args'   => array('2', NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_DOMAINSID, true),
				'times'  => '1',
				'return' => "S-1-5-21-3623811015-3361044348-66666666-1013")
		);

		$this->ldapConnection
			->method('getActiveDirectoryContext')
			->willReturn($context);

		$actual = $sut->findActiveDirectoryUsers();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function isUsernameInDomain_itReturnsTrue_whenUserIsVerifiedDomainMember()
	{
		$domainSid = 'S-1-5-21-3623811015-3361044348-30300820';
		$context = new NextADInt_ActiveDirectory_Context([$domainSid]);

		$sut = $this->sut(array('isVerifiedDomainMember'));
		
		$binarySid = array(
			0 => array(
				"objectsid" => array(
					0 => 'S-1-5-21-3623811015-3361044348-30300820-1234'
				)
			)
		);

		$this->ldapConnection->expects($this->once())
			->method("getAdLdap")
			->willReturn($this->adLDAP);

		$this->ldapConnection
			->method("getActiveDirectoryContext")
			->willReturn($context);

		$this->adLDAP->expects($this->once())
			->method('user_info')
			->with("administrator", array("objectsid"))
			->willReturn($binarySid);

		$actual = $sut->isUsernameInDomain("administrator");

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isUsernameInDomain_itReturnsFalse_whenUserIsNotInDomain()
	{
		$domainSid = 'S-1-5-21-3623811015-3361044348-30300820';
		$context = new NextADInt_ActiveDirectory_Context([$domainSid]);

		$sut = $this->sut(array('isVerifiedDomainMember'));

		$binarySid = array(
			0 => array(
				"objectsid" => array(
					0 => 'S-1-5-21-3623811015-3361044348-66666-1234'
				)
			)
		);

		$this->ldapConnection->expects($this->once())
			->method("getAdLdap")
			->willReturn($this->adLDAP);

		$this->ldapConnection
			->method("getActiveDirectoryContext")
			->willReturn($context);

		$this->adLDAP->expects($this->once())
			->method('user_info')
			->with("administrator", array("objectsid"))
			->willReturn($binarySid);

		$actual = $sut->isUsernameInDomain("administrator");

		$this->assertFalse($actual);
	}

	/**
	 * @issue #138
	 * @test
	 */
	public function findActiveDirectoryUsers_breaksWhenDomainSidIsEmtpy_gh138()
	{
		$domainSid = 'S-1-5-21-3623811015-3361044348-30300820';
		$context = new NextADInt_ActiveDirectory_Context([$domainSid]);

		$sut = $this->sut(array('isVerifiedDomainMember'));

		$users = array(
			0 => new WP_User(),
			1 => new WP_User()
		);

		$users[0]->ID = 1;
		$users[0]->user_login = 'administrator';
		$users[1]->ID = 2;
		$users[1]->user_login = 'NotDomainMemberAdministrator';

		$args = array(
			'blog_id'    => '1',
			'meta_key'   => NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
					'value'   => '',
					'compare' => '!=',
				),
			),
			'exclude'    => array(1)
		);

		$expected = array(
			0 => new WP_User()
		);

		$expected[0]->ID = 1;
		$expected[0]->user_login = 'administrator';

		WP_Mock::wpFunction('get_current_blog_id', array(
			'times'  => '1',
			'return' => '1',
		));

		WP_Mock::wpFunction('get_users', array(
				'args'   => array($args),
				'times'  => '1',
				'return' => $users)
		);

		WP_Mock::wpFunction('get_user_meta', array(
				'args'   => array('1', NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_DOMAINSID, true),
				'times'  => '1',
				'return' => "S-1-5-21-3623811015-3361044348-30300820-1013")
		);

		WP_Mock::wpFunction('get_user_meta', array(
				'args'   => array('2', NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Persistence_Repository::META_KEY_DOMAINSID, true),
				'times'  => '1',
				'return' => null)
		);

		$this->ldapConnection
			->method('getActiveDirectoryContext')
			->willReturn($context);

		$actual = $sut->findActiveDirectoryUsers();

		$this->assertEquals($expected, $actual);
	}
}