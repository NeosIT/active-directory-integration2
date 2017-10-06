<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Ui_Table_ProfileAssignment')) {
	return;
}

if (!class_exists('WP_MS_Sites_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-ms-sites-list-table.php');
}

/**
 * NextADInt_Multisite_Ui_Table_ProfileAssignment displays the table with all blogs and their assigned ADI profile.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Ui_Table_ProfileAssignment extends WP_MS_Sites_List_Table
{
	const NEXT_AD_INT_SITE_NAME_COLUMN = 'next-ad-int-site-name';

	/**
	 * Multisite_Ui_Table_BlogTable constructor.
	 *
	 * @param array $args
	 */
	public function __construct(array $args = array())
	{
		parent::__construct($args);

		$this->status_list = array();
	}

	/**
	 * Register any hooks
	 */
	public function register() {
		add_action('manage_sites_custom_column', array($this, 'addContent'), 1, 2);
	}

	/**
	 * Add the ADI profile for the current blog
	 *
	 * @param string $columnName
	 * @param int    $blogId
	 *
	 * @return string
	 */
	public function addContent($columnName, $blogId)
	{
		if ($columnName == self::NEXT_AD_INT_SITE_NAME_COLUMN) {
			$details = NextADInt_Core_Util_Internal_WordPress::getSite($blogId);

			if ($details && !empty($details->blogname)) {
				echo $details->blogname;

				return;
			}

			echo "<em>" . __('Cannot find valid site name.', 'next-active-directory-integration') . '</em>';
		}
	}

	/**
	 * @return array
	 */
	public function get_columns()
	{
		$sites_columns = array(
			'cb'                        => '<input type="checkbox" />',
			self::NEXT_AD_INT_SITE_NAME_COLUMN 	=> __('Site Name', 'next-active-directory-integration'),
			'blogname'                  => __('URL', 'next-active-directory-integration'),
		);

		/**
		 * Filter the displayed site columns in Sites list table.
		 *
		 * @since MU
		 *
		 * @param array $sites_columns An array of displayed site columns. Default 'cb',
		 *                             'blogname', 'lastupdated', 'registered', 'users'.
		 */
		return apply_filters('wpmu_blogs_columns', $sites_columns);
	}

	/**
	 *
	 * @global string $s
	 * @global string $mode
	 * @global wpdb   $wpdb
	 */
	public function prepare_items()
	{
		global $s, $mode, $wpdb;

		$current_site = get_current_site();

		$mode = (empty($_REQUEST['mode'])) ? 'list' : $_REQUEST['mode'];

		$per_page = $this->get_items_per_page('sites_network_per_page');

		$pagenum = $this->get_pagenum();

		$id = isset($_REQUEST['id']) ? wp_unslash(trim($_REQUEST['id'])) : '';
		$s = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';
		$wild = '';
		if (false !== strpos($s, '*')) {
			$wild = '%';
			$s = trim($s, '*');
		}

		/*
		 * If the network is large and a search is not being performed, show only
		 * the latest blogs with no paging in order to avoid expensive count queries.
		 */
		if (!$s && wp_is_large_network()) {
			if (!isset($_REQUEST['orderby'])) {
				$_GET['orderby'] = $_REQUEST['orderby'] = '';
			}
			if (!isset($_REQUEST['order'])) {
				$_GET['order'] = $_REQUEST['order'] = 'DESC';
			}
		}

		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";

		if (empty($s)) {
			// Nothing to do.
		} elseif (preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $s)
			|| preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.?$/', $s)
			|| preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.?$/', $s)
			|| preg_match('/^[0-9]{1,3}\.$/', $s)
		) {
			// IPv4 address
			$sql = $wpdb->prepare("SELECT blog_id FROM {$wpdb->registration_log} WHERE {$wpdb->registration_log}.IP LIKE %s",
				$wpdb->esc_like($s) . $wild);
			$reg_blog_ids = $wpdb->get_col($sql);

			if (!$reg_blog_ids) {
				$reg_blog_ids = array(0);
			}

			$query = "SELECT *
				FROM {$wpdb->blogs}
				WHERE site_id = '{$wpdb->siteid}'
				AND {$wpdb->blogs}.blog_id IN (" . implode(', ', $reg_blog_ids) . ")";
		} else {
			if (is_numeric($s) && empty($wild)) {
				$query .= $wpdb->prepare(" AND ( {$wpdb->blogs}.blog_id = %s )", $s);
			} elseif (is_subdomain_install()) {
				$blog_s = str_replace('.' . $current_site->domain, '', $s);
				$blog_s = $wpdb->esc_like($blog_s) . $wild . $wpdb->esc_like('.' . $current_site->domain);
				$query .= $wpdb->prepare(" AND ( {$wpdb->blogs}.domain LIKE %s ) ", $blog_s);
			} else {
				if ($s != trim('/', $current_site->path)) {
					$blog_s = $wpdb->esc_like($current_site->path . $s) . $wild . $wpdb->esc_like('/');
				} else {
					$blog_s = $wpdb->esc_like($s);
				}
				$query .= $wpdb->prepare(" AND  ( {$wpdb->blogs}.path LIKE %s )", $blog_s);
			}
		}

		if (!empty($id)) {
			$ids = explode(',', $id);
			$cleanIds = array_map(function($id) {
				return wp_unslash(trim($id));
			}, $ids);

			$query .= $wpdb->prepare(" AND {$wpdb->blogs}.blog_id IN (" . implode(', ', $cleanIds) . ")", null);
		}

		$order_by = isset($_REQUEST['orderby']) ? $_REQUEST['orderby'] : '';
		if ($order_by === 'registered') {
			$query .= ' ORDER BY registered ';
		} elseif ($order_by === 'lastupdated') {
			$query .= ' ORDER BY last_updated ';
		} elseif ($order_by === 'blogname') {
			if (is_subdomain_install()) {
				$query .= ' ORDER BY domain ';
			} else {
				$query .= ' ORDER BY path ';
			}
		} elseif ($order_by === 'blog_id') {
			$query .= ' ORDER BY blog_id ';
		} else {
			$order_by = null;
		}

		if (isset($order_by)) {
			$order = (isset($_REQUEST['order']) && 'DESC' === strtoupper($_REQUEST['order'])) ? "DESC" : "ASC";
			$query .= $order;
		}

		// Don't do an unbounded count on large networks
		if (!wp_is_large_network()) {
			$total = $wpdb->get_var(str_replace('SELECT *', 'SELECT COUNT( blog_id )', $query));
		}

		$query .= " LIMIT " . intval(($pagenum - 1) * $per_page) . ", " . intval($per_page);
		$this->items = $wpdb->get_results($query, ARRAY_A);

        // after d242446e599ce79a61ee6180613b4ffcf83e92c0 we have to use WP_Site instead of an array
        // ADI-335
        global $wp_version;
        if ( version_compare( $wp_version, '4.6-alpha-37736', '>=')) {
            foreach ($this->items as $key => $value) {
                // ADI-336
                $this->items[$key] = WP_Site::get_instance($value['blog_id']);
            }
        }

        if (wp_is_large_network()) {
			$total = count($this->items);
		}

		$this->set_pagination_args(array(
			'total_items' => $total,
			'per_page'    => $per_page,
		));
	}

	/**
	 * Check for the amount of ids given and set it to the count.
	 *
	 * @param string $option
	 * @param int    $default
	 *
	 * @return int
	 */
	protected function get_items_per_page($option, $default = 20)
	{
		if (!empty($_REQUEST['id'])) {
			$id = explode(',', $_REQUEST['id']);
			$idCount = count($id);

			if (wp_is_large_network() && $idCount > 10000) {
				return 9999;
			}

			return $idCount;
		}

		return parent::get_items_per_page($option, $default);
	}

	/**
	 * Override the bulk actions from the {@see WP_MS_Sites_List_Table} b/c we don't need them.
	 *
	 * @return array
	 */
	protected function get_bulk_actions()
	{
		return array();
	}

	/**
	 * Override the default column_cb which prevents the main site checkbox to be shown.
	 *
	 * @param array $blog
	 */
	public function column_cb($blog)
	{
		$blogname = untrailingslashit($blog['domain'] . $blog['path']);

		echo sprintf('<label class="screen-reader-text" for="blog_%d">%s</label>', $blog['blog_id'],
			sprintf(__('Select %s', 'next-active-directory-integration'), $blogname)
		);

		echo sprintf('<input type="checkbox" id="blog_%d" name="allblogs[]" value="%d" />', $blog['blog_id'],
			esc_attr($blog['blog_id']));
	}
}