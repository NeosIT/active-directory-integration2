<?php

if ( ! defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Logger')) {
	return;
}


/**
 * Core_Logger Simple logging fascade
 *
 * Internally, log4php is used.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Core_Logger
{
	const LOG_PATH = 'logs/debug.log';

	private static $logging = true;

	private static $fileConfig = array(
		'class'  => 'LoggerAppenderFile',
		'layout' => array(
			'class' => 'LoggerLayoutPattern',
			'params' => array(
				'conversionPattern' => "[%-5level] %class::%method [line %line] %msg %ex\r\n"
			)
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
			'params' => array(
				'conversionPattern' => '[%-5level] %msg %ex<br />'
			)
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
	 * Create default config
	 *
	 * @param bool $useFile
	 * @param bool $useEcho
	 * @return array
	 */
	public static function createDefaultConfiguration($useFile, $useEcho) {
		$config = self::$generalConfig;

		if ($useFile) {
			$config['rootLogger']['appenders'][] = 'file';
			$config['appenders']['file'] = self::$fileConfig;
			$config['appenders']['file']['params']['file'] = NEXT_AD_INT_PATH . '/' . self::LOG_PATH;
		}

		if ($useEcho) {
			$config['rootLogger']['appenders'][] = 'echo';
			$config['appenders']['echo'] = self::$echoConfig;
		}

		return $config;
	}

	/**
	 * Enable file logging but disable screen logging
	 */
	public static function logMessages()
	{
		Logger::resetConfiguration();

		if (self::$logging) {
			Logger::configure(self::createDefaultConfiguration(true, false));
		}
	}

	/**
	 * Enable file and screen logging
	 */
	public static function displayAndLogMessages()
	{
		Logger::resetConfiguration();

		if (self::$logging) {
			Logger::configure(self::createDefaultConfiguration(true, true));
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
