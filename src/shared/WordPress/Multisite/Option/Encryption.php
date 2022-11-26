<?php

namespace Dreitier\WordPress\Multisite\Option;

/**
 * Encryption holds all values for our encryption type.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Encryption
{
	const NONE = 'none',
		STARTTLS = 'starttls',
		LDAPS = 'ldaps';


	public static function getValues()
	{
		return array(
			self::NONE,
			self::STARTTLS,
			self::LDAPS,
		);
	}
}