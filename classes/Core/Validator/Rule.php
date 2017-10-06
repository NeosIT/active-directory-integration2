<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Validator_Rule')) {
	return;
}

/**
 * NextADInt_Core_Validator_Rule provides the validate method for our rules.
 */
interface NextADInt_Core_Validator_Rule
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return mixed
	 */
	public function validate($value, $data);
}