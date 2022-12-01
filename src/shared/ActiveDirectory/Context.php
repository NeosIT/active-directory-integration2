<?php
namespace Dreitier\ActiveDirectory;

/**
 * Context for Active Directory related things
 *
 * @author Christopher Klein <ckl[at]dreitier[dot]com>
 * @access public
 * @since 2.2.0
 */
class Context
{
	/**
	 * List of domain SIDs
	 *
	 * @var array
	 */
	private $domainSids;

	/**
	 * @param $domainSids
	 * @throws \Exception
	 */
	public function __construct(array $domainSids)
	{
		if (!is_array($domainSids) || (sizeof($domainSids) == 0)) {
			throw new \Exception("Atleast one domain SID must be configured");
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
	 * @param ?Sid $objectSid
	 * @param false $primaryDomainOnly
	 * @return bool
	 */
	public function isMember(?Sid $objectSid, $primaryDomainOnly = false)
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

		return apply_filters(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'object_has_ad_context_membership', $r, $objectSid, $primaryDomainOnly, $this);
	}

	/**
	 * Check the membership and throws an exception if the SID is not a member
	 *
	 * @param Sid $objectSid
	 * @return bool
	 * @throws \Exception
	 */
	public function checkMembership(Sid $objectSid) {
		if (!$this->isMember($objectSid)) {
			throw new \Exception("SID '" . $objectSid . "' is not member of any of the following domain SIDs: " . $this);
		}

		return true;
	}

	public function __toString() {
		return "Context={domainSids={" . implode(",", $this->domainSids) . "}}"	;
	}
}