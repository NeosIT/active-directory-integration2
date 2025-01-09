<?php

namespace Dreitier\Nadi\User\Profile\Ui;

use Dreitier\Ldap\Attribute\Attribute;
use Dreitier\Ldap\Attribute\Repository;
use Dreitier\Nadi\Synchronization\ActiveDirectorySynchronizationService;
use Dreitier\Nadi\Vendor\Twig\Environment;
use Dreitier\Test\BasicTestCase;
use Dreitier\WordPress\Multisite\Configuration\Service;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class ShowLdapAttributesTest extends BasicTestCase
{
	/* @var Service|MockObject */
	private $configuration;

	/* @var TwigContainer|MockObject */
	private $twigContainer;

	/* @var Repository|MockObject */
	private $attributeRepository;

	/**
	 * @var ActiveDirectorySynchronizationService|MockObject
	 */
	private $syncToActiveDirectory;

	/* @var Environment|MockObject */
	private $twig;


	public function setUp(): void
	{
		$this->configuration = $this->createMock(Service::class);
		$this->twigContainer = $this->createMock(TwigContainer::class);
		$this->attributeRepository = $this->createMock(Repository::class);
		$this->syncToActiveDirectory = $this->createMock(ActiveDirectorySynchronizationService::class);

		$this->twig = $this->getMockBuilder(Environment::class)
			->disableOriginalConstructor()
			->onlyMethods(array('render'))// do not replace this line with ->disableProxyingToOriginalMethods()
			->getMock();

		\WP_Mock::setUp();
	}

	public function tearDown(): void
	{
		\WP_Mock::tearDown();
	}

	/**
	 * @param null $methods
	 *
	 * @return ShowLdapAttributes|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(ShowLdapAttributes::class)
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->twigContainer,
					$this->attributeRepository,
					$this->syncToActiveDirectory
				)
			)
			->onlyMethods($methods)
			->getMock();
	}


	/**
	 * @test
	 */
	public function register_AddAction()
	{
		$sut = $this->sut();


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
	public function createViewModel_createsData()
	{
		$attributes = [];
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
	public function createViewModel_containsSynchronizationUnavailableErrorMessage()
	{
		$attributes = [];
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
			->will($this->throwException(new \Exception("ERR")));

		$actual = $sut->createViewModel($wpUser, $isOwnProfile);

		$this->assertFalse($actual['adi_synchronization_available']);
		$this->assertEquals('ERR', $actual['adi_synchronization_error_message']);
	}

	/**
	 * @test
	 */
	public function createViewModel_containsRequirementForEnteringPassword()
	{
		$attributes = [];
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
	public function createAttributesViewModel_delegatesToCreateAttributeViewModel()
	{
		$sut = $this->sut(array('createAttributeViewModel'));

		$attributes = array('mail' => new Attribute());

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
		$sut = $this->sut();

		$metaObject = new Attribute();
		$metaObject->setMetakey('m_key');

		$user = (object)array(
			'ID' => 123
		);

		\WP_Mock::userFunction(
			'get_user_meta', array(
				'args' => array($user->ID, $metaObject->getMetakey(), true),
				'times' => 1,
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
		$sut = $this->sut();

		$metaObject = new Attribute();

		$user = (object)array(
			'ID' => 123
		);

		\WP_Mock::userFunction(
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
		$sut = $this->sut();

		$metaObject = new Attribute();
		$metaObject->setMetakey('m_key');
		$metaObject->setDescription('do stuff');

		$user = (object)array(
			'ID' => 123
		);

		\WP_Mock::userFunction(
			'get_user_meta', array(
				'args' => array($user->ID, $metaObject->getMetakey(), true),
				'times' => 1,
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
		$sut = $this->sut();

		$metaObject = new Attribute();
		$metaObject->setMetakey('m_key');

		$user = (object)array(
			'ID' => 123
		);

		\WP_Mock::userFunction(
			'get_user_meta', array(
				'args' => array($user->ID, $metaObject->getMetakey(), true),
				'times' => 1,
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
		$sut = $this->sut();

		$metaObject = new Attribute();
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
		$sut = $this->sut();

		$metaObject = new Attribute();
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
		$sut = $this->sut();

		$metaObject = new Attribute();
		$metaObject->setType('string');
		$metaObject->setSyncable(false);

		$user = (object)array(
			'ID' => 123
		);

		$value = $sut->createAttributeViewModel($metaObject, $user, true, true, true);
		$this->assertEquals('plain', $value['outputType']);
	}
}