<?php

namespace Dreitier\Util;

use Dreitier\Test\BasicTest;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class StringUtilTest extends BasicTest
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

		$actual = StringUtil::split($string, "\n");

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @issue ADI-248
	 * @test
	 */
	public function ADI248_splitNonEmpty_onlyReturnsNonEmptyLines()
	{
		$string = "  ; ; test ";

		$expected = array("test");

		$actual = StringUtil::splitNonEmpty($string, ";");
		$this->assertEquals($expected, $actual);
	}

	/**
	 * This has been added as a possible regression test.
	 * @test
	 */
	public function splitNonEmpty_whenHavingNoSeparator_itReturnsOneElement()
	{
		$string = "KRB.REALM=upn-suffix1.ad";
		$actual = StringUtil::splitNonEmpty($string, ";");
		$this->assertEquals([$string], $actual);
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

		$actual = StringUtil::splitText($string);
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

		$actual = StringUtil::splitText($string);
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

		$actual = StringUtil::splitText($string);
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

		$actual = StringUtil::splitText($string);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function objectSidToDomainSid_itReturnsDomainSidOfObject()
	{
		$objectSid = "S-1-5-21-0000000000-0000000000-0000000000-1234";
		$domainSid = StringUtil::objectSidToDomainSid($objectSid);

		$expected = "S-1-5-21-0000000000-0000000000-0000000000";

		$this->assertEquals($expected, $domainSid);
	}

	/**
	 * @test
	 */
	public function isEmptyOrWhitespace_withText_returnsTrue()
	{
		$string = 'Test';

		$result = StringUtil::isEmptyOrWhitespace($string);

		$this->assertFalse($result);
	}

	/**
	 * @test
	 */
	public function isEmptyOrWhitespace_withEmptyString_returnsTrue()
	{
		$string = '';

		$result = StringUtil::isEmptyOrWhitespace($string);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function isEmptyOrWhitespace_withWhitespaceOnly_returnsTrue()
	{
		$string = '    ';

		$result = StringUtil::isEmptyOrWhitespace($string);

		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function startsWith_withNeedleAtStart_returnsTrue()
	{
		$string = 'This is a text.';
		$needle = 'This';

		$result = StringUtil::startsWith($needle, $string);
		$this->assertTrue($result);
	}

	/**
	 * @test
	 */
	public function startsWith_withNeedleInTheMiddle_returnsFalse()
	{
		$string = 'This is a text.';
		$needle = 'text';

		$result = StringUtil::startsWith($needle, $string);
		$this->assertFalse($result);
	}

	/**
	 * @issue ADI-420
	 * @test
	 */
	public function ADI_420_firstChars_whenBooleanIsPassed_itReturnsBoolean()
	{

		$result = StringUtil::firstChars(true);
		$this->assertTrue($result);
	}

	/**
	 * @issue ADI-420
	 * @test
	 */
	public function ADI_420_firstChars_itReturnsTheFirstPart_whenMaxCharsArgumentIsUsed()
	{
		$chars = 10;

		$result = StringUtil::firstChars('abcdefghijKLMN', $chars, false);
		$this->assertEquals('abcdefghij', $result);
	}

	/**
	 * @issue ADI-420
	 * @test
	 */
	public function ADI_420_firstChars_itReturnsByteInfo()
	{
		$chars = 5;

		$result = StringUtil::firstChars('abcdefghij', $chars, true);
		$this->assertEquals('abcde (... 5 bytes more)', $result);
	}
}