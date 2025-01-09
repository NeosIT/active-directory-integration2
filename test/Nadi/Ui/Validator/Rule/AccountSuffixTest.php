<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

class AccountSuffixTest extends BasicTestCase
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
	 * @return AccountSuffix|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(AccountSuffix::class)
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
	public function validate_withSingleEmailConflict_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate('test@test.ad', []);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withSingleEmail_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('@test.ad', []);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withEmailListConflict_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate('@test;test@test.ad', []);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withEmailList_returntrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('@test.ad;@test2.ad;@test3.ad', []);

		$this->assertTrue($actual);
	}
}