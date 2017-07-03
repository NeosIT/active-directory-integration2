<?php

if ( ! defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_LoggerFactory')) {
	return;
}

/**
 * Created by PhpStorm.
 * User: dme@ad.neos-it.de
 * Date: 7/3/17
 * Time: 12:58 PM
 */

class NextADInt_Core_LoggerFactory {

	public function _construct() {

	}

	public static function getDefaultLogger($className) {

		$logger = new Monolog\Logger($className);

		$stream = new \Monolog\Handler\StreamHandler(NEXT_AD_INT_PATH . '/logs/debug.log', Monolog\Logger::DEBUG);

		$output = "%datetime% [%level_name%] %extra.class%::%extra.function% [line %extra.line%] %message%\n";
		$formatter = new \Monolog\Formatter\LineFormatter($output);
		$stream->setFormatter($formatter);

		$processor = new Monolog\Processor\IntrospectionProcessor(Monolog\Logger::DEBUG);

		$logger->pushHandler($stream);
		$logger->pushProcessor($processor);

		return $logger;
	}


}