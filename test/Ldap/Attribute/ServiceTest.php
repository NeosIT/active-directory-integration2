<?php

/**
 * Ut_Ldap_Attribute_ServiceTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class Ut_Ldap_Attribute_ServiceTest extends Ut_BasicTest
{
	/* @var Ldap_Attribute_Repository|PHPUnit_Framework_MockObject_MockObject $configuration */
	private $attributeRepository;

	/**
	 * @var Ldap_Connection|PHPUnit_Framework_MockObject_MockObject $ldapConnection
	 */
	private $ldapConnection;

	public function setUp()
	{
		$this->attributeRepository = $this->createMock('Ldap_Attribute_Repository');
		$this->ldapConnection = $this->createMock('Ldap_Connection');

		WP_Mock::setUp();

		WP_Mock::wpFunction(
			'__', array(
				'args'       => array(WP_Mock\Functions::type('string'), ADI_I18N),
				'return_arg' => 0,
			)
		);
	}

	public function tearDown()
	{
		WP_Mock::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return Ldap_Attribute_Service|PHPUnit_Framework_MockObject_MockObject
	 */
	public function sut($methods)
	{
		return $this->getMockBuilder('Ldap_Attribute_Service')
			->setConstructorArgs(array(
				$this->ldapConnection,
				$this->attributeRepository,
			))
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

		$attribute = new Ldap_Attribute();
		$attribute->setMetakey('meta');
		$attribute->setDescription('desc');
		$attribute->setType('string');
		$attribute->setSyncable(true);
		$attribute->setViewable(true);

		$expected = new Ldap_Attributes(false, array($attribute));
		$credentials = new Adi_Authentication_Credentials('sam@test.ad', 'pw');

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

		$actual = Ldap_Attribute_Service::getLdapAttribute('cn', $attributeValues);
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

		$actual = Ldap_Attribute_Service::getLdapAttribute('telephonenumber', $attributeValues);
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

		$actual = Ldap_Attribute_Service::getLdapAttribute('cn', $attributeValues);
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

		$actual = Ldap_Attribute_Service::getLdapAttribute('telephonenumber', $attributeValues);
		$this->assertEquals('', $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withList_delegateToRightMethod()
	{
		$metaObject = new Ldap_Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('list');

		$adResponse = array(
			'metaKey' => "someValue" . "\n" . "coolValue",
		);

		$expected = array(
			'someValue',
			'coolValue',
		);

		$actual = Ldap_Attribute_Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withString_returnArrayWithString()
	{
		$metaObject = new Ldap_Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => array('someValue'),
		);

		$actual = Ldap_Attribute_Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals($adResponse['metaKey'], $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withEmptyValue_returnArrayWithSpace()
	{
		$metaObject = new Ldap_Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => '',
		);

		$actual = Ldap_Attribute_Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals(array(' '), $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withNonArrayResult_returnArrayWithResult()
	{
		$metaObject = new Ldap_Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => 'Krabbenburger Geheimformel',
		);

		$actual = Ldap_Attribute_Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals(array($adResponse['metaKey']), $actual);
	}

	/**
	 * @test
	 */
	public function getObjectSid_itReturnsObjectSidOfUsername()
	{
		$this->markTestIncomplete(
			'This test has not been implemented yet.'
		);
	}
}