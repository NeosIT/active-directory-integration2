<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Multisite_Option_Attribute')) {
	return;
}

/**
 * Multisite_Option_Attribute contains meta names for options.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Multisite_Option_Attribute
{
	const TITLE = 'title';
	const TYPE = 'type';
	const DESCRIPTION = 'description';
	const DETAIL = 'detail';
	const ANGULAR_ATTRIBUTES = 'angular_attributes';
	const SHOW_PERMISSION = 'show_permission';
	const TRANSIENT = 'transient';

	const ELEMENTS = 'elements';
	const INLINE = 'inline';

	const TAB_TITLE = 'tabTitle';
	const GROUP_TITLE = 'groupTitle';

	const DEFAULT_VALUE = 'defaultValue';
	const PERSIST_DEFAULT_VALUE = 'persistDefaultValue';

	const DISABLED = 'disabled';
	const DISABLED_MESSAGE = 'disabled_message';

	const SANITIZER = 'sanitizer';

	const TYPE_STRUCTURE = 'type_structure';
}