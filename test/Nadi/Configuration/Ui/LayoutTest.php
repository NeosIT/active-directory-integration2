<?php

namespace Dreitier\Nadi\Configuration\Ui;

use Dreitier\Nadi\Configuration\Options;
use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class LayoutTest extends BasicTestCase
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
	public function getOptionsGrouping_isArray()
	{
		$this->mockFunction__();
		$optionsGrouping = Layout::get();
		$this->assertTrue(is_array($optionsGrouping));
	}

	#[Test]
	public function getOptionGrouping_arrayNotEmpty()
	{
		$this->mockFunction__();
		$optionsGrouping = Layout::get();

		$this->assertTrue(is_array($optionsGrouping));
		$this->assertTrue(!empty($optionsGrouping));
	}

	#[Test]
	public function getOptionsGrouping_checkStructure()
	{
		$this->mockFunction__();
		$optionsGrouping = Layout::get();
		$this->assertTrue(is_array($optionsGrouping));

		$security = $optionsGrouping['Security'];
		$this->assertTrue(is_array($security));
	}
}