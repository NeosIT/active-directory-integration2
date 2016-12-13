<?php

/**
 * Ut_NextADInt_Ldap_Attribute_RepositoryTest
 *
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Ldap_Attribute_RepositoryTest extends Ut_BasicTest
{
	/* @var NextADInt_Multisite_Configuration_Service|PHPUnit_Framework_MockObject_MockObject $configuration */
	private $configuration;

	public function setUp()
	{
		parent::setUp();

		$this->configuration = $this->getMockBuilder('NextADInt_Multisite_Configuration_Service')
			->disableOriginalConstructor()
			->setMethods(array('getOptionValue'))
			->getMock();
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	public function sut($methods = null)
	{
		return $this->getMockBuilder('NextADInt_Ldap_Attribute_Repository')
			->setConstructorArgs(array($this->configuration))
			->setMethods($methods)
			->getMock();
	}

	/**
	 * @test
	 */
	public function getCustomAttributeDefinitions_withCorrectString_returnParsedArray()
	{
		$sut = $this->sut(null);

		$string = 'attributeName1:string:next_ad_int_lastName:description:true:true:true' . ";"
			. 'attributeName2:string:next_ad_int_lastName:description:true:true:true';
		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES)
			->willReturn($string);

		$expected = array(
			'attributeName1' => array(
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE                 => 'string',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE  => 'next_ad_int_lastName',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_OVERWRITE_EMPTY      => 'true',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_DESCRIPTION          => 'description',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_SYNC_TO_AD           => 'true',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE => 'true',
			),
			'attributeName2' => array(
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE                 => 'string',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE  => 'next_ad_int_lastName',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_OVERWRITE_EMPTY      => 'true',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_DESCRIPTION          => 'description',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_SYNC_TO_AD           => 'true',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE => 'true',
			),
		);

		$actual = $sut->getCustomAttributeDefinitions();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getCustomAttributeDefinitions_withInvalidString_returnEmptyArray()
	{
		$sut = $this->sut(null);

		$string = '';
		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES)
			->willReturn($string);

		$actual = $sut->getCustomAttributeDefinitions();
		$this->assertEquals(array(), $actual);
	}

	/**
	 * @test
	 */
	public function getCustomAttributeDefinitions_calledMethodTwice_returnParsedArrayFromCache()
	{
		$sut = $this->sut(null);

		$string = 'attributeName1:string:next_ad_int_lastName:description:true:true:true';
		$this->configuration->expects($this->once())
			->method('getOptionValue')
			->with(NextADInt_Adi_Configuration_Options::ADDITIONAL_USER_ATTRIBUTES)
			->willReturn($string);

		$expected = array(
			'attributeName1' => array(
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE                 => 'string',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE  => 'next_ad_int_lastName',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_OVERWRITE_EMPTY      => 'true',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_DESCRIPTION          => 'description',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_SYNC_TO_AD           => 'true',
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE => 'true',
			),
		);

		$sut->getCustomAttributeDefinitions();
		$actual = $sut->getCustomAttributeDefinitions();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getAttributeNames_delegateToOtherMethod_returnArrayWithAttributeNames()
	{
		$sut = $this->sut(array('getWhitelistedAttributes'));

		$additionalAttribute = array(
			'attribute1' => new NextADInt_Ldap_Attribute(),
		);

		$sut->expects($this->once())
			->method('getWhitelistedAttributes')
			->willReturn($additionalAttribute);

		$expected = array(
			'attribute1',
		);

		$actual = $sut->getAttributeNames();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getWhitelistedAttributes_callOnce_checkDelegatedMethods()
	{
		$sut = $this->sut(array('createDefaultAttributes', 'createCustomAttributes'));

		$default = array(
			'cn' => new NextADInt_Ldap_Attribute(),
		);

		$all = array(
			'cn'      => $default['cn'],
			'ipphone' => new NextADInt_Ldap_Attribute(),
		);

		$sut->expects($this->once())
			->method('createDefaultAttributes')
			->with(array())
			->willReturn($default);

		$sut->expects($this->once())
			->method('createCustomAttributes')
			->with($default)
			->willReturn($all);

		$actual = $sut->getWhitelistedAttributes();
		$this->assertEquals($all, $actual);
	}

	/**
	 * @test
	 */
	public function getWhitelistedAttributes_callTwice_returnValueFromCache()
	{
		$sut = $this->sut(array('createDefaultAttributes', 'createCustomAttributes'));

		$default = array(
			'cn' => new NextADInt_Ldap_Attribute(),
		);

		$all = array(
			'cn'      => $default['cn'],
			'ipphone' => new NextADInt_Ldap_Attribute(),
		);

		$sut->expects($this->once())
			->method('createDefaultAttributes')
			->with(array())
			->willReturn($default);

		$sut->expects($this->once())
			->method('createCustomAttributes')
			->with($default)
			->willReturn($all);

		$sut->getWhitelistedAttributes();
		$actual = $sut->getWhitelistedAttributes();
		$this->assertEquals($all, $actual);
	}

	/**
	 * @test
	 */
	public function filterWhitelistedAttributes_filterForAll_returnAllMetaObjects()
	{
		$sut = $this->sut(array('getWhitelistedAttributes'));

		$metaObjects = array(
			'cn'      => new NextADInt_Ldap_Attribute(),
			'ipphone' => new NextADInt_Ldap_Attribute(),
		);
		$metaObjects['cn']->setViewable(false);
		$metaObjects['ipphone']->setViewable(true);

		$sut->expects($this->once())
			->method('getWhitelistedAttributes')
			->willReturn($metaObjects);

		$expects = array(
			'cn'      => $metaObjects['cn'],
			'ipphone' => $metaObjects['ipphone'],
		);

		$actual = $sut->filterWhitelistedAttributes(null);
		$this->assertEquals($expects, $actual);
	}

	/**
	 * @test
	 */
	public function filterWhitelistedAttributes_filterForShowFalse_returnAllNonViewableMetaObjects()
	{
		$sut = $this->sut(array('getWhitelistedAttributes'));

		$metaObjects = array(
			'cn'      => new NextADInt_Ldap_Attribute(),
			'ipphone' => new NextADInt_Ldap_Attribute(),
		);
		$metaObjects['cn']->setViewable(false);
		$metaObjects['ipphone']->setViewable(true);

		$sut->expects($this->once())
			->method('getWhitelistedAttributes')
			->willReturn($metaObjects);

		$expects = array(
			'cn' => $metaObjects['cn'],
		);

		$actual = $sut->filterWhitelistedAttributes(false);
		$this->assertEquals($expects, $actual);
	}

	/**
	 * @test
	 */
	public function filterWhitelistedAttributes_filterForShowTrue_returnAllViewableMetaObjects()
	{
		$sut = $this->sut(array('getWhitelistedAttributes'));

		$metaObjects = array(
			'cn'      => new NextADInt_Ldap_Attribute(),
			'ipphone' => new NextADInt_Ldap_Attribute(),
		);
		$metaObjects['cn']->setViewable(false);
		$metaObjects['ipphone']->setViewable(true);

		$sut->expects($this->once())
			->method('getWhitelistedAttributes')
			->willReturn($metaObjects);

		$expects = array(
			'ipphone' => $metaObjects['ipphone'],
		);

		$actual = $sut->filterWhitelistedAttributes(true);
		$this->assertEquals($expects, $actual);
	}

	/**
	 * @test
	 */
	public function createCustomAttributes_delegateToMethod_returnExpectedResult()
	{
		$sut = $this->sut(array('getCustomAttributeDefinitions', 'createAttribute'));

		$additionalAttributes = array(
			'cn' => array(
				'cn',
				'string',
				'metaKey',
			),
		);

		$sut->expects($this->once())
			->method('getCustomAttributeDefinitions')
			->willReturn($additionalAttributes);

		$cn = new NextADInt_Ldap_Attribute();

		$sut->expects($this->once())
			->method('createAttribute')
			->with($additionalAttributes['cn'], 'cn')
			->willReturn($cn);

		$expected = array(
			'cn' => $cn,
		);

		$actual = $sut->createCustomAttributes();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function createCustomAttributes_withNonEmptyArray_returnArrayWithValues()
	{
		$sut = $this->sut(array('getCustomAttributeDefinitions', 'createAttribute'));

		$additionalAttributes = array(
			'cn' => array(
				'cn',
				'string',
				'metaKey',
			),
		);

		$sut->expects($this->once())
			->method('getCustomAttributeDefinitions')
			->willReturn($additionalAttributes);

		$cn = new NextADInt_Ldap_Attribute();

		$sut->expects($this->once())
			->method('createAttribute')
			->with($additionalAttributes['cn'], 'cn')
			->willReturn($cn);

		$param = array(
			'ipphone' => new NextADInt_Ldap_Attribute(),
		);

		$expected = array(
			'ipphone' => $param['ipphone'],
			'cn'      => $cn,
		);

		$actual = $sut->createCustomAttributes($param);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function createDefaultAttributes_delegateToMethod_returnExpectedResult()
	{
		$sut = $this->sut(array('createAttribute'));

		$sut->createDefaultAttributes();

		$cn = new NextADInt_Ldap_Attribute();

		$sut->expects($this->any())
			->method('createAttribute')
			->withConsecutive(
				array(null, 'cn'),
				array(null, 'givenname'),
				array(null, 'sn'),
				array(null, 'displayname'),
				array(null, 'description'),
				array(null, 'mail'),
				array(null, 'samaccountname'),
				array(null, 'userprincipalname'),
				array(null, 'useraccountcontrol'),
				array(null, 'objectguid'),
				array(null, 'domainsid')
			)
			->will(
				$this->onConsecutiveCalls(
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute()
				)
			);

		$expected = array(
			'cn'                 => new NextADInt_Ldap_Attribute(),
			'givenname'          => new NextADInt_Ldap_Attribute(),
			'sn'                 => new NextADInt_Ldap_Attribute(),
			'displayname'        => new NextADInt_Ldap_Attribute(),
			'description'        => new NextADInt_Ldap_Attribute(),
			'mail'               => new NextADInt_Ldap_Attribute(),
			'samaccountname'     => new NextADInt_Ldap_Attribute(),
			'userprincipalname'  => new NextADInt_Ldap_Attribute(),
			'useraccountcontrol' => new NextADInt_Ldap_Attribute(),
			'objectguid'         => new NextADInt_Ldap_Attribute(),
			'domainsid'          => new NextADInt_Ldap_Attribute()
		);

		$actual = $sut->createDefaultAttributes();
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function createDefaultAttributes_withNonEmptyArray_returnArrayWithValues()
	{
		$sut = $this->sut(array('getCustomAttributeDefinitions', 'createAttribute'));

		$cn = new NextADInt_Ldap_Attribute();

		$sut->expects($this->any())
			->method('createAttribute')
			->withConsecutive(
				array(null, 'cn'),
				array(null, 'givenname'),
				array(null, 'sn'),
				array(null, 'displayname'),
				array(null, 'description'),
				array(null, 'mail'),
				array(null, 'samaccountname'),
				array(null, 'userprincipalname'),
				array(null, 'useraccountcontrol'),
				array(null, 'objectguid'),
				array(null, 'domainsid')
			)
			->will(
				$this->onConsecutiveCalls(
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute(),
					new NextADInt_Ldap_Attribute()
				)
			);

		$param = array(
			'ipphone' => new NextADInt_Ldap_Attribute(),
		);

		$expected = array(
			'ipphone'            => $param['ipphone'],
			'cn'                 => new NextADInt_Ldap_Attribute(),
			'givenname'          => new NextADInt_Ldap_Attribute(),
			'sn'                 => new NextADInt_Ldap_Attribute(),
			'displayname'        => new NextADInt_Ldap_Attribute(),
			'description'        => new NextADInt_Ldap_Attribute(),
			'mail'               => new NextADInt_Ldap_Attribute(),
			'samaccountname'     => new NextADInt_Ldap_Attribute(),
			'userprincipalname'  => new NextADInt_Ldap_Attribute(),
			'useraccountcontrol' => new NextADInt_Ldap_Attribute(),
			'objectguid'         => new NextADInt_Ldap_Attribute(),
			'domainsid'          => new NextADInt_Ldap_Attribute()
		);

		$actual = $sut->createDefaultAttributes($param);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function createAttribute_returnObject()
	{
		$sut = $this->sut(array('getViewableAttributeDefinitions'));
		$this->mockFunction__();

		$attribute = array(
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE                 => 'string',
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE  => 'next_ad_int_lastName',
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_SYNC_TO_AD           => 'true',
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE => 'true',
		);


		$actual = $sut->createAttribute($attribute, "adAttributeName");
		$this->assertEquals('string', $actual->getType());
		$this->assertEquals('next_ad_int_lastName', $actual->getMetakey());
		$this->assertEquals('adAttributeName', $actual->getDescription());
		$this->assertEquals(true, $actual->isSyncable());
		$this->assertEquals(true, $actual->isViewable());
	}

	/**
	 * @test
	 */
	public function createAttribute_withEmptyAttribute_returnsMetaKeyWithPrefixAndAttributeName()
	{
		$sut = $this->sut();
		$this->mockFunction__();

		$result = $sut->createAttribute(array(), 'objectguid');

		$this->assertEquals('next_ad_int_objectguid', $result->getMetakey());
	}

	/**
	 * @test
	 */
	public function resolveType_trimString_returnTrimmedString()
	{
		$array = array(
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE => '  bool  ',
			'  bool  ',
			null,
		);

		$actual = NextADInt_Ldap_Attribute_Repository::resolveType($array);
		$this->assertEquals('bool', $actual);
	}

	/**
	 * @test
	 */
	public function resolveType_sanitizeKnownType_returnKnownType()
	{
		$array = array(
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE => "integer",
			'integer',
			null,
		);

		$actual = NextADInt_Ldap_Attribute_Repository::resolveType($array);
		$this->assertEquals('integer', $actual);
	}

	/**
	 * @test
	 */
	public function resolveType_sanitizeUnknownType_returnString()
	{
		$array = array(
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE => "NewAttributeType",
			'stuff',
			null,
		);

		$actual = NextADInt_Ldap_Attribute_Repository::resolveType($array);
		$this->assertEquals('string', $actual);
	}

	/**
	 * @test
	 */
	public function resolveWordPressAttribute_returnWordPressAttribute()
	{
		$array = array(
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE => "testWordPressAttribute",
			null,
			'',
		);

		$actual = NextADInt_Ldap_Attribute_Repository::resolveWordPressAttribute($array);
		$this->assertEquals("testWordPressAttribute", $actual);
	}

	/**
	 * @test
	 */
	public function resolveSyncToAd_returnTrue()
	{
		$array = array(
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_SYNC_TO_AD => "true",
			null,
			'',
		);

		$actual = NextADInt_Ldap_Attribute_Repository::resolveSyncToAd($array);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function resolveViewInUserProfile_returnTrue()
	{
		$array = array(
			NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE => "true",
			null,
			'',
		);

		$actual = NextADInt_Ldap_Attribute_Repository::resolveViewInUserProfile($array);
		$this->assertEquals(true, $actual);
	}

	/**
	 * @test
	 */
	public function convertAttributeMapping_returnArray()
	{
		$sut = $this->sut(null);

		$attributeString = "testAdAttribute:string:testWordPressMetakey:description:true:true:true;";

		$expected = array(
			"testAdAttribute" => array(
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_TYPE                 => "string",
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_WORDPRESS_ATTRIBUTE  => "testWordPressMetakey",
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_DESCRIPTION          => "description",
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_VIEW_IN_USER_PROFILE => "true",
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_SYNC_TO_AD           => "true",
				NextADInt_Adi_Configuration_Options::ATTRIBUTES_COLUMN_OVERWRITE_EMPTY      => "true",
			),
		);

		$actual = NextADInt_Ldap_Attribute_Repository::convertAttributeMapping($attributeString);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function checkAttributeNamesForConflict_withConflict_returnTrue() 
	{		
		$attributeString = "testAdAttribute:string:testWordPressMetakey:description:true:true:true;testAdAttribute:string:testWordPressMetakey:description:true:true:true";
		$actual = NextADInt_Ldap_Attribute_Repository::checkAttributeNamesForConflict($attributeString);
		$this->assertTrue($actual);
	}

	/**
	 * @test
	 */
	public function checkAttributeNamesForConflict_withoutConflict_returnfalse() 
	{		
		$attributeString = "testAdAttribute:string:testWordPressMetakey:description:true:true:true;testAdAttribute2:string:testWordPressMetakey:description:true:true:true;";
		$actual = NextADInt_Ldap_Attribute_Repository::checkAttributeNamesForConflict($attributeString);
		$this->assertFalse($actual);		
	}


	/**
	 * @test
	 */
	public function lookupDescription_withDescription_returnDescription()
	{
		$array = array(
			'metaKey',
			'description',
			null,
		);

		$actual = NextADInt_Ldap_Attribute_Repository::lookupDescription($array, 'metaKey');
		$this->assertEquals('description', $actual);
	}

	/**
	 * @test
	 */
	public function lookupDescription_withoutDescription_returnDefaultDescription()
	{
		$this->mockFunction__();

		$array = array(
			'metaKey',
			'',
			null,
		);

		$actual = NextADInt_Ldap_Attribute_Repository::lookupDescription($array, 'metaKey');
		$this->assertEquals('metaKey', $actual);
	}

	/**
	 * @test
	 */
	public function getSyncableAttributes_returnsOnlySyncables()
	{
		$sut = $this->sut(array('getWhitelistedAttributes'));

		$attributeCn = new NextADInt_Ldap_Attribute();
		$attributeMail = new NextADInt_Ldap_Attribute();
		$attributeMail->setSyncable(true);

		$sut->expects($this->once())
			->method('getWhitelistedAttributes')
			->willReturn(array('cn' => $attributeCn, 'mail' => $attributeMail));

		$actual = $sut->getSyncableAttributes();

		$this->assertEquals(array('mail' => $attributeMail), $actual);
	}

	/**
	 * @test
	 */
	public function getDefaultAttributeMetaKeys_returnsExpectedResult()
	{
		$expected = array(
			'next_ad_int_cn',
			'next_ad_int_givenname',
			'next_ad_int_sn',
			'next_ad_int_displayname',
			'next_ad_int_description',
			'next_ad_int_mail',
			'next_ad_int_samaccountname',
			'next_ad_int_userprincipalname',
			'next_ad_int_useraccountcontrol',
			'next_ad_int_objectguid',
			'next_ad_int_domainsid'
		);

		$actual = NextADInt_Ldap_Attribute_Repository::getDefaultAttributeMetaKeys();

		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function findAllBinaryAttributes_returnsExpectedResult()
	{
		$expected = array('objectguid');
		$actual = NextADInt_Ldap_Attribute_Repository::findAllBinaryAttributes();

		$this->assertEquals($expected, $actual);
	}
}