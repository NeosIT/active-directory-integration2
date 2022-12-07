<?php

namespace Dreitier\Nadi\Log;

use Dreitier\Nadi\Vendor\Monolog\Logger as MonologLogger;

/**
 * Custom logging class.
 * This is primarily available due to compatability reasons with NADI v2.
 */
class Logger extends MonologLogger
{
	/**
	 * 'warn' has been dropped after monolog 1.x
	 *
	 * @param $message
	 * @param array $context
	 * @return void
	 */
	public function warn($message, array $context = []): void
	{
		$this->warning($message, $context);
	}
}