<?php
namespace Dreitier\Ldap\Attribute;

use Dreitier\Test\BasicTest;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class ConverterTest extends BasicTest
{
	public function setUp() : void
	{
		\WP_Mock::setUp();
	}

	public function tearDown() : void
	{
		\WP_Mock::tearDown();
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_string()
	{
		$stringTestReturn = Converter::formatAttributeValue('string', 555);
		$this->assertTrue("555" === $stringTestReturn);
		$this->assertTrue(is_string($stringTestReturn));
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_integer()
	{
		$integerTestReturn = Converter::formatAttributeValue('integer', 55.5);
		$this->assertEquals(55, $integerTestReturn);
		$this->assertTrue(is_integer($integerTestReturn));
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_boolean()
	{
		$boolTestReturn = Converter::formatAttributeValue('bool', '0');
		$this->assertEquals(false, $boolTestReturn);
		$this->assertTrue(is_bool($boolTestReturn));
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_time_utcTime()
	{
		$value = "20160131113515"; // 31.01.2016 11:35:15

		\WP_Mock::wpFunction(
			'get_option', array(
			'args'   => array('date_format', 'Y-m-d'),
			'times'  => 1,
			'return' => 'F j, Y'
		)
		);

		\WP_Mock::wpFunction(
			'get_option', array(
			'args'   => array('time_format', 'H:i:s'),
			'times'  => 1,
			'return' => 'g:i a'
		)
		);

		\WP_Mock::wpFunction(
			'date_i18n', array(
			'args'   => array('F j, Y / g:i a', 1454240115, true),
			'times'  => 1,
			'return' => 'January 31, 2016 / 11:35 am'
		)
		);

		$expected = "January 31, 2016 / 11:35 am";
		$this->assertEquals($expected, Converter::formatAttributeValue('time', $value));
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_time_localTime()
	{
		$value = "20160131113515Z"; // 31.01.2016 11:35:15

		\WP_Mock::wpFunction(
			'get_option', array(
			'args'   => array('gmt_offset', 0),
			'times'  => 1,
			'return' => 1
		)
		);

		\WP_Mock::wpFunction(
			'get_option', array(
			'args'   => array('date_format', 'Y-m-d'),
			'times'  => 1,
			'return' => 'F j, Y'
		)
		);

		\WP_Mock::wpFunction(
			'get_option', array(
			'args'   => array('time_format', 'H:i:s'),
			'times'  => 1,
			'return' => 'g:i a'
		)
		);

		\WP_Mock::wpFunction(
			'date_i18n', array(
			'args'   => array('F j, Y / g:i a', 1454243715, true),
			'times'  => 1,
			'return' => 'January 31, 2016 / 12:35 pm'
		)
		);

		$expected = "January 31, 2016 / 12:35 pm";
		$this->assertEquals($expected, Converter::formatAttributeValue('time', $value));
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_timestamp()
	{
		$fileTime = 130987999470000000;
		$unixTime = 1454326347;
		$offset = 1;

		\WP_Mock::wpFunction(
			'get_option', array(
			'args'   => array('gmt_offset', 0),
			'times'  => 1,
			'return' => $offset
		)
		);

		\WP_Mock::wpFunction(
			'get_option', array(
			'args'   => array('date_format', 'Y-m-d'),
			'times'  => 1,
			'return' => 'F j, Y'
		)
		);

		\WP_Mock::wpFunction(
			'get_option', array(
			'args'   => array('time_format', 'H:i:s'),
			'times'  => 1,
			'return' => 'g:i a'
		)
		);

		\WP_Mock::wpFunction(
			'date_i18n', array(
			'args'   => array('F j, Y / g:i a', $unixTime + 3600, true),
			'times'  => 1,
			'return' => 'February 1, 2016 / 11:32 am'
		)
		);

		$expected = 'February 1, 2016 / 11:32 am';
		$actual = Converter::formatAttributeValue('timestamp', $fileTime);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_octet()
	{
		$octetTest = "This is a test String";
		$expected = "VGhpcyBpcyBhIHRlc3QgU3RyaW5n";

		$octetTestReturn = Converter::formatAttributeValue("octet", $octetTest);
		$this->assertEquals($expected, $octetTestReturn);
		$this->assertTrue(is_string($octetTestReturn));
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_cn_returnCnWithEscapedComma()
	{
		$cnTest = "CN=Ellen\,Bogen, OU=EDV, OU=Benutzer, DC=faq-o-matic, DC=net";

		$cnTestReturn = Converter::formatAttributeValue("cn", $cnTest);
		$this->assertEquals("Ellen,Bogen", $cnTestReturn);
		$this->assertTrue(is_string($cnTestReturn));
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_cn_returnCn()
	{
		$cnTest = "CN=Ellen Bogen, OU=EDV, OU=Benutzer, DC=faq-o-matic, DC=net";

		$cnTestReturn = Converter::formatAttributeValue("cn", $cnTest);
		$this->assertEquals("Ellen Bogen", $cnTestReturn);
		$this->assertTrue(is_string($cnTestReturn));
	}

	/**
	 * @test
	 */
	public function formatAttributeValue_invalidCn_returnEmptyString()
	{
		$cnTest = "invalid";

		$cnTestReturn = Converter::formatAttributeValue("cn", $cnTest);
		$this->assertEquals('', $cnTestReturn);
		$this->assertTrue(is_string($cnTestReturn));
	}
}
