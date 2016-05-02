<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Multisite_Option_TypeTest extends Ut_BasicTest
{
	/**
	 * @test
	 */
	public function checkConstantsValue()
	{
		$sut = new ReflectionClass('Multisite_Option_Type');
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
			'EDITABLE_LIST' => 'editable_list'
		);

		$this->assertEquals($expected, $actual);
	}
}