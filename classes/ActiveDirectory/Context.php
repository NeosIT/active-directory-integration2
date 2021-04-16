<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_ActiveDirectory_Context')) {
	return;
}

/**
 * Context for Active Directory related things
 *
 * @author Christopher Klein <me[at]schakko[dot]de>
 * @access public
 * @since 2.2.0
 */
class NextADInt_ActiveDirectory_Context
{
	/**
	 * List of domain SIDs
	 *
	 * @var array
	 */
	private $domainSids;

	/**
	 * NextADInt_ActiveDirectory_Context constructor.
	 * @param $domainSids
	 * @throws Exception
	 */
	public function __construct(array $domainSids)
	{
		if (!is_array($domainSids) || (sizeof($domainSids) == 0)) {
			throw new Exception("Atleast one domain SID must be configured");
		}

		$this->domainSids = array_map('strtoupper', $domainSids);
	}

	/**
	 * Get the first defined domain SID, in an AD forest, this is the SID of the domain which NADI is connected to.
	 * @return mixed
	 */
	public function getPrimaryDomainSid()
	{
		return $this->domainSids[0];
	}

	/**
	 * Retrieve all SIDs for this context.
	 * @return array of strings
	 */
	public function getForestSids()
	{
		return $this->domainSids;
	}

	/**
	 * Return true if the given SID is part of one the SIDs defined for the context
	 *
	 * @param ?NextADInt_ActiveDirectory_Sid $objectSid
	 * @param false $primaryDomainOnly
	 * @return bool
	 */
	public function isMember(?NextADInt_ActiveDirectory_Sid $objectSid, $primaryDomainOnly = false)
	{
		if (!$objectSid) {
			return false;
		}

		$useSid = $objectSid->getDomainPartAsSid();

		if (!$useSid) {
			return false;
		}

		$sidAsString = $useSid->getFormatted();
		$checkSids = $primaryDomainOnly ? [$this->domainSids[0]] : $this->domainSids;

		$r = in_array($sidAsString, $checkSids);

		return apply_filters(NEXT_AD_INT_PREFIX . 'object_has_ad_context_membership', $r, $objectSid, $primaryDomainOnly, $this);
	}

	/**
	 * Check the membership and throws an exception if the SID is not a member
	 *
	 * @param NextADInt_ActiveDirectory_Sid $objectSid
	 * @return bool
	 * @throws Exception
	 */
	public function checkMembership(NextADInt_ActiveDirectory_Sid $objectSid) {
		if (!$this->isMember($objectSid)) {
			throw new Exception("SID '" . $objectSid . "' is not member of any of the following domain SIDs: " . $this);
		}

		return true;
	}

	public function __toString() {
		return "Context={domainSids={" . implode(",", $this->domainSids) . "}}"	;
	}
}