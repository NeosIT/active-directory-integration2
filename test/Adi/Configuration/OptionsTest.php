<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Configuration_OptionsTest extends Ut_BasicTest
{

	/* @var NextADInt_Ldap_Attribute_Description */
	private $attributeDescription;

	/** @var NextADInt_Adi_Configuration_Options */
	private $sut;

	public function setUp()
	{
		parent::setUp();

		$this->sut = new NextADInt_Adi_Configuration_Options();
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
        WP_Mock::wpFunction('__', array(
            'args'       => array(WP_Mock\Functions::type('string'), 'next-active-directory-integration'),
            'times'      => '0+',
            'return_arg' => 0
        ));

		$option = $this->sut->get('port');
		$this->assertTrue(is_array($option));
		$this->assertEquals('Port', $option['title']);
		$this->assertEquals('number', $option['type']);
		$this->assertEquals('Port on which Active Directory listens. Unencrypted LDAP or STARTTLS uses port 389. LDAPS listens on port 636.', $option['description']);
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