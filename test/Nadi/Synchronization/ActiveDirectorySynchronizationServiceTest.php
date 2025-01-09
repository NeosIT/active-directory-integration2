<?php

namespace Dreitier\Nadi\Synchronization;

use Dreitier\Ldap\Attribute\Attribute;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Ldap\Connection;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTestCase;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class ActiveDirectorySynchronizationServiceTest extends BasicTestCase
{
	/* @var Service | MockObject */
	private $configuration;

	/* @var Connection | MockObject */
	private $ldapConnection;

	/* @var \Dreitier\Ldap\Attribute\Service | MockObject */
	private $attributeService;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
		$this->ldapConnection = $this->createMock(Connection::class);
		$this->attributeService = $this->createMock(\Dreitier\Ldap\Attribute\Service::class);

		ob_start();
	}

	public function tearDown(): void
	{
		parent::tearDown();
		ob_end_clean();
	}

	/**
	 * @param null $methods
	 *
	 * @return ActiveDirectorySynchronizationService|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(ActiveDirectorySynchronizationService::class)
			->setConstructorArgs(
				array(
					$this->attributeService,
					$this->configuration,
					$this->ldapConnection
				)
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function synchronize_succeeds()
	{
		$sut = $this->sut(array('prepareForSync', 'getUsers', 'synchronizeUser', 'finishSynchronization'));

		$attributes = array('cn' => new Attribute());
		$users = array((object)array('ID' => 1));

		$attributeRepository = $this->createMock(Repository::class);

		$this->behave($this->attributeService, 'getRepository', $attributeRepository);
		$this->behave($attributeRepository, 'getSyncableAttributes', $attributes);

		$sut->expects($this->once())
			->method('prepareForSync')
			->willReturn(true);

		$sut->expects($this->once())
			->method('getUsers')
			->willReturn($users);

		$sut->expects($this->once())
			->method('synchronizeUser')
			->with($users[0], $attributes)
			->willReturn(true);

		$sut->expects($this->once())
			->method('finishSynchronization')
			->willReturn(1);

		$actual = $sut->synchronize();
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function ADI_145_synchronize_itCallsFilter_next_ad_int_sync_wp2ad_filter_synchronizable_users()
	{
		$sut = $this->sut(array('prepareForSync', 'getUsers', 'synchronizeUser', 'finishSynchronization'));

		$attributes = array('cn' => new Attribute());
		$users = array((object)array('ID' => 1));
		$modifiedUsers = array((object)array('ID' => 2));

		$attributeRepository = $this->createMock(Repository::class);

		$this->behave($this->attributeService, 'getRepository', $attributeRepository);
		$this->behave($attributeRepository, 'getSyncableAttributes', $attributes);

		$sut->expects($this->once())
			->method('getUsers')
			->willReturn($users);

		$sut->expects($this->once())
			->method('prepareForSync')
			->willReturn(true);

		$sut->expects($this->once())
			->method('synchronizeUser')
			->with($modifiedUsers[0])
			->willReturn(true);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'sync_wp2ad_filter_synchronizable_users')
			->with($users)
			->reply($modifiedUsers);

		$actual = $sut->synchronize();
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function prepareForSync_syncToAdIsDisabled_returnFalse()
	{
		$sut = $this->sut(array('isEnabled'));

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(false);


		$actual = $this->invokeMethod($sut, 'prepareForSync', []);
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function prepareForSync_whenUsernameIsNotInDomain_itReturnFalse()
	{
		$sut = $this->sut(array('isEnabled', 'getServiceAccountUsername', 'getServiceAccountPassword', 'connectToAdLdap', 'isUsernameInDomain'));

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$sut->expects($this->once())
			->method('getServiceAccountUsername')
			->willReturn("username");

		$sut->expects($this->once())
			->method('getServiceAccountPassword')
			->willReturn("password");

		$sut->expects($this->once())
			->method('connectToAdLdap')
			->with("username", "password")
			->willReturn(true);

		$sut->expects($this->once())
			->method('isUsernameInDomain')
			->with("username")
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'prepareForSync', []);
		$this->assertEquals(false, $actual);

	}

	/**
	 * @test
	 */
	public function prepareForSync_connectionNotEstablished_returnFalse()
	{
		$sut = $this->sut(array('startTimer', 'connectToAdLdap', 'increaseExecutionTime', 'isEnabled'));

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$sut->expects($this->once())
			->method('startTimer');

		$sut->expects($this->once())
			->method('connectToAdLdap')
			->with('username', 'password')
			->willReturn(false);

		$actual = $this->invokeMethod($sut, 'prepareForSync', array('username', 'password'));
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function prepareForSync_syncToAdIsEnabled_returnTrue()
	{
		$sut = $this->sut(array('startTimer', 'connectToAdLdap', 'increaseExecutionTime', 'isEnabled', 'getServiceAccountUsername', 'getServiceAccountPassword', 'isUsernameInDomain'));

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$sut->expects($this->once())
			->method('getServiceAccountUsername')
			->willReturn('username');

		$sut->expects($this->once())
			->method('getServiceAccountPassword')
			->willReturn('password');

		$sut->expects($this->once())
			->method('startTimer');

		$sut->expects($this->once())
			->method('connectToAdLdap')
			->with('username', 'password')
			->willReturn(true);

		$sut->expects($this->once())
			->method('increaseExecutionTime');

		$sut->expects($this->once())
			->method('isUsernameInDomain')
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'prepareForSync', []);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function getUsers_withEmptyArray_returnFalse()
	{
		$sut = $this->sut(array('findActiveDirectoryUsers'));

		$sut->expects($this->once())
			->method('findActiveDirectoryUsers')
			->with(66)
			->willReturn([]);

		$actual = $this->invokeMethod($sut, 'getUsers', array(66));
		$this->assertEquals(0, sizeof($actual));
	}

	/**
	 * @test
	 */
	public function getUsers_withNonEmptyArray_returnArray()
	{
		$sut = $this->sut(array('findActiveDirectoryUsers'));

		$users = array((object)array('ID' => 66));

		$sut->expects($this->once())
			->method('findActiveDirectoryUsers')
			->with(66)
			->willReturn($users);

		$actual = $this->invokeMethod($sut, 'getUsers', array(66));
		$this->assertEquals($users, $actual);
	}


	/**
	 * @test
	 */
	public function synchronizeUser_withAttributes_syncUser()
	{
		$sut = $this->sut(array('findAttributesOfUser'));

		$attributes = array('cn' => new Attribute());
		$attributesToSync = array('metakey_cn' => array('cn_value'));

		$user = (object)array(
			'user_login' => 'User',
			'ID' => 97
		);

		$sut->expects($this->once())
			->method('findAttributesOfUser')
			->with(97, $attributes)
			->willReturn($attributesToSync);

		$this->ldapConnection->expects($this->once())
			->method('modifyUserWithoutSchema')
			->with($user, $attributesToSync)
			->willReturn(true);

		$actual = $this->invokeMethod($sut, 'synchronizeUser', array($user, $attributes));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function synchronizeUser_withAttributeValueEmpty_syncUser()
	{
		$sut = $this->sut(array('findAttributesOfUser'));

		$attributes = array('mail' => new Attribute());
		$attributesToSync = array('metakey_mail' => array(''));

		$user = (object)array(
			'user_login' => 'User',
			'ID' => 97
		);
		$expectedAttributesToSync = array('metakey_mail' => []);


		$sut->expects($this->once())
			->method('findAttributesOfUser')
			->with(97, $attributes)
			->willReturn($attributesToSync);

		$this->ldapConnection->expects($this->once())
			->method('modifyUserWithoutSchema')
			->with($user, $expectedAttributesToSync)
			->willReturn(true);


		$actual = $this->invokeMethod($sut, 'synchronizeUser', array($user, $attributes));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function ADI_145_synchronizeUser_itCallsFilter_nextadi_sync_wp2ad_filter_synchronizable_attributes()
	{
		$sut = $this->sut(array('findAttributesOfUser'));

		$allowedAttributes = array('cn' => new Attribute());
		$attributesToSync = array('metakey_mail' => array(''));
		$modifiedAttributesToSync = array('modified_metakey_mail' => array(''));

		$user = (object)array(
			'user_login' => 'User',
			'ID' => 97
		);

		$sut->expects($this->once())
			->method('findAttributesOfUser')
			->with(97, $allowedAttributes)
			->willReturn($attributesToSync);

		$this->ldapConnection->expects($this->once())
			->method('modifyUserWithoutSchema')
			->with($user, $modifiedAttributesToSync)
			->willReturn(true);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'sync_wp2ad_filter_synchronizable_attributes')
			->with($attributesToSync, $user, $allowedAttributes)
			->reply($modifiedAttributesToSync);

		$actual = $this->invokeMethod($sut, 'synchronizeUser', array($user, $allowedAttributes));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function ADI_145_synchronizeUser_itCallsAction_nextadi_sync_wp2ad_after_user_synchronize()
	{
		$sut = $this->sut(array('findAttributesOfUser'));

		$allowedAttributes = array('xn' => new Attribute());
		$attributesToSync = array('metakey_cn' => array('cn_value'));

		$user = (object)array(
			'user_login' => 'User',
			'ID' => 97
		);

		$sut->expects($this->once())
			->method('findAttributesOfUser')
			->with(97, $allowedAttributes)
			->willReturn($attributesToSync);

		$this->ldapConnection->expects($this->once())
			->method('modifyUserWithoutSchema')
			->with($user, $attributesToSync)
			->willReturn(666);

		\WP_Mock::expectAction(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'sync_wp2ad_after_user_synchronize', 666, $user, $attributesToSync, $allowedAttributes);

		$actual = $this->invokeMethod($sut, 'synchronizeUser', array($user, $allowedAttributes));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function findAttributesOfUser_onlyReturnsAttributesAvailableInWordPress()
	{
		$sut = $this->sut();

		$meta = new Attribute();
		$meta->setMetakey('next_ad_int_mail');
		$attributes = array('mail' => $meta);

		\WP_Mock::userFunction('get_user_meta', array(
			'args' => 879,
			'times' => 1,
			'return' => array('cn' => '666', 'next_ad_int_mail' => 'mail@test.ad')
		));

		$actual = $this->invokeMethod($sut, 'findAttributesOfUser', array(879, $attributes));
		$this->assertEquals(array('mail' => 'mail@test.ad'), $actual);
	}

	/**
	 * @test
	 */
	public function finishSynchronisation_logElapsedTime_doNothing()
	{
		$sut = $this->sut(array('getElapsedTime'));

		$sut->expects($this->once())
			->method('getElapsedTime');

		$this->invokeMethod($sut, 'finishSynchronization', array(1));
	}


	/**
	 * @test
	 */
	public function isSynchronizable_delegatesToFindActiveDirectoryUsers()
	{
		$sut = $this->sut(array('findActiveDirectoryUsers'));
		$sut->expects($this->once())
			->method('findActiveDirectoryUsers')
			->with(666)
			->willReturn(array(1));

		$this->assertEquals(true, $sut->isSynchronizable(666));
	}

	/**
	 * @test
	 */
	public function hasActiveDirectoryAttributeEditPermission_itReturnTrue_ifOwnProfileShouldBeEdited()
	{
		$sut = $this->sut();

		$this->assertTrue($sut->hasActiveDirectoryAttributeEditPermission(true));
	}

	/**
	 * @test
	 */
	public function hasActiveDirectoryAttributeEditPermission_itReturnTrue_ifAnotherProfileShouldBeEdited_andUserIsAdmin()
	{
		$sut = $this->sut();

		\WP_Mock::userFunction('current_user_can', array(
			'args' => 'edit_users',
			'times' => 1,
			'return' => true
		));

		$this->assertTrue($sut->hasActiveDirectoryAttributeEditPermission(false));
	}

	/**
	 * @test
	 */
	public function isEnabled_delegatesToConfiguration()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options ::SYNC_TO_AD_ENABLED)
			->willReturn(true);

		$this->assertTrue($sut->isEnabled());
	}

	/**
	 * @test
	 */
	public function getServiceAccountUsername_delegatesToConfiguration()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::SYNC_TO_AD_GLOBAL_USER)
			->willReturn('username');

		$this->assertEquals('username', $sut->getServiceAccountUsername());
	}

	/**
	 * @test
	 */
	public function getServiceAccountPassword_delegatesToConfiguration()
	{
		$sut = $this->sut();

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn('password');

		$this->assertEquals('password', $sut->getServiceAccountPassword());
	}

	/**
	 * @test
	 */
	public function assertSynchronizationAvailable_throwsExceptionIfDisabled()
	{
		$sut = $this->sut(array('isEnabled'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(false);

		try {
			$sut->assertSynchronizationAvailable(1, true);
			$this->assertTrue(false /* guard */);
		} catch (\Exception $e) {
			$this->assertStringContainsString("is not enabled", $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function assertSynchronizationAvailable_throwsExceptionIfUserIsNotSynchronizable()
	{
		$sut = $this->sut(array('isEnabled', 'isSynchronizable'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$sut->expects($this->once())
			->method('isSynchronizable')
			->with(666)
			->willReturn(false);

		try {
			$sut->assertSynchronizationAvailable(666, true);
			$this->assertTrue(false /* guard */);
		} catch (\Exception $e) {
			$this->assertStringContainsString("not have a corresponding Active Directory account", $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function assertSynchronizationAvailable_throwsExceptionIfEditingAForeignProfile_withoutServiceAccount()
	{
		$sut = $this->sut(array('isEnabled', 'isSynchronizable', 'isServiceAccountEnabled'));
		$this->mockFunction__();

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(true);

		$sut->expects($this->once())
			->method('isSynchronizable')
			->with(666)
			->willReturn(true);


		$sut->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(false);

		try {
			$sut->assertSynchronizationAvailable(666, false);
			$this->assertTrue(false /* guard */);
		} catch (\Exception $e) {
			$this->assertStringContainsString("no Sync To AD service account available", $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function isEditable_checksAvailabilityOfSynchronization()
	{
		$sut = $this->sut(array('hasActiveDirectoryAttributeEditPermission', 'assertSynchronizationAvailable'));
		$userId = 666;
		$isOwnProfile = true;

		$sut->expects($this->once())
			->method('hasActiveDirectoryAttributeEditPermission')
			->with($isOwnProfile)
			->willReturn(true);

		$sut->expects($this->once())
			->method('assertSynchronizationAvailable')
			->with($userId, $isOwnProfile)
			->willReturn(true);

		$this->assertTrue($sut->isEditable($userId, $isOwnProfile));
	}
}