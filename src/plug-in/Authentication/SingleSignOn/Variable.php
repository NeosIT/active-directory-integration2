<?php

namespace Dreitier\Nadi\Authentication\SingleSignOn;

/**
 * Variable contains all environment identifiers which could point to SSO user principle.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Stefan Fiedler <sfi@neos-it.de>
 *
 * @access
 */
class Variable
{
	const REMOTE_USER = 'REMOTE_USER';

	const X_REMOTE_USER = 'X-REMOTE-USER';
	// ADI-389 see github issue#29
	const HTTP_X_REMOTE_USER = 'HTTP_X_REMOTE_USER';

	const PHP_AUTH_USER = 'PHP_AUTH_USER';

	public static function getValues()
	{
		return array(
			self::REMOTE_USER,
			self::X_REMOTE_USER,
			self::HTTP_X_REMOTE_USER,
			self::PHP_AUTH_USER
		);
	}
}