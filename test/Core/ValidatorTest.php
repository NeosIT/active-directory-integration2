<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Core_ValidatorTest extends Ut_BasicTest
{

	public function setUp()
	{
		parent::setUp();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 *
	 * @return NextADInt_Core_Validator| PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Core_Validator')
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function validate_returnResult()
	{
		$expected = new NextADInt_Core_Validator_Result();
		$expected->addValidationResult(
			'sync_to_ad_global_user', 'Username has to contain a suffix.'
		);

		$suffixRule = new NextADInt_Multisite_Validator_Rule_Suffix(
			'Username has to contain a suffix.', '@'
		);

		$data = array(
			'sync_to_ad_global_user' => array(
				'option_value' => 'Administrator',
			)
		);

		$sut = $this->sut(null);
		$sut->addRule('sync_to_ad_global_user', $suffixRule);

		$actual = $sut->validate($data);

		$this->assertEquals($expected, $actual);
	}
}
