<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Option_Type')) {
	return;
}

/**
 * NextADInt_Multisite_Option_Type contains all option types.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Multisite_Option_Type
{
	const TEXT = 'text';
	const NUMBER = 'number';
	const PASSWORD = 'password';
	const CHECKBOX = 'checkbox';
	const RADIO = 'radio';
	const SELECT = 'select';
	const TEXTAREA = 'textarea';
	const CUSTOM = 'custom';
	const COMBOBOX = 'combobox';
	const AUTHCODE = 'authcode';
	const EDITABLE_LIST = 'editable_list';
	const TABLE = 'table';
	const VERIFICATION_PASSWORD = 'verification_password';
	const DOMAIN_SID = 'domain_sid';
	const LABEL = 'label';
}