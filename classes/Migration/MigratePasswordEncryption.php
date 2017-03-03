<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Migration_MigratePasswordEncryption')) {
	return;
}

/**
 * NextADInt_Migration_MigrateEncryption migrates the encrypted password for sync to ad, sync to workpress and sso.
 *
 * @author  Tobias Hellmann <the@neos-it.de>
 * @access
 */
class NextADInt_Migration_MigratePasswordEncryption extends NextADInt_Core_Migration_Configuration_Abstract
{
	public function __construct(NextADInt_Adi_Dependencies $dependencyContainer)
	{
		parent::__construct($dependencyContainer);
	}

	/**
	 * Get the position for this migration.
	 *
	 * @return integer
	 */
	public static function getId()
	{
		return 3;
	}

	/**
	 * Execute the migration.
	 * @throws Exception
	 */
	public function execute()
	{
		$this->migrateBlogs();
		$this->migrateProfiles();
	}

	/**
	 * Migrate the stored passwords.
	 *
	 * @param NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository
	 * @param                                                             $id
	 */
	protected function migrateConfig(NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository, $id )
	{
		$passwordOptionNames = array(
			NextADInt_Adi_Configuration_Options::SYNC_TO_AD_GLOBAL_PASSWORD,
			NextADInt_Adi_Configuration_Options::SYNC_TO_WORDPRESS_PASSWORD,
			NextADInt_Adi_Configuration_Options::SSO_PASSWORD
		);

		foreach ($passwordOptionNames as $optionName) {
			$this->convertEncryptedPassword($configurationRepository, $id, $optionName);
		}
	}

	/**
	 * Convert the a stored password from the old (with mcrypt, plugin version <= 2.0.11) to the new format
	 * (with https://github.com/defuse/php-encryption, OpenSSL, plugin version >= 2.0.12).
	 *
	 * @param NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository
	 * @param $id
	 * @param $optionName
	 */
	public function convertEncryptedPassword(NextADInt_Multisite_Configuration_Persistence_ConfigurationRepository $configurationRepository, $id, $optionName)
	{
		$encryptedPassword = $configurationRepository->findRawValue($id, $optionName);
		$password = $this->legacyDecrypt($encryptedPassword);
		$configurationRepository->persistSanitizedValue($id, $optionName, $password);
	}

	/**
	 * Decrypt the stored passwords from version <= 2.0.11
	 *
	 * @param $encryptedText
	 *
	 * @return bool|string
	 */
	public function legacyDecrypt($encryptedText)
	{
	    // check if mcrypt is available
        if (!NextADInt_Core_Util::native()->isLoaded('mcrypt')) {
            return false;
        }

		// save current encoding and switch to UTF-8
		$encoding = mb_internal_encoding();
		mb_internal_encoding('UTF8');

		// split $data into $iv and $encrypted
		$parts = mb_split('_', $encryptedText);
		if (sizeof($parts) !== 2) {
			return false;
		}

		// split $data into $iv and $encrypted
		$iv = base64_decode($parts[0]);
		$encrypted = base64_decode($parts[1]);

		// get key
		$key = hash('md5', AUTH_SALT . 'Active Directory Integration');
		// decrypt $data
		$data = @mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $encrypted, MCRYPT_MODE_CBC, $iv);

		// split $data into $mac and $plainText
		$mac = mb_substr($data, 0, 64);
		$plainText = mb_substr($data, 64);

		// remove padding from $plainText
		$plainText = rtrim($plainText, "\0");

		// create 'new' $mac
		$newMac = hash_hmac("sha256", $iv . $plainText, $key);

		// 'new' $mac and the 'old' $mac must be identical
		if (!next_ad_int_hash_equals($mac, $newMac)) {
			return false;
		}

		// switch to the old character encoding
		mb_internal_encoding($encoding);

		return $plainText;
	}
}