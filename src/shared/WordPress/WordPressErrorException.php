<?php

namespace Dreitier\WordPress;

use Dreitier\Util\Exception;
use Dreitier\Util\Logger\LogFacade;

/**
 * WordPressErrorException encapsulates a {@link WP_Error} object inside an exception
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @access
 */
class WordPressErrorException extends Exception
{
	/** @var \WP_Error */
	private $wordPressError;

	public function __construct(\WP_Error $wordPressError)
	{
		parent::__construct('', 0, null);

		$this->wordPressError = $wordPressError;
	}

	/**
	 * @return \WP_Error
	 */
	public function getWordPressError()
	{
		return $this->wordPressError;
	}

	/**
	 * Throw a new {@see WordPressErrorException} using the given $error. If the given value
	 * is not an instance of {@see WP_Error}, false will be returned.
	 *
	 * @param \WP_Error|mixed $error
	 *
	 * @return bool
	 *
	 * @throws WordPressErrorException
	 */
	public static function processWordPressError($error)
	{
		if (!is_wp_error($error)) {
			return false;
		}

		LogFacade::error($error->get_error_messages());

		throw new WordPressErrorException($error);
	}
}