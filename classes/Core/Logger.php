<?php

if ( ! defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Logger')) {
	return;
}


/**
 * NextADInt_Core_Logger Simple logging fascade
 *
 * Internally, log4php is used.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Core_Logger
{
    const RELATIVE_LOG_PATH = '/logs/debug.log';
    const FILE_CONVERSION_PATTERN_FILTER = 'next_ad_int_file_conversion_pattern_filter';
    const ECHO_CONVERSION_PATTERN_FILTER = 'next_ad_int_echo_conversion_pattern_filter';
	const TABLE_CONVERSION_PATTERN_FILTER = 'next_ad_int_table_conversion_pattern_filter';

	private static $logging = true;

	private static $fileConfig = array(
		'class'  => 'LoggerAppenderFile',
		'layout' => array(
			'class' => 'LoggerLayoutPattern',
			'params' => array()
		),
		'params' => array(
			'file'   => 'debug.log',
			'append' => true
		)
	);

	private static $echoConfig = array(
		'class' => 'LoggerAppenderEcho',
		'layout' => array(
			'class' => 'LoggerLayoutPattern',
			'params' => array()
		),
		'params' => array(
			'htmlLineBreaks' => 'true',
		)
	);

	private static $generalConfig = array(
		'rootLogger' => array(
			'appenders' => array(),
		),
		'appenders' => array()
	);

	 /**
         * Get default absolute path to log file
         *
         * @return string
         */
	public static function getDefaultLogPath() {
		return NEXT_AD_INT_PATH . self::RELATIVE_LOG_PATH;
	}

	/**
	 * Create default config
	 *
	 * @param bool $useFile
	 * @param bool $useEcho
	 * @param string $path
	 * @return array
	 */
	public static function createDefaultConfiguration($useFile, $useEcho, $path = '') {
		if (!$path) {
			$path = self::getDefaultLogPath();
		}

		$config = self::$generalConfig;

		if ($useFile) {
			$config['rootLogger']['appenders'][] = 'file';
			$config['appenders']['file'] = self::$fileConfig;

            // set the conversionPattern
            $pattern = apply_filters(NextADInt_Core_Logger::FILE_CONVERSION_PATTERN_FILTER, NEXT_AD_INT_FILE_CONVERSION_PATTERN);
            $config['appenders']['file']['layout']['params']['conversionPattern'] = $pattern;

			$config['appenders']['file']['params']['file'] = $path;
		}

		if ($useEcho) {
			$config['rootLogger']['appenders'][] = 'echo';
			$config['appenders']['echo'] = self::$echoConfig;

            // set the conversionPattern
            $pattern = apply_filters(NextADInt_Core_Logger::TABLE_CONVERSION_PATTERN_FILTER, NEXT_AD_INT_TABLE_CONVERSION_PATTERN);
            $config['appenders']['echo']['layout']['params']['conversionPattern'] = $pattern;
		}

		return $config;
	}

	/**
	 * Enable file logging but disable screen logging
	 */
	public static function logMessages($customPath = '')
	{
		Logger::resetConfiguration();

		if (self::$logging) {
			if ($customPath && ($customPath !== self::getDefaultLogPath())) {
				Logger::configure(self::createDefaultConfiguration(true, false, $customPath));
			} else {
				Logger::configure(self::createDefaultConfiguration(true, false));
			}
		}
	}

	/**
	 * Disable file logging but enable screen logging
	 */
	public static function displayMessages()
	{
		Logger::resetConfiguration();

		if (self::$logging) {
			Logger::configure(self::createDefaultConfiguration(false, true));
		}
	}

	/**
	 * Enable file and screen logging
	 */
	public static function displayAndLogMessages($customPath = '')
	{
		Logger::resetConfiguration();

		if (self::$logging) {
			if ($customPath && ($customPath !== self::getDefaultLogPath())) {
				Logger::configure(self::createDefaultConfiguration(true, true, $customPath));
			} else {
				Logger::configure(self::createDefaultConfiguration(true, true));
			}
		}
	}

	/**
	 * Disable outputs
	 */
	public static function logNothing()
	{
		Logger::resetConfiguration();
		Logger::configure(array());
	}

	/**
	 * Disable logging
	 */
	public static function disableLogging() {
		self::logNothing();
		self::$logging = false;
	}

	/**
	 * Enable default logging
	 */
	public static function enableLogging() {
		self::logMessages();
		self::$logging = true;
	}

	/**
	 * Set log level
	 * @param string $level
	 */
	public static function setLevel($level)
	{
		$root = Logger::getRootLogger();
		$root->setLevel($level);
	}

	/**
	 * Helper method to create a string representaion of an object or array of objects
	 * @param array|object $object
	 * @return string
	 */
	public static function toString($object) {
		if (is_array($object)) {
			$r = array();

			foreach ($object as $element) {
				$r[] = (string)$element;
			}

			return implode(", ", $r);
		}

		return $object;
	}

	/**
	 * @param string $level
	 * @return bool
	 */
	public static function equalLevel($level) {
		$currentLevel = Logger::getRootLogger()->getLevel();
		return $currentLevel->equals($level);
	}
}
