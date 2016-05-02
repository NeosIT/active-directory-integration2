<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Adi_Authentication_Ui_ShowBlockedMessageTest extends Ut_BasicTest
{
	/* @var Multisite_Configuration_Service| PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var Multisite_View_TwigContainer| PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var Twig_Environment| PHPUnit_Framework_MockObject_MockObject */
	private $twigEnvironment;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->createMock('Multisite_Configuration_Service');
		$this->twigContainer = $this->createMock('Multisite_View_TwigContainer');
		$this->twigEnvironment = $this->createMock('Twig_Environment');
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function blockCurrentUser_die()
	{
		$sut = $this->sut(array('showBlockMessage'));

		\WP_Mock::wpFunction('wp_die', array(
			'times' => 1)
		);

		$sut->blockCurrentUser();
	}

	/**
	 *
	 * @return Adi_Authentication_Ui_ShowBlockedMessage|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('Adi_Authentication_Ui_ShowBlockedMessage')
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->twigContainer
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function blockCurrentUser_triggerShowBlockMessage()
	{
		$sut = $this->sut(array('showBlockMessage'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Adi_Configuration_Options::BLOCK_TIME)
			->willReturn('5');

		$sut->expects($this->once())
			->method('showBlockMessage')
			->with('5');

		$sut->blockCurrentUser();
	}

	/**
	 * @test
	 */
	public function showBlockMessage()
	{
		$sut = $this->sut(null);

		$this->twigContainer->expects($this->once())
			->method('getTwig')
			->willReturn($this->twigEnvironment);

		$this->twigEnvironment->expects($this->once())
			->method('render');

		\WP_Mock::wpFunction('__', array(
			'return_arg' => 0)
		);

		WP_Mock::wpFunction('wp_die', array(
			'args'  => 'Your account is blocked for <span id=\'secondsLeft\'>5</span> seconds.',
			'times' => '1')
		);

		$sut->showBlockMessage(5);
	}
}
