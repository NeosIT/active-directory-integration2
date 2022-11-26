<?php

namespace Dreitier\Ldap\Attribute;

use Dreitier\Test\BasicTest;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class DescriptionTest extends BasicTest
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
	 * @test
	 */
	public function findAll_callMethodForTheFirstTime_returnExpectedDescriptions()
	{
		$descriptions = Description::findAll();

		$this->assertTrue(is_array($descriptions));
		$this->assertEquals(38, sizeof($descriptions));
	}

	/**
	 * @test
	 */
	public function findAll_callMethodForTheSecondTime_returnExpectedDescriptions()
	{
		Description::findAll();
		$descriptions = Description::findAll();

		$this->assertTrue(is_array($descriptions));
		$this->assertEquals(38, sizeof($descriptions));
	}

	/**
	 * @test
	 */
	public function find_withExistingAttribute_returnExpectedDescription()
	{
		$description = Description::find('cn', '');
		$this->assertEquals('Common Name', $description);
	}

	/**
	 * @test
	 */
	public function find_withNoDefaultAdAttribute_withCustomDescription_returnCustomDescription()
	{
		$attribute = Description::find('nadi_customAttribute', 'customAttributeDescription');
		$this->assertEquals('customAttributeDescription', $attribute);
	}
}