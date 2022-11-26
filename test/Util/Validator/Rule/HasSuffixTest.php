<?php

namespace Dreitier\Util\Validator\Rule;

use Dreitier\Test\BasicTest;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

class HasSuffixTest extends BasicTest
{
	const VALIDATION_MESSAGE = 'Username has to contain a suffix.';

	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return HasSuffix|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(HasSuffix::class)
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE, '@',
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withEmptyMessage_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('', array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_returnMessage()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate('Administrator', array());

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate('Administrator@test.ad', array());

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function getMsg()
	{
		$sut = $this->sut(null);

		$actual = $sut->getMsg();

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}
}