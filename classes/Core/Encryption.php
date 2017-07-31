<?php
if ( ! defined( 'ABSPATH' ) )
{
	die( 'Access denied.' );
}

if ( class_exists( 'NextADInt_Core_Encryption' ) )
{
	return;
}

/**
 * NextADInt_Core_Encryption provides methods to encrypt and decrypt
 * credentials e.g for the synchronization between WordPress and Active Directory users.
 * This class uses the defuse/php-encryption library and is PHP 7.1 compatible.
 * Link to the library https://github.com/defuse/php-encryption
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access public
 */
class NextADInt_Core_Encryption
{
	/** @var Monolog\Logger */
	private $logger;



	public function __construct()
	{
		$this->logger = NextADInt_Core_Logger::getLogger();
	}

	/**
	 * This method will encrypt the $plainText and return the encrypted text.
	 *
	 * @param $plainText
	 *
	 * @return string
	 */
	public function encrypt( $plainText )
	{
		$password = 'Next Active Directory Integration' . AUTH_SALT;

		try
		{
			$encryptedText = Defuse\Crypto\Crypto::encryptWithPassword( $plainText, $password );
		} catch ( Exception $e )
		{
			// prevent the PHP stack trace display by catching all exception because the stack trace can contain the $password.
			$this->logger->warning( 'Plain text can not be encrypted. ' . $e->getMessage());

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
	public function decrypt( $encryptedText )
	{
		$password = 'Next Active Directory Integration' . AUTH_SALT;

		// do not decrypt empty texts
        if (!$encryptedText) {
            return false;
        }

		try
		{
			$plainText = Defuse\Crypto\Crypto::decryptWithPassword( $encryptedText, $password );
		} catch ( Exception $e )
		{
			// prevent the PHP stack trace display by catching all exception because the stack trace can contain the $password.
			$this->logger->warn( 'Encrypted text "' . $encryptedText . '" can not be decrypted. ' . $e->getMessage());

			return false;
		}

		return $plainText;
	}
}