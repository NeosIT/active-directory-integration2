<?php

namespace Dreitier\Nadi\Authentication;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Nadi\LoginState;
use Dreitier\Test\BasicTest;
use Dreitier\WordPress\Multisite\Configuration\Service;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class PasswordValidationServiceTest extends BasicTest
{
	/* @var Service|MockObject */
	private $configuration;

	/* @var LoginState| MockObject */
	private $loginState;

	public function setUp(): void
	{
		parent::setUp();

		$this->configuration = $this->createMock(Service::class);
		$this->loginState = new LoginState();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function overridePasswordCheck_isAdmin()
	{
		$sut = $this->sut(null);
		$check = 'isAdmin';
		$password = null;
		$hash = null;
		$userId = '1';

		$returnedValue = $sut->overridePasswordCheck($check, $password, $hash, $userId);

		$this->assertTrue(is_string($returnedValue));
		$this->assertEquals('isAdmin', $check);
	}

	/**
	 * @return PasswordValidationService| MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(PasswordValidationService::class)
			->setConstructorArgs(
				array(
					$this->loginState,
					$this->configuration
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function overridePasswordCheck_isAuthorized()
	{
		$sut = $this->sut(null);
		$userId = '2';
		$this->loginState->setAuthenticationSucceeded();

		$returnedValue = $sut->overridePasswordCheck(null, null, null, $userId);

		$this->assertEquals(true, $returnedValue);
	}

	/**
	 * @test
	 */
	public function overridePasswordCheck_localPasswordCheckFallbackActivated()
	{
		$sut = $this->sut(null);
		$userId = '2';
		$check = true;

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($userId, NEXT_AD_INT_PREFIX . 'samaccountname', true),
				'times' => '1',
				'return' => true)
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::FALLBACK_TO_LOCAL_PASSWORD)
			->willReturn(true);

		$returnedValue = $sut->overridePasswordCheck($check, null, null, $userId);
		$this->assertTrue($returnedValue);

	}

	/**
	 * @test
	 */
	public function overridePasswordCheck_localPasswordCheckFallbackDeactivated()
	{
		$sut = $this->sut(null);
		$userId = '2';

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($userId, NEXT_AD_INT_PREFIX . 'samaccountname', true),
				'times' => '1',
				'return' => true)
		);

		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(Options::FALLBACK_TO_LOCAL_PASSWORD)
			->willReturn(false);

		$returnedValue = $sut->overridePasswordCheck(null, null, null, $userId);
		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function overridePasswordCheck_LocalPasswordCheck()
	{
		$sut = $this->sut(null);
		$userId = '2';
		$check = true;

		\WP_Mock::wpFunction('get_user_meta', array(
				'args' => array($userId, NEXT_AD_INT_PREFIX . 'samaccountname', true),
				'times' => '1',
				'return' => false)
		);

		$returnedValue = $sut->overridePasswordCheck($check, null, null, $userId);

		$this->assertTrue($returnedValue);
	}
}