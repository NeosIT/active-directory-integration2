<?php

namespace Dreitier\Nadi\Synchronization;

use Dreitier\ActiveDirectory\Context;
use Dreitier\AdLdap\AdLdap;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\ConnectionDetails;
use Dreitier\Nadi\User\Persistence\Repository;
use Dreitier\Test\BasicTest;
use Dreitier\Util\Internal\Native;
use Dreitier\Util\Util;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Exception\CommandNotFoundException;

/**
 * Stub for AbstractSynchronizationService
 */
class AbstractSynchronizationServiceStub extends AbstractSynchronizationService
{
	public function __construct(Service    $configuration,
								Connection $connection, \Dreitier\Ldap\Attribute\Service $attributeService)
	{
		parent::__construct($configuration, $connection, $attributeService);
	}
}

/**
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class AbstractSynchronizationServiceTest extends BasicTest
{
	/* @var Service | MockObject */
	private $configuration;

	/* @var Connection | MockObject */
	private $ldapConnection;

	/* @var \Dreitier\Ldap\Attribute\Service | MockObject */
	private $attributeService;

	/* @var AdLdap | MockObject */
	private $adLDAP;

	/* @var Native|\Mockery\MockInterface */
	private $internalNative;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
		$this->ldapConnection = $this->createMock(Connection::class);
		$this->attributeService = $this->createMock(\Dreitier\Ldap\Attribute\Service::class);
		$this->adLDAP = $this->createMock(AdLdap::class);

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		Util::native($this->internalNative);
	}

	public function tearDown(): void
	{
		parent::tearDown();
		Util::native(null);
	}

	/**
	 * @param null $methods
	 *
	 * @return AbstractSynchronizationServiceStub|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(AbstractSynchronizationServiceStub::class)
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

		$connectionDetails = new ConnectionDetails();
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
		$context = new Context([$domainSid]);

		$sut = $this->sut(array('isVerifiedDomainMember', 'findActiveDirectoryUsers'));

		$users = array(
			0 => new \WP_User()
		);

		$users[0]->ID = 1;
		$users[0]->user_login = "administrator";


		$expected = array(
			'1234' => 'administrator'
		);

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array('1', NEXT_AD_INT_PREFIX . Repository::META_KEY_OBJECT_GUID, true),
				'times' => '1',
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
		$context = new Context([$domainSid]);

		$sut = $this->sut(array('isVerifiedDomainMember'));

		$users = array(
			0 => new \WP_User(),
			1 => new \WP_User()
		);

		$users[0]->ID = 1;
		$users[0]->user_login = 'administrator';
		$users[1]->ID = 2;
		$users[1]->user_login = 'NotDomainMemberAdministrator';

		$args = array(
			'blog_id' => '1',
			'meta_key' => NEXT_AD_INT_PREFIX . Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => NEXT_AD_INT_PREFIX . Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
					'value' => '',
					'compare' => '!=',
				),
			),
			'exclude' => array(1)
		);

		$expected = array(
			0 => new \WP_User()
		);

		$expected[0]->ID = 1;
		$expected[0]->user_login = 'administrator';

		\WP_Mock::wpFunction('get_current_blog_id', array(
			'times' => '1',
			'return' => '1',
		));

		\WP_Mock::wpFunction('get_users', array(
				'args' => array($args),
				'times' => '1',
				'return' => $users)
		);

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array('1', NEXT_AD_INT_PREFIX . Repository::META_KEY_DOMAINSID, true),
				'times' => '1',
				'return' => "S-1-5-21-3623811015-3361044348-30300820-1013")
		);

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array('2', NEXT_AD_INT_PREFIX . Repository::META_KEY_DOMAINSID, true),
				'times' => '1',
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
		$context = new Context([$domainSid]);

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
		$context = new Context([$domainSid]);

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
		$context = new Context([$domainSid]);

		$sut = $this->sut(array('isVerifiedDomainMember'));

		$users = array(
			0 => new \WP_User(),
			1 => new \WP_User()
		);

		$users[0]->ID = 1;
		$users[0]->user_login = 'administrator';
		$users[1]->ID = 2;
		$users[1]->user_login = 'NotDomainMemberAdministrator';

		$args = array(
			'blog_id' => '1',
			'meta_key' => NEXT_AD_INT_PREFIX . Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key' => NEXT_AD_INT_PREFIX . Repository::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME,
					'value' => '',
					'compare' => '!=',
				),
			),
			'exclude' => array(1)
		);

		$expected = array(
			0 => new \WP_User()
		);

		$expected[0]->ID = 1;
		$expected[0]->user_login = 'administrator';

		\WP_Mock::wpFunction('get_current_blog_id', array(
			'times' => '1',
			'return' => '1',
		));

		\WP_Mock::wpFunction('get_users', array(
				'args' => array($args),
				'times' => '1',
				'return' => $users)
		);

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array('1', NEXT_AD_INT_PREFIX . Repository::META_KEY_DOMAINSID, true),
				'times' => '1',
				'return' => "S-1-5-21-3623811015-3361044348-30300820-1013")
		);

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array('2', NEXT_AD_INT_PREFIX . Repository::META_KEY_DOMAINSID, true),
				'times' => '1',
				'return' => null)
		);

		$this->ldapConnection
			->method('getActiveDirectoryContext')
			->willReturn($context);

		$actual = $sut->findActiveDirectoryUsers();

		$this->assertEquals($expected, $actual);
	}
}