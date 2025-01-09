<?php

namespace Dreitier\WordPress\Multisite\Option;

use Dreitier\Test\BasicTestCase;
use ReflectionClass;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class TypeTest extends BasicTestCase
{
	/**
	 * @test
	 */
	public function checkConstantsValue()
	{
		$sut = new ReflectionClass(Type::class);
		$actual = $sut->getConstants();

		$expected = array(
			'TEXT' => 'text',
			'NUMBER' => 'number',
			'PASSWORD' => 'password',
			'CHECKBOX' => 'checkbox',
			'SELECT' => 'select',
			'TEXTAREA' => 'textarea',
			'CUSTOM' => 'custom',
			'COMBOBOX' => 'combobox',
			'AUTHCODE' => 'authcode',
			'RADIO' => 'radio',
			'TABLE' => 'table',
			'EDITABLE_LIST' => 'editable_list',
			'VERIFICATION_PASSWORD' => 'verification_password',
			'DOMAIN_SID' => 'domain_sid',
			'LABEL' => 'label'
		);

		$this->assertEquals($expected, $actual);
	}
}