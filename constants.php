<?php

if (!defined('ABSPATH')) {
    die('Access denied.');
}

define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX', 'next_ad_int_');
define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_VERSION', '3.0');
define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH', dirname(__FILE__));
define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_FILE', NEXT_ACTIVE_DIRECTORY_INTEGRATION_PLUGIN_PATH . '/index.php');
// see https://logging.apache.org/log4php/docs/layouts/pattern.html for creating own conversion pattern
define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_FILE_CONVERSION_PATTERN', "%date{Y-m-d H:i:s} [%-5level] %class::%method [line %line] %msg %ex\r\n");
define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_ECHO_CONVERSION_PATTERN', '[%-5level] %msg %ex<br />');
define('NEXT_ACTIVE_DIRECTORY_INTEGRATION_TABLE_CONVERSION_PATTERN', '[%-5level]|%msg|%ex<br />');