<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (interface_exists('NextADInt_Core_Migration')) {
	return;
}

/**
 * Core_Migration provides base methods for interacting with migrations. 
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
interface NextADInt_Core_Migration
{
	/**
	 * Get the position for this migration.
	 *
	 * @return integer
	 */
	public static function getId();

	/**
	 * Execute the migration.
	 *
	 * @return boolean
	 */
	public function execute();
}