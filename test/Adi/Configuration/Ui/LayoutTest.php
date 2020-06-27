<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Adi_Configuration_Ui_LayoutTest extends Ut_BasicTest
{

	public function setUp() : void
	{
		parent::setUp();
	}

	public function tearDown() : void
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

		$actual = array(
			"For security reasons you can use the following options to prevent brute force attacks on your user accounts.",
			"<div class=\"update-message notice inline notice-warning notice-alt\"> We highly recommend you to use <a href=\"https://wordpress.org/plugins/better-wp-security/\">iThemes Security</a> to secure your WordPress environment. <br> NADI Brute Force Protection will not receive updates anymore after the NADI v2.0.13 release and we are planning on removing it completely later this year. </div>",
			);

		$description = $bruteForce[NextADInt_Adi_Configuration_Ui_Layout::DESCRIPTION];
		$this->assertEquals($actual, $description);

		$options = $bruteForce[NextADInt_Adi_Configuration_Ui_Layout::OPTIONS];
		$this->assertEquals(NextADInt_Adi_Configuration_Options::MAX_LOGIN_ATTEMPTS, $options[0]);
		$this->assertEquals(NextADInt_Adi_Configuration_Options::ADMIN_EMAIL, $options[4]);
	}
}