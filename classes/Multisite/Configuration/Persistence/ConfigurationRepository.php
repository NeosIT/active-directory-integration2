<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Configuration_Persistence_ConfigurationRepository')) {
	return;
}

interface Multisite_Configuration_Persistence_ConfigurationRepository
{
	public function findSanitizedValue($id, $optionName);

	public function persistSanitizedValue($id, $optionName, $optionValue);
}