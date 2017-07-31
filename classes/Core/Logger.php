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

	public static $isTestmode = false;

	/**
	 * @param $className
	 * @param string $customPath // TODO reimplement customPath logging and Logging to php error log / WP_Error log
	 */
	public static function initializeLogger($customPath = '')
	{

		if (NextADInt_Core_Logger::$isTestmode) {
			NextADInt_Core_Logger::setUpTestLogger();
			return;
		}

		if (null != NextADInt_Core_Logger::$logger) {
			return;
		}

		NextADInt_Core_Logger::$logger = new Monolog\Logger('nadiMainLogger');

		// Create Handlers
		$stream = new \Monolog\Handler\StreamHandler(NEXT_AD_INT_PATH . '/logs/debug.log', Monolog\Logger::DEBUG);
		$frontendLogHandler = new NextADInt_Core_Logger_Handlers_FrontendLogHandler(\Monolog\Logger::DEBUG);

		// Formats
		$outputFile = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%\n";
		$outputFrontend = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%";

		// Create Formatter
		$formatterFile = new \Monolog\Formatter\LineFormatter($outputFile);
		$formatterFrontend = new \Monolog\Formatter\LineFormatter($outputFrontend);

		//Set Formatter
		$stream->setFormatter($formatterFile);
		$frontendLogHandler->setFormatter($formatterFrontend);

		// Create Processor to collect information like className, methodName, line... etc
		$processor = new Monolog\Processor\IntrospectionProcessor(Monolog\Logger::DEBUG);

		// Push Handlers
		NextADInt_Core_Logger::$logger->pushHandler($stream);
		NextADInt_Core_Logger::$logger->pushHandler($frontendLogHandler);

		// Push Processors
		NextADInt_Core_Logger::$logger->pushProcessor($processor);

		// Adding Logger to registry so we are able to check globally if logger exists
		\Monolog\Registry::addLogger(NextADInt_Core_Logger::$logger);

		// If exception is thrown we do not have writing permission to the log directory nor file. In that case we create a new logger and log to the php error log
		try {
			NextADInt_Core_Logger::$logger->debug("Checking writing permission for log path successfully.");
		} catch (Exception $ex) {
			$errorMessage = $ex->getMessage();
			error_log($errorMessage);

			// Create a frontend only Logger
			NextADInt_Core_Logger::createFrontendOnlyLogger();
		}
	}

	/**
	 * Return the global Logger instance
	 *
	 * @return \Monolog\Logger
	 */
	public static function getLogger()
	{

		if (null == NextADInt_Core_Logger::$logger) {
			NextADInt_Core_Logger::initializeLogger();
		}

		return NextADInt_Core_Logger::$logger;
	}


	/**
	 * @return \Monolog\Handler\HandlerInterface[]
	 */
	private function getHandlers() {
		return NextADInt_Core_Logger::$logger->getHandlers();
	}

	/**
	 * @param $handlers
	 * @return NextADInt_Core_Logger_Handlers_FrontendLogHandler
	 */
	private function getFrontendHandler($handlers) {
		foreach ($handlers as $handler) {
			if (is_a($handler, 'NextADInt_Core_Logger_Handlers_FrontendLogHandler')) {
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
		$frontendHandler->enable();
	}

	/**
	 * Disables the log buffer inside the frontend handler
	 */
	public static function disableFrontendHandler() {
		$handlers = NextADInt_Core_Logger::getHandlers();
		$frontendHandler = NextADInt_Core_Logger::getFrontendHandler($handlers); // TODO Add Exception Handling for Frontend Handler stuff
		$frontendHandler->disable();
	}

	/**
	 * Returns the buffered log for Frontend rendering
	 */
	public static function getBufferedLog() {
		$handlers = NextADInt_Core_Logger::getHandlers();
		$frontendHandler = NextADInt_Core_Logger::getFrontendHandler($handlers);
		return $frontendHandler->getBufferedLog();
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

	//
	public static function setUpTestLogger() {

		// Check if test logger already exists before setting up a new one.
		if (\Monolog\Registry::hasLogger('nadiTestLogger')) {
			return;
		}

		// Create TestLogger
		NextADInt_Core_Logger::$logger = new Monolog\Logger('nadiTestLogger');

		// Create Handlers // Todo Reimplement Custom Path
		$nullHandler = new \Monolog\Handler\NullHandler();
		$frontendLogHandler = new NextADInt_Core_Logger_Handlers_FrontendLogHandler(\Monolog\Logger::DEBUG);

		// Formats
		$outputFrontend = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%";

		// Create Formatter
		$formatterFrontend = new \Monolog\Formatter\LineFormatter($outputFrontend);

		//Set Formatter
		$frontendLogHandler->setFormatter($formatterFrontend);

		// Create Processor to collect information like className, methodName, line... etc
		$processor = new Monolog\Processor\IntrospectionProcessor(Monolog\Logger::DEBUG);

		// Push Handlers
		NextADInt_Core_Logger::$logger->pushHandler($nullHandler);
		NextADInt_Core_Logger::$logger->pushHandler($frontendLogHandler);

		// Push Processors
		NextADInt_Core_Logger::$logger->pushProcessor($processor);

		// Adding Logger to registry so we are able to check globally if logger exists
		\Monolog\Registry::addLogger(NextADInt_Core_Logger::$logger);
	}

	private static function createFrontendOnlyLogger() {
		// Remove old Logger because we do not want to log unnecessary information to the php-error log
		if (\Monolog\Registry::hasLogger('nadiMainLogger')) {
			\Monolog\Registry::removeLogger('nadiMainLogger');
		}

		// Creating new Frontend Only Logger and set it as main logger
			NextADInt_Core_Logger::$logger = new Monolog\Logger('nadiMainLogger');

		// Create Handlers
		$frontendLogHandler = new NextADInt_Core_Logger_Handlers_FrontendLogHandler(\Monolog\Logger::DEBUG);

		// Formats
		$outputFrontend = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%";

		// Create Formatter
		$formatterFrontend = new \Monolog\Formatter\LineFormatter($outputFrontend);

		//Set Formatter
		$frontendLogHandler->setFormatter($formatterFrontend);

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
