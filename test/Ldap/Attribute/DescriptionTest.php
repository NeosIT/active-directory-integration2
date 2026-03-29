<?php

namespace Dreitier\Ldap\Attribute;

use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class DescriptionTest extends BasicTestCase
{
	public function setUp(): void
	{
		parent::setUp();
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	#[Test]
	public function findAll_callMethodForTheFirstTime_returnExpectedDescriptions()
	{
		$descriptions = Description::findAll();

		$this->assertTrue(is_array($descriptions));
		$this->assertEquals(38, sizeof($descriptions));
	}

	#[Test]
	public function findAll_callMethodForTheSecondTime_returnExpectedDescriptions()
	{
		Description::findAll();
		$descriptions = Description::findAll();

		$this->assertTrue(is_array($descriptions));
		$this->assertEquals(38, sizeof($descriptions));
	}

	#[Test]
	public function find_withExistingAttribute_returnExpectedDescription()
	{
		$description = Description::find('cn', '');
		$this->assertEquals('Common Name', $description);
	}

	#[Test]
	public function find_withNoDefaultAdAttribute_withCustomDescription_returnCustomDescription()
	{
		$attribute = Description::find('nadi_customAttribute', 'customAttributeDescription');
		$this->assertEquals('customAttributeDescription', $attribute);
	}
}