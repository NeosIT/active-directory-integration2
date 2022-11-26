<?php

namespace Dreitier\Nadi\Configuration\Ui;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTest;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class LayoutTest extends BasicTest
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
	public function getOptionsGrouping_isArray()
	{
		$this->mockFunction__();
		$optionsGrouping = Layout::get();
		$this->assertTrue(is_array($optionsGrouping));
	}

	/**
	 * @test
	 */
	public function getOptionGrouping_arrayNotEmpty()
	{
		$this->mockFunction__();
		$optionsGrouping = Layout::get();

		$this->assertTrue(is_array($optionsGrouping));
		$this->assertTrue(!empty($optionsGrouping));
	}

	/**
	 * @test
	 */
	public function getOptionsGrouping_checkStructure()
	{
		$this->mockFunction__();
		$optionsGrouping = Layout::get();
		$this->assertTrue(is_array($optionsGrouping));

		$security = $optionsGrouping['Security'];
		$this->assertTrue(is_array($security));
	}
}