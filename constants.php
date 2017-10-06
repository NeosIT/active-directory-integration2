<?php

if (!defined('ABSPATH')) {
    die('Access denied.');
}

define('NEXT_AD_INT_PLUGIN_VERSION', '2.0');
define('NEXT_AD_INT_PREFIX', 'next_ad_int_');
define('NEXT_AD_INT_PATH', dirname(__FILE__));
define('NEXT_AD_INT_URL', plugins_url('', __FILE__));
define('NEXT_AD_INT_PLUGIN_FILE', plugin_basename(NEXT_AD_INT_PATH . '/index.php'));
// see https://logging.apache.org/log4php/docs/layouts/pattern.html for creating own conversion pattern
define('NEXT_AD_INT_FILE_CONVERSION_PATTERN', "%date{Y-m-d H:i:s} [%-5level] %class::%method [line %line] %msg %ex\r\n");
define('NEXT_AD_INT_ECHO_CONVERSION_PATTERN', '[%-5level] %msg %ex<br />');
define('NEXT_AD_INT_TABLE_CONVERSION_PATTERN', '[%-5level]|%msg|%ex<br />');