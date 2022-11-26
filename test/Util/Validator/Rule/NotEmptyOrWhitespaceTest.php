<?php

namespace Dreitier\Util\Validator\Rule;


use Dreitier\Test\BasicTest;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NotEmptyOrWhitespaceTest extends BasicTest
{
	const VALIDATION_MESSAGE = 'Validation failed.';

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
	 * @param $msg string
	 *
	 * @return NotEmptyOrWhitespace|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(NotEmptyOrWhitespace::class)
			->setConstructorArgs(
				array(
					self::VALIDATION_MESSAGE,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_withEmptyString_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withWhitespaceOnly_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"   ",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withWhitespacesAndLetters_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			" test ",
			null
		);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withLettersOnly_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"test",
			null
		);

		$this->assertTrue($actual);
	}
}