<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Adi_Authentication_SsoTest extends Ut_BasicTest
{
	/* @var Multisite_Configuration_Service| PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	public function setUp()
	{
		$this->configuration = $this->getMockBuilder('Multisite_Configuration_Service')
			->disableOriginalConstructor()
			->setMethods(array('getOptionValue'))
			->getMock();

		\WP_Mock::setUp();
	}

	public function tearDown()
	{
		\Wp_Mock::tearDown();
	}

	/**
	 * @test
	 */
	public function autoLogin_callLoginForm()
	{
		$sut = $this->sut(array('loginForm'));

		WP_Mock::wpFunction(
			'is_user_logged_in', array(
			'times'  => 1,
			'return' => false
		)
		);

		$sut->expects($this->once())
			->method('loginForm');

		$sut->autoLogin();
	}

	/**
	 *
	 * @return Adi_Authentication_Sso| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Authentication_Sso')
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
	public function register()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::AUTO_LOGIN)
			->willReturn(true);

		\WP_Mock::expectActionAdded('init', array($sut, 'autoLogin'));
		\WP_Mock::expectActionAdded('login_form', array($sut, 'loginForm'));

		$sut->register();
	}
}