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
		$attribute = NextADInt_Ldap_Attribute_Description::find('cn', 'fallback');
		$this->assertEquals('Common Name', $attribute);
	}

	/**
	 * @test
	 */
	public function find_withNoExistingAttribute_returnFallback()
	{
		$attribute = NextADInt_Ldap_Attribute_Description::find('not_existing', 'fallback');
		$this->assertEquals('fallback', $attribute);
	}
}