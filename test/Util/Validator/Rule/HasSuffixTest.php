<?php

namespace Dreitier\Util\Validator\Rule;

use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

class HasSuffixTest extends BasicTestCase
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
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(HasSuffix::class)
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE, '@',
				)
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withEmptyMessage_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('', []);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_returnMessage()
	{
		$sut = $this->sut();

		$actual = $sut->validate('Administrator', []);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('Administrator@test.ad', []);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function getMsg()
	{
		$sut = $this->sut();

		$actual = $sut->getMsg();

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}
}