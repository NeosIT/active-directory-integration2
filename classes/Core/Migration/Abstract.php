<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Migration_Abstract')) {
	return;
}

/**
 * NextADInt_Core_Migration_Abstract provides the base functionality for migrations.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
abstract class NextADInt_Core_Migration_Abstract implements NextADInt_Core_Migration
{
	/** @var NextADInt_Adi_Dependencies */
	private $dependencyContainer;

	/**
	 * NextADInt_Core_Migration_Abstract constructor.
	 *
	 * @param NextADInt_Adi_Dependencies $dependencyContainer
	 */
	public function __construct(NextADInt_Adi_Dependencies $dependencyContainer)
	{
		$this->dependencyContainer = $dependencyContainer;
	}

	/**
	 * @return NextADInt_Adi_Dependencies
	 */
	public function getDependencyContainer()
	{
		return $this->dependencyContainer;
	}
}