<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Adi_Configuration_OptionsTest extends Ut_BasicTest
{

	/* @var Ldap_Attribute_Description */
	private $attributeDescription;

	/** @var Adi_Configuration_Options */
	private $sut;

	public function setUp()
	{
		parent::setUp();

		$this->sut = new Adi_Configuration_Options();
	}

	public function tearDown()
	{
		\WP_Mock::tearDown();
	}

	/**
	 * @test
	 * tests the retrieve process of OptionMetaData for a specific given option
	 */
	public function getOptionMetaData()
	{
		$option = $this->sut->get('port');
		$this->assertTrue(is_array($option));
		$this->assertEquals('Port', $option['title']);
		$this->assertEquals('number', $option['type']);
		$this->assertEquals('Port on which Active Directory listens (defaults to "389").', $option['description']);
		$this->assertEquals(389, $option['defaultValue']);
		$this->assertEquals(array(0 => 'integerRange', 1 => 0, 2 => 65535), $option['sanitizer']);
	}

	/**
	 * @test
	 * retrieves the optionMetaData of all Options
	 */
	public function getOptionsMetaData()
	{
		$getOptionsMetaDataContainer = $this->sut->getAll();

		$this->assertTrue(is_array($getOptionsMetaDataContainer));
	}
}