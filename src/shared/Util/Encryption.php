<?php

namespace Dreitier\Util;

use Dreitier\Nadi\Log\NadiLog;
use Dreitier\Nadi\Vendor\Defuse\Crypto\Crypto;
use Dreitier\Nadi\Vendor\Monolog\Logger;

/**
 * Provides methods to encrypt and decrypt
 * credentials e.g for the synchronization between WordPress and Active Directory users.
 * This class uses the defuse/php-encryption library and is compatible with PHP 8.1.
 * Link to the library https://github.com/defuse/php-encryption
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class Encryption
{
	/** @var Logger */
	private $logger;

	public function __construct()
	{
		$this->logger = NadiLog::getInstance();
	}

	/**
	 * Return `AUTH_SALT` constant or an empty string if not defined.
	 * This has been added to make NADI compatible with newer PHP versions and WordPress installation in which `AUTH_SALT` is not defined.
	 *
	 * To be able to change the `AUTH_SALT`, one can define a key `NEXT_ACTIVE_DIRECTORY_INTEGRATION_ENCRYPTION_KEY` with the old `AUTH_SALT`.
	 *
	 * @issue #164
	 * @issue #173
	 * @see https://github.com/NeosIT/active-directory-integration2/issues/164
	 */
	public static function getSalt()
	{
		if (defined('NEXT_ACTIVE_DIRECTORY_INTEGRATION_ENCRYPTION_KEY')) {
			return NEXT_ACTIVE_DIRECTORY_INTEGRATION_ENCRYPTION_KEY;
		}

		if (defined('AUTH_SALT')) {
			return AUTH_SALT;
		}

		return '';
	}

	/**
	 * This method will encrypt the $plainText and return the encrypted text.
	 *
	 * @param $plainText
	 *
	 * @return string
	 */
	public function encrypt($plainText)
	{
		$password = 'Next Active Directory Integration' . self::getSalt();

		try {
			$encryptedText = Crypto::encryptWithPassword($plainText, $password);
		} catch (\Exception $e) {
			// prevent the PHP stack trace display by catching all exception because the stack trace can contain the $password.
			$this->logger->warning('Plain text can not be encrypted. ' . $e->getMessage());

			return false;
		}

		return $encryptedText;
	}

	/**
	 * This method will decrypt the $encryptedText and return the plain text.
	 *
	 * @param $encryptedText
	 *
	 * @return string
	 */
	public function decrypt($encryptedText)
	{
		$password = 'Next Active Directory Integration' . self::getSalt();

		// do not decrypt empty texts
		if (!$encryptedText) {
			return false;
		}

		try {
			$plainText = Crypto::decryptWithPassword($encryptedText, $password);
		} catch (\Exception $e) {
			// prevent the PHP stack trace display by catching all exception because the stack trace can contain the $password.
			$this->logger->warning('Encrypted text "' . $encryptedText . '" can not be decrypted. ' . $e->getMessage());

			return false;
		}

		return $plainText;
	}
}