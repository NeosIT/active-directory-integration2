<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Ldap_Attributes')) {
	return;
}

/**
 * Value object for LDAP attributes
 *
 * @author Christopher Klein <ckl@neos-it.de>
 * @access public
 */
class NextADInt_Ldap_Attributes
{
	/**
	 * @var array
	 */
	private $raw = array();

	/**
	 * @var array
	 */
	private $filtered = array();

	/**
	 * Ldap_Attributes constructor.
	 * @param array $raw
	 * @param array $filtered
	 */
	public function __construct($raw = array(), $filtered = array())
	{
		$this->raw = $raw;
		$this->filtered = $filtered;
	}

	/**
	 * @return array
	 */
	public function getFiltered()
	{
		return $this->filtered;
	}

	/**
	 * @param array $filtered
	 */
	public function setFiltered($filtered)
	{
		$this->filtered = $filtered;
	}

	/**
	 * Return a filtered attribute or $default
	 * @param string $attributeName
	 * @param null $default
	 * @return null
	 */
	public function getFilteredValue($attributeName, $default = null) {
		if (isset($this->filtered)) {
			if (isset($this->filtered[$attributeName])) {
				return $this->filtered[$attributeName];
			}
		}

		return $default;
	}

	/**
	 * @return array
	 */
	public function getRaw()
	{
		return $this->raw;
	}

	/**
	 * @param array $raw
	 */
	public function setRaw($raw)
	{
		$this->raw = $raw;
	}
	
	/**
	 * @param string $domainSid
	 */
	public function setDomainSid($domainSid) {
		if (isset($this->filtered)) {
			$this->filtered["domainsid"] = $domainSid;
		}
	}
}