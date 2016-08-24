<?php
if (!defined('ABSPATH')) {
	die('Access denied.');
}

if (class_exists('NextADInt_Core_Encryption')) {
	return;
}

/**
 * NextADInt_Core_Encryption provides methods to encrypt and decrypt
 * credentials e.g for the synchronization between WordPress and Active Directory users.
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Core_Encryption
{
	const KEY_HASH = 'md5';
	const KEY_SALT1 = 'Active Directory Integration';
	const CUSTOM_KEY_OPTION_NAME = 'key_salt';
	const TEXT_HASH = 'sha256';
	const TEXT_HASH_LENGTH = '32';
	const CIPHER = MCRYPT_RIJNDAEL_256;
	const MODE = MCRYPT_MODE_CBC;
	const RANDOM = MCRYPT_DEV_URANDOM;
	const MAC_HASH = 'sha256';
	const MAC_HASH_LENGTH = 64;
	const ENCODING = 'UTF8';

	/**
	 * Encrypt the $plainText with the underlying encryption method
	 *
	 * @param string $plainText
	 *
	 * @return string base64 encoded
	 */
	public function encrypt($plainText)
	{
		// save current encoding and switch to UTF-8
		$encoding = mb_internal_encoding();
		mb_internal_encoding(self::ENCODING); //TODO remove it?

		// secret key with two salts
		$key = $this->getKey();

		// create random iv
		$iv = $this->getRandomIv();

		// generate HMAC-SHA-256
		// this hash validates against IV and plainText
		$mac = hash_hmac(self::MAC_HASH, $iv . $plainText, $key);

		// data to be encrypted, it consists of $mac and $plainText
		$data = $mac . $plainText;

		// encrypt $data
		$encrypted = mcrypt_encrypt(self::CIPHER, $key, $data, self::MODE, $iv);

		// add prefix $iv to encrypted $data
		$output = base64_encode($iv) . '_' . base64_encode($encrypted);

		// switch to the old character encoding
		mb_internal_encoding($encoding); //TODO remove it?

		return $output;
	}

	/**
	 * Return the key which is based upon the AUTH_SALT and KEY_SALT1
	 * @return string
	 */
	public function getKey()
	{
		$algorithm = self::KEY_HASH;
		$key = AUTH_SALT . self::KEY_SALT1;
		$hash = hash($algorithm, $key);

		return $hash;
	}

	/**
	 * Return the random IV
	 *
	 * @return string
	 */
	public function getRandomIv()
	{
		$ivSize = $this->getIvSize();
		$random = self::RANDOM;
		$iv = mcrypt_create_iv($ivSize, $random);

		return $iv;
	}

	/**
	 * Get size of random IV
	 *
	 * @return int
	 */
	public function getIvSize()
	{
		$cipher = self::CIPHER;
		$mode = self::MODE;
		$size = mcrypt_get_iv_size($cipher, $mode);

		return $size;
	}

	/**
	 * Decrypt $cipherText with underlying encryption
	 *
	 * @param string $base64
	 *
	 * @return string plain text
	 */
	public function decrypt($base64)
	{
		// save current encoding and switch to UTF-8
		$encoding = mb_internal_encoding();
		mb_internal_encoding(self::ENCODING);

		// split $data into $iv and $encrypted
		$parts = mb_split('_', $base64);
		if (sizeof($parts) !== 2) {
			return false;
		}

		// split $data into $iv and $encrypted
		$iv = base64_decode($parts[0]);
		$encrypted = base64_decode($parts[1]);

		// get key
		$key = $this->getKey();
		// decrypt $data
		$data = mcrypt_decrypt(self::CIPHER, $key, $encrypted, self::MODE, $iv);

		// split $data into $mac and $plainText
		$mac = mb_substr($data, 0, self::MAC_HASH_LENGTH);
		$plainText = mb_substr($data, self::MAC_HASH_LENGTH);

		// remove padding from $plainText
		$plainText = rtrim($plainText, "\0");

		// create 'new' $mac
		$newMac = hash_hmac(self::MAC_HASH, $iv . $plainText, $key);

		// 'new' $mac and the 'old' $mac must be identical
		if (!next_ad_int_hash_equals($mac, $newMac)) {
			return false;
		}

		// switch to the old character encoding
		mb_internal_encoding($encoding);

		return $plainText;
	}
}