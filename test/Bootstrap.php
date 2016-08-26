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
	define('ADI_PREFIX', 'adi2_');
	define('ADI_URL', '');
	define('ADI_I18N', 'ad-integration-2.0');
	define('AUTH_SALT', '</q|_f-py65|-Cy*E)9$]}jI/x1KqLMIF_rc1g]`=vsa`9RjA,r1ufr(lM2L*YBp');
	define('OBJECT', 987);
	define('ADI_PLUGIN_FILE', 'active-directory-integration2/index.php');

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

require_once "$path/Autoloader.php";
$autoLoader = new Adi_Autoloader();
$autoLoader->register();

require_once "$path/functions.php";

require_once "$path/vendor/autoload.php";

require_once "$path/vendor/twig/twig/lib/Twig/Autoloader.php";
Twig_Autoloader::register();

require_once "$path/test/BasicTest.php";
require_once "$path/test/ItBasicTest.php";
require_once "$path/test/DatabaseTest.php";
