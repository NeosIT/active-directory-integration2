<?php

namespace Dreitier\WordPress;

/**
 * WordPressSiteRepository contains wrapper methods which delegates to WordPress functions.
 * This is required to keep this plugin compatible with older WordPress versions.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 *
 * @access public
 */
class WordPressSiteRepository
{
	private function __clone()
	{
	}

	/**
	 * Delegate to either get_sites (for >= 4.6) or wp_get_sites (for < 4.6).
	 *
	 * @return mixed
	 * @see wp_get_sites, get_sites
	 *
	 */
	public static function getSites($param = array())
	{
		global $wp_version;

		if (version_compare($wp_version, '4.6', '>=')) {
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
	 * @param null|int $blogId
	 * @return WP_Site
	 * @see get_site, get_blog_details
	 * @issue ADI-419
	 */
	public static function getSite($blogId = null)
	{
		global $wp_version;

		if (version_compare($wp_version, '4.7', '>=')) {
			return get_site($blogId);
		}

		return get_blog_details($blogId);
	}
}