<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Validator_Rule')) {
	return;
}

/**
 * Core_Validator_Rule provides the validate method for our rules.
 */
interface Core_Validator_Rule
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