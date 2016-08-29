<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 */
class Ut_NextADInt_Core_Session_HandlerTest extends Ut_BasicTest
{

	/* @var NextADInt_Core_Util_Internal_Native|\Mockery\MockInterface */
	private $internalNative;

	public function setUp()
	{
		parent::setUp();

		// mock native functions
		$this->internalNative = $this->createMockedNative();
		NextADInt_Core_Util::native($this->internalNative);
	}

	public function tearDown()
	{
		parent::tearDown();
		// release mocked native functions
		NextADInt_Core_Util::native(null);
	}

	/**
	 * @return NextADInt_Core_Session_Handler| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Core_Session_Handler')
			->disableOriginalConstructor()
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getInstance_startSession()
	{
		$sut = $this->sut(null);

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
		$sut = $this->sut(null);

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
		$_SESSION[ADI_PREFIX . $key] = 'value';

		$sut = $this->sut(null);

		$sut->clearValue($key);

		$this->assertTrue(!isset($_SESSION[ADI_PREFIX . $key]));
	}
}
