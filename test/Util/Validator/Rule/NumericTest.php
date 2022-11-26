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
class NumericTest extends BasicTest
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
	 * @return Numeric|MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(Numeric::class)
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
	public function validate_withString_returnString()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			"test",
			null
		);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withNumeric_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->validate(
			123,
			null
		);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isNegative_withNegativeNumeric_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isNegative(-123);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isNegative_withPositiveNumeric_returnFalse()
	{
		$sut = $this->sut(null);

		$actual = $sut->isNegative(123);

		$this->assertFalse($actual);
	}


	/**
	 * @test
	 */
	public function isPositive_withPositiveNumeric_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isPositive(123);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isPositive_withNegativeNumeric_returnFalse()
	{
		$sut = $this->sut(null);

		$actual = $sut->isPositive(-123);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isFloat_withFloatNumeric_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isFloat(123.4);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isFloat_withoutFloatNumeric_returnFalse()
	{
		$sut = $this->sut(null);

		$actual = $sut->isFloat(123);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isZero_withZero_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isZero(0);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function isZero_withNumeric_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isZero(123);

		$this->assertFalse($actual);
	}
}