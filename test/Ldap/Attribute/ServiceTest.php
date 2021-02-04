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

	public function setUp(): void
	{
		parent::setUp();

		$this->attributeRepository = $this->createMock('NextADInt_Ldap_Attribute_Repository');
		$this->ldapConnection = $this->createMock('NextADInt_Ldap_Connection');

		if (!class_exists('adLDAP')) {
			//get adLdap
			require_once NEXT_AD_INT_PATH . '/vendor/adLDAP/adLDAP.php';
		}

		$this->adLdap = parent::createMock('adLDAP');
	}

	public function tearDown(): void
	{
		parent::tearDown();
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
	public function resolveLdapAttributes_triggersNecessaryMethods()
	{
		$sut = $this->sut(array('findLdapAttributesOfUser'));

		$attribute = new NextADInt_Ldap_Attribute();
		$attribute->setMetakey('meta');
		$attribute->setDescription('desc');
		$attribute->setType('string');
		$attribute->setSyncable(true);
		$attribute->setViewable(true);

		$expected = new NextADInt_Ldap_Attributes(false, array($attribute));
		$userQuery = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials('sam@test.ad', 'pw')->toUserQuery()->withGuid('guid');

		$sut->expects($this->exactly(3))
			->method('findLdapAttributesOfUser')
			->withConsecutive(
				array($this->callback(function (NextADInt_Ldap_UserQuery $q) use ($userQuery) {
					return $q->getPrincipal() == 'guid' && $q->isGuid();
				})),
				array($this->callback(function (NextADInt_Ldap_UserQuery $q) use ($userQuery) {
					return $q->getPrincipal() == 'sam@test.ad' && !$q->isGuid();
				})),
				array($this->callback(function (NextADInt_Ldap_UserQuery $q) use ($userQuery) {
					return $q->getPrincipal() == 'sam' && !$q->isGuid();
				})),
			)
			->willReturn($expected);

		$actual = $sut->resolveLdapAttributes($userQuery);

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
		$sut = $this->sut(array('findLdapCustomAttributeOfUser'));
		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials("user@upn");

		$objectSid = 'S-1-5-21-2127521184-1604012920-1887927527-72713';

		$sut->expects($this->once())
			->method('findLdapCustomAttributeOfUser')
			->with($credentials->toUserQuery(), 'objectsid')
			->willReturn(
				hex2bin(adLDAP::sidStringToHex($objectSid)));

		$this->assertEquals('S-1-5-21-2127521184-1604012920-1887927527-72713', $sut->getObjectSid($credentials)->getFormatted());
	}

	/**
	 * @test
	 * @issue ADI-412
	 */
	public function resolveLdapCustomAttribute_whenUserPrincipalNameReturnsNothing_itUsesSamaccountName()
	{
		$sut = $this->sut(array('findLdapCustomAttributeOfUser'));
		$sAMAccountName = 'user';
		$upn = $sAMAccountName . '@upnsuffix';

		$credentials = NextADInt_Adi_Authentication_PrincipalResolver::createCredentials($upn);
		$userQuery = $credentials->toUserQuery();
		$attribute = 'attribute';

		$sut->expects($this->exactly(2))
			->method('findLdapCustomAttributeOfUser')
			->withConsecutive(
			// TODO
				array($this->callback(function (NextADInt_Ldap_UserQuery $userQuery) use ($upn) {
					return $userQuery->getPrincipal() == $upn;
				}), $attribute),
				array($this->callback(function (NextADInt_Ldap_UserQuery $userQuery) use ($sAMAccountName) {
					return $userQuery->getPrincipal() == $sAMAccountName;
				}), $attribute)
			)
			->will(
				$this->onConsecutiveCalls(
					false,
					'value'
				)
			);

		$this->assertEquals('value', $sut->resolveLdapCustomAttribute($userQuery, $attribute));
	}

	/**
	 * @test
	 * @issue ADI-412
	 */
	public function findLdapCustomAttributeOfUser_whenAttributeIsAvailable_itReturnsValue()
	{
		$userQuery = NextADInt_Ldap_UserQuery::forPrincipal('username');

		$sut = $this->sut(array('parseLdapResponse'));
		$attribute = "objectsid";
		$attributes = array($attribute);
		$rawResponse = array($attribute => 'value');

		$this->ldapConnection->expects($this->once())
			->method('findAttributesOfUser')
			->with($userQuery, $attributes)
			->willReturn($rawResponse);

		$sut->expects($this->once())
			->method('parseLdapResponse')
			->with($attributes, $rawResponse)
			->willReturn($rawResponse);

		$this->assertEquals('value', $sut->findLdapCustomAttributeOfUser($userQuery, $attribute));
	}

	/**
	 * @test
	 * @issue ADI-145
	 */
	public function ADI_145_findLdapAttributesOfUser_itCallsFilter_nextadi_ldap_filter_synchronizable_attributes()
	{
		$userQuery = NextADInt_Ldap_UserQuery::forPrincipal('username');

		$sut = $this->sut(array('parseLdapResponse'));
		$attributeNames = array('a', 'b');
		$modifiedAttributeNames = array('a', 'c');

		$this->attributeRepository->expects($this->once())
			->method('getAttributeNames')
			->willReturn($attributeNames);

		$this->ldapConnection->expects($this->once())
			->method('findAttributesOfUser')
			->with($userQuery, $modifiedAttributeNames);

		\WP_Mock::onFilter(NEXT_AD_INT_PREFIX . 'ldap_filter_synchronizable_attributes')
			->with($attributeNames, $userQuery)
			->reply($modifiedAttributeNames);

		$sut->findLdapAttributesOfUser($userQuery);
	}
}