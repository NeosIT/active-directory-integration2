<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_User_Ui_ExtendUserList')) {
	return;
}

/**
 * Extends the original WordPress user list with additional columns for identifying Active Directory and deactivated users.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Adi_User_Ui_ExtendUserList
{
	/**
	 * @var NextADInt_Multisite_Configuration_Service
	 */
	private $configuration;

	/**
	 * @var NextADInt_Core_Util
	 */
	private $native;

	/**
	 * @param NextADInt_Multisite_Configuration_Service $configuration
	 */
	public function __construct(NextADInt_Multisite_Configuration_Service $configuration)
	{
		$this->configuration = $configuration;
		$this->native = NextADInt_Core_Util::native();
	}

	/**
	 * Add an 'user is disabled' indicator on the user management screen.
	 */
	public function register()
	{
		// escape if SHOW_USER_STATUS is false
		if (!$this->configuration->getOptionValue(NextADInt_Adi_Configuration_Options::SHOW_USER_STATUS)) {
			return;
		}

		add_filter('manage_users_columns', array($this, 'addColumns'));
		add_filter('manage_users_custom_column', array($this, 'addContent'), 10, 3);
	}

	/**
	 * Return name of "User disabled" column
	 *
	 * @return string
	 */
	public function __columnUserDisabled() {
		return NEXT_AD_INT_PREFIX . 'user_disabled';
	}

	/**
	 * Return name of 'user' column
	 *
	 * @return string
	 */
	public function __columnIsAdiUser() {
		return NEXT_AD_INT_PREFIX . 'user';
	}

	/**
	 * Return name of "Managed by NADI CRM PE" column
	 *
	 * @return string
	 */
	public function __columnManagedByCrmPe() {
		$blogId = get_current_blog_id();
		return NEXT_AD_INT_PREFIX . 'pe_crm_is_managed_by_pe_' . $blogId;
	}

	/**
	 * Add 3 columns (ADI User, Disabled and CRM)
	 *
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function addColumns($columns)
	{
		$columns[$this->__columnIsAdiUser()] = __('NADI User', 'next-active-directory-integration');
		$columns[$this->__columnUserDisabled()] = __('Disabled', 'next-active-directory-integration');

		// NADIS-5 Check if premium extension CRM is enabled before rendering column
		if ($this->native->isClassAvailable('NADIExt_Custom_User_Role_Management_Modifier')) {
			$columns[$this->__columnManagedByCrmPe()] = __('Managed by CRM', 'next-active-directory-integration');
		}

		return $columns;
	}

	/**
	 * Add content to the two columns ADI User and Disabled
	 * https://developer.wordpress.org/resource/dashicons/#lock
	 *
	 * @param string $value
	 * @param string $columnName
	 * @param int $userId
	 *
	 * @return string
	 */
	public function addContent($value, $columnName, $userId)
	{
		switch ($columnName) {
			case $this->__columnIsAdiUser():
				return $this->renderIsAdiUserColumn($userId);
			case $this->__columnUserDisabled():
				return $this->renderDisabledColumn($userId);
			case $this->__columnManagedByCrmPe():
				return $this->renderManagedByCrmPe($userId);
		}

		// return value because the other column must no be modified
		return $value;
	}

	/**
	 * Render username column to fill it with an icon
	 *
	 * @access package
	 * @param int $userId
	 * @return string
	 */
	function renderIsAdiUserColumn($userId) {
		$samAccountName = get_user_meta($userId, NEXT_AD_INT_PREFIX . 'samaccountname', true);

		if ($samAccountName) {
			// add a place holder?
			return '<div class="adi_user dashicons dashicons-admin-users">&nbsp;</div>';
		}

		// if samAccountName is empty, then return an empty string
		return '';
	}

	/**
	 * Render the disabled column to fill it with a reason
	 *
	 * @access package
	 * @param int $userId
	 * @return string empty string if no reason exists
	 */
	function renderDisabledColumn($userId) {
		$isUserDisabled = get_user_meta($userId, $this->__columnUserDisabled(), true);
		$reason = get_user_meta($userId, NEXT_AD_INT_PREFIX . 'user_disabled_reason', true);

		// fallback message
		if (!$reason || !is_string($reason) || strlen($reason) === 0){
			$reason = __('User is disabled by NADI.', 'next-active-directory-integration');
		}

		if ($isUserDisabled) {
			// add value
			return "<div class='adi_user_disabled'>$reason</div>";
		}

		// if user is not disabled, then return an empty string
		return '';

	}

	/**
	 * Render the is managed by NADI CRM column
	 *
	 * @access package
	 * @param int $userId
	 * @return string empty string if no reason exists
	 */
	function renderManagedByCrmPe($userId) {
		$isUserManagedByCrmPe = get_user_meta($userId, $this->__columnManagedByCrmPe(), true);

		if ($isUserManagedByCrmPe) {
			// add value
			return "<div class='adi_user_is_managed_by_crm_pe dashicons dashicons-yes'>&nbsp;</div>";
		}

		// if user is not disabled, then return an empty string
		return '';

	}
}