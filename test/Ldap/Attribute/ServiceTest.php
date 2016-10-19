<?php

/**
 * Ut_NextADInt_Ldap_Attribute_ServiceTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Ldap_Attribute_ServiceTest extends Ut_BasicTest
{
	/* @var NextADInt_Ldap_Attribute_Repository|PHPUnit_Framework_MockObject_MockObject $configuration */
	private $attributeRepository;

	/**
	 * @var NextADInt_Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection
	 */
	private $ldapConnection;

	/**
	 * @var adLDAP|PHPUnit_Framework_MockObject_MockObject $adLdap
	 */
	private $adLdap;

	public function setUp()
	{
		$this->attributeRepository = $this->createMock('NextADInt_Ldap_Attribute_Repository');
		$this->ldapConnection = $this->createMock('NextADInt_Ldap_Connection');

		if (!class_exists('adLDAP')) {
			//get adLdap
			require_once NEXT_AD_INT_PATH . '/vendor/adLDAP/adLDAP.php';
		}

		WP_Mock::setUp();

		WP_Mock::wpFunction(
			'__', array(
				'args'       => array(WP_Mock\Functions::type('string'), NEXT_AD_INT_I18N),
				'return_arg' => 0,
			)
		);

		$this->adLdap = parent::createMock('adLDAP');
	}

	public function tearDown()
	{
		WP_Mock::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return NextADInt_Ldap_Attribute_Service|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder('NextADInt_Ldap_Attribute_Service')
			->setConstructorArgs(
				array(
					$this->ldapConnection,
					$this->attributeRepository,
				)
			)
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function parseLdapResponse_adResponseContainsNameAndValue_returnArrayWithNameAndValue()
	{
		$sut = $this->sut(null);

		$attributeNames = array(
			'cn',
		);

		$activeDirectoryResponse = array(
			'cn' => array(
				'count' => 1,
				'AlbeTrem4',
			),
		);

		$expected = array(
			'cn' => 'AlbeTrem4',
		);

		$actual = $sut->parseLdapResponse($attributeNames, $activeDirectoryResponse);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findLdapAttributesOfUser_triggersNecessaryMethods()
	{
		$sut = $this->sut(array('findLdapAttributesOfUsername'));

		$attribute = new NextADInt_Ldap_Attribute();
		$attribute->setMetakey('meta');
		$attribute->setDescription('desc');
		$attribute->setType('string');
		$attribute->setSyncable(true);
		$attribute->setViewable(true);

		$expected = new NextADInt_Ldap_Attributes(false, array($attribute));
		$credentials = new NextADInt_Adi_Authentication_Credentials('sam@test.ad', 'pw');

		$sut->expects($this->exactly(3))
			->method('findLdapAttributesOfUsername')
			->withConsecutive(
				array('guid', true),
				array('sam'),
				array('sam@test.ad')
			)
			->willReturn($expected);

		$actual = $sut->findLdapAttributesOfUser($credentials, 'guid');

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getLdapAttribute_withArrayElementCount_removeArrayElementCount()
	{
		$attributeValues = array(
			'cn' => array(
				'count' => 1,
				'AlbeTrem4',
			),
		);

		$actual = NextADInt_Ldap_Attribute_Service::getLdapAttribute('cn', $attributeValues);
		$this->assertEquals('AlbeTrem4', $actual);
	}

	/**
	 * @test
	 */
	public function getLdapAttribute_withMultipleValues_returnConcatenatedValues()
	{
		$attributeValues = array(
			'telephonenumber' => array(
				'123456789',
				'987654321',
			),
		);

		$expected = "123456789" . "\n" . "987654321";

		$actual = NextADInt_Ldap_Attribute_Service::getLdapAttribute('telephonenumber', $attributeValues);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getLdapAttribute_withSingleValue_returnValue()
	{
		$attributeValues = array(
			'cn' => 'Hugo68',
		);

		$actual = NextADInt_Ldap_Attribute_Service::getLdapAttribute('cn', $attributeValues);
		$this->assertEquals('Hugo68', $actual);
	}

	/**
	 * @test
	 */
	public function getLdapAttribute_wrongAttributeName_returnEmptyString()
	{
		$attributeValues = array(
			'cn' => 'Hugo68',
		);

		$actual = NextADInt_Ldap_Attribute_Service::getLdapAttribute('telephonenumber', $attributeValues);
		$this->assertEquals('', $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withList_delegateToRightMethod()
	{
		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('list');

		$adResponse = array(
			'metaKey' => "someValue" . "\n" . "coolValue",
		);

		$expected = array(
			'someValue',
			'coolValue',
		);

		$actual = NextADInt_Ldap_Attribute_Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withString_returnArrayWithString()
	{
		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => array('someValue'),
		);

		$actual = NextADInt_Ldap_Attribute_Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals($adResponse['metaKey'], $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withEmptyValue_returnArrayWithSpace()
	{
		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => '',
		);

		$actual = NextADInt_Ldap_Attribute_Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals(array(' '), $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withNonArrayResult_returnArrayWithResult()
	{
		$metaObject = new NextADInt_Ldap_Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => 'Krabbenburger Geheimformel',
		);

		$actual = NextADInt_Ldap_Attribute_Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals(array($adResponse['metaKey']), $actual);
	}

	/**
	 * @test
	 */
	public function getObjectSid_itReturnsObjectSidOfUsername()
	{
		$sut = $this->sut(array('parseLdapResponse'));

		$this->ldapConnection->expects($this->once())
			->method('findAttributesOfUser')
			->with('administrator', array('objectsid'), false)
			->willReturn(array());

		$sut->expects($this->once())
			->method('parseLdapResponse')
			->with(array('objectsid'), array())
			->willReturn(array("objectsid" => "S-1-5-21-0000000000-0000000000-0000000000-1234"));

		$this->ldapConnection->expects($this->once())
			->method('getAdLdap')
			->willReturn($this->adLdap);

		$this->adLdap->expects($this->once())
			->method('convertObjectSidBinaryToString')
			->with('S-1-5-21-0000000000-0000000000-0000000000-1234')
			->willReturn('S-1-5-21-0000000000-0000000000-0000000000');

		$sut->getObjectSid("administrator");
	}

	/**
	 * @test
	 * @issue ADI-145
	 */
	public function ADI_145_findLdapAttributesOfUsername_itCallsFilter_nextadi_ldap_filter_synchronizable_attributes() {
		$sut = $this->sut(array('parseLdapResponse'));
		$attributeNames = array('a', 'b');
		$modifiedAttributeNames = array('a', 'c');

		$this->attributeRepository->expects($this->once())
			->method('getAttributeNames')
			->willReturn($attributeNames);

		$this->ldapConnection->expects($this->once())
			->method('findAttributesOfUser')
			->with('username', $modifiedAttributeNames, false);

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'ldap_filter_synchronizable_attributes')
			->with($attributeNames, 'username', false)
			->reply($modifiedAttributeNames);

		$sut->findLdapAttributesOfUsername('username', false);
	}
}