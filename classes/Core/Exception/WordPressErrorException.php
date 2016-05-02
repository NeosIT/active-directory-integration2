<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Exception_WordPressErrorException')) {
	return;
}

/**
 * Core_Exception_WordPressErrorException encapsulates a {@link WP_Error} object inside an exception
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class Core_Exception_WordPressErrorException extends Core_Exception
{
	/** @var WP_Error */
	private $wordPressError;

	public function __construct(WP_Error $wordPressError)
	{
		parent::__construct(null, null, null);

		$this->wordPressError = $wordPressError;
	}

	/**
	 * @return WP_Error
	 */
	public function getWordPressError()
	{
		return $this->wordPressError;
	}
}