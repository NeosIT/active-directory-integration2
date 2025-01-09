<?php

namespace Dreitier\Nadi\Ui;

use Dreitier\Ldap\Attribute\Description;
use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTestCase;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class OptionsTest extends BasicTestCase
{

	/* @var Description */
	private $attributeDescription;

	/** @var Options */
	private $sut;

	public function setUp(): void
	{
		parent::setUp();

		$this->sut = new Options();
	}

	public function tearDown(): void
	{
		\WP_Mock::tearDown();
	}

	/**
	 * @test
	 * tests the retrieve process of OptionMetaData for a specific given option
	 */
	public function getOptionMetaData()
	{
		$this->mockFunction__();
		$option = $this->sut->get('port');
		$this->assertTrue(is_array($option));
		$this->assertEquals('Port', $option['title']);
		$this->assertEquals('number', $option['type']);
		$this->assertEquals('Port on which the Active Directory listens. Unencrypted LDAP or STARTTLS use port 389. LDAPS listens on port 636.', $option['description']);
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