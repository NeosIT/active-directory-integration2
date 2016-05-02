<?php

/**
 * Ut_Ldap_Attribute_DescriptionTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Ldap_Attribute_DescriptionTest extends Ut_BasicTest
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
		$descriptions = Ldap_Attribute_Description::findAll();

		$this->assertTrue(is_array($descriptions));
		$this->assertEquals(35, sizeof($descriptions));
	}

	/**
	 * @test
	 */
	public function findAll_callMethodForTheSecondTime_returnExpectedDescriptions()
	{
		Ldap_Attribute_Description::findAll();
		$descriptions = Ldap_Attribute_Description::findAll();

		$this->assertTrue(is_array($descriptions));
		$this->assertEquals(35, sizeof($descriptions));
	}

	/**
	 * @test
	 */
	public function find_withExistingAttribute_returnExpectedDescription()
	{
		$attribute = Ldap_Attribute_Description::find('cn', 'fallback');
		$this->assertEquals('Common Name', $attribute);
	}

	/**
	 * @test
	 */
	public function find_withNoExistingAttribute_returnFallback()
	{
		$attribute = Ldap_Attribute_Description::find('not_existing', 'fallback');
		$this->assertEquals('fallback', $attribute);
	}
}