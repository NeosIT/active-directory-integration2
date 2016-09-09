<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

/**
 * ADI_Autoloader provides the logic for loading all classes under the ADI_ namespace.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Autoloader
{
	const SOURCE_FOLDER = 'classes';
	const PHP_FILE_EXTENSION = '.php';
	const NAMESPACE_SEPARATOR = '_';
    const CLASS_PREFIX = 'NextADInt_';

	/**
	 * Register our load method as an autoloader function.
	 */
	public function register()
	{
		spl_autoload_register(array($this, 'load'));
	}

	/**
	 * Try loading the given class.
	 *
	 * @param $class
	 */
	public function load($class)
	{
		$classPath = DIRECTORY_SEPARATOR . self::SOURCE_FOLDER . DIRECTORY_SEPARATOR;

		// the given class is not registered under our namespace, so we continue to the next autoloader
		if (!$this->isPluginClass($class)) {
			return;
		}

		//split path into pieces
		$pathPieces = $this->convertClassNameToPathArray($class, self::NAMESPACE_SEPARATOR);
		// convert the array with our path fragments to a absolute path
		$directoryPath = $this->convertPathArrayToAbsolutePath($pathPieces, NEXT_AD_INT_PATH . $classPath);
		// build the absolute file path using the created directory path
		$file = $this->buildFilePath($directoryPath, $pathPieces);

		// validate path
		$this->checkPathToFile($file);

		// if the file exists, load it
		$this->loadFile($file);
	}

	/**
	 * Build the absolute file path to the class.
	 *
	 * @param $directoryPath
	 * @param $pathPieces
	 *
	 * @return string
	 */
	private function buildFilePath($directoryPath, $pathPieces)
	{
		$fileName = array_pop($pathPieces);

		return $directoryPath . $fileName . self::PHP_FILE_EXTENSION;
	}

	/**
	 * Convert the given path array to an absolute path.
	 *
	 * @param $pathPieces
	 * @param $classPath
	 *
	 * @return string
	 */
	private function convertPathArrayToAbsolutePath($pathPieces, $classPath)
	{
		$path = $classPath;

		// add sub folder
		for ($i = 0; $i < count($pathPieces) - 1; $i++) {
			$path = $path . $pathPieces[$i] . DIRECTORY_SEPARATOR;
		}

		return $path;
	}

	/**
	 * Check if the given class name starts with our ADI_ prefix.
	 *
	 * @param $class
	 *
	 * @return bool
	 */
	private function isPluginClass($class)
	{
		$prefixes = array('Adi', 'Core', 'Ldap', 'Multisite', 'Migration');

		foreach ($prefixes as $prefix) {
		    $totalPrefix = self::CLASS_PREFIX . $prefix;
			$len = strlen($totalPrefix);

			if (strncmp($totalPrefix, $class, $len) === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convert the given class name into an array which contains our path to the correct file.
	 *
	 * @param $class
	 * @param $namespaceSeparator
	 *
	 * @return array
	 */
	private function convertClassNameToPathArray($class, $namespaceSeparator)
	{
	    $class = substr($class, strlen(self::CLASS_PREFIX));
		return explode($namespaceSeparator, $class);
	}

	/**
	 * Check if the given file exists and load it.
	 *
	 * @param $file
	 */
	private function loadFile($file)
	{
		if (file_exists($file)) {
			require $file;
		}
	}

	/**
	 * Validate path to file (for unix compatibility)
	 *
	 * @param $pathToFile
	 */
	private function checkPathToFile($pathToFile)
	{
//		$realPath = realpath($pathToFile);
//
//		if ($realPath !== $pathToFile) {
//			print "The path to a php file is invalid and not unix compatible! Requested: '$realPath' Actual: '$pathToFile'\r\n";
//
//			$debugBacktraceOpts = array(0);
//
//			if (version_compare(phpversion() , '5.4.0', '>')) {
//				print "Stack Trace:\r\n\r\n";
//
//				print_r(debug_backtrace(0, 5));
//			}
//
//			exit;
//		}
	}
}