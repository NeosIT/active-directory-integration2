<?php

namespace Dreitier\Nadi\User;

/**
 * DuplicateEmailPrevention contains the values for the meta data
 * {@see Options::DUPLICATE_EMAIL_PREVENTION}.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
abstract class DuplicateEmailPrevention
{
	const PREVENT = 'prevent',
		CREATE = 'create',
		ALLOW = 'allow';

	private function __construct()
	{

	}

	private function __clone()
	{

	}
}