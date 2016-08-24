<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Core_Util_StringUtilTest extends Ut_BasicTest
{
	/**
	 * @test
	 */
	public function split_removesCarriageReturnAndSplitsStringByNewLines()
	{
		$string = "This \r\n is \r\n a \r\n test \r\n string.";
		$expected = array(
			'This ',
			' is ',
			' a ',
			' test ',
			' string.',
		);

		$actual = NextADInt_Core_Util_StringUtil::split($string, "\n");

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @issue ADI-248
	 * @test
	 */
	public function ADI248_splitText_onlyReturnsNonEmptyLines()
	{
		$string = "  ; ; test ";

		$expected = array("test");

		$actual = NextADInt_Core_Util_StringUtil::splitNonEmpty($string, ";");
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function splitText_withUnixLineBreaks_returnLinesInArray()
	{
		$string = 'abba' . "\n" . 'cddc' . "\n";

		$expected = array(
			'abba',
			'cddc',
		);

		$actual = NextADInt_Core_Util_StringUtil::splitText($string);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function splitText_withWindowLineBreaks_returnLinesInArray()
	{
		$string = 'abba' . "\r\n" . 'cddc' . "\n\r" . 'effe';

		$expected = array(
			'abba',
			'cddc',
			'effe',
		);

		$actual = NextADInt_Core_Util_StringUtil::splitText($string);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function splitText_withBeginningWhitespaces_returnTrimmedValues()
	{
		$string = '  a bba  ';

		$expected = array(
			'a bba',
		);

		$actual = NextADInt_Core_Util_StringUtil::splitText($string);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function splitText_withEmptyLines_returnNotEmptyLines()
	{
		$string = "\n" . 'abba' . "\n" . '     ' . "\n" . '' . "\n" . 'cddc';

		$expected = array(
			'abba',
			'cddc',
		);

		$actual = NextADInt_Core_Util_StringUtil::splitText($string);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function objectSidToDomainSid_itReturnsDomainSidOfObject()
	{
		$objectSid = "S-1-5-21-0000000000-0000000000-0000000000-1234";
		$domainSid = NextADInt_Core_Util_StringUtil::objectSidToDomainSid($objectSid);

		$expected = "S-1-5-21-0000000000-0000000000-0000000000";

		$this->assertEquals($expected, $domainSid);
	}

	/**
	 * @test
	 */
	public function isEmptyOrWhitespace_withText_returnsTrue()
	{
		$string = 'Test';

		$result = NextADInt_Core_Util_StringUtil::isEmptyOrWhitespace($string);

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function isEmptyOrWhitespace_withEmptyString_returnsTrue()
	{
		$string = '';

		$result = NextADInt_Core_Util_StringUtil::isEmptyOrWhitespace($string);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function isEmptyOrWhitespace_withWhitespaceOnly_returnsTrue()
	{
		$string = '    ';

		$result = NextADInt_Core_Util_StringUtil::isEmptyOrWhitespace($string);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function startsWith_withNeedleAtStart_returnsTrue()
	{
		$string = 'This is a text.';
		$needle = 'This';

		$result = NextADInt_Core_Util_StringUtil::startsWith($needle, $string);
		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function startsWith_withNeedleInTheMiddle_returnsFalse()
	{
		$string = 'This is a text.';
		$needle = 'text';

		$result = NextADInt_Core_Util_StringUtil::startsWith($needle, $string);
		$this->assertFalse($result);
	}
}