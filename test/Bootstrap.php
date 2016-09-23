<?php
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
    define('NEXT_AD_INT_PLUGIN_VERSION', '2.0');
    define('NEXT_AD_INT_PREFIX', 'next_ad_int_');
    define('NEXT_AD_INT_URL', '');
    define('NEXT_AD_INT_I18N', 'next_ad_int');
    define('NEXT_AD_INT_FILE_CONVERSION_PATTERN', "[%-5level] %class::%method [line %line] %msg %ex\r\n");
    define('NEXT_AD_INT_ECHO_CONVERSION_PATTERN', '[%-5level] %msg %ex<br />');
	define('AUTH_SALT', '</q|_f-py65|-Cy*E)9$]}jI/x1KqLMIF_rc1g]`=vsa`9RjA,r1ufr(lM2L*YBp');
	define('OBJECT', 987);

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

		public function getErrorKey() {
			if (sizeof($this->value) > 0) {
				return $this->value[0];
			}

			return null;
		}

		public function getErrors()
		{
			return $this->errors;
		}
	}

	class WP_Roles
	{
		private $role_names;

		public function __construct()
		{
			$this->role_names = array(
				'administrator' => 'Administrator',
				'editor'        => 'Editor',
				'author'        => 'Author',
				'contributor'   => 'Contributor',
				'subscriber'    => 'Subscriber',
			);
		}

		public function is_role($role)
		{
			return isset($this->role_names[$role]);
		}
	}

	class WP_User {
		
	}

    class WP_MS_Sites_List_Table {

    }

    // now we can mock this empty class instead of creating a mock from an non existing class
    // this is a workaround for a bug https://github.com/sebastianbergmann/phpunit-mock-objects/issues/321
    class BlueprintClass {

    }
}

// these three functions are copied from wp-includes/formatting.php
function stripslashes_deep( $value ) {
    return map_deep( $value, 'stripslashes_from_strings_only' );
}
function stripslashes_from_strings_only( $value ) {
    return is_string($value) ? stripslashes($value) : $value;
}
function map_deep( $value, $callback ) {
    if ( is_array( $value ) ) {
        foreach ( $value as $index => $item ) {
            $value[ $index ] = map_deep( $item, $callback );
        }
    } elseif ( is_object( $value ) ) {
        $object_vars = get_object_vars( $value );
        foreach ( $object_vars as $property_name => $property_value ) {
            $value->$property_name = map_deep( $property_value, $callback );
        }
    } else {
        $value = call_user_func( $callback, $value );
    }
    return $value;
}

// Jenkins does continuously fail with "allowed memory size of 134217728 bytes exhausted at..." during testing
ini_set("memory_limit", "2G");

// search for the plugin root folder with the classes subfolder
$path = dirname(__FILE__);
for ($i = 0; $i < 9; $i++) {
	$path = dirname($path);
	if (file_exists($path . '/classes')) {
		break;
	}
}

define('NEXT_AD_INT_PATH', $path);

// get plugin folder name from path
$pluginName = explode('/', $path);
$pluginName = $pluginName[sizeof($pluginName) - 1];
$pluginName = explode('\\', $pluginName);
$pluginName = $pluginName[sizeof($pluginName) - 1];
define('NEXT_AD_INT_PLUGIN_FILE', "$pluginName/index.php");

require_once "$path/Autoloader.php";
$autoLoader = new NextADInt_Autoloader();
$autoLoader->register();

require_once "$path/functions.php";

require_once "$path/vendor/autoload.php";

require_once "$path/vendor/twig/twig/lib/Twig/Autoloader.php";
Twig_Autoloader::register();

require_once "$path/test/BasicTest.php";
require_once "$path/test/ItBasicTest.php";
require_once "$path/test/DatabaseTest.php";
