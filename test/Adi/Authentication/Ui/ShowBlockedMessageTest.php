<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Authentication_Ui_ShowBlockedMessageTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service| PHPUnit_Framework_MockObject_MockObject */
	private $configuration;

	/* @var NextADInt_Multisite_View_TwigContainer| PHPUnit_Framework_MockObject_MockObject */
	private $twigContainer;

	/* @var Twig\Environment | PHPUnit_Framework_MockObject_MockObject */
	private $twigEnvironment;

	public function setUp() : void
	{
		parent::setUp();

		$this->configuration = $this->createMock('NextADInt_Multisite_Configuration_Service');
		$this->twigContainer = $this->createMock('NextADInt_Multisite_View_TwigContainer');
		$this->twigEnvironment = $this->createMock('Twig\Environment');
	}

	public function tearDown() : void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function blockCurrentUser_executeShowBlockMessage()
	{
		$sut = $this->sut(array('showBlockMessage'));

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->willReturn(5);

		$sut->expects($this->once())
			->method('showBlockMessage')
			->with(5);

		$sut->blockCurrentUser();
	}

	/**
	 *
	 * @return NextADInt_Adi_Authentication_Ui_ShowBlockedMessage|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Adi_Authentication_Ui_ShowBlockedMessage')
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
			->with(NextADInt_Adi_Configuration_Options::BLOCK_TIME)
			->willReturn('5');

		$sut->expects($this->once())
			->method('showBlockMessage')
			->with('5');

		$sut->blockCurrentUser();
	}

	/**
	 * @test
	 */
	public function showBlockMessage_forNormalAuthentification()
	{
		$sut = $this->sut(null);
		$this->mockFunction__();

		$this->twigContainer->expects($this->once())
			->method('getTwig')
			->willReturn($this->twigEnvironment);

		$this->twigEnvironment->expects($this->once())
			->method('render');

		WP_Mock::wpFunction('wp_die', array(
			'args'  => 'Authentication denied by Next Active Directory Integration Brute Force Protection. <br> Your account is blocked for <span id=\'secondsLeft\'>5</span> seconds.',
			'times' => '1')
		);

		$sut->showBlockMessage(5);
	}

	/**
	 * @test
	 */
	public function showBlockMessage_forXmlRpc() {
		$sut = $this->sut(null);
		$this->mockFunction__();
		$timeLeft = 5;

		$_SERVER['PHP_SELF'] = 'xmlrpc.php';

		WP_Mock::wpFunction('wp_die', array(
				'args'  => 'Authentication denied by Next Active Directory Integration Brute Force Protection. Your account is blocked for ' . $timeLeft . ' seconds.',
				'times' => '1')
		);

		$sut->showBlockMessage(5);
	}
}
