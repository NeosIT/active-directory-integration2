<?php
/**
 * This class modifies Twig's global functions so that they don't get added to defined namespace if they are already present there.
 * @issue #185
 */
define("VENDOR_REPACKAGED_DIR", dirname(__FILE__) . "/vendor-repackaged");
$fileToPatch = VENDOR_REPACKAGED_DIR . '/twig/twig/src/Extension/CoreExtension.php';

$content = file_get_contents($fileToPatch);

$lineBreak  = "\n";
$lines = explode($lineBreak, $content);
$inGlobalNamespace = false;
$inFunction = false;
$out = [];

foreach ($lines as $line) {
	if (preg_match("/^namespace\s\{/", $line, $r)) {
		$inGlobalNamespace = true;
	}
	
	if (!$inGlobalNamespace) {
		$out[] = $line;
		continue;
	}
	
	if (preg_match("/^function ([\w|\_]*)\(.*/", $line, $r)) {
		$function = $r[1];
		$out[] = "if (!function_exists(__NAMESPACE__ . '\\$function')) {";
		$out[] = "\t$line";
		$inFunction = true;
		continue;
	}
	
	if ($inFunction && preg_match('/^\}\s*/', $line)) {
		$inFunction = false;
		$out[] = "\t} // function";
		$out[] = "} // if function_exists";
		continue;
	}
	
	$out[] = $line;
}

// write patched file
file_put_contents($fileToPatch, implode($lineBreak, $out));
include_once(VENDOR_REPACKAGED_DIR . "/autoload.php");

// ensure that we don't have any syntax errors - otherwise composer will fail
include $fileToPatch;