<?php

namespace Dreitier\Nadi\User\Persistence;

use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\User\User;
use Dreitier\Nadi\Vendor\Monolog\Logger;
use Dreitier\Util\ArrayUtil;
use Dreitier\WordPress\WordPressErrorException;

/**
 * Repository interacts with the {@see WP_User} data.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @author  Sebastian Weinert <swe@neos-it.de>
 * @author  Danny Meißner <dme@neos-it.de>
 *
 * @access
 */
class Repository
{
	/** @var Logger */
	private $logger;

	/**
	 * Meta key which holds the Active Directory username of a WordPress user
	 */
	const META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME = 'samaccountname';

	/**
	 * Meta key which holds the Active Directory object GUID of a WordPress user
	 */
	const META_KEY_OBJECT_GUID = 'objectguid';
	const META_KEY_DOMAINSID = 'domainsid';

	public function __construct()
	{
		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Find a {@see WP_User} by the given $id.
	 *
	 * @param integer $id
	 *
	 * @return false|\WP_User
	 */
	public function findById($id)
	{
		return $this->findByKey('id', $id);
	}

	/**
	 * Find a {@see WP_User} by the given $username.
	 *
	 * @param string $username
	 *
	 * @return false|\WP_User
	 */
	public function findByUsername($username)
	{
		return $this->findByKey('login', $username);
	}

	/**
	 * Find a {@see WP_User} by the given $username.
	 *
	 * @param string $email
	 *
	 * @return false|\WP_User
	 */
	public function findByEmail($email)
	{
		return $this->findByKey('email', $email);
	}

	/**
	 * Delegate the call to wordpress get_user_by() function.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return false|\WP_User
	 */
	protected function findByKey($key, $value)
	{
		return get_user_by($key, $value);
	}

	/**
	 * Find one or multiple users by the given meta key
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return array of WP_User
	 */
	public function findByMetaKey($key, $value = null)
	{
		$options =
			array('meta_key' => $key, 'fields' => 'all' /* get WP_User objects */);

		if ($value != null) {
			$options['meta_value'] = $value;
		}

		return get_users($options);
	}

	/**
	 * Find the user meta information for the user with the given ID
	 *
	 * @param int $userId
	 * @return array of arrays: array('last_name' => array('My last name'), 'first_name' => array('My first name')
	 */
	public function findUserMeta($userId)
	{
		return get_user_meta($userId);
	}

	/**
	 * Update wp_user_meta with given values
	 *
	 * @param int $userId
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return int|bool int if key does not exist, bool if it is updated, false on error
	 */
	public function updateMetaKey($userId, $key, $value)
	{
		return update_user_meta($userId, $key, $value);
	}

	/**
	 * Find the object GUID of provided WP_User or WP_User->ID
	 * @param \WP_User|int $wpUserOrUserId
	 * @return string|null
	 */
	public function findObjectGuidOfUser(\WP_User|int $wpUserOrUserId): ?string
	{
		$userId = is_int($wpUserOrUserId) ? $wpUserOrUserId : $wpUserOrUserId->ID;
		$objectGuidOrNull = get_user_meta($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . self::META_KEY_OBJECT_GUID, true);

		return $objectGuidOrNull;
	}

	/**
	 * Return a WP_User with the given sAMAccountName
	 *
	 * @param string $sAMAccountName
	 *
	 * @return WP_User|false
	 */
	public function findBySAMAccountName($sAMAccountName)
	{
		$result = $this->findByMetaKey(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . self::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME, $sAMAccountName);

		return ArrayUtil::findFirstOrDefault($result, false);
	}

	/**
	 * Update the sAMAccountName meta key
	 *
	 * @param int $userId
	 * @param string $sAMAccountName
	 */
	public function updateSAMAccountName($userId, $sAMAccountName)
	{
		$this->updateMetaKey($userId,NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . self::META_KEY_ACTIVE_DIRECTORY_SAMACCOUNTNAME, $sAMAccountName);
	}

	/**
	 * Update the objectGuid meta key
	 *
	 * @param int $userId
	 * @param $objectGuid
	 * @return void
	 */
	public function updateObjectGuid(int $userId, $objectGuid)
	{
		$this->updateMetaKey($userId, NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . self::META_KEY_OBJECT_GUID, $objectGuid);
	}

	/**
	 * Return a WP_User with the given sAMAccountName
	 *
	 * @param $guid
	 *
	 * @return bool|mixed
	 */
	public function findByObjectGuid($guid)
	{
		// ADI-702: A deleted user from Active Directory is mapped to the wrong user in WordPress
		// Originally fixed and report by T. Kowalchuk <kowaty[at]<redacted>wi.us>
		if (empty(trim($guid))) {
			return false;
		}

		$result = $this->findByMetaKey(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . self::META_KEY_OBJECT_GUID, $guid);

		return ArrayUtil::findFirstOrDefault($result, false);
	}

	/**
	 * Check if the given $email is already existing.
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function isEmailExisting($email)
	{
		$user = $this->findByEmail($email);

		return (false !== $user);
	}

	/**
	 * Update the email for the given $userId.
	 *
	 * @param integer $userId
	 * @param string $mail
	 */
	public function updateEmail($userId, $mail)
	{
		$this->updateProperty($userId, 'user_email', $mail);
	}

	/**
	 * Update the password for the given $userId.
	 *
	 * @param integer $userId
	 * @param string $password
	 */
	public function updatePassword($userId, $password)
	{
		$this->updateProperty($userId, 'user_pass', $password);
	}

	/**
	 * Update the property $key for the given $userId.
	 *
	 * @param integer $userId
	 * @param string $key
	 * @param mixed $value
	 */
	protected function updateProperty($userId, $key, $value)
	{
		//update user
		$param = array('ID' => $userId, $key => $value);
		$wpError = wp_update_user($param);

		//check for errors
		if (is_wp_error($wpError)) {
			$wpUser = $this->findById($userId);
			$messages = print_r($wpError->get_error_messages(), true);
			$this->logger->error("Could not update user '$wpUser->display_name' ($userId): $messages");
		}
	}

	/**
	 * @param User $user
	 *
	 * @param $email
	 *
	 * @return int|WP_Error
	 *
	 * @throws WordPressErrorException
	 */
	public function create(User $user, $email)
	{
		$result = wp_create_user($user->getUserLogin(), $user->getCredentials()->getPassword(), $email);

		if (is_wp_error($result)) {
			// log error
			$this->logger->error("Can not create user '{$user->getUserLogin()}' because of: " . json_encode($result));
			WordPressErrorException::processWordPressError($result);
		}

		$this->logger->debug("Create user '{$user->getUserLogin()}'.");

		return $result;
	}

	/**
	 * @param User $user
	 * @param array $userData
	 *
	 * @return int|WP_Error
	 */
	public function update(User $user, $userData)
	{
		$result = wp_update_user($userData);

		if (is_wp_error($result)) {
			$message = print_r($result->get_error_messages(), true);
			$this->logger->error("Could not update user '{$user->getUserLogin()}' ({$user->getId()}): " . $message);
		}

		return $result;
	}
}
