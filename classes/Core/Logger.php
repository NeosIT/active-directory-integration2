<?php


if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Logger')) {
	return;
}

/**
 * NextADInt_Core_Logger Simple logging fascade
 *
 * Internally, monolog is used.
 *
 * @author Danny Meißner <dme@neos-it.de>
 * @access public
 */
class NextADInt_Core_Logger
{
	/* @var \Monolog\Logger */
	private static $logger;

	/**
	 * @var string
	 */
	private static $defaultPath = NEXT_AD_INT_PATH . '/logs/';

	/**
	 * @var bool
	 */
	public static $isTestmode = false;

	/**
	 * Create a logger instance with a null appender
	 */
	public static function createLogger()
	{
		// If we are are running tests disable the logger to prevent console output on console / jenkins.
		if (NextADInt_Core_Logger::$isTestmode) {
			NextADInt_Core_Logger::setUpTestLogger();
			return;
		}

		// We are pushing a NullHandler in order to catch messages thrown before the streamHandler is initialized
		$nullHandler = new \Monolog\Handler\NullHandler(Monolog\Logger::DEBUG);

		if (null != NextADInt_Core_Logger::$logger) {
			return;
		}

		NextADInt_Core_Logger::$logger = new Monolog\Logger('nadiMainLogger');

		NextADInt_Core_Logger::$logger->pushHandler($nullHandler);

		// Adding Logger to registry so we are able to check globally if logger exists
		\Monolog\Registry::addLogger(NextADInt_Core_Logger::$logger);
	}

	/**
	 * @param $loggingEnabled
	 * @param string $customPath
	 */
	public static function initializeLogger($loggingEnabled, $customPath = '')
	{
		// Disable the NullHandler so we can start to configure the logger to log messages
		NextADInt_Core_Logger::disableNullHandler();

		// Create a frontendOnly Logger if logging is not enabled in NADI options
		if (!$loggingEnabled) {
			NextADInt_Core_Logger::createFrontendOnlyLogger();
			return;
		}

		$logPath = NextADInt_Core_Logger::$defaultPath;

		if ($customPath !== '') {
			$logPath = $customPath;
		}

		// Check permission to Logging path before we register a stream appender to prevent exception thrown for trying to access file without permission
		if (!NextADInt_Core_Logger::hasWritingPermission($logPath)) {

			error_log("Could not write to nadi-debug.log file in " . $logPath . " . Please check permissions.");

			// Create a frontend only Logger due missing permissions to write to file
			NextADInt_Core_Logger::createFrontendOnlyLogger();
			return;
		}

		// Create Handlers
		$streamHandler = NextADInt_Core_Logger::createStreamHandler($logPath);
		$frontendLogHandler = NextADInt_Core_Logger::createFrontendHandler();

		// Create Processor to collect information like className, methodName, line... etc
		$processor = new Monolog\Processor\IntrospectionProcessor(Monolog\Logger::DEBUG);

		// Push Handlers
		NextADInt_Core_Logger::$logger->pushHandler($streamHandler);
		NextADInt_Core_Logger::$logger->pushHandler($frontendLogHandler);

		// Push Processors
		NextADInt_Core_Logger::$logger->pushProcessor($processor);
	}


	/**
	 * Disabled the NullHandler in our Logger which was added on Logger create to catch all messages before the streamHandler was configured and added.
	 */
	private static function disableNullHandler()
	{
		if (!\Monolog\Registry::hasLogger('nadiTestLogger')) {
			return;
		}

		if (null == NextADInt_Core_Logger::$logger) {
			return;
		}

		$nullHandler = NextADInt_Core_Logger::getNullHandler(NextADInt_Core_Logger::$logger->getHandlers());

		if (null == $nullHandler) {
			return;
		}

		$nullHandler->close();
	}

	/**
	 * @param $loggingPath
	 * @return \Monolog\Handler\StreamHandler
	 */
	private static function createStreamHandler($loggingPath)
	{
		// Create Handlers
		$streamHandler = new \Monolog\Handler\StreamHandler($loggingPath . 'nadi-debug.log', Monolog\Logger::DEBUG);

		// Formats
		$outputFile = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%\n";

		// Create Formatter
		$formatterFile = new \Monolog\Formatter\LineFormatter($outputFile);

		//Set Formatter
		$streamHandler->setFormatter($formatterFile);

		return $streamHandler;
	}

	/**
	 * @return NextADInt_Core_Logger_Handlers_FrontendLogHandler
	 */
	private static function createFrontendHandler()
	{
		// Create Handlers
		$frontendHandler = new NextADInt_Core_Logger_Handlers_FrontendLogHandler(\Monolog\Logger::DEBUG);

		// Formats
		$outputFrontend = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%";

		// Create Formatter
		$formatterFrontend = new \Monolog\Formatter\LineFormatter($outputFrontend);

		//Set Formatter
		$frontendHandler->setFormatter($formatterFrontend);

		return $frontendHandler;
	}

	/**
	 * Checks if the logger can log to the in the streamHandler configured path if file does not exist, create it
	 */
	private static function hasWritingPermission($pathToFile)
	{
		return touch($pathToFile . 'nadi-debug.log');
	}

	/**
	 * Return the global Logger instance
	 *
	 * @return \Monolog\Logger
	 */
	public static function getLogger()
	{
		if (null == NextADInt_Core_Logger::$logger) {
			NextADInt_Core_Logger::createLogger();
		}

		return NextADInt_Core_Logger::$logger;
	}


	/**
	 * @return \Monolog\Handler\HandlerInterface[]
	 */
	private static function getHandlers()
	{
		return NextADInt_Core_Logger::$logger->getHandlers();
	}

	/**
	 * @param $handlers
	 * @return NextADInt_Core_Logger_Handlers_FrontendLogHandler
	 */
	private static function getFrontendHandler($handlers)
	{
		foreach ($handlers as $handler) {
			if (is_a($handler, 'NextADInt_Core_Logger_Handlers_FrontendLogHandler')) {
				return $handler;
			}
		}

		return null;
	}

	/**
	 * @param $handlers
	 * @return NextADInt_Core_Logger_Handlers_FrontendLogHandler
	 */
	private static function getStreamHandler($handlers)
	{
		foreach ($handlers as $handler) {
			if (is_a($handler, \Monolog\Handler\StreamHandler::class)) {
				return $handler;
			}
		}

		return null;
	}

	/**
	 * @param $handlers
	 * @return NextADInt_Core_Logger_Handlers_FrontendLogHandler
	 */
	private static function getNullHandler($handlers)
	{
		foreach ($handlers as $handler) {
			if (is_a($handler, \Monolog\Handler\NullHandler::class)) {
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
		$handlers = NextADInt_Core_Logger::getHandlers();
		$frontendHandler = NextADInt_Core_Logger::getFrontendHandler($handlers);

		if (is_object($frontendHandler)) {
            $frontendHandler->enable();
        }
	}

	/**
	 * Disables the log buffer inside the frontend handler
	 */
	public static function disableFrontendHandler()
	{
		$handlers = NextADInt_Core_Logger::getHandlers();
		$frontendHandler = NextADInt_Core_Logger::getFrontendHandler($handlers);

		if (is_object($frontendHandler)) {
            $frontendHandler->disable();
        }
	}

	/**
	 * Returns the buffered log for Frontend rendering
	 */
	public static function getBufferedLog()
	{
		$handlers = NextADInt_Core_Logger::getHandlers();
		$frontendHandler = NextADInt_Core_Logger::getFrontendHandler($handlers);

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
		if (\Monolog\Registry::hasLogger('nadiTestLogger')) {
			return;
		}

		// Create TestLogger
		NextADInt_Core_Logger::$logger = new Monolog\Logger('nadiTestLogger');

		// Create Handlers
		$nullHandler = new \Monolog\Handler\NullHandler();

		// Push Handlers
		NextADInt_Core_Logger::$logger->pushHandler($nullHandler);

		// Adding Logger to registry so we are able to check globally if logger exists
		\Monolog\Registry::addLogger(NextADInt_Core_Logger::$logger);
	}

	/**
	 * If NADI does not have writing permission to the log path/file log to frontend only.
	 */
	private static function createFrontendOnlyLogger()
	{
		// Remove old Logger because we do not want to log unnecessary information to the php-error log
		if (\Monolog\Registry::hasLogger('nadiMainLogger')) {
			\Monolog\Registry::removeLogger('nadiMainLogger');
		}

		// Creating new Frontend Only Logger and set it as main logger
		NextADInt_Core_Logger::$logger = new Monolog\Logger('nadiMainLogger');

		// Create Handlers
		$frontendLogHandler = NextADInt_Core_Logger::createFrontendHandler();

		// Create Processor to collect information like className, methodName, line... etc
		$processor = new Monolog\Processor\IntrospectionProcessor(Monolog\Logger::DEBUG);

		// Push Handlers
		NextADInt_Core_Logger::$logger->pushHandler($frontendLogHandler);

		// Push Processors
		NextADInt_Core_Logger::$logger->pushProcessor($processor);

		// Adding Logger to registry so we are able to check globally if logger exists
		\Monolog\Registry::addLogger(NextADInt_Core_Logger::$logger);
	}
}
