<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Ut_NextADInt_Adi_Authentication_SingleSignOn_VariableTest')) {
	return;
}

/**
 * Ut_NextADInt_Adi_Authentication_SingleSignOn_VariableTest TODO short description
 *
 * Ut_NextADInt_Adi_Authentication_SingleSignOn_VariableTest TODO long description
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Ut_NextADInt_Adi_Authentication_SingleSignOn_VariableTest extends Ut_BasicTest
{
	/**
	 * @test
	 */
	public function getValues_returnsExpectedResult()
	{
		$expected = array(
			NextADInt_Adi_Authentication_SingleSignOn_Variable::REMOTE_USER,
			NextADInt_Adi_Authentication_SingleSignOn_Variable::X_REMOTE_USER,
		);

		$actual = NextADInt_Adi_Authentication_SingleSignOn_Variable::getValues();

		$this->assertEquals($expected, $actual);
	}
}