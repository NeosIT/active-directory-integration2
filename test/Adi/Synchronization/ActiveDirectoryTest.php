<?php
/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Synchronization_ActiveDirectoryTest extends Ut_BasicTest
{
	/* @var Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var Ldap_Connection | PHPUnit_Framework_MockObject_MockObject */
	private $ldapConnection;

	/* @var Ldap_Attribute_Service | PHPUnit_Framework_MockObject_MockObject */
	private $attributeService;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->ldapConnection = $this->createMock('Ldap_Connection');
		$this->attributeService = $this->createMock('Ldap_Attribute_Service');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param null $methods
	 *
	 * @return Adi_Synchronization_ActiveDirectory|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Synchronization_ActiveDirectory')
			->setConstructorArgs(
				array(
					$this->attributeService,
					$this->configuration,
					$this->ldapConnection
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function synchronize_succeeds()
	{
		$sut = $this->sut(array('prepareForSync', 'getSyncableAttributes', 'getUsers', 'synchronizeUser', 'finishSynchronization'));

		$attributes  = array('cn' => new Ldap_Attribute());
		$users = array((object) array('ID' => 1));

		$attributeRepository = $this->createMock('Ldap_Attribute_Repository');

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
	public function prepareForSync_syncToAdIsDisabled_returnFalse()
	{
		$sut = $this->sut(array('isEnabled'));

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(false);


		$actual = $this->invokeMethod($sut, 'prepareForSync', array());
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
		$sut = $this->sut(array('startTimer', 'connectToAdLdap', 'increaseExecutionTime', 'isEnabled', 'getServiceAccountUsername', 'getServiceAccountPassword'));

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

		$actual = $this->invokeMethod($sut, 'prepareForSync', array());
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
			->willReturn(array());

		$actual = $this->invokeMethod($sut, 'getUsers', array(66));
		$this->assertEquals(0, sizeof($actual));
	}

	/**
	 * @test
	 */
	public function getUsers_withNonEmptyArray_returnArray()
	{
		$sut = $this->sut(array('findActiveDirectoryUsers'));

		$users = array((object) array('ID' => 66));

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

		$attributes = array('cn' => new Ldap_Attribute());
		$attributesToSync = array('metakey_cn' => array('cn_value'));

		$user = (object) array(
			'user_login' => 'User',
			'ID' => 97
		);

		$sut->expects($this->once())
			->method('findAttributesOfUser')
			->with(97, $attributes)
			->willReturn($attributesToSync);

		$this->ldapConnection->expects($this->once())
			->method('modifyUserWithoutSchema')
			->with('User', $attributesToSync)
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

		$attributes = array('mail' => new Ldap_Attribute());
		$attributesToSync = array('metakey_mail' => array(''));

		$user = (object) array(
			'user_login' => 'User',
			'ID' => 97
		);
		$expectedAttributesToSync = array('metakey_mail' => array());


		$sut->expects($this->once())
			->method('findAttributesOfUser')
			->with(97, $attributes)
			->willReturn($attributesToSync);

		$this->ldapConnection->expects($this->once())
			->method('modifyUserWithoutSchema')
			->with('User', $expectedAttributesToSync)
			->willReturn(true);


		$actual = $this->invokeMethod($sut, 'synchronizeUser', array($user, $attributes));
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function findAttributesOfUser_onlyReturnsAttributesAvailableInWordPress()
	{
		$sut = $this->sut(null);

		$meta = new Ldap_Attribute();
		$meta->setMetakey('adi2_mail');
		$attributes = array('mail' => $meta);

		\WP_Mock::wpFunction('get_user_meta', array(
			'args' => 879,
			'times' => 1,
			'return' => array('cn' => '666', 'adi2_mail' => 'mail@test.ad')
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
	public function isSynchronizable_delegatesToFindActiveDirectoryUsers() {
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
	public function hasActiveDirectoryAttributeEditPermission_itReturnTrue_ifOwnProfileShouldBeEdited() {
		$sut = $this->sut(null);

		$this->assertTrue($sut->hasActiveDirectoryAttributeEditPermission(true));
	}

	/**
	 * @test
	 */
	public function hasActiveDirectoryAttributeEditPermission_itReturnTrue_ifAnotherProfileShouldBeEdited_andUserIsAdmin() {
		$sut = $this->sut(null);

		\WP_Mock::wpFunction('current_user_can', array(
			'args' => 'edit_users',
			'times' => 1,
			'return' => true
		));

		$this->assertTrue($sut->hasActiveDirectoryAttributeEditPermission(false));
	}

	/**
	 * @test
	 */
	public function isEnabled_delegatesToConfiguration() {
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_AD_ENABLED)
			->willReturn(true);

		$this->assertTrue($sut->isEnabled());
	}

	/**
	 * @test
	 */
	public function getServiceAccountUsername_delegatesToConfiguration() {
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_USER)
			->willReturn('username');

		$this->assertEquals('username', $sut->getServiceAccountUsername());
	}

	/**
	 * @test
	 */
	public function getServiceAccountPassword_delegatesToConfiguration() {
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD)
			->willReturn('password');

		$this->assertEquals('password', $sut->getServiceAccountPassword());
	}

	/**
	 * @test
	 */
	public function assertSynchronizationAvailable_throwsExceptionIfDisabled() {
		$sut = $this->sut(array('isEnabled'));

		$sut->expects($this->once())
			->method('isEnabled')
			->willReturn(false);

		try {
			$sut->assertSynchronizationAvailable(1, true);
			$this->assertTrue(false /* guard */);
		}
		catch (Exception $e) {
			$this->assertContains("is not enabled", $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function assertSynchronizationAvailable_throwsExceptionIfUserIsNotSynchronizable() {
		$sut = $this->sut(array('isEnabled', 'isSynchronizable'));

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
		}
		catch (Exception $e) {
			$this->assertContains("not have a corresponding Active Directory account", $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function assertSynchronizationAvailable_throwsExceptionIfEditingAForeignProfile_withoutServiceAccount() {
		$sut = $this->sut(array('isEnabled', 'isSynchronizable', 'isServiceAccountEnabled'));

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
		}
		catch (Exception $e) {
			$this->assertContains("no Sync To AD service account available", $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function isEditable_checksAvailabilityOfSynchronization() {
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