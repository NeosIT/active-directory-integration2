<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_User_Profile_Ui_ShowLdapAttributesTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Multisite_View_TwigContainer|PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var NextADInt_Ldap_Attribute_Repository|PHPUnit_Framework_MockObject_MockObject */
	private $attributeRepository;

	/**
	 * @var NextADInt_Adi_Synchronization_ActiveDirectory|PHPUnit_Framework_MockObject_MockObject
	 */
	private $syncToActiveDirectory;

	/* @var Twig_Environment|PHPUnit_Framework_MockObject_MockObject */
	private $twig;


	public function setUp()
	{
		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->twigContainer = $this->createMock('NextADInt_Multisite_View_TwigContainer');
		$this->attributeRepository = $this->createMock('NextADInt_Ldap_Attribute_Repository');
		$this->syncToActiveDirectory = $this->createMock('NextADInt_Adi_Synchronization_ActiveDirectory');

		$this->twig = $this->getMockBuilder('Twig_Environment')
			->disableOriginalConstructor()
			->setMethods(array('render'))// do not replace this line with ->disableProxyingToOriginalMethods()
			->getMock();

		\WP_Mock::setUp();
	}

	public function tearDown()
	{
		\WP_Mock::tearDown();
	}

	/**
	 * @param null $methods
	 *
	 * @return NextADInt_Adi_User_Profile_Ui_ShowLdapAttributes|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_User_Profile_Ui_ShowLdapAttributes')
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->twigContainer,
					$this->attributeRepository,
					$this->syncToActiveDirectory
				)
			)
			->setMethods($methods)
			->getMock();
	}


	/**
	 * @test
	 */
	public function register_AddAction()
	{
		$sut = $this->sut(null);


		\WP_Mock::expectActionAdded('show_user_profile', array($sut, 'extendOwnProfile'));
		\WP_Mock::expectActionAdded('edit_user_profile', array($sut, 'extendForeignProfile'));

		$sut->register();
	}

	/**
	 * @test
	 */
	public function addUserAttributesToOwnProfile()
	{
		$sut = $this->sut(array('extendProfile'));
		$user = "TestUser";
		$sut->expects($this->once())
			->method('extendProfile')
			->with($user, true);

		$sut->extendOwnProfile($user);
	}

	/**
	 * @test
	 */
	public function addUserAttributesToForeignProfile()
	{
		$sut = $this->sut(array('extendProfile'));
		$user = "TestUser";
		$sut->expects($this->once())
			->method('extendProfile')
			->with($user, false);

		$sut->extendForeignProfile($user);
	}

	/**
	 * @test
	 */
	public function extendProfile_doNotShow()
	{
		$sut = $this->sut(array("createAttributeViewModel"));
		$user = (object)array(
			"ID" => 1,
			"data" => array(
			"ID" => 1,
			"user_login" => "testuser123",
			)
		);

		$attributes = array(
			"testAttribute" => array(
				"type" => "string",
				"metakey" => "wpMetaKey",
				"description" => "test",
				"syncable" => true,
				"viewable" => true,
			)
		);

		$sut->expects($this->once())
			->method("createAttributeViewModel");

		$this->attributeRepository->expects($this->once())
			->method("filterWhitelistedAttributes")
			->willReturn($attributes);

		$this->twigContainer->expects($this->once())
			->method('getTwig')
			->willReturn($this->twig);

		$sut->extendProfile($user, null);
	}

	/**
	 * @test
	 */
	public function extendProfile_rendersView()
	{
		$sut = $this->sut(array('isShowAttributesEnabled', 'createViewModel'));
		$this->mockFunction__();
		$wpUser = (object)array(
			'ID' => '123',
		);

		$data = array('data' => true);
        $i18n = array(
            'additionalInformation' => 'Additional Information provided by Next Active Directory Integration',
            'reenterPassword' => 'Reenter password',
            'youMustEnterPassword' => 'If you want to save the changes in "Additional Information" back to the Active Directory you must enter your password.',
            'canNotBeEdited' => 'Profile can not be edited or synchronized back to Active Directory:'
        );

		$sut->expects($this->once())
			->method('createViewModel')
			->willReturn($data);

		$this->twigContainer->expects($this->once())
			->method('getTwig')
			->willReturn($this->twig);

		$this->twig->expects($this->once())
			->method('render')
			->with('user-profile-ad-attributes.twig', array('renderData' => $data, 'i18n' => $i18n));


		$sut->extendProfile($wpUser, true);
	}

	/**
	 * @test
	 */
	public function createViewModel_createsData() {
		$attributes = array();
		$wpUser = (object)array(
			'ID' => '123',
		);
		$isOwnProfile = true;

		$sut = $this->sut(array('createAttributesViewModel'));

		$this->attributeRepository->expects($this->once())
			->method('filterWhitelistedAttributes')
			->with(true)
			->willReturn($attributes);

		$this->syncToActiveDirectory->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(true);

		$this->syncToActiveDirectory->expects($this->once())
			->method('isEditable')
			->with(123, $isOwnProfile)
			->willReturn(true);

		$sut->method('createAttributesViewModel')
			->with($attributes, $wpUser, true)
			->willReturn($attributes);

		$actual = $sut->createViewModel($wpUser, $isOwnProfile);

		$this->assertFalse($actual['require_password']);
		$this->assertTrue($actual['adi_is_editable']);
		$this->assertNull($actual['adi_synchronization_error_message']);
		$this->assertEquals($attributes, $actual['attributes']);
	}

	/**
	 * @test
	 */
	public function createViewModel_containsSynchronizationUnavailableErrorMessage() {
		$attributes = array();
		$wpUser = (object)array(
			'ID' => '123',
		);
		$isOwnProfile = true;

		$sut = $this->sut(array('createAttributesViewModel'));

		$this->syncToActiveDirectory->expects($this->once())
			->method('isEditable')
			->with(123, $isOwnProfile)
			->willReturn(false);

		$this->syncToActiveDirectory->expects($this->once())
			->method('assertSynchronizationAvailable')
			->with($wpUser->ID, $isOwnProfile)
			->will($this->throwException(new Exception("ERR")));

		$actual = $sut->createViewModel($wpUser, $isOwnProfile);

		$this->assertFalse($actual['adi_synchronization_available']);
		$this->assertEquals('ERR', $actual['adi_synchronization_error_message']);
	}

	/**
	 * @test
	 */
	public function createViewModel_containsRequirementForEnteringPassword() {
		$attributes = array();
		$wpUser = (object)array(
			'ID' => '123',
		);
		$isOwnProfile = true;

		$sut = $this->sut(array('createAttributesViewModel'));

		$this->syncToActiveDirectory->expects($this->once())
			->method('isEditable')
			->with(123, $isOwnProfile)
			->willReturn(true);

		$this->syncToActiveDirectory->expects($this->once())
			->method('isServiceAccountEnabled')
			->willReturn(false);

		$actual = $sut->createViewModel($wpUser, $isOwnProfile);

		$this->assertTrue($actual['require_password']);
	}

	/**
	 * @test
	 */
	public function createAttributesViewModel_delegatesToCreateAttributeViewModel() {
		$sut = $this->sut(array('createAttributeViewModel'));

		$attributes = array('mail' => new NextADInt_Ldap_Attribute());

		$sut->expects($this->once())
			->method('createAttributeViewModel')
			->with($attributes['mail'], 666, true)
			->willReturn(true);

		$actual = $sut->createAttributesViewModel($attributes, 666, true);

		$this->assertEquals($actual['mail'], true);
	}

	/**
	 * @test
	 */
	public function createAttributeViewModel_withMetakey()
	{
		$sut = $this->sut(null);

		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setMetakey('m_key');

		$user = (object)array(
			'ID' => 123
		);

		\WP_Mock::wpFunction(
			'get_user_meta', array(
			'args'   => array($user->ID, $metaObject->getMetakey(), true),
			'times'  => 1,
			'return' => '123456789'
		)
		);

		$value = $sut->createAttributeViewModel($metaObject, $user, true, true, true);
		$this->assertEquals(false, $value['noAttribute']);
		$this->assertEquals('m_key', $value['metaKey']);
		$this->assertEquals('123456789', $value['value']);
		$this->assertEquals('', $value['description']);
	}

	/**
	 * @test
	 */
	public function createAttributeViewModel_withoutMetakey()
	{
		$sut = $this->sut(null);

		$metaObject = new NextADInt_Ldap_Attribute();

		$user = (object)array(
			'ID' => 123
		);

		\WP_Mock::wpFunction(
			'get_user_meta', array(
			'times' => 0,
		)
		);

		$value = $sut->createAttributeViewModel($metaObject, $user, true, true, true);
		$this->assertEquals(true, $value['noAttribute']);
		$this->assertEquals('', $value['metaKey']);
		$this->assertEquals('', $value['value']);
	}

	/**
	 * @test
	 */
	public function createAttributeViewModel_withDescription()
	{
		$sut = $this->sut(null);

		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setMetakey('m_key');
		$metaObject->setDescription('do stuff');

		$user = (object)array(
			'ID' => 123
		);

		\WP_Mock::wpFunction(
			'get_user_meta', array(
			'args'   => array($user->ID, $metaObject->getMetakey(), true),
			'times'  => 1,
			'return' => '123456789'
		)
		);

		$value = $sut->createAttributeViewModel($metaObject, $user, true, true, true);
		$this->assertEquals('do stuff', $value['description']);
	}

	/**
	 * @test
	 */
	public function createAttributeViewModel_withoutDescription()
	{
		$sut = $this->sut(null);

		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setMetakey('m_key');

		$user = (object)array(
			'ID' => 123
		);

		\WP_Mock::wpFunction(
			'get_user_meta', array(
			'args'   => array($user->ID, $metaObject->getMetakey(), true),
			'times'  => 1,
			'return' => ''
		)
		);

		$value = $sut->createAttributeViewModel($metaObject, $user, true, true, true);
		$this->assertEquals(true, $value['noAttribute']);
		$this->assertEquals('', $value['description']);
	}

	/**
	 * @test
	 */
	public function createAttributeViewModel_withTypeList()
	{
		$sut = $this->sut(null);

		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setType('list');
		$metaObject->setSyncable(true);

		$user = (object)array(
			'ID' => 123
		);

		$value = $sut->createAttributeViewModel($metaObject, $user, true, true, false);
		$this->assertEquals('textarea', $value['outputType']);
	}

	/**
	 * @test
	 */
	public function createAttributeViewModel_withTypeString()
	{
		$sut = $this->sut(null);

		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setType('string');
		$metaObject->setSyncable(true);

		$user = (object)array(
			'ID' => 123
		);

		$value = $sut->createAttributeViewModel($metaObject, $user, true, false, true);
		$this->assertEquals('text', $value['outputType']);
	}

	/**
	 * @test
	 */
	public function createAttributeViewModel_withoutTypeList()
	{
		$sut = $this->sut(null);

		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setType('string');
		$metaObject->setSyncable(false);

		$user = (object)array(
			'ID' => 123
		);

		$value = $sut->createAttributeViewModel($metaObject, $user, true, true, true);
		$this->assertEquals('plain', $value['outputType']);
	}
}