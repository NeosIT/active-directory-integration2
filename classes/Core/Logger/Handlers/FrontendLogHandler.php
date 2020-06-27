<?php

/**
 * This runtime handler temporally stores log messages and provides access to them at runtime
 *
 * @author Danny Meißner  <dme@neos-it.de>
 */
class NextADInt_Core_Logger_Handlers_FrontendLogHandler extends Monolog\Handler\AbstractProcessingHandler
{

	private $log;

	private $enabled = false;

	/**
	 * @param integer $level  The minimum logging level at which this handler will be triggered
	 * @param Boolean $bubble Whether the messages that are handled can bubble up the stack or not
	 */
	public function __construct($level = Monolog\Logger::DEBUG, $bubble = true)
	{
		parent::__construct($level, $bubble);
	}

	public function enable() {
		$this->enabled = true;
	}

	public function disable() {
		$this->enabled = false;
	}

	protected function write(array $record)
	{
		if (!$this->enabled) {
			return;
		}

		$this->log[] = $record;
	}

	public function getBufferedLog() {
		$log = $this->log;

		// Clean Log
		$this->log = [];

		return $log;
	}

}