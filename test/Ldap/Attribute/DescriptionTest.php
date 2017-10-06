<?php

/**
 * Ut_NextADInt_Ldap_Attribute_DescriptionTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Ldap_Attribute_DescriptionTest extends Ut_BasicTest
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
	 * @test
	 */
	public function findAll_callMethodForTheFirstTime_returnExpectedDescriptions()
	{
		$descriptions = NextADInt_Ldap_Attribute_Description::findAll();

		$this->assertTrue(is_array($descriptions));
		$this->assertEquals(35, sizeof($descriptions));
	}

	/**
	 * @test
	 */
	public function findAll_callMethodForTheSecondTime_returnExpectedDescriptions()
	{
		NextADInt_Ldap_Attribute_Description::findAll();
		$descriptions = NextADInt_Ldap_Attribute_Description::findAll();

		$this->assertTrue(is_array($descriptions));
		$this->assertEquals(35, sizeof($descriptions));
	}

	/**
	 * @test
	 */
	public function find_withExistingAttribute_returnExpectedDescription()
	{
		$description = NextADInt_Ldap_Attribute_Description::find('cn', '');
		$this->assertEquals('Common Name', $description);
	}

	/**
	 * @test
	 */
	public function find_withNoDefaultAdAttribute_withCustomDescription_returnCustomDescription()
	{
		$attribute = NextADInt_Ldap_Attribute_Description::find('nadi_customAttribute', 'customAttributeDescription');
		$this->assertEquals('customAttributeDescription', $attribute);
	}
}