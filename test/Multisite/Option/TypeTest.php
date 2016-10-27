<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_NextADInt_Multisite_Option_TypeTest extends Ut_BasicTest
{
	/**
	 * @test
	 */
	public function checkConstantsValue()
	{
		$sut = new ReflectionClass('NextADInt_Multisite_Option_Type');
		$actual = $sut->getConstants();

		$expected = array(
			'TEXT'     => 'text',
			'NUMBER'   => 'number',
			'PASSWORD' => 'password',
			'CHECKBOX' => 'checkbox',
			'SELECT'   => 'select',
			'TEXTAREA' => 'textarea',
			'CUSTOM'   => 'custom',
			'COMBOBOX' => 'combobox',
			'AUTHCODE' => 'authcode',
			'RADIO' => 'radio',
			'TABLE' => 'table',
			'EDITABLE_LIST' => 'editable_list',
			'VERIFICATION_PASSWORD' => 'verification_password',
			'DOMAIN_SID' => 'domain_sid',
			'NETBIOS_NAME' => 'netbios_name',
		);

		$this->assertEquals($expected, $actual);
	}
}