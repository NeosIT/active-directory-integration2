<?php
if (!defined('ABSPATH')) {
    die('Access denied.');
}

if (class_exists('NextADInt_Core_Util_Internal_WordPress')) {
    return;
}

/**
 * NextADInt_Core_Util_Internal_Native contains wrapper methods which delegates to WordPress functions.
 * This is required to keep this plugin compatible with older WordPress versions.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 *
 * @access public
 */
class NextADInt_Core_Util_Internal_WordPress
{
    private function __clone()
    {
    }

    /**
     * Delegate to either get_sites (for >= 4.6) or wp_get_sites (for < 4.6).
     *
     * @see wp_get_sites, get_sites
     *
     * @return mixed
     */
    public static function getSites()
    {
        global $wp_version;

        if ( version_compare( $wp_version, '4.6', '>=')) {
            return get_sites();
        }

        return wp_get_sites();
    }
}