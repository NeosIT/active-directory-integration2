<?php
// enable all error reporting information to catch deprecation warnings etc.
error_reporting(E_ALL);

// check modules
if (!extension_loaded('mbstring')) {
	die("PHP extension mbstring is missing");
}

// check modules
if (!extension_loaded('ldap')) {
	die("PHP extension ldap is missing");
}

if (!defined('ABSPATH')) {
	// TODO move this and the definition in index.php to its own file so it can be included once
	define('ABSPATH', '');
	define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_URL', '');

	// TODO in eigene Klassen auslagern
	class WP_Error
	{
		private $value;
		private $errors;

		public function __construct()
		{
			$this->value = func_get_args();
			$this->errors = array();
		}

		public function getConstructorArgs()
		{
			return $this->value;
		}

		public function add($key, $value)
		{
			$this->errors[$key] = $value;
		}

		public function getErrorKey()
		{
			if (sizeof($this->value) > 0) {
				return $this->value[0];
			}

			return null;
		}

		public function getErrors()
		{
			return $this->errors;
		}

		public function get_error_message()
		{
			return $this->value[0];
		}
	}

	class WP_Roles
	{
		public $roles;

		public function __construct()
		{
			$this->roles = array(
				'administrator' => array(
					'name' => 'Administrator',
				),
				'editor' => array(
					'name' => 'Editor',
				),
				'author' => array(
					'name' => 'Author',
				),
				'contributor' => array(
					'name' => 'Contributor',
				),
				'subscriber' => array(
					'name' => 'Subscriber',
				),
			);
		}

		public function is_role($role)
		{
			return isset($this->roles[$role]);
		}
	}

	class WP_User
	{
		public $data;

		public function __construct()
		{
			$this->data = (object)array(
				'user_login' => 'hugo',
				'user_email' => 'hugo@test.ad',
				'ID' => '666'
			);
		}

		public function __get($name)
		{
			return $this->data->$name;
		}

		public function setExpectedUserLogin($expected)
		{
			$this->data->user_login = $expected;
		}

		public function setExpectedUserEmail($expected)
		{
			$this->data->user_email = $expected;
		}

		public function setID($expected)
		{
			$this->data->ID = $expected;
		}
	}

	class WP_MS_Sites_List_Table
	{

	}

	// now we can mock this empty class instead of creating a mock from an non existing class
	// this is a workaround for a bug https://github.com/sebastianbergmann/phpunit-mock-objects/issues/321
	class BlueprintClass
	{

	}
}

// these three functions are copied from wp-includes/formatting.php
function stripslashes_deep($value)
{
	return map_deep($value, 'stripslashes_from_strings_only');
}

function stripslashes_from_strings_only($value)
{
	return is_string($value) ? stripslashes($value) : $value;
}

function map_deep($value, $callback)
{
	if (is_array($value)) {
		foreach ($value as $index => $item) {
			$value[$index] = map_deep($item, $callback);
		}
	} elseif (is_object($value)) {
		$object_vars = get_object_vars($value);
		foreach ($object_vars as $property_name => $property_value) {
			$value->$property_name = map_deep($property_value, $callback);
		}
	} else {
		$value = call_user_func($callback, $value);
	}
	return $value;
}

// Jenkins does continuously fail with "allowed memory size of 134217728 bytes exhausted at..." during testing
ini_set("memory_limit", "2G");

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

require_once NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH . "/test/BasicTest.php";
require_once NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH . "/test/BasicIntegrationTest.php";
