<?php

/**
 * @author Tobias Hellmann <the@neos-it.de>
 * @access private
 */
class Ut_Multisite_Option_AttributeTest extends Ut_BasicTest
{
	/**
	 * @test
	 */
	public function checkConstantValues()
	{
		$sut = new ReflectionClass('Multisite_Option_Attribute');
		$actual = $sut->getConstants();

		$expected = array(
			'TITLE'                 => 'title',
			'TYPE'                  => 'type',
			'DESCRIPTION'           => 'description',
			'DETAIL'                => 'detail',
			'ELEMENTS'              => 'elements',
			'INLINE'                => 'inline',
			'TAB_TITLE'             => 'tabTitle',
			'GROUP_TITLE'           => 'groupTitle',
			'DEFAULT_VALUE'         => 'defaultValue',
			'DISABLED'              => 'disabled',
			'DISABLED_MESSAGE'      => 'disabled_message',
			'SANITIZER'             => 'sanitizer',
			'TYPE_STRUCTURE'        => 'type_structure',
			'PERSIST_DEFAULT_VALUE' => 'persistDefaultValue',
			'ANGULAR_ATTRIBUTES'    => 'angular_attributes',
			'SHOW_PERMISSION'       => 'show_permission',
			'TRANSIENT'             => 'transient',
		);

		$this->assertEquals($expected, $actual);
	}
}