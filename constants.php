<?php

if (!defined('ABSPATH')) {
    die('Access denied.');
}

define('NEXT_AD_INT_PLUGIN_VERSION', '2.0');
define('NEXT_AD_INT_PREFIX', 'next_ad_int_');
define('NEXT_AD_INT_PATH', dirname(__FILE__));
define('NEXT_AD_INT_URL', plugins_url('', __FILE__));
define('NEXT_AD_INT_I18N', 'next_ad_int');
define('NEXT_AD_INT_PLUGIN_FILE', plugin_basename(NEXT_AD_INT_PATH . '/index.php'));