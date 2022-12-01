<?php

namespace Dreitier\Nadi\User\Profile\Ui;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class PreventEmailChangeTest extends BasicTest
{
	/* @var Service| MockObject */
	private $configuration;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return PreventEmailChange| MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(PreventEmailChange::class)
			->setConstructorArgs(
				array(
					$this->configuration
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_returnBecauseForbidden()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::PREVENT_EMAIL_CHANGE)
			->willReturn(false);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function register_addActions()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::PREVENT_EMAIL_CHANGE)
			->willReturn(true);

		\WP_Mock::expectActionAdded('show_user_profile', array($sut, 'disableEmailField'));
		\WP_Mock::expectActionAdded('personal_options_update', array($sut, 'addMissingEmailAddressToPOST'));
		\WP_Mock::expectActionAdded('user_profile_update_errors', array($sut, 'preventEmailChange'), 0, 3);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function disableEmailField_echoExecuted()
	{
		$sut = $this->sut(null);

		$user = (object)array(
			'ID' => 1
		);
		$samaccountname = "testName";

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true),
				'times' => 1,
				'return' => $samaccountname)
		);

		\WP_Mock::wpFunction('current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => false)
		);

		// suppress output
		ob_start();
		$sut->disableEmailField($user);
		ob_end_clean();
	}

	/**
	 * @test
	 */
	public function disableEmailField_echoNotExecuted()
	{
		$sut = $this->sut(null);

		$user = (object)array(
			'ID' => 1
		);
		$samaccountname = "testName";

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true),
				'times' => 1,
				'return' => $samaccountname)
		);

		\WP_Mock::wpFunction('current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => true)
		);

		$sut->disableEmailField($user);
	}

	/**
	 * @test
	 */

	public function preventEmailChange()
	{
		$sut = $this->sut(null);

		$user = (object)array(
			'ID' => 1,
			'user_email' => 'test@company.it',
			'user_login' => 'testUsername'
		);
		$samaccountname = "testName";

		$_POST[NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'email_change'] = "someValue";

		\WP_Mock::expectFilterAdded('send_password_change_email', '__return_false');
		\WP_Mock::expectFilterAdded('send_email_change_email', '__return_false');

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true),
				'times' => 1,
				'return' => $samaccountname)
		);

		\WP_Mock::wpFunction('current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => false)
		);

		\WP_Mock::wpFunction('get_user_by', array(
				'args' => array('id', $user->ID,),
				'times' => 1,
				'return' => $user)
		);

		\WP_Mock::wpFunction('delete_option', array(
				'args' => array($user->ID . '_new_email'),
				'times' => 1,)
		);

		$errors = (object)array();

		$sut->preventEmailChange($errors, null, $user);
		$this->assertEquals('test@company.it', $_POST['email']);
		$this->assertEquals('test@company.it', $_REQUEST['email']);
	}

	/**
	 * @test
	 * @issue ADI-670
	 */
	public function ADI_670_disablePreventEmailChange_ifUserParameterIsMissing()
	{
		$errors = (object)array();
		$user = (object)array('missing_ID' => -1);

		$sut = $this->sut(null);

		\WP_Mock::expectFilterNotAdded('send_password_change_email', '__return_false');

		$sut->preventEmailChange($errors, null, $user);
	}

	/**
	 * @test
	 */
	public function addMissingEmailAddressToPOST_ReturnBecauseAdmin()
	{
		$sut = $this->sut(null);

		$userId = 1;
		$samaccountname = "testUser";

		$user = (object)array(
			'ID' => 1,
			'user_email' => 'test@company.it',
			'user_login' => 'testUsername'
		);

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true),
				'times' => 1,
				'return' => $samaccountname)
		);

		\WP_Mock::wpFunction('current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => true)
		);

		$sut->addMissingEmailAddressToPOST($userId);
	}

	/**
	 * @test
	 */
	public function addMissingEmailAddressToPOST_ReturnBecauseNoSamaccountname()
	{
		$sut = $this->sut(null);

		$userId = 1;

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true),
				'times' => 1,
				'return' => '')
		);

		\WP_Mock::wpFunction('current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => false)
		);

		$sut->addMissingEmailAddressToPOST($userId);


	}

	/**
	 * @test
	 */
	public function addMissingEmailAddressToPost()
	{
		$sut = $this->sut(null);

		$user = (object)array(
			'ID' => 1,
			'user_email' => 'test@company.it',
			'user_login' => 'testUsername'
		);

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($user->ID,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'samaccountname', true),
				'times' => 1,
				'return' => 'TestUser')
		);

		\WP_Mock::wpFunction('current_user_can', array(
				'args' => 'manage_options',
				'times' => 1,
				'return' => false)
		);

		\WP_Mock::wpFunction('get_user_by', array(
				'args' => array('id', $user->ID),
				'times' => 1,
				'return' => $user)
		);

		$sut->addMissingEmailAddressToPOST($user->ID);

		$this->assertEquals($user->user_email, $_POST['email']);
		$this->assertEquals($user->user_email, $_REQUEST['email']);
	}
}