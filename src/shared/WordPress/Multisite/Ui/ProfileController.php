<?php

namespace Dreitier\WordPress\Multisite\Ui;


use Dreitier\Util\Message\Message;
use Dreitier\WordPress\Multisite\Configuration\Persistence\BlogConfigurationRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\DefaultProfileRepository;
use Dreitier\WordPress\Multisite\Configuration\Persistence\ProfileRepository;

/**
 * ProfileController validates and stores the creation/change/delete of a profile.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access  public
 */
class ProfileController
{
	/* @var ProfileRepository $profileRepository */
	private $profileRepository;
	/* @var BlogConfigurationRepository */
	private $blogConfigurationRepository;
	/** @var DefaultProfileRepository */
	private $defaultProfileRepository;

	/**
	 * @param ProfileRepository $profileRepository
	 * @param BlogConfigurationRepository $blogConfigurationRepository
	 * @param DefaultProfileRepository $defaultProfileRepository
	 */
	public function __construct(ProfileRepository           $profileRepository,
								BlogConfigurationRepository $blogConfigurationRepository,
								DefaultProfileRepository    $defaultProfileRepository
	)
	{
		$this->profileRepository = $profileRepository;
		$this->blogConfigurationRepository = $blogConfigurationRepository;
		$this->defaultProfileRepository = $defaultProfileRepository;
	}

	/**
	 * Returns ID NAME and IS_ACTIVE for all profiles
	 *
	 * @return array
	 */
	public function findAll()
	{
		return $this->profileRepository->findAll();
	}

	/**
	 * Find all profiles with their associated sites.
	 */
	public function findAllProfileAssociations()
	{
		$profiles = $this->profileRepository->findAllIds();
		$r = array();

		foreach ($profiles as $profileId) {
			$data = $this->blogConfigurationRepository->findProfileAssociations($profileId);
			$r[$profileId] = $data;
		}

		return $r;
	}

	/**
	 * Check if the key 'type' of the array $data exists.
	 *
	 * @param array $data
	 *
	 * @return bool
	 */
	public function validateType(array $data)
	{
		if (isset($data['type'])) {
			return true;
		}

		return false;
	}

	/**
	 * Save the profile.
	 *
	 * @param array $data
	 * @param int $profile
	 *
	 * @return bool|false|int
	 */
	public function saveProfile(array $data, $profile)
	{
		$dataToSave = (!empty($data)) ? $data['options'] : array();

		if (empty($dataToSave) || empty($dataToSave['profile_name'])) {
			return false;
		}

		return $this->saveProfileInternal($dataToSave, $profile);
	}

	/**
	 * Delegate the save call to the corresponding repository method.
	 *
	 * @param array $data
	 * @param int $profile
	 *
	 * @return int
	 */
	protected function saveProfileInternal(array $data, $profile)
	{
		if (empty($profile)) {
			return $this->profileRepository->insertProfileData($data);
		}

		$this->profileRepository->updateProfileData($data, $profile);

		return $profile;
	}

	/**
	 * Create a profile with the $data.
	 *
	 * @param array $data
	 *
	 * @return bool|false|int|void
	 */
	function addProfile(array $data)
	{
		if (!$this->validateName($data) || !$this->validateDescription($data)) {
			return;
		}

		return $this->profileRepository->insert($data['name'], $data['description']);
	}

	/**
	 * Delete a profile with the data from the $data.
	 *
	 * @param int $id
	 *
	 * @return array|bool
	 */
	public function deleteProfile($id)
	{
		if (empty($id)) {
			return false;
		}

		$defaultProfileId = $this->defaultProfileRepository->findProfileId();

		if ($id == $defaultProfileId) {
			$this->defaultProfileRepository->saveProfileId(-1);
		}

		try {
			$this->profileRepository->delete($id);
		} catch (\Exception $e) {
			return Message::error(__('An error occurred while deleting the profile.', 'next-active-directory-integration'))->toArray();
		}

		return Message::success(__('The profile was deleted successfully.', 'next-active-directory-integration'))->toArray();
	}

	/**
	 * Change a profile with the data from the $data.
	 *
	 * @param array $data
	 */
	function changeProfile($data)
	{
		if (!$this->validateId($data) || !$this->validateName($data) || !$this->validateDescription($data)) {
			return;
		}

		$this->profileRepository->updateName($data['id'], $data['name']);
		$this->profileRepository->updateDescription($data['id'], $data['description']);
	}

	/**
	 * Check if the key 'type' of the array $data exists.
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function validateName($data)
	{
		if (isset($data['name'])) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the key 'type' of the array $data exists.
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function validateDescription($data)
	{
		if (isset($data['description'])) {
			return true;
		}

		return false;
	}

	/**
	 * Check if the key 'type' of the array $data exists.
	 *
	 * @param $data
	 *
	 * @return bool
	 */
	public function validateId($data)
	{
		if (isset($data['id'])) {
			return true;
		}

		return false;
	}
}