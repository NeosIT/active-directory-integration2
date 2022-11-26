<?php

namespace Dreitier\WordPress\Multisite\Option;

use Dreitier\Test\BasicTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class SanitizerTest extends BasicTest
{
	public function setUp(): void
	{
		\WP_Mock::setUp();
	}

	public function tearDown(): void
	{
		\WP_Mock::tearDown();
	}

	/**
	 * @test
	 */
	public function sanitize_boolean_checkString()
	{
		$sut = $this->sut(null);

		$params = array(
			'boolean' // name of the call-method
		);

		$this->assertTrue($sut->sanitize('TrUe', $params, array()));
		$this->assertTrue($sut->sanitize('0.0', $params, array()));
		$this->assertTrue($sut->sanitize('1', $params, array()));
		$this->assertTrue($sut->sanitize('stuff', $params, array()));
		$this->assertTrue($sut->sanitize(' ', $params, array()));

		$this->assertFalse($sut->sanitize('fAlSe', $params, array()));
		$this->assertFalse($sut->sanitize('0', $params, array()));
		$this->assertFalse($sut->sanitize('', $params, array()));
	}

	/**
	 * @param $methods
	 *
	 * @return Sanitizer|MockObject
	 */
	public function sut($methods)
	{
		return $connection = $this->getMockBuilder(Sanitizer::class)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function sanitize_boolean_checkNumber()
	{
		$sut = $this->sut(null);

		$params = array(
			'boolean' // name of the call-method
		);

		$this->assertTrue($sut->sanitize(1, $params, array()));
		$this->assertFalse($sut->sanitize(0, $params, array()));
	}

	/**
	 * @test
	 */
	public function sanitize_boolean_checkSpecialValues()
	{
		$sut = $this->sut(null);

		$params = array(
			'boolean' // name of the call-method
		);

		$this->assertTrue($sut->sanitize(true, $params, array()));
		$this->assertFalse($sut->sanitize(false, $params, array()));
		$this->assertFalse($sut->sanitize(null, $params, array()));
	}

	/**
	 * @test
	 */
	public function sanitize_integer()
	{
		$sut = $this->sut(null);

		$params = array(
			'integer' // name of the call-method
		);
		$metadata = array(
			Attribute::DEFAULT_VALUE => 666
		);

		$this->assertEquals(0, $sut->sanitize('0', $params, $metadata));
		$this->assertEquals(0, $sut->sanitize('0.1', $params, $metadata));
		$this->assertEquals(1, $sut->sanitize('1', $params, $metadata));
		$this->assertEquals(314, $sut->sanitize('314.159', $params, $metadata));

		$this->assertEquals(0, $sut->sanitize(0, $params, $metadata));
		$this->assertEquals(66, $sut->sanitize(66, $params, $metadata));
		$this->assertEquals(34, $sut->sanitize(34.9978, $params, $metadata));

		$this->assertEquals(666, $sut->sanitize(null, $params, $metadata));
		$this->assertEquals(666, $sut->sanitize("no number", $params, $metadata));
		$this->assertEquals(666, $sut->sanitize(true, $params, $metadata));
		$this->assertEquals(666, $sut->sanitize(false, $params, $metadata));
		$this->assertEquals(666, $sut->sanitize(array(), $params, $metadata));
		$this->assertEquals(666, $sut->sanitize(array('a' => 'b'), $params, $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_email_withCorrectEmail()
	{
		$sut = $this->sut(null);

		$params = array(
			'email' // name of the call-method
		);
		$metadata = array(
			Attribute::DEFAULT_VALUE => 'aa@bb.com'
		);

		\WP_Mock::wpFunction(
			'sanitize_email', array(
				'args' => '   a@b.de! ',
				'times' => 1,
				'return' => 'a@b.de'
			)
		);

		\WP_Mock::wpFunction(
			'is_email', array(
				'args' => 'a@b.de',
				'times' => 1,
				'return' => 'a@b.de'
			)
		);

		$this->assertEquals('a@b.de', $sut->sanitize('   a@b.de! ', $params, $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_email_withInvalidEmailAndDefaultValue()
	{
		$sut = $this->sut(null);

		$params = array(
			'email' // name of the call-method
		);
		$metadata = array(
			Attribute::DEFAULT_VALUE => 'aa@bb.com'
		);

		\WP_Mock::wpFunction(
			'sanitize_email', array(
				'args' => '   ab.de! ',
				'times' => 1,
				'return' => 'ab.de'
			)
		);

		\WP_Mock::wpFunction(
			'is_email', array(
				'args' => 'ab.de',
				'times' => 1,
				'return' => false
			)
		);

		$this->assertEquals('aa@bb.com', $sut->sanitize('   ab.de! ', $params, $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_email_withInvalidEmailAndNoDefaultValue()
	{
		$sut = $this->sut(null);

		$params = array(
			'email' // name of the call-method
		);
		$metadata = array();

		\WP_Mock::wpFunction(
			'sanitize_email', array(
				'args' => '   ab.de! ',
				'times' => 1,
				'return' => 'ab.de'
			)
		);

		\WP_Mock::wpFunction(
			'is_email', array(
				'args' => 'ab.de',
				'times' => 1,
				'return' => false
			)
		);

		$this->assertEquals(null, $sut->sanitize('   ab.de! ', $params, $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_integerRange_noBorder()
	{
		$sut = $this->sut(null);

		$params = array(
			'integerRange', // name of the call-method
		);
		$metadata = array(
			Attribute::DEFAULT_VALUE => 999
		);

		$this->assertEquals(100, $sut->sanitize(100, $params, $metadata));
		$this->assertEquals(200, $sut->sanitize("200", $params, $metadata));
		$this->assertEquals(300, $sut->sanitize('300', $params, $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_integerRange_leftBorder()
	{
		$sut = $this->sut(null);

		$params = array(
			'integerRange', // name of the call-method
			100, // left border
		);
		$metadata = array(
			Attribute::DEFAULT_VALUE => 999
		);

		$this->assertEquals(100, $sut->sanitize(100, $params, $metadata));
		$this->assertEquals(101, $sut->sanitize("101", $params, $metadata));
		$this->assertEquals(200, $sut->sanitize('200', $params, $metadata));

		$this->assertEquals(999, $sut->sanitize(99, $params, $metadata));
		$this->assertEquals(999, $sut->sanitize("90", $params, $metadata));
		$this->assertEquals(999, $sut->sanitize('-50', $params, $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_integerRange_rightBorder()
	{
		$sut = $this->sut(null);

		$params = array(
			'integerRange', // name of the call-method
			'', // no left border
			200 // right border
		);
		$metadata = array(
			Attribute::DEFAULT_VALUE => 666
		);

		$this->assertEquals(200, $sut->sanitize(200, $params, $metadata));
		$this->assertEquals(199, $sut->sanitize("199", $params, $metadata));
		$this->assertEquals(100, $sut->sanitize('100', $params, $metadata));

		$this->assertEquals(666, $sut->sanitize(201, $params, $metadata));
		$this->assertEquals(666, $sut->sanitize("202", $params, $metadata));
		$this->assertEquals(666, $sut->sanitize('9000', $params, $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_integerRange_leftAndRightBorders()
	{
		$sut = $this->sut(null);

		$params = array(
			'integerRange', // name of the call-method
			100, // no left border
			200 // right border
		);
		$metadata = array(
			Attribute::DEFAULT_VALUE => 155
		);

		$this->assertEquals(200, $sut->sanitize(200, $params, $metadata));
		$this->assertEquals(199, $sut->sanitize("199", $params, $metadata));
		$this->assertEquals(190, $sut->sanitize('190', $params, $metadata));

		$this->assertEquals(100, $sut->sanitize(100, $params, $metadata));
		$this->assertEquals(101, $sut->sanitize("101", $params, $metadata));
		$this->assertEquals(120, $sut->sanitize('120', $params, $metadata));

		$this->assertEquals(155, $sut->sanitize(99, $params, $metadata));
		$this->assertEquals(155, $sut->sanitize("90", $params, $metadata));
		$this->assertEquals(155, $sut->sanitize(201, $params, $metadata));
		$this->assertEquals(155, $sut->sanitize("9000", $params, $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_accumulation()
	{
		$sut = $this->sut(null);

		$params = array(
			'accumulation', // name of the call-method
			';', // separator
			array('integer') // sub method
		);
		$metadata = array(
			Attribute::DEFAULT_VALUE => 160
		);

		$output = $sut->sanitize('1;2;1.1;abc;null;666', $params, $metadata);
		// abc and null will be removed because they are no integers
		$this->assertEquals('1;2;1;666', $output);
	}

	/**
	 * @test
	 */
	public function sanitize_valueAssignment()
	{
		$sut = $this->sut(null);

		$metadata = array(
			Attribute::DEFAULT_VALUE => 'oh no'
		);

		$this->assertEquals(
			'aAa=bBb', $sut->sanitize(
			' aAa = bBb ', array(
			'valueAssignment',
			'=',
			false,
			false
		), $metadata
		)
		);
		$this->assertEquals(
			'aAa=bbb', $sut->sanitize(
			' aAa = bBb ', array(
			'valueAssignment',
			'=',
			false,
			true
		), $metadata
		)
		);
		$this->assertEquals(
			'aaa=bBb', $sut->sanitize(
			' aAa = bBb ', array(
			'valueAssignment',
			'=',
			true,
			false
		), $metadata
		)
		);
		$this->assertEquals(
			'aaa=bbb', $sut->sanitize(
			' aAa = bBb ', array(
			'valueAssignment',
			'=',
			true,
			true
		), $metadata
		)
		);

		$this->assertEquals(
			'aAa=bbb', $sut->sanitize(
			' aAa = bBb ', array(
			'valueAssignment',
			'=',
			false
		), $metadata
		)
		);
		$this->assertEquals('aAa=bbb', $sut->sanitize(' aAa = bBb ', array('valueAssignment', '='), $metadata));
		$this->assertEquals('aAa=bbb', $sut->sanitize(' aAa = bBb ', array('valueAssignment'), $metadata));

		$this->assertEquals('oh no', $sut->sanitize(' aAa  ', array('valueAssignment'), $metadata));
		$this->assertEquals('oh no', $sut->sanitize(' aAa=  ', array('valueAssignment'), $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_selection()
	{
		$sut = $this->sut(null);

		$metadata = array(
			Attribute::DEFAULT_VALUE => 'Kaugummi',
			Attribute::ELEMENTS => array(
				'Bitterschokolade',
				'Edelnougat',
				'Honigkuchen'
			)
		);

		$this->assertEquals('Honigkuchen', $sut->sanitize('Honigkuchen', array('selection'), $metadata));
		$this->assertEquals('Kaugummi', $sut->sanitize('Donuts', array('selection'), $metadata));
	}

	/**
	 * @test
	 */
	public function sanitize_custom()
	{
		$sut = $this->sut(null);

		$expected = "Bitterschokolade:1\n" .
			"Edelnougat:555\n" .
			"Honigkuchen:26";

		$this->assertEquals($expected, $sut->sanitize($expected, array('custom'), $expected));
	}

	/**
	 * @test
	 */
	public function sanitize_authcode_weakAuthCode_generateNewAuthCode()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction(
			'wp_generate_password', array(
				'return' => 'xahFwxtHSx5kMjCfTzTL'
			)
		);

		$this->assertEquals('xahFwxtHSx5kMjCfTzTL', $sut->sanitize('weakpassword', array('authcode'), array(), true));
	}

	/**
	 * @test
	 */
	public function sanitize_authcode_nonString_generateNewAuthCode()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction(
			'wp_generate_password', array(
				'return' => 'xahFwxtHSx5kMjCfTzTL'
			)
		);

		$this->assertEquals('xahFwxtHSx5kMjCfTzTL', $sut->sanitize(999, array('authcode'), array(), true));
	}

	/**
	 * @test
	 */
	public function sanitize_authcode_onlyReadValue_returnOldAuthCode()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction(
			'wp_generate_password', array(
				'return' => 'xahFwxtHSx5kMjCfTzTL'
			)
		);

		$this->assertEquals(999, $sut->sanitize(999, array('authcode'), array(), false));
	}

	/**
	 * @test
	 */
	public function sanitize_authcode_strongAuthcode_returnOldAuthCode()
	{
		$sut = $this->sut(null);

		\WP_Mock::wpFunction(
			'wp_generate_password', array(
				'return' => 'xahFwxtHSx5kMjCfTzTL'
			)
		);

		$pw = "very_very_very_strong_password";
		$this->assertEquals($pw, $sut->sanitize($pw, array('authcode'), array(), true));
	}


	/**
	 * @test
	 */
	public function sanitize_arrayBelowOneReturnFalse()
	{
		$sut = $this->sut(null);

		$array = array();

		$returnedValue = $sut->sanitize(null, $array, null);

		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function sanitize_checkCalledMethod()
	{
		$sut = $this->sut(array('string'));

		$value = ' 123 ';
		$array = array(true, false, true);
		$optionData = array('defaultValue' => 123);

		$sut->expects($this->once())
			->method('string')
			->with($value, $array, $optionData);

		$sut->sanitize($value, array('string', true, false, true), $optionData);
	}

	/**
	 * @test
	 */
	public function boolean_StringReturnFalse()
	{
		$sut = $this->sut(null);
		$value = "false";

		$returnedValue = $sut->boolean($value, null, null);
		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function boolean_StringReturnTrue()
	{
		$sut = $this->sut(null);
		$value = "true";

		$returnedValue = $sut->boolean($value, null, null);
		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function boolean_NumericReturnFalse()
	{
		$sut = $this->sut(null);
		$value = 0;

		$returnedValue = $sut->boolean($value, null, null);
		$this->assertFalse($returnedValue);
	}

	/**
	 * @test
	 */
	public function boolean_NumericReturnTrue()
	{
		$sut = $this->sut(null);
		$value = 1;

		$returnedValue = $sut->boolean($value, null, null);
		$this->assertTrue($returnedValue);
	}

	/**
	 * @test
	 */
	public function boolean_BoolReturnValue()
	{
		$sut = $this->sut(null);
		$valueTrue = true;
		$valueFalse = false;

		$returnedValueTrue = $sut->boolean($valueTrue, null, null);
		$returnedValueFalse = $sut->boolean($valueFalse, null, null);

		$this->assertTrue($returnedValueTrue);
		$this->assertFalse($returnedValueFalse);
	}

	/**
	 * @test
	 */
	public function integer_returnInteger()
	{
		$sut = $this->sut(null);

		$value = 42;

		$returnedValue = $sut->integer($value, null, null);
		$this->assertEquals($value, $returnedValue);
	}

	/**
	 * @test
	 */
	public function integer_returnDefaultValue()
	{
		$sut = $this->sut(null);

		$value = "";
		$optionDataTrue = array('defaultValue' => 42);

		$returnedValue = $sut->integer($value, null, $optionDataTrue);
		$this->assertEquals(42, $returnedValue);
	}

	/**
	 * @test
	 */
	public function integer_returnNull()
	{
		$sut = $this->sut(null);

		$value = "";
		$optionDataTrue = array();

		$returnedValue = $sut->integer($value, null, $optionDataTrue);
		$this->assertNull($returnedValue);
	}

	/**
	 * @test
	 */
	public function string_returnDefaultValue()
	{
		$sut = $this->sut(null);

		$optionData = array('defaultValue' => 'someDefaultString');
		$userParams = array();
		$userParams[2] = true;
		$returnedValue = $sut->string("", $userParams, $optionData);

		$this->assertEquals('someDefaultString', $returnedValue);

	}

	/**
	 * @test
	 */
	public function string_returnValue()
	{
		$sut = $this->sut(null);

		$optionData = array('defaultValue' => 'someDefaultString');
		$userParams = array();
		$userParams[2] = true;
		$returnedValue = $sut->string("someValue", $userParams, $optionData);

		$this->assertEquals('someValue', $returnedValue);
	}

	/**
	 * @test
	 */
	public function email_returnDefault()
	{
		$sut = $this->sut(null);
		$value = "noEmail";
		$optionData = array('defaultValue' => 'test@company.it');

		\WP_Mock::wpFunction(
			'sanitize_email', array(
				'args' => $value,
				'times' => '1',
				'return' => $value
			)
		);

		\WP_Mock::wpFunction(
			'is_email', array(
				'args' => $value,
				'times' => '1',
				'return' => false
			)
		);

		$returnedValue = $sut->email($value, null, $optionData);
		$this->assertEquals('test@company.it', $returnedValue);
	}

	/**
	 * @test
	 */
	public function email_returnValue()
	{
		$sut = $this->sut(null);
		$value = "test@company.it";
		$optionData = array('defaultValue' => 'testdefault@company.it');

		\WP_Mock::wpFunction(
			'sanitize_email', array(
				'args' => $value,
				'times' => '1',
				'return' => $value
			)
		);

		\WP_Mock::wpFunction(
			'is_email', array(
				'args' => $value,
				'times' => '1',
				'return' => true
			)
		);

		$returnedValue = $sut->email($value, null, $optionData);
		$this->assertEquals('test@company.it', $returnedValue);
	}

	/**
	 * @test
	 */
	public function integerRange_returnValue()
	{
		$sut = $this->sut(null);
		$value = 5;
		$optionData = array('defaultValue' => 20);
		$userParams = array(0, 10);

		$returnedValue = $sut->integerRange($value, $userParams, $optionData);
		$this->assertEquals($value, $returnedValue);
	}


	/**
	 * @test
	 */
	public function integerRange_returnDefault()
	{
		$sut = $this->sut(null);
		$value = null;
		$optionData = array('defaultValue' => 20);
		$userParams = array(0, 10);

		$returnedValue = $sut->integerRange($value, $userParams, $optionData);
		$this->assertEquals(20, $returnedValue);
	}

	/**
	 * @test
	 */
	public function integerRange_returnNull()
	{
		$sut = $this->sut(null);
		$value = null;
		$optionData = array();
		$userParams = array(0, 10);

		$returnedValue = $sut->integerRange($value, $userParams, $optionData);
		$this->assertNull($returnedValue);
	}

	/**
	 * @test
	 */
	public function accumulation_returnResult()
	{
		$sut = $this->sut(array('sanitize'));
		$value = "something;somethingNew;somethingOld";
		$optionData = array();
		$userParams = array();

		$sut->expects($this->exactly(3))
			->method('sanitize')
			->withConsecutive(
				array('something', null, null),
				array('somethingNew', null, null),
				array('somethingOld', null, null)
			)
			->will(
				$this->onConsecutiveCalls(
					'something',
					'somethingNew',
					'somethingOld'
				)
			);

		$returnedValue = $sut->accumulation($value, $userParams, $optionData);
		$this->assertEquals("something;somethingNew;somethingOld", $returnedValue);
	}

	/**
	 * @test
	 */
	public function valueAssignment_returnLeftEqualsRight()
	{
		$sut = $this->sut(array('string'));
		$value = "something=somethingNew";
		$optionData = array();
		$userParams = array();

		$sut->expects($this->exactly(2))
			->method('string')
			->withConsecutive(
				array('something', array(false, true), null),
				array('somethingNew', array(true, true), null)
			)
			->will(
				$this->onConsecutiveCalls(
					'something',
					'somethingNew'
				)
			);

		$expectedReturn = 'something=somethingNew';

		$returnedValue = $sut->valueAssignment($value, $userParams, $optionData);
		$this->assertEquals($expectedReturn, $returnedValue);
	}


	/**
	 * @test
	 */
	public function valueAssignment_returnDefault()
	{
		$sut = $this->sut(null);
		$value = "something;somethingNew;somethingOld";
		$optionData = array('defaultValue' => 'DefaultLeft=DefaultRight');
		$userParams = array();

		$expectedReturn = 'DefaultLeft=DefaultRight';

		$returnedValue = $sut->valueAssignment($value, $userParams, $optionData);
		$this->assertEquals($expectedReturn, $returnedValue);
	}


	/**
	 * @test
	 */
	public function valueAssignment_returnDefaultReasonLeftOrRightEmptyString()
	{
		$sut = $this->sut(array('string', 'getDefaultValue'));
		$value = "something=somethingNew";
		$optionData = array('defaultValue' => 'DefaultLeft=DefaultRight');
		$userParams = array();

		$sut->expects($this->exactly(2))
			->method('string')
			->withConsecutive(
				array('something', array(false, true), null),
				array('somethingNew', array(true, true), null)
			)
			->will(
				$this->onConsecutiveCalls(
					'',
					''
				)
			);

		$sut->expects($this->once())
			->method('getDefaultValue')
			->with($optionData)
			->willReturn('DefaultLeft=DefaultRight');

		$expectedReturn = 'DefaultLeft=DefaultRight';

		$returnedValue = $sut->valueAssignment($value, $userParams, $optionData);
		$this->assertEquals($expectedReturn, $returnedValue);
	}

	/**
	 * @test
	 */
	public function selection_returnValue()
	{
		$sut = $this->sut(null);
		$value = "someValue";
		$optionData = array('elements' => array('someValue'));
		$userParams = array();


		$returnedValue = $sut->selection($value, $userParams, $optionData);
		$this->assertEquals($value, $returnedValue);
	}

	/**
	 * @test
	 */
	public function selection_returnDefaultValue()
	{
		$sut = $this->sut(null);
		$value = "someValue";
		$optionData = array('elements' => array('someOtherValue'), 'defaultValue' => 'defaultValue');
		$userParams = array();

		$returnedValue = $sut->selection($value, $userParams, $optionData);
		$this->assertEquals('defaultValue', $returnedValue);
	}
}

