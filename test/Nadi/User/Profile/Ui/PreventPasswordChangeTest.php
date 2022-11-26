<?php

namespace Dreitier\Nadi\User\Profile\Ui;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\User\Manager;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;
use WP_Mock;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class PreventPasswordChangeTest extends BasicTest
{
	/* @var Service | MockObject */
	private $configuration;

	/* @var Manager | MockObject */
	private $userManager;

	public function setUp(): void
	{
		$this->configuration = $this->createMock(Service::class);
		$this->userManager = $this->createMock(Manager::class);

		WP_Mock::setUp();
	}

	public function tearDown(): void
	{
		WP_Mock::tearDown();
	}

	/* @return PreventPasswordChange| MockObject */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(PreventPasswordChange::class)
			->setConstructorArgs(
				array(
					$this->configuration,
					$this->userManager
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function register_localPasswordChangeNotAllowed()
	{
		$sut = $this->sut(null);

		\WP_Mock::expectFilterAdded('show_password_fields', array($sut, 'showPasswordFields'), 10, 2);

		$sut->register();
	}

	/**
	 * @test
	 */
	public function isPasswordChangeEnabled_delegatesToconfiguration()
	{
		$sut = $this->sut(null);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::ENABLE_PASSWORD_CHANGE)
			->willReturn(true);

		$this->assertTrue($sut->isPasswordChangeEnabled());
	}

	/**
	 * @test
	 */
	public function showPasswordFields_usesParentSetting_ifNoActiveDirectoryAccountIsGiven()
	{
		$sut = $this->sut(null);

		$wpUser = (object)array('ID' => 666);

		$this->userManager->expects($this->once())
			->method('hasActiveDirectoryAccount')
			->with($wpUser)
			->willReturn(false);

		$this->assertTrue($sut->showPasswordFields(true, $wpUser));
	}

	/**
	 * @test
	 */
	public function showPasswordFields_itReturnsAdiSetting_ifActiveDirectoryAccountIsGiven()
	{
		$sut = $this->sut(array('isPasswordChangeEnabled'));

		$wpUser = (object)array('ID' => 666);

		$this->userManager->expects($this->once())
			->method('hasActiveDirectoryAccount')
			->with($wpUser)
			->willReturn(true);

		$sut->expects($this->once())
			->method('isPasswordChangeEnabled')
			->willReturn(true);

		$this->assertTrue($sut->showPasswordFields(false, $wpUser));
	}
}

