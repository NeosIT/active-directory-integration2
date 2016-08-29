<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Persistence_WordPressRepository')) {
	return;
}

/**
 * NextADInt_Core_Persistence_WordPressRepository contains help methods for finding/persisting data to the database.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Core_Persistence_WordPressRepository
{

	/**
	 * Add WordPress base_prefix and NEXT_AD_INT_PREFIX to the table name and returns it.
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function getTableName($name)
	{
		global $wpdb;
		$basePrefix = $wpdb->base_prefix;

		return $basePrefix . NEXT_AD_INT_PREFIX . $name;
	}

	/**
	 * Wrapper for $wpdb query.
	 *
	 * @param string $sql
	 * @param array $args
	 *
	 * @return false|int
	 */
	public function wpdb_query($sql, $args = array())
	{
		global $wpdb;
		$sql = $this->wpdb_prepare($sql, $args);

		return $wpdb->query($sql);
	}

	/**
	 * Wrapper for $wpdb get_var.
	 *
	 * @param string $sql
	 * @param array $args
	 *
	 * @return null|string
	 */
	public function wpdb_get_var($sql, $args = array())
	{
		global $wpdb;
		$sql = $this->wpdb_prepare($sql, $args);

		return $wpdb->get_var($sql);
	}

	/**
	 * Wrapper for $wpdb get_row.
	 *
	 * @param string $sql
	 * @param array $args
	 * @param       $output
	 *
	 * @return array|null|object|void
	 */
	public function wpdb_get_row($sql, $args = array(), $output = OBJECT)
	{
		// TODO: where does the default value for $output come from?
		global $wpdb;
		$sql = $this->wpdb_prepare($sql, $args);

		return $wpdb->get_row($sql, $output);
	}

	/**
	 * Wrapper for $wpdb get_row.
	 *
	 *
	 *
	 * @param string $sql
	 * @param array $args
	 * @param       $output
	 *
	 * @return array|null|object|void
	 */
	public function wpdb_get_col($sql, $args = array())
	{
		// TODO: where does the default value for $output come from?
		global $wpdb;
		$sql = $this->wpdb_prepare($sql, $args);

		return $wpdb->get_col($sql);
	}

	/**
	 * Wrapper for $wpdb get_results.
	 *
	 * @param string $sql
	 * @param array $args
	 * @param       $mode
	 *
	 * @return array|null|object
	 */
	public function wpdb_get_results($sql, $args = array(), $mode)
	{
		global $wpdb;
		$sql = $this->wpdb_prepare($sql, $args);

		return $wpdb->get_results($sql, $mode);
	}

	/**
	 * Wrapper for $wpdb insert.
	 *
	 * @param string $sql
	 * @param      $data
	 * @param null $format
	 *
	 * @return false|int
	 */
	public function wpdb_insert($table, $data, $format = null)
	{
		global $wpdb;

		return $wpdb->insert($table, $data, $format);
	}

	/**
	 * Wrapper for $wpdb update.
	 *
	 * @param string $sql
	 * @param $data
	 * @param $where
	 *
	 * @return false|int
	 */
	public function wpdb_update($table, $data, $where)
	{
		global $wpdb;

		return $wpdb->update($table, $data, $where);
	}

	/**
	 * Wrapper for $wpdb delete.
	 *
	 * @param string $sql
	 * @param $where
	 *
	 * @return false|int
	 */
	public function wpdb_delete($table, $where)
	{
		global $wpdb;

		return $wpdb->delete($table, $where);
	}

	/**
	 * Wrapper for $wpdb prepare.
	 *
	 * @param string $sql
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function wpdb_prepare($sql, $args = array())
	{
		if (0 === sizeof($args)) {
			return $sql;
		}

		global $wpdb;
		array_unshift($args, $sql);

		return call_user_func_array(array($wpdb, 'prepare'), $args);
	}

	/**
	 * Get the options table.
	 * @return string
	 */
	public function getTableSiteMeta() {
		global $wpdb;

		return $wpdb->sitemeta;
	}

	/**
	 * Returns the last query from $wpdb.
	 *
	 * @return array
	 */
	public function getLastQuery()
	{
		global $wpdb;

		return $wpdb->last_query;
	}

	/**
	 * Returns the last error from $wpdb.
	 *
	 * @return string#
	 */
	public function getLastError()
	{
		global $wpdb;

		return $wpdb->last_error;
	}
}