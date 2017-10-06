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
    public static function getSites($param = array())
    {
        global $wp_version;

        if (version_compare( $wp_version, '4.6', '>=')) {
            $sites = get_sites($param);
            $oldStyle = array();

            // convert WP_Site to the old array style
            foreach ($sites as $site) {
                array_push($oldStyle, $site->to_array());
            }

            return $oldStyle;
        }

        return wp_get_sites($param);
    }

    /**
     * Delegate to either get_site (for >= 4.7) or get_blog_details (for < 4.7).
     *
     * @see get_site, get_blog_details
     * @issue ADI-419
     * @param null|int $blogId
     * @return WP_Site
     */
    public static function getSite($blogId = null) {
        global $wp_version;

        if (version_compare($wp_version, '4.7', '>=')) {
            return get_site($blogId);
        }

        return get_blog_details($blogId);
    }
}