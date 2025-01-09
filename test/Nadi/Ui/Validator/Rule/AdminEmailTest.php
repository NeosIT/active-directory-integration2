<?php
namespace Dreitier\Nadi\Ui\Validator\Rule;

use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Message\Type;
use PHPUnit\Framework\MockObject\MockObject;

class AdminEmailTest extends BasicTestCase
{
	const VALIDATION_MESSAGE = 'Username has to contain a suffix.';

	public function setUp() : void
	{
		parent::setUp();
	}

	public function tearDown() : void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return AdminEmail|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(AdminEmail::class)
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

		$actual = $sut->validate('@test.ad', []);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withSingleEmail_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('test@test.ad', []);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function validate_withEmailListConflict_returnString()
	{
		$sut = $this->sut();

		$actual = $sut->validate('test@test.ad;test2@;test3@test.ad', []);

		$this->assertEquals(array(Type::ERROR => self::VALIDATION_MESSAGE), $actual);
	}

	/**
	 * @test
	 */
	public function validate_withEmailList_returnTrue()
	{
		$sut = $this->sut();

		$actual = $sut->validate('test@test.ad;test2@test.ad;test3@test.ad', []);

		$this->assertTrue($actual);
	}
}