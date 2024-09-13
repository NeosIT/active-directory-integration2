<?php

namespace Dreitier\Nadi\User\Profile\Ui;

use Dreitier\Nadi\User\Manager;
use Dreitier\Nadi\Vendor\Twig\Environment;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\View\TwigContainer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class ProvideDisableUserOptionTest extends BasicTest
{
	/* @var TwigContainer| MockObject */
	private $twigContainer;

	/* @var Environment|MockObject */
	private $twig;

	/* @var Manager| MockObject */
	private $userManager;

	public function setUp(): void
	{
		parent::setUp();

		$this->twigContainer = $this->createMock(TwigContainer::class);
		$this->userManager = $this->createMock(Manager::class);
		$this->twig = $this->createMock(Environment::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return ProvideDisableUserOption| MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(ProvideDisableUserOption::class)
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

		\WP_Mock::userFunction(
			'current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
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

		\WP_Mock::userFunction(
			'current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
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
		$this->mockFunction__();

		$user = (object)array(
			'ID' => 2,
		);

		$userMeta = array(
			'email' => 'test@company.it',
			'firstName' => 'testFirstName',
			'lastName' => 'testLastName',
		);

		\WP_Mock::userFunction('current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => true,)
		);

		\WP_Mock::userFunction('get_user_meta', array(
				'args' => array(2,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_disabled_reason', true),
				'times' => 1,
				'return' => $userMeta,)
		);

		$this->twigContainer->expects($this->once())
			->method('getTwig')
			->willReturn($this->twig);

		$this->twig->expects($this->once())
			->method('render')
			->with(
				'user-profile-option.twig', array(
					'userDisabled' => true,
					'disabledReason' => $userMeta,
					'i18n' => array(
						'userDisabled' => 'User Disabled',
						'emailWillBeChanged' => 'If selected, the user can not log in and his e-mail address will be changed for security reasons. The e-mail address is restored if the user is reenabled.',
						'informationOnLastDisabling' => 'Information on last disabling: ',
						'warning' => 'Attention: This flag is automatically set (or unset) by Sync to WordPresss and its state may change on next run of synchronization.'
					)
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
		$this->mockFunction__();

		$userMessage = "User \"TestUser\" with ID \"2\" manually disabled by \"Admin\" with the ID \"1\".";

		$userId = 2;

		$_POST[NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_disabled'] = '1';
		$_POST['email'] = "";

		$this->userManager->expects($this->once())
			->method('isDisabled')
			->with($userId)
			->willReturn(false);

		$userObject = (object)array(
			'user_login' => 'TestUser',
			'user_email' => 'test@company.it',
		);

		$userObject2 = (object)array(
			'user_login' => 'Admin',
			'user_email' => 'admin@company.it',
			'ID' => 1,
		);


		\WP_Mock::userFunction('get_userdata', array(
				'args' => $userId,
				'times' => 1,
				'return' => $userObject,)
		);

		\WP_Mock::userFunction('get_user_by', array(
				'args' => array('id', $userId),
				'times' => 1,
				'return' => $userObject,)
		);

		\WP_Mock::userFunction('wp_get_current_user', array(
				'times' => 1,
				'return' => $userObject2,)
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
		$_POST[NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'user_disabled'] = '0';
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

		\WP_Mock::userFunction('get_user_by', array(
				'args' => array('id', $userId),
				'times' => 1,
				'return' => $userObject,)
		);

		$sut->saveOption($userId);
		$this->assertEquals('test@company.it', $_POST['email']);
	}
}