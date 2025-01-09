<?php

namespace Dreitier\Util\Session;

use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Internal\Native;
use Dreitier\Util\Util;
use PHPUnit\Framework\MockObject\MockObject;
use WPChill\DownloadMonitor\Shop\Session\Session;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 */
class SessionHandlerTest extends BasicTestCase
{

	/* @var Native|\Mockery\MockInterface */
	private $internalNative;

	public function setUp(): void
	{
		parent::setUp();

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		Util::native($this->internalNative);
	}

	public function tearDown(): void
	{
		parent::tearDown();
		// release mocked native functions
		Util::native(null);
	}

	/**
	 * @return SessionHandler|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(SessionHandler::class)
			->disableOriginalConstructor()
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getInstance_startSession()
	{
		$sut = $this->sut();

		$this->internalNative->expects($this->once())
			->method('getSessionId')
			->willReturn('');

		$this->internalNative->expects($this->once())
			->method('startSession');

		$sut->getInstance(null);
	}

	/**
	 * @test
	 */
	public function getInstance_dontStartSession()
	{
		$sut = $this->sut();

		$this->internalNative->expects($this->once())
			->method('getSessionId')
			->willReturn(1);

		$this->internalNative->expects($this->never())
			->method('startSession');

		$sut->getInstance(null);
	}

	/**
	 * @test
	 */
	public function setValue_withKeyAndValue()
	{
		$key = 'testKey';
		$value = 'testValue';

		$sut = $this->sut(array('normalizeKey'));

		$sut->expects($this->once())
			->method('normalizeKey')
			->with($key)
			->willReturn($key);

		$sut->setValue($key, $value);

		$this->assertEquals($_SESSION[$key], $value);
	}

	/**
	 * @test
	 */
	public function getValue_withKey_returnValue()
	{
		$key = 'testKey';
		$_SESSION[$key] = 'value';

		$sut = $this->sut(array('normalizeKey'));

		$sut->expects($this->once())
			->method('normalizeKey')
			->with($key)
			->willReturn($key);

		$actual = $sut->getValue($key);

		$this->assertEquals('value', $actual);
	}

	/**
	 * @test
	 */
	public function getValue_withKey_returnDefault()
	{
		$key = 'testKey';

		$sut = $this->sut(array('normalizeKey'));

		$sut->expects($this->once())
			->method('normalizeKey')
			->with($key)
			->willReturn($key);

		$actual = $sut->getValue($key, 'defaultValue');

		$this->assertEquals('defaultValue', $actual);
	}

	/**
	 * @test
	 */
	public function clearValue_withKey_unsetKey()
	{
		$key = 'testKey';
		$_SESSION[NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . $key] = 'value';

		$sut = $this->sut();

		$sut->clearValue($key);

		$this->assertTrue(!isset($_SESSION[NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . $key]));
	}
}
