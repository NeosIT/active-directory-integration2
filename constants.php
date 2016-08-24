<?php

if (!defined('ABSPATH')) {
    die('Access denied.');
}

define('ADI_PLUGIN_VERSION', '2.0');
define('ADI_PREFIX', 'adi2_');
define('ADI_PATH', dirname(__FILE__));
define('ADI_URL', plugins_url('', __FILE__));
define('ADI_I18N', 'ad-integration-2.0');
define('ADI_PLUGIN_NAME', 'active-directory-integration2');
define('ADI_PLUGIN_FILE', ADI_PLUGIN_NAME . '/index.php');