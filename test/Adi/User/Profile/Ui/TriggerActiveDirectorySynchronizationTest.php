<?php


/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronizationTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service | PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Adi_Synchronization_ActiveDirectory | PHPUnit_Framework_MockObject_MockObject */
	private $syncToActiveDirectory;

	/* @var NextADInt_Ldap_Attribute_Repository | PHPUnit_Framework_MockObject_MockObject */
	private $attributeRepository;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->syncToActiveDirectory = $this->createMock('NextADInt_Adi_Synchronization_ActiveDirectory');
		$this->attributeRepository = $this->createMock('NextADInt_Ldap_Attribute_Repository');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null, $errors = array())
	{
		return $this->getMockBuilder('NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization')
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
	public function updateForeignProfile_delegatesToUpdateProfile() {
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
	public function updateOwnProfile_delegatesToUpdateProfile() {
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
	public function updateProfile_triggersSynchronizationToActiveDirectory()
	{
		$userId = 1;

		$_POST = array(
			'user_email'         => 'test@company.it',
			'adi2_samaccountname' => 'testUser'
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

		$cn = new NextADInt_Ldap_Attribute();
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
			'meta_cn'        => 'abcd1234'
		);

		WP_Mock::wpFunction('update_user_meta', array(
			'args'  => array($userId, 'meta_cn', 'abcd1234'),
			'times' => 1)
		);

		$sut->updateWordPressProfile($userId, $data);
	}

	/**
	 * @test
	 */
	public function createLdapConnectionDetails_itReturnsServiceAccount_ifEnabled() {
		$sut = $this->sut(null);

		$this->syncToActiveDirectory->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(true);

		$this->syncToActiveDirectory->expects($this->once())
			->method('getServiceAccountUsername')
			->willReturn('serviceUsername');

		$this->syncToActiveDirectory->expects($this->once())
			->method('getServiceAccountPassword')
			->willReturn('servicePassword');

		$actual = $sut->createLdapConnectionDetails((object)array('ID' => 1));

		$this->assertEquals('serviceUsername', $actual->username);
		$this->assertEquals('servicePassword', $actual->password);
	}

	/**
	 * @test
	 */
	public function createLdapConnectionDetails_itReturnsNull_ifNoPasswordIsProvided() {
		$sut = $this->sut(array('getUsername', 'getAccountSuffix'));

		$this->syncToActiveDirectory->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(false);

		$actual = $sut->createLdapConnectionDetails((object)array('ID' => 1));
		$this->assertNull($actual);
	}

	/**
	 * @test
	 */
	public function createLdapConnectionDetails_itReturnsCustomConnection_ifPasswordIsProvided() {
		$sut = $this->sut(array('getUsername', 'getAccountSuffix'));

		$this->syncToActiveDirectory->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(false);

		$sut->expects($this->once())
			->method('getUsername')
			->with(1, 'username')
			->willReturn('new_username');

		$sut->expects($this->once())
			->method('getAccountSuffix')
			->with(1, 'username')
			->willReturn("@test.ad");

		$actual = $sut->createLdapConnectionDetails((object)array('ID' => 1, 'user_login' => 'username'), "  password  ");
		$this->assertEquals('new_username@test.ad', $actual->username);
		$this->assertEquals("password", 'password' /* trimmed */);
	}

	/**
	 * @test
	 */
	public function triggerSyncToActiveDirectory_usesTheCustomPassword()
	{
		$sut = $this->sut(array('createLdapConnectionDetails', 'synchronize'));

		$username = 'testUser';
		$wpUserdata = (object)array('user_login' => 'username');

		WP_Mock::wpFunction('get_userdata', array(
				'args'   => 1,
				'times'  => 1,
				'return' => $wpUserdata
		));

		$sut->expects($this->once())
			->method('createLdapConnectionDetails')
			->with($wpUserdata, 'password')
			->willReturn(null);

		$actual = $sut->triggerSyncToActiveDirectory(1, array(NEXT_AD_INT_PREFIX . NextADInt_Adi_User_Profile_Ui_TriggerActiveDirectorySynchronization::FORM_PASSWORD => 'password'));
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
		$ldapConnectionDetails = (object)array('username' =>'serviceUsername', 'password' => 'servicePassword');

		WP_Mock::wpFunction('get_userdata', array(
			'args'   => 666,
			'times'  => 1,
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
	public function getUsername_returnOnlyUsername()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction(
			'get_user_meta', array(
			'args'   => array(654, NEXT_AD_INT_PREFIX . 'account_suffix', true),
			'times'  => 1,
			'return' => ''
		)
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('');

		$username = $sut->getUsername(654, 'the@the-test.local');
		$this->assertEquals('the', $username);
	}

	/**
	 * @test
	 */
	public function getUsername_returnUsernameAndSuffix()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction('get_user_meta', array(
			'args'   => array(654, NEXT_AD_INT_PREFIX . 'account_suffix', true),
			'times'  => 1,
			'return' => '@the-test.local')
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('@the-test2.local');

		$username = $sut->getUsername(654, 'the@the-test.local');
		$this->assertEquals('the@the-test.local', $username);
	}

	/**
	 * @test
	 */
	public function getAccountSuffix_returnPersonalSuffix()
	{
		$sut = $this->sut(null);

		$userId = 1;
		$userName = 'testUser';

		WP_Mock::wpFunction('get_user_meta', array(
			'args'   => array($userId, NEXT_AD_INT_PREFIX . 'account_suffix', true),
			'times'  => '1',
			'return' => '@personalSuffix.it')
		);

		$returnedValue = $sut->getAccountSuffix($userId, $userName);
		$this->assertEquals('@personalSuffix.it', $returnedValue);
	}

	/**
	 * @test
	 */
	public function getAccountSuffix_returnOptionaAccountSuffix()
	{
		$sut = $this->sut(null);

		$userId = 1;
		$userName = 'testUser';
		$optionSuffix = '@option.it';

		WP_Mock::wpFunction('get_user_meta', array(
			'args'   => array($userId, NEXT_AD_INT_PREFIX . 'account_suffix', true),
			'times'  => '1',
			'return' => '')
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn($optionSuffix);

		$returnedValue = $sut->getAccountSuffix($userId, $userName);
		$this->assertEquals($optionSuffix, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getAccountSuffix_SplitSuffixFromUsername()
	{
		$sut = $this->sut(null);

		$userId = 1;
		$userName = 'testUser@splittedSuffix.it';
		$splittedSuffix = '@splittedSuffix.it';

		WP_Mock::wpFunction('get_user_meta', array(
			'args'   => array($userId, NEXT_AD_INT_PREFIX . 'account_suffix', true),
			'times'  => '1',
			'return' => '')
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('');

		$returnedValue = $sut->getAccountSuffix($userId, $userName);
		$this->assertEquals($splittedSuffix, $returnedValue);
	}

	/**
	 * @test
	 */
	public function getAccountSuffix_returnEmptyString()
	{
		$sut = $this->sut(null);

		$userId = 1;
		$userName = 'testUser';

		WP_Mock::wpFunction('get_user_meta', array(
			'args'   => array($userId, NEXT_AD_INT_PREFIX . 'account_suffix', true),
			'times'  => '1',
			'return' => '')
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ACCOUNT_SUFFIX)
			->willReturn('');

		$returnedValue = $sut->getAccountSuffix($userId, $userName);
		$this->assertEquals('', $returnedValue);
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

		$error = new WP_Error();

		$sut->generateError($error, null, null);

		$savedErrors = $error->getErrors();
		$this->assertEquals('message1', $savedErrors['code1']);
		$this->assertEquals('message2', $savedErrors['code2']);
	}
}