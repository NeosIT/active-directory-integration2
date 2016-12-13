<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Configuration_Ui_LayoutTest extends Ut_BasicTest
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
	public function getOptionsGrouping_isArray()
	{
		$this->mockFunction__();
		$optionsGrouping = NextADInt_Adi_Configuration_Ui_Layout::get();
		$this->assertTrue(is_array($optionsGrouping));
	}

	/**
	 * @test
	 */
	public function getOptionGrouping_arrayNotEmpty()
	{
		$this->mockFunction__();
		$optionsGrouping = NextADInt_Adi_Configuration_Ui_Layout::get();

		$this->assertTrue(is_array($optionsGrouping));
		$this->assertTrue(! empty($optionsGrouping));
	}

	/**
	 * @test
	 */
	public function getOptionsGrouping_checkStructure()
	{
		$this->mockFunction__();
		$optionsGrouping = NextADInt_Adi_Configuration_Ui_Layout::get();
		$this->assertTrue(is_array($optionsGrouping));

		$security = $optionsGrouping['Security'];
		$this->assertTrue(is_array($security));

		$bruteForce = $security['Brute-Force-Protection'];
		$this->assertTrue(is_array($bruteForce));

		$description = $bruteForce[NextADInt_Adi_Configuration_Ui_Layout::DESCRIPTION];
		$this->assertEquals(
			'For security reasons you can use the following options to prevent brute force attacks on your user accounts.',
			$description
		);

		$options = $bruteForce[NextADInt_Adi_Configuration_Ui_Layout::OPTIONS];
		$this->assertEquals(NextADInt_Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS, $options[0]);
		$this->assertEquals(NextADInt_Adi_Configuration_Options::ADMIN_EMAIL, $options[4]);
	}
}