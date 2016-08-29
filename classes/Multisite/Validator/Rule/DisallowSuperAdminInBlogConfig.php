<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_DisallowSuperAdminInBlogConfig')) {
	return;
}

/**
 * Multisite_Validator_Rule_DisallowSuperAdminOnBlogConfig adds validation to prevent a blog admin to set himself
 * as a super admin.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class NextADInt_Multisite_Validator_Rule_DisallowSuperAdminInBlogConfig extends NextADInt_Core_Validator_Rule_Abstract
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array  $data
	 *
	 * @return mixed
	 */
	public function validate($value, $data)
	{
		if ($this->isOnNetworkDashboard()) {
			return true;
		}

		$wpRoles = $this->getWpRoles($value);

		if (!NextADInt_Core_Util_ArrayUtil::containsIgnoreCase(NextADInt_Adi_Role_Manager::ROLE_SUPER_ADMIN, $wpRoles)) {
			return true;
		}

		return $this->getMsg();
	}

	/**
	 * Convert the given list string and retrieve all WordPress roles.
	 *
	 * @param $value
	 *
	 * @return array
	 */
	protected function getWpRoles($value)
	{
		// convert the given string into separate lines
		$roleMappings = NextADInt_Core_Util_StringUtil::split($value, ';');
		// remove empty values from the array
		$roleMappings = array_filter($roleMappings);

		return array_map(function($roleMappingString) {
			$roleMapping = NextADInt_Core_Util_StringUtil::split($roleMappingString, '=');

			return $roleMapping[1];
		}, $roleMappings);
	}

	/**
	 * Check if the user is currently on the network Dashboard.
	 *
	 * @return bool
	 */
	protected function isOnNetworkDashboard()
	{
		return NextADInt_Multisite_Util::isOnNetworkDashboard();
	}
}