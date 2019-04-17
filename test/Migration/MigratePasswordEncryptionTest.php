<?php

/**
 * Ut_NextADInt_Ldap_ConnectionTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Migration_MigratePasswordEncryptionTest extends Ut_BasicTest
{
	const LDAPS_PREFIX = 'ldaps://';

	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileRepository|PHPUnit_Framework_MockObject_MockObject $profileRepository */
	private $profileRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository|PHPUnit_Framework_MockObject_MockObject $profileConfigurationRepository */
	private $profileConfigurationRepository;

	/** @var NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository|PHPUnit_Framework_MockObject_MockObject $blogConfigurationRepository */
	private $blogConfigurationRepository;

	/** @var NextADInt_Adi_Dependencies|PHPUnit_Framework_MockObject_MockObject $dependencyContainer */
	private $dependencyContainer;

    /* @var NextADInt_Core_Util_Internal_Native|\Mockery\MockInterface */
    private $internalNative;

    public function setUp()
	{
		parent::setUp();
		$this->dependencyContainer = parent::createMock( 'NextADInt_Adi_Dependencies' );

		$this->profileRepository              = parent::createMock( 'NextADInt_Multisite_Configuration_Persistence_ProfileRepository' );
		$this->profileConfigurationRepository = parent::createMock( 'NextADInt_Multisite_Configuration_Persistence_ProfileConfigurationRepository' );
		$this->blogConfigurationRepository    = parent::createMock( 'NextADInt_Multisite_Configuration_Persistence_BlogConfigurationRepository' );

		$this->dependencyContainer->expects( $this->once() )
		                          ->method( 'getProfileRepository' )
		                          ->willReturn( $this->profileRepository );

		$this->dependencyContainer->expects( $this->once() )
		                          ->method( 'getProfileConfigurationRepository' )
		                          ->willReturn( $this->profileConfigurationRepository );

		$this->dependencyContainer->expects( $this->once() )
		                          ->method( 'getBlogConfigurationRepository' )
		                          ->willReturn( $this->blogConfigurationRepository );

        $this->internalNative = $this->createMockedNative();
        NextADInt_Core_Util::native($this->internalNative);
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Migration_MigratePasswordEncryption|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut( $methods = null )
	{
		return $connection = $this->getMockBuilder( 'NextADInt_Migration_MigratePasswordEncryption' )
		                          ->setConstructorArgs( array(
			                          $this->dependencyContainer,
		                          ) )
		                          ->setMethods( $methods )
		                          ->getMock();
	}

	/**
	 * @requires extension mcrypt
     * @requires extension mcrypt
	 * @test
	 */
	public function legacyDecrypt_decryptOldPassword_returnPlainTextPassword() {
		$encryptedText = 'iJJg0bUBGel2H3yI2M3SVvfznAURBKU8q2DyMlUKmDA=_CsKZQsl857xDEFJLvfnafsjrwFFVvWglHb6rqjOcOstmZ' .
		                 'ogFlwFnCHyBSTBNmQbHAF3nDAi6qstQyfLuhN1tuQaMUX12SP/lIx5lgoeE7NhurQ06PdL5Ypwe469AqBFW';

		$sut = $this->sut();

        $this->internalNative->expects($this->once())
            ->method('isLoaded')
            ->with('mcrypt')
            ->willReturn(true);

		$plainText = $sut->legacyDecrypt($encryptedText);

		$this->assertEquals('TestTestTest', $plainText);
	}

    /**
     * @test
     */
    public function legacyDecrypt_withoutMcrypt_returnFalse() {
        $encryptedText = 'iJJg0bUBGel2H3yI2M3SVvfznAURBKU8q2DyMlUKmDA=_CsKZQsl857xDEFJLvfnafsjrwFFVvWglHb6rqjOcOstmZ' .
            'ogFlwFnCHyBSTBNmQbHAF3nDAi6qstQyfLuhN1tuQaMUX12SP/lIx5lgoeE7NhurQ06PdL5Ypwe469AqBFW';

        $sut = $this->sut();

        $this->internalNative->expects($this->once())
            ->method('isLoaded')
            ->with('mcrypt')
            ->willReturn(false);

        $plainText = $sut->legacyDecrypt($encryptedText);

        $this->assertEquals(false, $plainText);
    }
}
