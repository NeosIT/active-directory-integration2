<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Adi_User_Meta_Persistence_Repository')) {
	return;
}

/**
 * NextADInt_Adi_User_Meta_Persistence_Repository interacts with the user meta data of the internal WordPress table wp_user_meta.
 *
 * @author  Sebastian Weinert <swe@neos-it.de>
 *
 * @access public
 */
class NextADInt_Adi_User_Meta_Persistence_Repository
{
	/** @var Logger $logger */
	private $logger;

	public function __construct()
	{
		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * Find the meta entry {@code $metaKey} for the given {@code $userId}.
	 *
	 * @param integer $userId
	 * @param string  $metaKey
	 * @param boolean $singleValue
	 *
	 * @return mixed
	 */
	public function find($userId, $metaKey, $singleValue)
	{
		return get_user_meta($userId, $metaKey, $singleValue);
	}

	/**
	 * Create a new meta entry for the given {@code $userId}.
	 *
	 * @param integer $userId
	 * @param string  $metaKey
	 * @param mixed   $metaValue
	 *
	 * @return false|int
	 */
	public function create($userId, $metaKey, $metaValue)
	{
		return add_user_meta($userId, $metaKey, $metaValue);
	}

	/**
	 * Update a meta entry for the given {@code $userId}.
	 *
	 * @param integer $userId
	 * @param string  $metaKey
	 * @param mixed   $metaValue
	 *
	 * @return bool|int
	 */
	public function update($userId, $metaKey, $metaValue)
	{
		return update_user_meta($userId, $metaKey, $metaValue);
	}

	/**
	 * Remove the meta entry {@code $metaKey} from the user {@code $userId}.
	 *
	 * @param integer $userId
	 * @param string  $metaKey
	 *
	 * @return bool
	 */
	public function delete($userId, $metaKey)
	{
		return delete_user_meta($userId, $metaKey);
	}

	/**
	 * Disable the given {@link WP_User} using the meta data.
	 *
	 * @param WP_User $userData
	 * @param string  $reason
	 */
	public function disableUser(WP_User $userData, $reason = '')
	{
		$userId = $userData->ID;

		$this->update($userId, NEXT_AD_INT_PREFIX . 'user_disabled', true);
		$this->update($userId, NEXT_AD_INT_PREFIX . 'user_disabled_reason', $reason);

		$this->logger->info("Delete e-mail of disabled user '$userData->user_login' ($userId).");

		// store e-mail in meta
		$this->update($userId, NEXT_AD_INT_PREFIX . 'user_disabled_email', $userData->user_email);
	}

	/**
	 * Enable the given {@link WP_User} using the meta data.
	 *
	 * @param WP_User $userData
	 */
	public function enableUser(WP_User $userData)
	{
		$userId = $userData->ID;

		$this->update($userId, NEXT_AD_INT_PREFIX . 'user_disabled', false);
		$this->update($userId, NEXT_AD_INT_PREFIX . 'user_disabled_reason', '');
		$this->delete($userId, NEXT_AD_INT_PREFIX . 'user_disabled_email');
	}

	/**
	 * Check if user $userId is disabled
	 *
	 * @param integer $userId
	 *
	 * @return bool
	 */
	public function isUserDisabled($userId)
	{
		$disabled = $this->find($userId, NEXT_AD_INT_PREFIX . 'user_disabled', true);

		return (bool)$disabled;
	}
}