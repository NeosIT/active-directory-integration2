<?php

namespace Dreitier\Util\Validator;

use Dreitier\Test\BasicTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class ResultTest extends BasicTest
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @return Result| MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder(Result::class)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function isValid_returnFalse()
	{
		$sut = $this->sut(null);

		$sut->addValidationResult(
			'sync_to_ad_global_user', 'Username has to contain a suffix.'
		);

		$actual = $sut->isValid();

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function isValid_returnTrue()
	{
		$sut = $this->sut(null);

		$actual = $sut->isValid();

		$this->assertTrue($actual);
	}
}
