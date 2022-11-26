<?php

namespace Dreitier\Nadi\Ui\Validator\Rule;


use Dreitier\Nadi\Role\Manager;
use Dreitier\Util\ArrayUtil;
use Dreitier\Util\Message\Type;
use Dreitier\Util\StringUtil;
use Dreitier\Util\Validator\Rule\RuleAdapter;
use Dreitier\WordPress\Multisite\Util;

/**
 * DisallowInvalidWordPressRoles adds validation to prevent a blog admin to set himself
 * as a super admin.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class DisallowInvalidWordPressRoles extends RuleAdapter
{
	/**
	 * Validate the given data.
	 *
	 * @param string $value
	 * @param array $data
	 *
	 * @return mixed
	 */
	public function validate($value, $data)
	{
		$wpRoles = $this->getWpRoles($value);

		// check for invalid roles
		if ($this->containsInvalidRoles($wpRoles)) {
			$msg = $this->getMsg();
			return $msg[1];
		}

		// check for super user in blog
		if ($this->isOnNetworkDashboard()) {
			return true;
		}

		if (ArrayUtil::containsIgnoreCase(Manager::ROLE_SUPER_ADMIN, $wpRoles)) {
			$msg = $this->getMsg();
			return array(Type::ERROR => $msg[Type::ERROR][0]);
		}

		// all ok
		return true;
	}

	/**
	 * Check if all @param $roles exist in the WordPress instance.
	 *
	 * @param $roles
	 * @return bool
	 */
	protected function containsInvalidRoles($roles)
	{
		$wpRoles = new \WP_Roles();
		foreach ($roles as $role) {
			if (!$wpRoles->is_role($role) && $role !== Manager::ROLE_SUPER_ADMIN) {
				return true;
			}
		}

		return false;
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
		$roleMappings = StringUtil::split($value, ';');
		// remove empty values from the array
		$roleMappings = array_filter($roleMappings);

		return array_map(function ($roleMappingString) {
			$roleMapping = StringUtil::split($roleMappingString, '=');

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
		return Util::isOnNetworkDashboard();
	}
}