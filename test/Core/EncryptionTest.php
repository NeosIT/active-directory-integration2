<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Core_EncryptionTest extends PHPUnit_Framework_TestCase
{
	/* @var Core_Encryption $enryptionHandler */
	private $encryptionHandler;

	//setting up encryptionHandler
	public function setUp()
	{
		$this->encryptionHandler = new Core_Encryption();
	}

	public function testEncrypt_encryptPlainText()
	{
		$plainText = 'TestTestTest';
		$encryptedPlainText = $this->encryptionHandler->encrypt($plainText); //encrypting

		$this->assertEquals(173, strlen($encryptedPlainText));
	}

	//testing plaintext encryption and decryption on plaintext example
	public function testEncrypt_encryptAndDecryptPlainText()
	{
		$plaintext = 'testtesttest';
		$encryptedPlainText = $this->encryptionHandler->encrypt($plaintext); //encrypting
		$decryptedPlainText = $this->encryptionHandler->decrypt($encryptedPlainText); //decrypting

		$this->assertEquals(
			$decryptedPlainText, $plaintext
		); //comparing input with output after encryption and decryption.
	}

	public function testEncrypt_encryptSamePlainTextTwice_encrytedTextsWillBeDifferent()
	{
		$plainText = 'plain';
		$encrypted1 = $this->encryptionHandler->encrypt($plainText);
		$encrypted2 = $this->encryptionHandler->encrypt($plainText);

		$this->assertTrue($encrypted1 !== $encrypted2);
	}

	public function testDecrypt_decryptEncryptedText()
	{
		$encryptedText
			= 'iJJg0bUBGel2H3yI2M3SVvfznAURBKU8q2DyMlUKmDA=_CsKZQsl857xDEFJLvfnafsjrwFFVvWglHb6rqjOcOstmZogFlwFnCHyBSTBNmQbHAF3nDAi6qstQyfLuhN1tuQaMUX12SP/lIx5lgoeE7NhurQ06PdL5Ypwe469AqBFW';
		$plainText = $this->encryptionHandler->decrypt($encryptedText);

		$this->assertEquals('TestTestTest', $plainText);
	}
}
