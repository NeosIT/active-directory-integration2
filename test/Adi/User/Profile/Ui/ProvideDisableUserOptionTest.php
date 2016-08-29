<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOptionTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_View_TwigContainer| PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var Twig_Environment|PHPUnit_Framework_MockObject_MockObject */
	private $twig;

	/* @var NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption| PHPUnit_Framework_MockObject_MockObject */
	private $userManager;

	public function setUp()
	{
		parent::setUp();

		$this->twigContainer = parent::createMock('NextADInt_Multisite_View_TwigContainer');
		$this->userManager = parent::createMock('NextADInt_Adi_User_Manager');
		$this->twig = parent::createMock('Twig_Environment');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_User_Profile_Ui_ProvideDisableUserOption')
			->setConstructorArgs(
				array(
					$this->twigContainer,
					$this->userManager,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register()
	{
		$sut = $this->sut(array('addOption', 'persistSanitized'));

		\WP_Mock::expectActionAdded('edit_user_profile', array($sut, 'addOption'));
		\WP_Mock::expectActionAdded('edit_user_profile_update', array($sut, 'saveOption'), 100, 1);

		$sut->register();
	}


	/**
	 * @test
	 */
	public function addOption_userHasNotPermission()
	{
		$sut = $this->sut(null);

		WP_Mock::wpFunction(
			'current_user_can', array(
				'args'   => 'manage_options',
				'times'  => 1,
				'return' => false,
			)
		);

		$sut->addOption(null);
	}

	/**
	 * @test
	 */
	public function addOption_returnBecauseAdmin()
	{
		$sut = $this->sut(null);

		$user = (object)array(
			'ID' => 1,
		);

		WP_Mock::wpFunction(
			'current_user_can', array(
				'args'   => 'manage_options',
				'times'  => 1,
				'return' => true,
			)
		);

		$sut->addOption($user);
	}


	/**
	 * @test
	 */
	public function addOption_disableUserShowMessage()
	{
		$sut = $this->sut(null);

		$user = (object)array(
			'ID' => 2,
		);

		$userMeta = array(
			'email'     => 'test@company.it',
			'firstName' => 'testFirstName',
			'lastName'  => 'testLastName',
		);


		WP_Mock::wpFunction(
			'current_user_can', array(
				'args'   => 'manage_options',
				'times'  => 1,
				'return' => true,
			)
		);

		WP_Mock::wpFunction(
			'get_user_meta', array(
				'args'   => array(2, NEXT_AD_INT_PREFIX . 'user_disabled_reason', true),
				'times'  => 1,
				'return' => $userMeta,
			)
		);

		$this->twigContainer->expects($this->once())
			->method('getTwig')
			->willReturn($this->twig);

		$this->twig->expects($this->once())
			->method('render')
			->with(
				'user-profile-option.twig', array(
					'userDisabled'   => true,
					'disabledReason' => $userMeta,
				)
			);

		$this->userManager->expects($this->once())
			->method('isDisabled')
			->with(2)
			->willReturn(true);

		$sut->addOption($user);
	}

	/**
	 * @test
	 */
	public function saveOption_blockUser()
	{
		$sut = $this->sut(null);

		$userMessage = "User manually disabled by \"TestUser\" with the ID 2.";

		$userId = 2;

		$_POST[NEXT_AD_INT_PREFIX . 'user_disabled'] = '1';
		$_POST['email'] = "";

		$this->userManager->expects($this->once())
			->method('isDisabled')
			->with($userId)
			->willReturn(false);

		$userObject = (object)array(
			'user_login' => 'TestUser',
			'user_email' => 'test@company.it',
		);


		\WP_Mock::wpFunction(
			'get_userdata', array(
				'args'   => $userId,
				'times'  => 1,
				'return' => $userObject,
			)
		);

		\WP_Mock::wpFunction(
			'get_user_by', array(
				'args'   => array('id', $userId),
				'times'  => 1,
				'return' => $userObject,
			)
		);


		$this->userManager->expects($this->once())
			->method('disable')
			->with($userId, $userMessage);

		$sut->saveOption($userId);
		$this->assertEquals('test@company.it', $_POST['email']);
	}

	/**
	 * @test
	 */
	public function saveOption_unblockUser()
	{
		$sut = $this->sut(null);

		$userId = 2;
		$_POST[NEXT_AD_INT_PREFIX . 'user_disabled'] = '0';
		$_POST['email'] = "";

		$userObject = (object)array(
			'user_login' => 'TestUser',
			'user_email' => 'test@company.it',
		);

		$this->userManager->expects($this->once())
			->method('isDisabled')
			->with($userId)
			->willReturn(true);

		$this->userManager->expects($this->once())
			->method('enable')
			->with($userId);

		\WP_Mock::wpFunction(
			'get_user_by', array(
				'args'   => array('id', $userId),
				'times'  => 1,
				'return' => $userObject,
			)
		);

		$sut->saveOption($userId);
		$this->assertEquals('test@company.it', $_POST['email']);
	}
}