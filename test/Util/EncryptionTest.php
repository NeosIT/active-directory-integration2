<?php

namespace Dreitier\Util;

use Dreitier\Test\BasicTestCase;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class EncryptionTest extends BasicTestCase
{
	/* @var Encryption $enryptionHandler */
	private $encryptionHandler;

	/**
	 * // setting up encryptionHandler
	 */
	public function setUp(): void
	{
		parent::setUp();
		$this->encryptionHandler = new Encryption();
	}

	/**
	 * @test
	 */
	public function encrypt_encryptPlainText()
	{
		$plainText = 'AD-Password';
		$encryptedPlainText = $this->encryptionHandler->encrypt($plainText);

		$this->assertNotEmpty($encryptedPlainText);
	}

	/**
	 * testing plaintext encryption and decryption on plaintext example
	 * @test
	 */
	public function encrypt_encryptAndDecryptPlainText()
	{
		$plaintext = 'testtesttest';
		$encryptedPlainText = $this->encryptionHandler->encrypt($plaintext);
		$decryptedPlainText = $this->encryptionHandler->decrypt($encryptedPlainText);

		$this->assertEquals($decryptedPlainText, $plaintext);
	}

	/**
	 * @test
	 */
	public function encrypt_encryptSamePlainTextTwice_encrytedTextsWillBeDifferent()
	{
		$plainText = 'plain';
		$encrypted1 = $this->encryptionHandler->encrypt($plainText);
		$encrypted2 = $this->encryptionHandler->encrypt($plainText);

		$this->assertTrue($encrypted1 !== $encrypted2);
	}

	const UNENCRYPTED_STRING = 'some-string';
	const ENCRYPTED_STRING_WITHOUT_AUTH_SALT = 'def502001be3090326b042e65abae8f8be685c2729fc31ceb03992eb2453bfe41e130f52a8b0640aa63335b4a24174ec438e707f24313b1b8250955664993ebc6f9889b918ed95a0fe646970396774fe23e530fe0f680b1133f6655dd80bc4';

	/**
	 * @test
	 * @see https://github.com/NeosIT/active-directory-integration2/issues/164
	 */
	public function decrypt_decryptEncryptedText()
	{
		$expected = self::UNENCRYPTED_STRING;
		// die($this->encryptionHandler->encrypt($expected);
		$encryptedString = self::ENCRYPTED_STRING_WITHOUT_AUTH_SALT;

		$plainText = $this->encryptionHandler->decrypt($encryptedString);

		$this->assertEquals($expected, $plainText);
	}

	/**
	 * This test runs in a separate process to not interfere with already defined constants in previous tests (GH_173...)
	 *
	 * @test
	 * @issue #164
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function GH_164_when_AUTH_SALT_isPresent_itIsUsedToDecrypt()
	{
		// AUTH_SALT has been removed from PHPUnit bootstrapping
		define("AUTH_SALT", "a_random_auth_salt_string");

		$expected = self::UNENCRYPTED_STRING;
		// $encryptedString = $this->encryptionHandler->encrypt($expected);
		$encryptedString = 'def50200a3d1b8eb1a9277040fc71ddbf00f1b15d65d84fea5c998a113ba312da483c38b0d3fb91fd49fc07518003d59b1d693dd89f7ed21c9ce5d9ce9394330706a0a5b581a36e362f00c9fb2f4957548f9ff80bf627b0528b8c6526010ea';

		$plainText = $this->encryptionHandler->decrypt($encryptedString);
		$this->assertNotEquals(self::ENCRYPTED_STRING_WITHOUT_AUTH_SALT, $plainText);
		$this->assertEquals($expected, $plainText);
	}

	/**
	 * This test runs in a separate process to not interfere with already defined constants in previous tests (GH_164...)
	 *
	 * @test
	 * @issue #173
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function GH_173_when_NEXT_ACTIVE_DIRECTORY_INTEGRATION_ENCRYPTION_KEY_isPresent_itHasPrecedenceOver_AUTH_SALT()
	{
		// assume, that AUTH_SALT has been defined
		define("AUTH_SALT", "new_auth_salt");
		// instead of using AUTH_SALT, we use our constant
		define("NEXT_ACTIVE_DIRECTORY_INTEGRATION_ENCRYPTION_KEY", "qwerty");

		$expected = self::UNENCRYPTED_STRING;
		// $encryptedString = $this->encryptionHandler->encrypt($expected);
		$encryptedString = 'def50200978836f1b920fe1ea637796dc2fb43d867a3f023a3d5d5772d254371a9188a77b94f7031b0467aef74ba68dd774ef069718753b0e0dce39749caf64fd0275d38292646a4aaf04f85424dbc57a8bc5b7a51cfb3f5814d750ff96c2e';

		$plainText = $this->encryptionHandler->decrypt($encryptedString);
		$this->assertEquals($expected, $plainText);
	}

	/**
	 * @test
	 */
	public function decrypt_withModifiedEncryptedText_returnFalse()
	{
		$encryptedText = 'modified';
		$plainText = $this->encryptionHandler->decrypt($encryptedText);

		$this->assertEquals(false, $plainText);
	}

	/**
	 * @test
	 */
	public function decrypt_emptyEncryptedText_returnFalse()
	{
		$plainText = $this->encryptionHandler->decrypt('');

		$this->assertEquals(false, $plainText);
	}
}