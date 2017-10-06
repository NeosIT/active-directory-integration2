<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Exception_WordPressErrorException')) {
	return;
}

/**
 * NextADInt_Core_Exception_WordPressErrorException encapsulates a {@link WP_Error} object inside an exception
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access
 */
class NextADInt_Core_Exception_WordPressErrorException extends NextADInt_Core_Exception
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