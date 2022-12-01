<?php

namespace Dreitier\Nadi\User\Profile\Ui;


use Dreitier\Ldap\Attribute\Attribute;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Ldap\Attributes;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\Synchronization\ActiveDirectorySynchronizationService;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class TriggerActiveDirectorySynchronizationTest extends BasicTest
{
	/* @var Service | MockObject */
	private $configuration;

	/* @var ActiveDirectorySynchronizationService | MockObject */
	private $syncToActiveDirectory;

	/* @var Repository | MockObject */
	private $attributeRepository;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
		$this->syncToActiveDirectory = $this->createMock(ActiveDirectorySynchronizationService::class);
		$this->attributeRepository = $this->createMock(Repository::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return TriggerActiveDirectorySynchronization|MockObject
	 */
	public function sut($methods = null, $errors = array())
	{
		return $this->getMockBuilder(TriggerActiveDirectorySynchronization::class)
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->syncToActiveDirectory,
					$this->attributeRepository,
					$errors
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_addsWordPressHooks()
	{
		$sut = $this->sut(null);

		\WP_Mock::expectActionAdded('personal_options_update', array($sut, 'updateOwnProfile'));
		\WP_Mock::expectActionAdded('edit_user_profile_update', array($sut, 'updateForeignProfile'));

		$sut->register();
	}

	/**
	 * @test
	 */
	public function updateForeignProfile_delegatesToUpdateProfile()
	{
		$sut = $this->sut(array('updateProfile'));

		$sut->expects($this->once())
			->method('updateProfile')
			->with(666, false)
			->willReturn(true);

		$this->assertTrue($sut->updateForeignProfile(666));
	}

	/**
	 * @test
	 */
	public function updateOwnProfile_delegatesToUpdateProfile()
	{
		$sut = $this->sut(array('updateProfile'));

		$sut->expects($this->once())
			->method('updateProfile')
			->with(666, true)
			->willReturn(true);

		$this->assertTrue($sut->updateOwnProfile(666));
	}

	/**
	 * @test
	 */
	public function updateProfile_triggersUpdateWordPressProfile()
	{
		$sut = $this->sut(array('updateWordPressProfile', 'triggerSyncToActiveDirectory'));

		$userId = 1;
		$isOwnProfile = false;

		$_POST = array(
			'user_email' => 'test@company.it'
		);

		\WP_Mock::expectActionAdded('user_profile_update_errors', array($sut, 'generateError'), 10, 3);

		$sut->expects($this->once())
			->method('updateWordPressProfile')
			->with($userId, $_POST);

		$this->syncToActiveDirectory->expects($this->once())
			->method('isEditable')
			->with($userId, $isOwnProfile)
			->willReturn(false);

		// Sync to AD is disabled;
		$sut->expects($this->never())
			->method('triggerSyncToActiveDirectory');

		$returnedValue = $sut->updateProfile($userId, $isOwnProfile);
		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function updateProfile_withEscapedCharacters_unescapeThem()
	{
		$userId = 1;

		$_POST = array(
			'user_email' => 'test@company.it',
			'next_ad_int_samaccountname' => 'test\\\\User'
		);

		$isOwnProfile = true;

		$sut = $this->sut(array('updateWordPressProfile', 'triggerSyncToActiveDirectory'));

		$data = array(
			'user_email' => 'test@company.it',
			'next_ad_int_samaccountname' => 'test\User'
		);

		\WP_Mock::expectActionAdded('user_profile_update_errors', array($sut, 'generateError'), 10, 3);

		$sut->expects($this->once())
			->method('updateWordPressProfile')
			->with($userId, $data);

		$this->syncToActiveDirectory->expects($this->once())
			->method('isEditable')
			->with($userId, $isOwnProfile)
			->willReturn(true);

		$sut->expects($this->once())
			->method('triggerSyncToActiveDirectory')
			->with($userId, $data)
			->willReturn('ad-user updated');

		$returnedValue = $sut->updateProfile($userId, true);
		$this->assertEquals('ad-user updated', $returnedValue);
	}

	/**
	 * @test
	 */
	public function updateProfile_triggersSynchronizationToActiveDirectory()
	{
		$userId = 1;

		$_POST = array(
			'user_email' => 'test@company.it',
			'next_ad_int_samaccountname' => 'testUser'
		);

		$isOwnProfile = true;

		$sut = $this->sut(array('updateWordPressProfile', 'triggerSyncToActiveDirectory'));

		$data = $_POST;

		\WP_Mock::expectActionAdded('user_profile_update_errors', array($sut, 'generateError'), 10, 3);

		$sut->expects($this->once())
			->method('updateWordPressProfile')
			->with($userId, $data);

		$this->syncToActiveDirectory->expects($this->once())
			->method('isEditable')
			->with($userId, $isOwnProfile)
			->willReturn(true);

		$sut->expects($this->once())
			->method('triggerSyncToActiveDirectory')
			->with($userId, $data)
			->willReturn('ad-user updated');

		$returnedValue = $sut->updateProfile($userId, true);
		$this->assertEquals('ad-user updated', $returnedValue);
	}

	/**
	 * @test
	 */
	public function updateWordPressProfile_triggers_update_user_meta()
	{
		$sut = $this->sut(null);

		$userId = 1;

		$cn = new Attribute();
		$cn->setMetakey('meta_cn');

		$attributes = array(
			'cn' => $cn
		);

		$this->attributeRepository->expects($this->once())
			->method('filterWhitelistedAttributes')
			->with(true)
			->willReturn($attributes);

		$data = array(
			'samaccountname' => 'testUser',
			'meta_cn' => 'abcd1234'
		);

		\WP_Mock::wpFunction('update_user_meta', array(
				'args' => array($userId, 'meta_cn', 'abcd1234'),
				'times' => 1)
		);

		$sut->updateWordPressProfile($userId, $data);
	}

	/**
	 * @test
	 */
	public function createLdapConnectionDetails_withServiceAccount_returnConnectionDetails()
	{
		$sut = $this->sut();

		$r = new \stdClass();
		$r->username = 'admin';
		$r->password = 'password123';

		$user = new \WP_User();

		$this->syncToActiveDirectory->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(true);

		$this->syncToActiveDirectory->expects($this->once())
			->method('getServiceAccountUsername')
			->willReturn('admin');

		$this->syncToActiveDirectory->expects($this->once())
			->method('getServiceAccountPassword')
			->willReturn('password123');

		$actual = $sut->createLdapConnectionDetails($user);

		$this->assertEquals($r, $actual);
	}

	/**
	 * @test
	 */
	public function createLdapConnectionDetails_withoutCustomPassword_returnNull()
	{
		$sut = $this->sut();

		$r = new \stdClass();
		$r->username = 'user';
		$r->password = 'password123';

		$user = new \WP_User();

		$this->syncToActiveDirectory->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(false);

		$this->syncToActiveDirectory->expects($this->never())
			->method('getServiceAccountUsername')
			->willReturn('admin');

		$this->syncToActiveDirectory->expects($this->never())
			->method('getServiceAccountPassword')
			->willReturn('password123');

		$actual = $sut->createLdapConnectionDetails($user);

		$this->assertNull($actual);
	}

	/**
	 * @test
	 */
	public function createLdapConnectionDetails_withCustomPassword_returnConnectionDetails()
	{
		$sut = $this->sut();

		$r = new \stdClass();
		$r->username = 'user@test.ad';
		$r->password = 'password123';

		$user = new \WP_User();
		$user->ID = 1;

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => array($user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'userprincipalname', true),
			'times' => 1,
			'return' => 'user@test.ad',
		));


		$this->syncToActiveDirectory->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(false);

		$this->syncToActiveDirectory->expects($this->never())
			->method('getServiceAccountUsername')
			->willReturn('user');

		$this->syncToActiveDirectory->expects($this->never())
			->method('getServiceAccountPassword')
			->willReturn('password123');

		$actual = $sut->createLdapConnectionDetails($user, 'password123');

		$this->assertEquals($r, $actual);
	}

	/**
	 * @test
	 */
	public function triggerSyncToActiveDirectory_usesTheCustomPassword()
	{
		$sut = $this->sut(array('createLdapConnectionDetails', 'synchronize'));

		$username = 'testUser';
		$wpUserdata = (object)array('user_login' => 'username');

		\WP_Mock::wpFunction('get_userdata', array(
			'args' => 1,
			'times' => 1,
			'return' => $wpUserdata
		));

		$sut->expects($this->once())
			->method('createLdapConnectionDetails')
			->with($wpUserdata, 'password')
			->willReturn(null);

		$actual = $sut->triggerSyncToActiveDirectory(1, array(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . TriggerActiveDirectorySynchronization::FORM_PASSWORD => 'password'));
		$this->assertEquals(false, $actual);
	}

	/**
	 * @test
	 */
	public function triggerSyncToActiveDirectory_startsSynchronization()
	{
		$sut = $this->sut(array('createLdapConnectionDetails'));

		$username = 'testUser';
		$wpUserdata = (object)array('ID' => 666, 'user_login' => 'username');
		$ldapConnectionDetails = (object)array('username' => 'serviceUsername', 'password' => 'servicePassword');

		\WP_Mock::wpFunction('get_userdata', array(
			'args' => 666,
			'times' => 1,
			'return' => $wpUserdata
		));

		$sut->expects($this->once())
			->method('createLdapConnectionDetails')
			->with($wpUserdata, null)
			->willReturn($ldapConnectionDetails);

		$this->syncToActiveDirectory->expects($this->once())
			->method('synchronize')
			->with(666, 'serviceUsername', 'servicePassword')
			->willReturn(true);

		$actual = $sut->triggerSyncToActiveDirectory(666, array());
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function triggerSyncToActiveDirectory_withUseServiceAccountAndNoCredentials_returnFalse()
	{
		$sut = $this->sut(array('createLdapConnectionDetails'));

		$this->mockFunction__();

		$wpUserdata = (object)array('ID' => 666, 'user_login' => 'username');
		$ldapConnectionDetails = (object)array('username' => 'serviceUsername', 'password' => 'servicePassword');

		$this->configuration->expects($this->exactly(3))
			->method('getOptionValue')
			->withConsecutive(
				array(Options::SYNC_TO_AD_USE_GLOBAL_USER),
				array(Options::SYNC_TO_AD_GLOBAL_USER),
				array(Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			)
			->will(
				$this->onConsecutiveCalls(
					true,
					"",
					""
				)
			);

		\WP_Mock::wpFunction('get_userdata', array(
			'args' => 666,
			'times' => 0,
			'return' => $wpUserdata
		));

		$sut->expects($this->never())
			->method('createLdapConnectionDetails')
			->with($wpUserdata, null)
			->willReturn($ldapConnectionDetails);

		$this->syncToActiveDirectory->expects($this->never())
			->method('synchronize')
			->with(666, 'serviceUsername', 'servicePassword')
			->willReturn(true);

		$actual = $sut->triggerSyncToActiveDirectory(666, array());
		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function generateError()
	{
		$errors = array(
			array('code1', 'message1'),
			array('code2', 'message2', 'data2'),
		);

		$sut = $this->sut(null, $errors);

		$error = new \WP_Error();

		$sut->generateError($error, null, null);

		$savedErrors = $error->getErrors();
		$this->assertEquals('message1', $savedErrors['code1']);
		$this->assertEquals('message2', $savedErrors['code2']);
	}
}