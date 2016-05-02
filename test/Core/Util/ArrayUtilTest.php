<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Core_Util_ArrayUtilTest extends Ut_BasicTest
{
	/**
	 * @test
	 */
	public function get_returnsValueFromArray_ifKeyExistsInArray()
	{
		$expected = 'value';
		$key = 'key';
		$data = array($key => $expected);

		$actual = Core_Util_ArrayUtil::get($key, $data, 'fallback');

		$this->assertSame($expected, $actual);
	}

	/**
	 * @test
	 */
	public function get_returnsFallback_ifKeyDoesNotExistInArray()
	{
		$expected = 'value';
		$key = 'key';
		$data = array('some other key' => 'some other value');

		$actual = Core_Util_ArrayUtil::get($key, $data, $expected);

		$this->assertSame($expected, $actual);
	}

	/**
	 * @test
	 */
	public function containsIgnoreCase_itIgnoresCase()
	{
		$actual = Core_Util_ArrayUtil::containsIgnoreCase('hello', array('HeLlO', 'WoRlD'));

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function compareKey_returnsTrue_ifValuesAreEqual()
	{
		$key = 'key';
		$compareValue = 'compare value';
		$array = array($key => $compareValue);

		$actual = Core_Util_ArrayUtil::compareKey($key, $compareValue, $array);

		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function compareKey_returnsFalse_ifValuesAreNotEqual()
	{
		$array = array('key' => 'value');
		$key = 'key';
		$compareValue = 'compare value';

		$actual = Core_Util_ArrayUtil::compareKey($key, $compareValue, $array);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function filter_withPreserveKeysFalse_returnsExpectedResult()
	{
		$array = array('key1' => 1, 'key2' => 2, 'key3' => 3);
		$expected = array(2, 3);

		$actual = Core_Util_ArrayUtil::filter(function($value, $key) {
			return (2 <= $value);
		}, $array, false);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function filter_withPreserveKeysTrue_returnsExpectedResult()
	{
		$array = array('key1' => 1, 'key2' => 2, 'key3' => 3);
		$expected = array('key2' => 2, 'key3' => 3);

		$actual = Core_Util_ArrayUtil::filter(function($value, $key) {
			return (2 <= $value);
		}, $array, true);

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findFirstOrDefault_withoutElementsAndWithoutDefault_returnsNull()
	{
		$actual = Core_Util_ArrayUtil::findFirstOrDefault(array());

		$this->assertNull($actual);
	}

	/**
	 * @test
	 */
	public function findFirstOrDefault_withoutElementsAndWithDefault_returnsDefault()
	{
		$actual = Core_Util_ArrayUtil::findFirstOrDefault(array(), false);

		$this->assertFalse($actual);
	}

	/**
	 * @test
	 */
	public function findFirstOrDefault_withElement_returnsElement()
	{
		$actual = Core_Util_ArrayUtil::findFirstOrDefault(array('test'));

		$this->assertEquals('test', $actual);
	}

	/**
	 * @test
	 */
	public function findFirstOrDefault_withMultipleElements_returnsFirstElement()
	{
		$actual = Core_Util_ArrayUtil::findFirstOrDefault(array('hello', 'world'));

		$this->assertEquals('hello', $actual);
	}
}