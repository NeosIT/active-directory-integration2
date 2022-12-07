<?php

namespace Monolog;

class Registry
{
	public static function getInstance($instance)
	{
		if ($instance !== 'nadiMainLogger') {
			throw new \Exception("Unable to handle log instance" . $instance);
		}

		return next_ad_int_logger();
	}
}