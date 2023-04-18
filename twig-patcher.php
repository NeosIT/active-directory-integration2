<?php
/**
 * This class renames Twig's global "twig_*" functions so that they have their own "dreitier_nadi_" prefix
 * @issue #185
 */
define("VENDOR_REPACKAGED_DIR", dirname(__FILE__) . "/vendor-repackaged");
define("TWIG_DIR", "twig/twig/src");
define("PREFIX", "dreitier_nadi_");

$patchedFiles = [];

$iterator = new RecursiveDirectoryIterator(VENDOR_REPACKAGED_DIR . '/' . TWIG_DIR);

foreach(new RecursiveIteratorIterator($iterator) as $file) {
	// only pick PHP files which are not PHPUnit test cases
	if (
		$file->isFile() 
		&& !$file->isDir() 
		&& $file->getExtension() == 'php'
	) {
		$path = $file->getRealpath();
		
		$content = file_get_contents($path);
		
		// find each twig_* function
		if (preg_match_all("/(?<preambel>.*)(?<function>twig\_([\w|\_]*))+/", $content, $r)) {
			$alreadyRemapped = [];
			foreach ($r['function'] as $idx => $functionName) {
				$preambel = $r['preambel'][$idx];
				#echo $preambel . PHP_EOL;
				#echo $functionName . PHP_EOL;

				// do not map already mapped files
				if (str_ends_with($preambel, PREFIX) || isset($alreadyRemapped[$functionName])) {
					#echo "-> Already mapped" . PHP_EOL;
					continue;
				}
				
				$remappedFunction = PREFIX . $functionName; 
				#echo $remappedFunction . PHP_EOL;
				$content = str_replace($functionName, $remappedFunction, $content);
				$alreadyRemapped[$functionName] = true;
			}
			
			file_put_contents($path, $content);
			
			if (!str_ends_with($file->getFilename(), "TestCase.php")) {
				$patchedFiles[] = $path;
			}
		}
	}
}

include_once(VENDOR_REPACKAGED_DIR . "/autoload.php");

// ensure that we don't have any syntax errors - otherwise composer will fail
foreach ($patchedFiles as $patchedFile) {
	include $patchedFile;
}