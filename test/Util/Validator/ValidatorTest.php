<?php

namespace Dreitier\Util\Validator;

use Dreitier\Test\BasicTestCase;
use Dreitier\Util\Validator\Rule\HasSuffix;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class ValidatorTest extends BasicTestCase
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
	 *
	 * @return Validator| MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(Validator::class)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_returnResult()
	{
		$expected = new Result();
		$expected->addValidationResult(
			'sync_to_ad_global_user', array('error' => 'Username has to contain a suffix.')
		);

		$suffixRule = new HasSuffix(
			'Username has to contain a suffix.', '@'
		);

		$data = array(
			'sync_to_ad_global_user' => array(
				'option_value' => 'Administrator',
			)
		);

		$sut = $this->sut();
		$sut->addRule('sync_to_ad_global_user', $suffixRule);

		$actual = $sut->validate($data);

		$this->assertEquals($expected, $actual);
	}
}
