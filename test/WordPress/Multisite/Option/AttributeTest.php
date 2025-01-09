<?php

namespace Dreitier\WordPress\Multisite\Option;

use Dreitier\Test\BasicTestCase;
use ReflectionClass;

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class AttributeTest extends BasicTestCase
{
	/**
	 * @test
	 */
	public function checkConstantValues()
	{
		$sut = new ReflectionClass(Attribute::class);
		$actual = $sut->getConstants();

		$expected = array(
			'TITLE' => 'title',
			'TYPE' => 'type',
			'DESCRIPTION' => 'description',
			'DETAIL' => 'detail',
			'ELEMENTS' => 'elements',
			'INLINE' => 'inline',
			'TAB_TITLE' => 'tabTitle',
			'GROUP_TITLE' => 'groupTitle',
			'DEFAULT_VALUE' => 'defaultValue',
			'DISABLED' => 'disabled',
			'DISABLED_MESSAGE' => 'disabled_message',
			'SANITIZER' => 'sanitizer',
			'TYPE_STRUCTURE' => 'type_structure',
			'PERSIST_DEFAULT_VALUE' => 'persistDefaultValue',
			'ANGULAR_ATTRIBUTES' => 'angular_attributes',
			'ANGULAR_BUTTON_ATTRIBUTES' => 'angular_button_attributes',
			'SHOW_PERMISSION' => 'show_permission',
			'TRANSIENT' => 'transient',
		);

		$this->assertEquals($expected, $actual);
	}
}