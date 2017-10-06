<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Core_EncryptionTest extends Ut_BasicTest
{
	/* @var NextADInt_Core_Encryption $enryptionHandler */
	private $encryptionHandler;

	/**
	 * // setting up encryptionHandler
	 */
	public function setUp()
	{
		parent::setUp();
		$this->encryptionHandler = new NextADInt_Core_Encryption();
	}

	/**
	 * @test
	 */
	public function encrypt_encryptPlainText()
	{
		$plainText          = 'AD-Password';
		$encryptedPlainText = $this->encryptionHandler->encrypt( $plainText );

		$this->assertNotEmpty( $encryptedPlainText );
	}

	/**
	 * testing plaintext encryption and decryption on plaintext example
	 * @test
	 */
	public function encrypt_encryptAndDecryptPlainText()
	{
		$plaintext          = 'testtesttest';
		$encryptedPlainText = $this->encryptionHandler->encrypt( $plaintext );
		$decryptedPlainText = $this->encryptionHandler->decrypt( $encryptedPlainText );

		$this->assertEquals( $decryptedPlainText, $plaintext );
	}

	/**
	 * @test
	 */
	public function encrypt_encryptSamePlainTextTwice_encrytedTextsWillBeDifferent()
	{
		$plainText  = 'plain';
		$encrypted1 = $this->encryptionHandler->encrypt( $plainText );
		$encrypted2 = $this->encryptionHandler->encrypt( $plainText );

		$this->assertTrue( $encrypted1 !== $encrypted2 );
	}

	/**
	 * @test
	 */
	public function decrypt_decryptEncryptedText()
	{
		$encryptedText = 'def50200837a4602f5fc24f746a3d77e38e36d533665c2767494aee67f4504abf9007e6bf8b3dca8006260818' .
		                 'cab20dd160c9e1674421bbf4db38149ffe3943baff2be4b6ad1b4a761b6a6b946cbc164e9197103f605dfc3dd' .
		                 '1fc9198ff01f';
		$plainText = $this->encryptionHandler->decrypt( $encryptedText );

		$this->assertEquals( 'AD-Password', $plainText );
	}

	/**
	 * @test
	 */
	public function decrypt_withModifiedEncryptedText_returnFalse()
	{
		$encryptedText = 'modified';
		$plainText = $this->encryptionHandler->decrypt( $encryptedText );

		$this->assertEquals( false, $plainText );
	}

    /**
     * @test
     */
    public function decrypt_emptyEncryptedText_returnFalse()
    {
        $plainText = $this->encryptionHandler->decrypt( '' );

        $this->assertEquals( false, $plainText );
    }
}