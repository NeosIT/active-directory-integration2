<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Assert')) {
	return;
}

/**
 * NextADInt_Core_Assert provides simple assertions to ensure condiitions and restrictions.
 *
 * @author Christopher Klein <ckl@neos-it.de>
 *
 * @access public
 */
class NextADInt_Core_Assert
{
	private function __construct()
	{
	}

	/**
	 * @param mixed $subject
	 * @param null|string $msg
	 * @throws Exception if $subject is null
	 */
	public static function notNull($subject, $msg = null)
	{
		if (null === $subject) {
			throw new Exception(($msg ? $msg : "Given parameter must not be null"));
		}
	}

	/**
	 * @param bool $subject
	 * @param null|string $msg
	 * @throws Exception if $subject evaluates to false
	 */
	public static function condition($subject, $msg = null)
	{
		if ($subject === false) {
			throw new Exception(($msg ? $msg : "Given condition must be true but is '$subject'."));
		}
	}

	/**
	 * @param mixed $subject
	 * @param null|string $msg
	 * @throws Exception If $subject is not numeric
	 */
	public static function numeric($subject, $msg = null)
	{
		if (!is_numeric($subject)) {
			throw new Exception(($msg ? $msg : "Given parameter must be numeric but is '$subject'"));
		}
	}

	/**
	 * @param mixed $subject
	 * @param null|string $msg
	 * @throws Exception If $subject is null. If it is a string it length must be > 0 (<strong>without</strong> trimming).
	 */
	public static function notEmpty($subject, $msg = null)
	{
		$defaultMsg = "Given parameter must not be empty";
		self::notNull($subject, $msg);

		if (is_string($subject) && (strlen($subject) == 0)) {
			throw new Exception(($msg ? $msg : $defaultMsg));
		}
	}

	/**
	 * @param mixed $subject
	 * @param null|string $msg
	 * @throws Exception If $subject is not numeric or is less than zero.
	 */
	public static function validId($subject, $msg = null)
	{
		self::numeric($subject, "Given parameter is not a valid numeric ID");

		if ($subject <= 0) {
			throw new Exception(($msg ? $msg : "Given parameter must be greater than 0 but is '$subject'"));
		}
	}
}