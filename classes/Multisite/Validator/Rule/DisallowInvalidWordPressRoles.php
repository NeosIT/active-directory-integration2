<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Multisite_Validator_Rule_DisallowInvalidWordPressRoles')) {
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
class NextADInt_Multisite_Validator_Rule_DisallowInvalidWordPressRoles extends NextADInt_Core_Validator_Rule_Abstract
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

		if (NextADInt_Core_Util_ArrayUtil::containsIgnoreCase(NextADInt_Adi_Role_Manager::ROLE_SUPER_ADMIN, $wpRoles)) {
		    $msg = $this->getMsg();
            return array(NextADInt_Core_Message_Type::ERROR => $msg[NextADInt_Core_Message_Type::ERROR][0]);
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
	protected function containsInvalidRoles($roles) {
        $wpRoles = new WP_Roles();
        foreach ($roles as $role) {
            if (!$wpRoles->is_role($role) && $role !== NextADInt_Adi_Role_Manager::ROLE_SUPER_ADMIN) {
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