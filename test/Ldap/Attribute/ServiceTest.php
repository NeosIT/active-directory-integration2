<?php

namespace Dreitier\Ldap\Attribute;

use Dreitier\AdLdap\AdLdap;
use Dreitier\Ldap\Attributes;
use Dreitier\Ldap\Connection;
use Dreitier\Ldap\UserQuery;
use Dreitier\Nadi\Authentication\PrincipalResolver;
use Dreitier\Test\BasicTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @author Sebastian Weinert <swe@neos-it.de>
 * @author Danny Meißner <dme@neos-it.de>
 * @access private
 */
class ServiceTest extends BasicTestCase
{
	/* @var Repository|MockObject $configuration */
	private $attributeRepository;

	/**
	 * @var Connection|MockObject $ldapConnection
	 */
	private $ldapConnection;

	/**
	 * @var adLDAP|MockObject $adLdap
	 */
	private $adLdap;

	public function setUp(): void
	{
		parent::setUp();

		$this->attributeRepository = $this->createMock(Repository::class);
		$this->ldapConnection = $this->createMock(Connection::class);

		$this->adLdap = parent::createMock(AdLdap::class);
	}

	public function tearDown(): void
	{
		parent::tearDown();
	}

	/**
	 * @param $methods
	 *
	 * @return Service|MockObject
	 */
	public function sut(array $methods = [])
	{
		return $this->getMockBuilder(Service::class)
			->setConstructorArgs(
				array(
					$this->ldapConnection,
					$this->attributeRepository,
				)
			)
			->onlyMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function parseLdapResponse_adResponseContainsNameAndValue_returnArrayWithNameAndValue()
	{
		$sut = $this->sut();

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

		$attribute = new Attribute();
		$attribute->setMetakey('meta');
		$attribute->setDescription('desc');
		$attribute->setType('string');
		$attribute->setSyncable(true);
		$attribute->setViewable(true);

		$expected = new Attributes(false, array($attribute));
		$userQuery = PrincipalResolver::createCredentials('sam@test.ad', 'pw')->toUserQuery()->withGuid('guid');

		$sut->expects($this->exactly(3))
			->method('findLdapAttributesOfUser')
			->with(...self::withConsecutive(
				array($this->callback(function (UserQuery $q) use ($userQuery) {
					return $q->getPrincipal() == 'guid' && $q->isGuid();
				})),
				array($this->callback(function (UserQuery $q) use ($userQuery) {
					return $q->getPrincipal() == 'sam@test.ad' && !$q->isGuid();
				})),
				array($this->callback(function (UserQuery $q) use ($userQuery) {
					return $q->getPrincipal() == 'sam' && !$q->isGuid();
				})),
			))
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

		$actual = Service::getLdapAttribute('cn', $attributeValues);
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

		$actual = Service::getLdapAttribute('telephonenumber', $attributeValues);
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

		$actual = Service::getLdapAttribute('cn', $attributeValues);
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

		$actual = Service::getLdapAttribute('telephonenumber', $attributeValues);
		$this->assertEquals('', $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withList_delegateToRightMethod()
	{
		$metaObject = new Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('list');

		$adResponse = array(
			'metaKey' => "someValue" . "\n" . "coolValue",
		);

		$expected = array(
			'someValue',
			'coolValue',
		);

		$actual = Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withString_returnArrayWithString()
	{
		$metaObject = new Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => array('someValue'),
		);

		$actual = Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals($adResponse['metaKey'], $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withEmptyValue_returnArrayWithSpace()
	{
		$metaObject = new Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => '',
		);

		$actual = Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals(array(' '), $actual);
	}

	/**
	 * @test
	 */
	public function getLdapValue_withNonArrayResult_returnArrayWithResult()
	{
		$metaObject = new Attribute();
		$metaObject->setMetakey('metaKey');
		$metaObject->setType('string');

		$adResponse = array(
			'metaKey' => 'Krabbenburger Geheimformel',
		);

		$actual = Service::getLdapValue($metaObject, $adResponse);
		$this->assertEquals(array($adResponse['metaKey']), $actual);
	}

	/**
	 * @test
	 */
	public function getObjectSid_itReturnsObjectSidOfUsername()
	{
		$sut = $this->sut(array('findLdapCustomAttributeOfUser'));
		$credentials = PrincipalResolver::createCredentials("user@upn");

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

		$credentials = PrincipalResolver::createCredentials($upn);
		$userQuery = $credentials->toUserQuery();
		$attribute = 'attribute';

		$sut->expects($this->exactly(2))
			->method('findLdapCustomAttributeOfUser')
			->with(...self::withConsecutive(
			// TODO
				array($this->callback(function (UserQuery $userQuery) use ($upn) {
					return $userQuery->getPrincipal() == $upn;
				}), $attribute),
				array($this->callback(function (UserQuery $userQuery) use ($sAMAccountName) {
					return $userQuery->getPrincipal() == $sAMAccountName;
				}), $attribute)
			))
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
		$userQuery = UserQuery::forPrincipal('username');

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
		$userQuery = UserQuery::forPrincipal('username');

		$sut = $this->sut(array('parseLdapResponse'));
		$attributeNames = array('a', 'b');
		$modifiedAttributeNames = array('a', 'c');

		$this->attributeRepository->expects($this->once())
			->method('getAttributeNames')
			->willReturn($attributeNames);

		$this->ldapConnection->expects($this->once())
			->method('findAttributesOfUser')
			->with($userQuery, $modifiedAttributeNames);

		\WP_Mock::onFilter(NEXT_ACTIVE_DIRECTORY_INTEGRATION_PREFIX . 'ldap_filter_synchronizable_attributes')
			->with($attributeNames, $userQuery)
			->reply($modifiedAttributeNames);

		$sut->findLdapAttributesOfUser($userQuery);
	}
}