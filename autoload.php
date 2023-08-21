<?php
/**
 * NADI has its own package loader and does not use composer's one.
 * This has been introduced because we are using strauss to re-package our runtime dependencies.
 * Each runtime dependency is packaged into the namespace Dreiter\\Nadi.
 *
 * At the moment, strauss does not allow mixing own packages and vendor packages without hacking, hence this autoloader exists.
 *
 * PHPUnit uses the dev-autoloader, so that Mockery and other packages can be loaded. This is not in our interest.
 */
if (defined("NADI_PACKAGES_LOADED")) {
	return;
}

// helper methods
require_once __DIR__ . '/constants.php';

// helper methods
require_once __DIR__ . '/functions.php';

$vendorDir = __DIR__ . '/vendor-repackaged';

if (!is_dir($vendorDir)) {
	die("Vendor directory has not been packaged, please download a proper NADI build or run composer install");
}

// find re-packaged dependencies
require_once $vendorDir . "/autoload.php";

// mapping of our namespaces.
// using composer's autoload.psr-4 feature is not possible as we would interfere with the dependencies
$mapNamespacesToSourceDirectories = [
	"Dreitier\\Nadi\\" => __DIR__ . '/src/plug-in',
	"Dreitier\\" => __DIR__ . '/src/shared',
];

// register our own namespaces.
// @see https://stackoverflow.com/a/39774973/2545275
foreach ($mapNamespacesToSourceDirectories as $namespace => $sourceDirectory) {
	spl_autoload_register(function ($classname) use ($namespace, $sourceDirectory) {
		// Check if the namespace matches the class we are looking for
		if (preg_match("#^" . preg_quote($namespace) . "#", $classname)) {
			// Remove the namespace from the file path since it's psr4
			$classname = str_replace($namespace, "", $classname);
			$filename = preg_replace("#\\\\#", "/", $classname) . ".php";
			$fullpath = $sourceDirectory . "/$filename";

			if (file_exists($fullpath)) {
				include_once $fullpath;
			}
		}
	});
}

// fallback to grant compatability for premium extensions
require_once __DIR__ . '/src/compat-v2/stubs.php';

define("NADI_PACKAGES_LOADED", true);
