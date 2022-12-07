<?php

namespace Dreitier\Nadi\Log;

use Dreitier\Nadi\Vendor\Monolog\Registry;
use Dreitier\Nadi\Vendor\Monolog\Handler\NullHandler;
use Dreitier\Nadi\Vendor\Monolog\Processor\IntrospectionProcessor;
use Dreitier\Nadi\Vendor\Monolog\Handler\StreamHandler;
use Dreitier\Nadi\Vendor\Monolog\Formatter\LineFormatter;
use Dreitier\Util\Logger\Handlers\FrontendLogHandler;

/**
 * Logger for NADI
 *
 * Internally, monolog is used.
 *
 * @author Danny Meißner <dme@neos-it.de>
 * @access public
 */
class NadiLog
{
	/* @var Logger */
	private static $logger;

	/**
	 * @var string
	 */
	private static $defaultPath = NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH . '/logs/';

	public const MAIN_LOGGER = 'nadiMainLogger';

	public const TEST_LOGGER = 'nadiTestLogger';

	/**
	 * @var bool
	 */
	public static $isTestmode = false;

	public static function getInstance()
	{
		if (self::$logger == null) {
			self::createLogger();
		}

		return Registry::getInstance(self::$isTestmode ? self::TEST_LOGGER : self::MAIN_LOGGER);
	}

	/**
	 * Create a logger instance with a null appender
	 */
	public static function createLogger()
	{
		// If we are running tests disable the logger to prevent console output on console / jenkins.
		if (self::$isTestmode) {
			self::setUpTestLogger();
			return;
		}

		// We are pushing a NullHandler in order to catch messages thrown before the streamHandler is initialized
		$nullHandler = new NullHandler(Logger::DEBUG);

		if (null != self::$logger) {
			return;
		}

		self::$logger = new Logger(self::MAIN_LOGGER);

		self::$logger->pushHandler($nullHandler);

		// Adding Logger to registry so we are able to check globally if logger exists
		Registry::addLogger(self::$logger);
	}

	/**
	 * @param $loggingEnabled
	 * @param string $customPath
	 */
	public static function initializeLogger($loggingEnabled, $customPath = '')
	{
		// Disable the NullHandler so we can start to configure the logger to log messages
		self::disableNullHandler();

		// Create a frontendOnly Logger if logging is not enabled in NADI options
		if (!$loggingEnabled) {
			self::createFrontendOnlyLogger();
			return;
		}

		$logPath = self::$defaultPath;

		if ($customPath !== '') {
			$logPath = $customPath;
		}

		// Check permission to Logging path before we register a stream appender to prevent exception thrown for trying to access file without permission
		if (!self::hasWritingPermission($logPath)) {

			error_log("Could not write to nadi-debug.log file in " . $logPath . " . Please check permissions.");

			// Create a frontend only Logger due missing permissions to write to file
			self::createFrontendOnlyLogger();
			return;
		}

		// Create Handlers
		$streamHandler = self::createStreamHandler($logPath);
		$frontendLogHandler = self::createFrontendHandler();

		// Create Processor to collect information like className, methodName, line... etc
		$processor = new IntrospectionProcessor(Logger::DEBUG);

		// Push Handlers
		self::$logger->pushHandler($streamHandler);
		self::$logger->pushHandler($frontendLogHandler);

		// Push Processors
		self::$logger->pushProcessor($processor);
	}

	/**
	 * Disabled the NullHandler in our Logger which was added on Logger create to catch all messages before the streamHandler was configured and added.
	 */
	private static function disableNullHandler()
	{
		if (!Registry::hasLogger(self::MAIN_LOGGER)) {
			return;
		}

		if (null == self::$logger) {
			return;
		}

		$nullHandler = self::getNullHandler(self::$logger->getHandlers());

		if (null == $nullHandler) {
			return;
		}

		$nullHandler->close();
	}

	/**
	 * @param $loggingPath
	 * @return StreamHandler
	 * @throws \Exception
	 */
	public static function createStreamHandler($loggingPath)
	{
		// Create Handlers
		$streamHandler = new StreamHandler($loggingPath . 'nadi-debug.log', Logger::DEBUG);

		// Formats
		$outputFile = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%\n";

		// Create Formatter
		$formatterFile = new LineFormatter($outputFile);

		//Set Formatter
		$streamHandler->setFormatter($formatterFile);

		return $streamHandler;
	}

	/**
	 * Create a new null handler
	 *
	 * @param int|string $level
	 * @return NullHandler
	 */
	public static function createNullHandler($level = Logger::DEBUG)
	{
		return new NullHandler($level);
	}

	/**
	 * @return FrontendLogHandler
	 */
	public static function createFrontendHandler()
	{
		// Create Handlers
		$frontendHandler = new FrontendLogHandler(Logger::DEBUG);

		// Formats
		$outputFrontend = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%";

		// Create Formatter
		$formatterFrontend = new LineFormatter($outputFrontend);

		//Set Formatter
		$frontendHandler->setFormatter($formatterFrontend);

		return $frontendHandler;
	}

	/**
	 * Checks if the logger can log to the in the streamHandler configured path if file does not exist, create it
	 */
	private static function hasWritingPermission($pathToFile)
	{
		return @touch($pathToFile . 'nadi-debug.log');
	}

	/**
	 * Return the global Logger instance
	 *
	 * @return \Monolog\Logger
	 */
	public static function getLogger()
	{
		if (null == self::$logger) {
			self::createLogger();
		}

		return self::$logger;
	}


	/**
	 * @return \Monolog\Handler\HandlerInterface[]
	 */
	private static function getHandlers()
	{
		return self::$logger->getHandlers();
	}

	/**
	 * @param $handlers
	 * @return FrontendLogHandler
	 */
	private static function getFrontendHandler($handlers)
	{
		foreach ($handlers as $handler) {
			if (is_a($handler, FrontendLogHandler::class)) {
				return $handler;
			}
		}

		return null;
	}

	/**
	 * @param $handlers
	 * @return FrontendLogHandler
	 */
	private static function getStreamHandler($handlers)
	{
		foreach ($handlers as $handler) {
			if (is_a($handler, StreamHandler::class)) {
				return $handler;
			}
		}

		return null;
	}

	/**
	 * @param $handlers
	 * @return FrontendLogHandler
	 */
	private static function getNullHandler($handlers)
	{
		foreach ($handlers as $handler) {
			if (is_a($handler, NullHandler::class)) {
				return $handler;
			}
		}

		return null;
	}

	/**
	 * Enables the log buffer inside the frontend handler
	 */
	public static function enableFrontendHandler()
	{
		$handlers = self::getHandlers();
		$frontendHandler = self::getFrontendHandler($handlers);

		if (is_object($frontendHandler)) {
			$frontendHandler->enable();
		}
	}

	/**
	 * Disables the log buffer inside the frontend handler
	 */
	public static function disableFrontendHandler()
	{
		$handlers = self::getHandlers();
		$frontendHandler = self::getFrontendHandler($handlers);

		if (is_object($frontendHandler)) {
			$frontendHandler->disable();
		}
	}

	/**
	 * Returns the buffered log for Frontend rendering
	 */
	public static function getBufferedLog()
	{
		$handlers = self::getHandlers();
		$frontendHandler = self::getFrontendHandler($handlers);

		if (is_object($frontendHandler)) {
			return $frontendHandler->getBufferedLog();
		}

		return null;
	}

	/**
	 * Helper method to create a string representaion of an object or array of objects
	 * @param array|object $object
	 * @return string
	 */
	public static function toString($object)
	{
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
	 * Set up a testLogger with a NullHandler to throw away all occurring messages.
	 */
	public static function setUpTestLogger()
	{
		// Check if test logger already exists before setting up a new one.
		if (Registry::hasLogger(self::TEST_LOGGER)) {
			return;
		}

		// Create TestLogger
		self::$logger = new Logger(self::TEST_LOGGER);

		// Create Handlers
		$nullHandler = new NullHandler();

		// Push Handlers
		self::$logger->pushHandler($nullHandler);

		// Adding Logger to registry so we are able to check globally if logger exists
		Registry::addLogger(self::$logger);
	}

	/**
	 * If NADI does not have writing permission to the log path/file log to frontend only.
	 */
	private static function createFrontendOnlyLogger()
	{
		// Remove old Logger because we do not want to log unnecessary information to the php-error log
		if (Registry::hasLogger(self::MAIN_LOGGER)) {
			Registry::removeLogger(self::MAIN_LOGGER);
		}

		// Creating new Frontend Only Logger and set it as main logger
		self::$logger = new Logger(self::MAIN_LOGGER);

		// Create Handlers
		$frontendLogHandler = self::createFrontendHandler();

		// Create Processor to collect information like className, methodName, line... etc
		$processor = new IntrospectionProcessor(Logger::DEBUG);

		// Push Handlers
		self::$logger->pushHandler($frontendLogHandler);

		// Push Processors
		self::$logger->pushProcessor($processor);

		// Adding Logger to registry so we are able to check globally if logger exists
		Registry::addLogger(self::$logger);
	}
}
