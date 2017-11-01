<?php

if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Logger')) {
	return;
}

/**
 * This class prevents not updated premium extensions from crashing the WordPress environment while trying to access the
 * old Logger class which was removed with NADI 2.1.0 This class will be removed with NADI 2.1.1
 *
 *
 * @author: dme@ad.neos-it.de
 */


class Logger
{
	public static function getLogger($arg) {
		return new DummyLoggerInstance();
	}
}

class DummyLoggerInstance
{
	public function info($arg = null, $arg2 = null) {}
	public function debug($arg = null, $arg2 = null) {}
	public function warn($arg = null, $arg2 = null) {}
	public function error($arg = null, $arg2 = null) {}
}