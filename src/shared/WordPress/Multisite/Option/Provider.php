<?php

namespace Dreitier\WordPress\Multisite\Option;

/**
 * Common interface for providing configuration options of a multisite plug-in
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
interface Provider
{
	/**
	 * Get the option meta data for an option.
	 *
	 * @param string $name
	 *
	 * @return array|null
	 */
	public function get($name);

	/**
	 * Exists the option with the name $name?
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function existOption($name);

	/**
	 * Get all option elements.
	 *
	 * @return array
	 */
	public function getAll();

	/**
	 * Get all option elements that are not transient.
	 *
	 * @return mixed
	 */
	public function getNonTransient();
}