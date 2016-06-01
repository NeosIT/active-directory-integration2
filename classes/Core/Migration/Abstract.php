<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('Core_Migration_Abstract')) {
	return;
}

/**
 * Core_Migration_Abstract provides the base functionality for migrations.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
abstract class Core_Migration_Abstract implements Core_Migration
{
	/** @var Adi_Dependencies */
	private $dependencyContainer;

	/**
	 * Core_Migration_Abstract constructor.
	 *
	 * @param Adi_Dependencies $dependencyContainer
	 */
	public function __construct(Adi_Dependencies $dependencyContainer)
	{
		$this->dependencyContainer = $dependencyContainer;
	}

	/**
	 * @return Adi_Dependencies
	 */
	public function getDependencyContainer()
	{
		return $this->dependencyContainer;
	}
}